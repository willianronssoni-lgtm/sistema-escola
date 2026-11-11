<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_bibliotecario();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csfr_verify($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Sessão expirada. Atualize a página e tente novamente.');
        redirect(url('alunos.php'));
    }

    $acao = $_POST['acao'] ?? '';
    $alunoId = (int)($_POST['aluno_id'] ?? 0);

    try {
        if ($acao === 'criar') {
            $matricula = trim($_POST['matricula'] ?? '');
            $nome = trim($_POST['nome'] ?? '');
            $turma = trim($_POST['turma'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';

            if ($matricula === '' || $nome === '' || strlen($senha) < 6) {
                throw new InvalidArgumentException('Informe matrícula, nome e senha com pelo menos 6 caracteres.');
            }

            $resultado = criar_usuario([
                'matricula' => $matricula,
                'nome' => $nome,
                'turma' => $turma,
                'email' => $email,
                'senha' => $senha,
                'tipo' => 'aluno',
            ]);

            if (!$resultado['sucesso']) {
                throw new RuntimeException($resultado['erro']);
            }

            flash('sucesso', 'Aluno cadastrado com sucesso.');
        } elseif ($acao === 'atualizar') {
            $resultado = atualizar_usuario($alunoId, [
                'nome' => trim($_POST['nome'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'turma' => trim($_POST['turma'] ?? ''),
                'ativo' => (int)($_POST['ativo'] ?? 1),
            ]);

            if (!$resultado['sucesso']) {
                throw new RuntimeException($resultado['erro']);
            }

            flash('sucesso', 'Dados do aluno atualizados.');
        } elseif ($acao === 'suspender') {
            $dias = (int)($_POST['dias'] ?? 0);
            $ate = $dias > 0 ? date('Y-m-d', strtotime('+' . $dias . ' days')) : null;
            $resultado = atualizar_usuario($alunoId, ['suspenso_ate' => $ate]);
            if (!$resultado['sucesso']) {
                throw new RuntimeException($resultado['erro']);
            }
            flash('sucesso', $ate ? "Aluno suspenso até " . format_date($ate) . '.' : 'Suspensão do aluno removida.');
        } elseif ($acao === 'redefinir_senha') {
            $novaSenha = $_POST['nova_senha'] ?? '';
            $resultado = redefinir_senha_bibliotecario($alunoId, $novaSenha);
            if (!$resultado['sucesso']) {
                throw new RuntimeException($resultado['erro']);
            }
            flash('sucesso', 'Senha do aluno redefinida.');
        } elseif ($acao === 'excluir') {
            $resultado = excluir_usuario($alunoId);
            if (!$resultado['sucesso']) {
                throw new RuntimeException($resultado['erro']);
            }
            flash('sucesso', 'Aluno excluído.');
        } else {
            throw new InvalidArgumentException('Operação não reconhecida.');
        }
    } catch (Throwable $e) {
        error_log('Alunos: ' . $e->getMessage());
        flash('erro', $e instanceof PDOException
            ? 'Não foi possível concluir a operação. Verifique os dados informados.'
            : $e->getMessage());
    }

    redirect(url('alunos.php'));
}

$busca = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'ativos';

$where = ["u.tipo = 'aluno'"];
$params = [];

if ($busca !== '') {
    $where[] = '(u.nome LIKE ? OR u.matricula LIKE ? OR u.turma LIKE ?)';
    $termo = "%{$busca}%";
    array_push($params, $termo, $termo, $termo);
}

if ($status === 'ativos') {
    $where[] = 'u.ativo = 1';
} elseif ($status === 'inativos') {
    $where[] = 'u.ativo = 0';
} elseif ($status === 'suspensos') {
    $where[] = 'u.suspenso_ate IS NOT NULL AND u.suspenso_ate >= CURDATE()';
}

$sql = "
    SELECT u.*,
           (SELECT COUNT(*) FROM emprestimos e WHERE e.usuario_id = u.id AND e.data_devolucao IS NULL) AS emprestimos_ativos,
           (SELECT COUNT(*) FROM emprestimos e WHERE e.usuario_id = u.id AND e.data_devolucao IS NULL AND e.data_prevista_devolucao < CURDATE()) AS emprestimos_atrasados,
           (SELECT COUNT(*) FROM reservas r WHERE r.usuario_id = u.id AND r.status IN ('pendente','confirmada','na_fila')) AS reservas_ativas
    FROM usuarios u
    WHERE " . implode(' AND ', $where) . "
    ORDER BY u.nome
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$alunos = $stmt->fetchAll();

view('alunos', [
    'titulo' => 'Alunos',
    'alunos' => $alunos,
    'busca' => $busca,
    'status' => $status,
]);
