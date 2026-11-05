<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_bibliotecario();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csfr_verify($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Sessão expirada. Atualize a página e tente novamente.');
        redirect('/biblioteca/public/painel.php');
    }

    $acao = $_POST['acao'] ?? '';
    try {
        if ($acao === 'aluno') {
            $matricula = trim($_POST['matricula'] ?? '');
            $nome = trim($_POST['nome'] ?? '');
            $turma = trim($_POST['turma'] ?? '');
            $senha = $_POST['senha'] ?? '';
            if ($matricula === '' || $nome === '' || strlen($senha) < 6) {
                throw new InvalidArgumentException('Informe matrícula, nome e senha com pelo menos 6 caracteres.');
            }
            $resultado = criar_usuario([
                'matricula' => $matricula, 'nome' => $nome, 'turma' => $turma,
                'senha' => $senha, 'tipo' => 'aluno', 'email' => trim($_POST['email'] ?? '')
            ]);
            if (!$resultado['sucesso']) throw new RuntimeException($resultado['erro']);
            flash('sucesso', 'Aluno cadastrado com sucesso.');
        } elseif ($acao === 'livro') {
            $titulo = trim($_POST['titulo'] ?? '');
            $autor = trim($_POST['autor'] ?? '');
            $tombo = trim($_POST['tombo'] ?? '');
            if ($titulo === '' || $autor === '' || $tombo === '') {
                throw new InvalidArgumentException('Título, autor e tombo são obrigatórios.');
            }
            $db->beginTransaction();
            $stmt = $db->prepare('INSERT INTO obras (titulo, autor, categoria, isbn, editora, ano) VALUES (?, ?, ?, ?, ?, ?)');
            $ano = trim($_POST['ano'] ?? '');
            $stmt->execute([$titulo, $autor, trim($_POST['categoria'] ?? '') ?: null, trim($_POST['isbn'] ?? '') ?: null, trim($_POST['editora'] ?? '') ?: null, $ano !== '' ? (int)$ano : null]);
            $obraId = (int)$db->lastInsertId();
            $stmt = $db->prepare("INSERT INTO exemplares (obra_id, tombo, status, localizacao) VALUES (?, ?, 'disponivel', ?)");
            $stmt->execute([$obraId, $tombo, trim($_POST['localizacao'] ?? '') ?: null]);
            $db->commit();
            registrar_log('cadastro_obra', 'obras', $obraId, ['titulo' => $titulo, 'tombo' => $tombo]);
            flash('sucesso', 'Livro e exemplar adicionados ao acervo.');
        } elseif ($acao === 'emprestimo') {
            $alunoId = (int)($_POST['aluno_id'] ?? 0);
            $exemplarId = (int)($_POST['exemplar_id'] ?? 0);
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = 'aluno' AND ativo = 1 FOR UPDATE");
            $stmt->execute([$alunoId]);
            if (!$stmt->fetch()) throw new RuntimeException('Aluno não encontrado ou inativo.');
            $permissao = usuario_pode_emprestar($alunoId);
            if (!$permissao['pode']) throw new RuntimeException($permissao['motivo']);
            $stmt = $db->prepare("SELECT id FROM exemplares WHERE id = ? AND status = 'disponivel' FOR UPDATE");
            $stmt->execute([$exemplarId]);
            if (!$stmt->fetch()) throw new RuntimeException('Exemplar indisponível. Atualize a página.');
            $hoje = date('Y-m-d');
            $prevista = date('Y-m-d', strtotime('+' . PRAZO_EMPRESTIMO_DIAS . ' days'));
            $stmt = $db->prepare('INSERT INTO emprestimos (exemplar_id, usuario_id, bibliotecario_id, data_retirada, data_prevista_devolucao) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$exemplarId, $alunoId, usuario_id(), $hoje, $prevista]);
            $stmt = $db->prepare("UPDATE exemplares SET status = 'emprestado' WHERE id = ?");
            $stmt->execute([$exemplarId]);
            $db->commit();
            registrar_log('emprestimo_criado', 'emprestimos', (int)$db->lastInsertId(), ['usuario_id' => $alunoId, 'exemplar_id' => $exemplarId]);
            criar_notificacao($alunoId, 'devolucao_proxima', 'Empréstimo registrado', 'A devolução está prevista para ' . format_date($prevista) . '.', '/biblioteca/public/meus-emprestimos.php');
            flash('sucesso', 'Empréstimo registrado. Devolução prevista para ' . format_date($prevista) . '.');
        } elseif ($acao === 'devolucao') {
            $emprestimoId = (int)($_POST['emprestimo_id'] ?? 0);
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT emp.*, e.obra_id, e.id AS exemplar_id FROM emprestimos emp JOIN exemplares e ON e.id = emp.exemplar_id WHERE emp.id = ? AND emp.data_devolucao IS NULL FOR UPDATE");
            $stmt->execute([$emprestimoId]);
            $emp = $stmt->fetch();
            if (!$emp) throw new RuntimeException('Empréstimo ativo não encontrado.');
            $hoje = date('Y-m-d');
            $atraso = max(0, (int)((strtotime($hoje) - strtotime($emp['data_prevista_devolucao'])) / 86400));
            $stmt = $db->prepare('UPDATE emprestimos SET data_devolucao = ?, dias_atraso = ? WHERE id = ?');
            $stmt->execute([$hoje, $atraso, $emprestimoId]);
            $stmt = $db->prepare("UPDATE exemplares SET status = 'disponivel' WHERE id = ?");
            $stmt->execute([$emp['exemplar_id']]);

            // Se houver fila, o exemplar fica reservado para o próximo aluno.
            $fila = proximo_da_fila((int)$emp['obra_id']);
            if ($fila) {
                $expira = date('Y-m-d H:i:s', strtotime('+' . PRAZO_RESERVA_HORAS . ' hours'));
                $stmt = $db->prepare("UPDATE reservas SET status = 'confirmada', exemplar_id = ?, data_confirmacao = NOW(), data_expiracao = ?, posicao_fila = NULL WHERE id = ?");
                $stmt->execute([$emp['exemplar_id'], $expira, $fila['id']]);
                $stmt = $db->prepare("UPDATE exemplares SET status = 'reservado' WHERE id = ?");
                $stmt->execute([$emp['exemplar_id']]);
                criar_notificacao((int)$fila['usuario_id'], 'livro_disponivel', 'Livro disponível para retirada', 'Sua vez chegou. Retire o exemplar até ' . format_datetime($expira) . '.', '/biblioteca/public/minhas-reservas.php');
            }
            if ($atraso > 0) {
                $suspensaoAte = date('Y-m-d', strtotime('+' . $atraso . ' days'));
                $stmt = $db->prepare('UPDATE usuarios SET suspenso_ate = ? WHERE id = ?');
                $stmt->execute([$suspensaoAte, $emp['usuario_id']]);
                $stmt = $db->prepare('UPDATE emprestimos SET suspensao_aplicada = 1 WHERE id = ?');
                $stmt->execute([$emprestimoId]);
            }
            $db->commit();
            registrar_log('emprestimo_devolvido', 'emprestimos', $emprestimoId, ['dias_atraso' => $atraso]);
            flash('sucesso', $atraso > 0 ? "Devolução registrada com {$atraso} dia(s) de atraso." : 'Devolução registrada com sucesso.');
        } else {
            throw new InvalidArgumentException('Operação não reconhecida.');
        }
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('Painel da biblioteca: ' . $e->getMessage());
        flash('erro', $e instanceof PDOException ? 'Não foi possível concluir. Verifique os dados e se a matrícula/tombo já existem.' : $e->getMessage());
    }
    redirect('/biblioteca/public/painel.php');
}

$stats = [
    'alunos' => (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'aluno' AND ativo = 1")->fetchColumn(),
    'obras' => (int)$db->query('SELECT COUNT(*) FROM obras')->fetchColumn(),
    'disponiveis' => (int)$db->query("SELECT COUNT(*) FROM exemplares WHERE status = 'disponivel'")->fetchColumn(),
    'ativos' => (int)$db->query('SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL')->fetchColumn(),
];
$alunos = $db->query("SELECT id, nome, matricula FROM usuarios WHERE tipo = 'aluno' AND ativo = 1 ORDER BY nome")->fetchAll();
$exemplares = $db->query("SELECT e.id, e.tombo, o.titulo FROM exemplares e JOIN obras o ON o.id = e.obra_id WHERE e.status = 'disponivel' ORDER BY o.titulo, e.tombo")->fetchAll();
$ativos = $db->query("SELECT emp.id, emp.data_prevista_devolucao, u.nome AS aluno, o.titulo, e.tombo FROM emprestimos emp JOIN usuarios u ON u.id = emp.usuario_id JOIN exemplares e ON e.id = emp.exemplar_id JOIN obras o ON o.id = e.obra_id WHERE emp.data_devolucao IS NULL ORDER BY emp.data_prevista_devolucao")->fetchAll();
$ultimos = $db->query('SELECT titulo, autor FROM obras ORDER BY criado_em DESC LIMIT 8')->fetchAll();

view('painel', ['titulo' => 'Painel do bibliotecário', 'stats' => $stats, 'alunos' => $alunos, 'exemplares' => $exemplares, 'ativos' => $ativos, 'ultimos' => $ultimos]);
