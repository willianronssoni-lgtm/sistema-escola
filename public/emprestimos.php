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
            $alunoId = (int)($_POST['aluno_id'] ?? 0);
            $exemplarId = (int)($_POST['exemplar_id'] ?? 0);

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT id, nome FROM usuarios WHERE id = ? AND tipo = 'aluno' AND ativo = 1 FOR UPDATE");
            $stmt->execute([$alunoId]);
            $aluno = $stmt->fetch();
            if (!$aluno) {
                throw new RuntimeException('Aluno não encontrado ou inativo.');
            }

            $permissao = usuario_pode_emprestar($alunoId);
            if (!$permissao['pode']) {
                throw new RuntimeException($permissao['motivo']);
            }

            $stmt = $db->prepare("SELECT id, tombo, obra_id FROM exemplares WHERE id = ? AND status = 'disponivel' FOR UPDATE");
            $stmt->execute([$exemplarId]);
            $exemplar = $stmt->fetch();
            if (!$exemplar) {
                throw new RuntimeException('Exemplar indisponível. Atualize a página.');
            }

            $hoje = date('Y-m-d');
            $prevista = date('Y-m-d', strtotime('+' . PRAZO_EMPRESTIMO_DIAS . ' days'));

            $stmt = $db->prepare("
                INSERT INTO emprestimos (exemplar_id, usuario_id, bibliotecario_id, data_retirada, data_prevista_devolucao)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$exemplarId, $alunoId, usuario_id(), $hoje, $prevista]);
            $emprestimoId = (int)$db->lastInsertId();

            $stmt = $db->prepare("UPDATE exemplares SET status = 'emprestado' WHERE id = ?");
            $stmt->execute([$exemplarId]);

            $db->commit();

            registrar_log('emprestimo_criado', 'emprestimos', $emprestimoId, [
                'usuario_id' => $alunoId,
                'exemplar_id' => $exemplarId,
            ]);

            criar_notificacao($alunoId, 'devolucao_proxima', 'Empréstimo registrado',
                'A devolução está prevista para ' . format_date($prevista) . '.',
                url('meus-emprestimos.php'));

            flash('sucesso', 'Empréstimo registrado para ' . $aluno['nome'] . '. Devolução prevista para ' . format_date($prevista) . '.');
        } elseif ($acao === 'renovar') {
            $emprestimoId = (int)($_POST['emprestimo_id'] ?? 0);

            $stmt = $db->prepare("SELECT * FROM emprestimos WHERE id = ? AND data_devolucao IS NULL");
            $stmt->execute([$emprestimoId]);
            $emprestimo = $stmt->fetch();
            if (!$emprestimo) {
                throw new RuntimeException('Empréstimo ativo não encontrado.');
            }
            if ((int)$emprestimo['renovado'] >= MAX_RENOVACOES) {
                throw new RuntimeException('Limite de renovações atingido para este empréstimo.');
            }

            $nova = date('Y-m-d', strtotime($emprestimo['data_prevista_devolucao'] . ' +' . PRAZO_RENOVACAO_DIAS . ' days'));
            if ($nova < date('Y-m-d')) {
                $nova = date('Y-m-d', strtotime('+' . PRAZO_RENOVACAO_DIAS . ' days'));
            }

            $stmt = $db->prepare("UPDATE emprestimos SET data_prevista_devolucao = ?, renovado = 1 WHERE id = ?");
            $stmt->execute([$nova, $emprestimoId]);

            registrar_log('emprestimo_renovado_bibliotecario', 'emprestimos', $emprestimoId);
            criar_notificacao((int)$emprestimo['usuario_id'], 'renovacao', 'Empréstimo renovado',
                'Seu empréstimo foi renovado. Nova devolução prevista para ' . format_date($nova) . '.',
                url('meus-emprestimos.php'));

            flash('sucesso', 'Empréstimo renovado até ' . format_date($nova) . '.');
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
