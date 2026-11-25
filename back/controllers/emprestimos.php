<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_bibliotecario();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csfr_verify($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Sessão expirada. Atualize a página e tente novamente.');
        redirect(url('emprestimos.php'));
    }

    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'registrar') {
            $resultado = servico_registrar_emprestimo(
                (int)($_POST['aluno_id'] ?? 0),
                (int)($_POST['exemplar_id'] ?? 0),
                (int)usuario_id()
            );

            flash('sucesso', 'Empréstimo registrado. Devolução prevista para ' . format_date($resultado['data_prevista']) . '.');
        } elseif ($acao === 'renovar') {
            $resultado = servico_renovar_emprestimo((int)($_POST['emprestimo_id'] ?? 0), (int)usuario_id(), true);

            if (!$resultado['sucesso']) {
                throw new RuntimeException($resultado['erro']);
            }

            flash('sucesso', 'Empréstimo renovado até ' . format_date($resultado['data_prevista']) . '.');
        } else {
            throw new InvalidArgumentException('Operação não reconhecida.');
        }
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Empréstimos: ' . $e->getMessage());
        flash('erro', $e instanceof PDOException
            ? 'Não foi possível concluir a operação. Verifique os dados informados.'
            : $e->getMessage());
    }

    redirect(url('emprestimos.php'));
}

$status = $_GET['status'] ?? 'ativos';
$busca = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if ($status === 'ativos') {
    $where[] = 'emp.data_devolucao IS NULL';
} elseif ($status === 'atrasados') {
    $where[] = 'emp.data_devolucao IS NULL AND emp.data_prevista_devolucao < CURDATE()';
} elseif ($status === 'devolvidos') {
    $where[] = 'emp.data_devolucao IS NOT NULL';
}

if ($busca !== '') {
    $where[] = '(u.nome LIKE ? OR u.matricula LIKE ? OR o.titulo LIKE ? OR x.tombo LIKE ?)';
    $termo = "%{$busca}%";
    array_push($params, $termo, $termo, $termo, $termo);
}

$sql = "
    SELECT emp.*, u.nome AS aluno, u.matricula, o.titulo AS obra_titulo, x.tombo
    FROM emprestimos emp
    JOIN usuarios u ON u.id = emp.usuario_id
    JOIN exemplares x ON x.id = emp.exemplar_id
    JOIN obras o ON o.id = x.obra_id
";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY emp.data_retirada DESC, emp.id DESC LIMIT 200';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$emprestimos = $stmt->fetchAll();

$stmt = $db->prepare("SELECT id, matricula, nome FROM usuarios WHERE tipo = 'aluno' AND ativo = 1 ORDER BY nome");
$stmt->execute();
$alunos = $stmt->fetchAll();

$stmt = $db->prepare("
    SELECT e.id, e.tombo, o.titulo
    FROM exemplares e
    JOIN obras o ON o.id = e.obra_id
    WHERE e.status = 'disponivel'
    ORDER BY o.titulo, e.tombo
");
$stmt->execute();
$exemplares = $stmt->fetchAll();

view('emprestimos', [
    'titulo' => 'Empréstimos',
    'emprestimos' => $emprestimos,
    'alunos' => $alunos,
    'exemplares' => $exemplares,
    'status' => $status,
    'busca' => $busca,
]);
