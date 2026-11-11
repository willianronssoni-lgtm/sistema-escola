<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_bibliotecario();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csfr_verify($_POST['csrf_token'] ?? '')) {
        flash('erro', 'Sessão expirada. Atualize a página e tente novamente.');
        redirect(url('painel.php'));
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
            $resultado = servico_registrar_emprestimo(
                (int)($_POST['aluno_id'] ?? 0),
                (int)($_POST['exemplar_id'] ?? 0),
                (int)usuario_id()
            );
            flash('sucesso', 'Empréstimo registrado. Devolução prevista para ' . format_date($resultado['data_prevista']) . '.');
        } elseif ($acao === 'devolucao') {
            $resultado = servico_registrar_devolucao((int)($_POST['emprestimo_id'] ?? 0));
            flash('sucesso', $resultado['dias_atraso'] > 0
                ? "Devolução registrada com {$resultado['dias_atraso']} dia(s) de atraso."
                : 'Devolução registrada com sucesso.');
        } else {
            throw new InvalidArgumentException('Operação não reconhecida.');
        }
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('Painel da biblioteca: ' . $e->getMessage());
        flash('erro', $e instanceof PDOException ? 'Não foi possível concluir. Verifique os dados e se a matrícula/tombo já existem.' : $e->getMessage());
    }
    redirect(url('painel.php'));
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
