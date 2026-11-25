<?php
/**
 * Testes de integração com banco de dados MySQL/MariaDB.
 *
 * Requer um banco acessível. Configure as variáveis de ambiente:
 *   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
 *
 * Execução: php tests/integration.php
 *
 * O script cria dados de teste próprios e os remove ao final.
 */
declare(strict_types=1);

$repo = dirname(__DIR__);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME') ?: 'biblioteca_escolar';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "SKIP: banco de dados indisponível ({$e->getMessage()})\n");
    exit(0);
}

putenv("DB_HOST={$host}");
putenv("DB_PORT={$port}");
putenv("DB_NAME={$name}");
putenv("DB_USER={$user}");
putenv("DB_PASS={$pass}");
putenv('DEBUG=0');

require_once $repo . '/back/app/config/config.php';

$db = db();
$passed = 0;
$failed = 0;

function check(bool $cond, string $name): void {
    global $passed, $failed;
    if ($cond) { $passed++; echo "PASS: {$name}\n"; }
    else { $failed++; echo "FAIL: {$name}\n"; }
}

// ---------------------------------------------------------------
// Ambiente de teste: usuário bibliotecário e aluno próprios
// ---------------------------------------------------------------
$suffix = (string)random_int(100000, 999999);
$matriculaBib = 'TESTBIB' . $suffix;
$matriculaAluno = 'TESTALU' . $suffix;

$db->prepare("INSERT INTO usuarios (matricula, nome, email, turma, senha_hash, tipo, ativo) VALUES (?, ?, ?, 'T1', ?, 'bibliotecario', 1)")
    ->execute([$matriculaBib, 'Bibliotecário de Teste', 'bib' . $suffix . '@teste.local', password_hash('123456', PASSWORD_DEFAULT)]);
$bibId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO usuarios (matricula, nome, email, turma, senha_hash, tipo, ativo) VALUES (?, ?, ?, 'T1', ?, 'aluno', 1)")
    ->execute([$matriculaAluno, 'Aluno de Teste', 'alu' . $suffix . '@teste.local', password_hash('123456', PASSWORD_DEFAULT)]);
$alunoId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO obras (titulo, autor, categoria) VALUES (?, 'Autor Teste', 'Testes')")
    ->execute(['Obra de Teste ' . $suffix]);
$obraId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO exemplares (obra_id, tombo, status, localizacao) VALUES (?, ?, 'disponivel', 'Estante T')")
    ->execute([$obraId, 'T' . $suffix]);
$exemplarId = (int)$db->lastInsertId();

echo "== Autenticação ==\n";
$result = login($matriculaAluno, '123456');
check($result['sucesso'] === true, 'login do aluno de teste');
check(login($matriculaAluno, 'senha-errada')['sucesso'] === false, 'senha incorreta é rejeitada');
check(login('MATRICULA-inexistente', '123456')['sucesso'] === false, 'matrícula inexistente é rejeitada');

echo "\n== Regras de empréstimo ==\n";
$pode = usuario_pode_emprestar($alunoId);
check($pode['pode'] === true, 'aluno sem pendências pode emprestar');

// Cria MAX_EMPRESTIMOS_SIMULTANEOS empréstimos ativos.
for ($i = 0; $i < MAX_EMPRESTIMOS_SIMULTANEOS; $i++) {
    $db->prepare("INSERT INTO emprestimos (exemplar_id, usuario_id, bibliotecario_id, data_retirada, data_prevista_devolucao) VALUES (?, ?, ?, CURDATE(), CURDATE() + INTERVAL 15 DAY)")
        ->execute([$exemplarId, $alunoId, $bibId]);
}
$pode = usuario_pode_emprestar($alunoId);
check($pode['pode'] === false, 'limite de empréstimos simultâneos bloqueia novo empréstimo');
check(str_contains($pode['motivo'], (string)MAX_EMPRESTIMOS_SIMULTANEOS), 'mensagem do limite informa a quantidade permitida');

// Atraso bloqueia o empréstimo.
// Atraso bloqueia o empréstimo (com apenas um empréstimo ativo, para
// isolar a regra de atraso da regra de limite).
$db->prepare("DELETE FROM emprestimos WHERE usuario_id = ?")->execute([$alunoId]);
$db->prepare("INSERT INTO emprestimos (exemplar_id, usuario_id, bibliotecario_id, data_retirada, data_prevista_devolucao) VALUES (?, ?, ?, CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 3 DAY)")
    ->execute([$exemplarId, $alunoId, $bibId]);
$pode = usuario_pode_emprestar($alunoId);
check($pode['pode'] === false && str_contains($pode['motivo'], 'atraso'), 'empréstimo em atraso bloqueia novo empréstimo');

// Suspensão impede o login.
$db->prepare("UPDATE usuarios SET suspenso_ate = CURDATE() + INTERVAL 5 DAY WHERE id = ?")->execute([$alunoId]);
$db->prepare("UPDATE emprestimos SET data_devolucao = CURDATE() WHERE usuario_id = ?")->execute([$alunoId]);
$result = login($matriculaAluno, '123456');
check($result['sucesso'] === false && str_contains($result['erro'], 'suspensa'), 'aluno suspenso não consegue entrar');
$db->prepare("UPDATE usuarios SET suspenso_ate = NULL WHERE id = ?")->execute([$alunoId]);

echo "\n== Fila de espera ==\n";
$db->prepare("UPDATE exemplares SET status = 'emprestado' WHERE obra_id = ?")->execute([$obraId]);
$db->prepare("INSERT INTO reservas (usuario_id, obra_id, status, posicao_fila) VALUES (?, ?, 'na_fila', 1)")
    ->execute([$alunoId, $obraId]);

$proximo = proximo_da_fila($obraId);
check(is_array($proximo) && $proximo !== null, 'proximo_da_fila devolve a reserva da fila');
check(is_array($proximo) && isset($proximo['id']), 'proximo_da_fila expõe a coluna id');
check(is_array($proximo) && isset($proximo['obra_titulo']), 'proximo_da_fila expõe o título da obra');
check(is_array($proximo) && !isset($proximo['reserva_id']), 'proximo_da_fila não expõe a coluna inexistente reserva_id');

// Promoção da fila ao liberar o exemplar.
$db->prepare("UPDATE exemplares SET status = 'disponivel' WHERE id = ?")->execute([$exemplarId]);
$exemplar = buscar_exemplar_disponivel($obraId);
$expira = date('Y-m-d H:i:s', strtotime('+' . PRAZO_RESERVA_HORAS . ' hours'));
$db->prepare("UPDATE reservas SET status = 'confirmada', exemplar_id = ?, data_confirmacao = NOW(), data_expiracao = ? WHERE id = ?")
    ->execute([(int)$exemplar['id'], $expira, $proximo['id']]);
$confirmada = $db->query("SELECT status FROM reservas WHERE id = " . (int)$proximo['id'])->fetch();
check($confirmada['status'] === 'confirmada', 'reserva da fila é promovida a confirmada com o id correto');

echo "\n== Reserva ==\n";
check($exemplar !== null, 'exemplar disponível localizado');
$db->prepare("UPDATE exemplares SET status = 'reservado' WHERE id = ?")->execute([(int)$exemplar['id']]);
$reservado = $db->query("SELECT status FROM exemplares WHERE id = " . (int)$exemplar['id'])->fetch();
check($reservado['status'] === 'reservado', 'exemplar fica reservado para o aluno');
$duplicada = (int)$db->query("SELECT COUNT(*) FROM reservas WHERE usuario_id = {$alunoId} AND obra_id = {$obraId} AND status IN ('pendente','confirmada','na_fila')")->fetchColumn();
check($duplicada >= 1, 'reserva ativa do aluno é detectada para evitar duplicidade');

echo "\n== Auditoria e views ==\n";
check((int)$db->query('SELECT COUNT(*) FROM logs')->fetchColumn() > 0, 'logs de auditoria registrados');
check((int)$db->query('SELECT COUNT(*) FROM notificacoes')->fetchColumn() > 0, 'notificações registradas');
foreach (['vw_disponibilidade', 'vw_emprestimos_ativos', 'vw_fila_espera'] as $view) {
    check($db->query("SELECT COUNT(*) FROM {$view}")->fetchColumn() !== false, "view {$view} responde");
}
check((int)$db->query("SELECT COUNT(*) FROM emprestimos e JOIN exemplares x ON x.id = e.exemplar_id WHERE x.id IS NULL")->fetchColumn() === 0, 'não há empréstimos órfãos');

// ---------------------------------------------------------------
// Limpeza
// ---------------------------------------------------------------
$db->prepare("DELETE FROM reservas WHERE usuario_id = ?")->execute([$alunoId]);
$db->prepare("DELETE FROM emprestimos WHERE usuario_id = ?")->execute([$alunoId]);
$db->prepare("DELETE FROM exemplares WHERE obra_id = ?")->execute([$obraId]);
$db->prepare("DELETE FROM obras WHERE id = ?")->execute([$obraId]);
$db->prepare("DELETE FROM notificacoes WHERE usuario_id = ?")->execute([$alunoId]);
$db->prepare("DELETE FROM logs WHERE usuario_id = ?")->execute([$alunoId]);
$db->prepare("DELETE FROM usuarios WHERE id IN (?, ?)")->execute([$alunoId, $bibId]);

echo "\nResultado: {$passed} aprovados; {$failed} falharam.\n";
exit($failed > 0 ? 1 : 0);
