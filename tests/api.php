<?php
/**
 * Testes de integração da API REST.
 *
 * Usa o roteador diretamente (sem HTTP), contra um banco MySQL real.
 *
 * Execução: php tests/api.php
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

require_once $repo . '/app/config/config.php';

$db = db();
$passed = 0;
$failed = 0;

function check(bool $cond, string $name): void {
    global $passed, $failed;
    if ($cond) { $passed++; echo "PASS: {$name}\n"; }
    else { $failed++; echo "FAIL: {$name}\n"; }
}

/**
 * Executa uma rota da API e devolve [status, corpo].
 */
function chamar(string $metodo, string $rota, array $body = [], ?string $token = null, array $query = []): array {
    $resposta = api_dispatch($metodo, $rota, $body, $query, $token);
    return [$resposta['status'], $resposta['body']];
}

// ---------------------------------------------------------------
// Dados de teste
// ---------------------------------------------------------------
$suffix = (string)random_int(100000, 999999);
$matriculaBib = 'APIBIB' . $suffix;
$matriculaAluno = 'APIALU' . $suffix;

$db->prepare("INSERT INTO usuarios (matricula, nome, email, turma, senha_hash, tipo, ativo) VALUES (?, ?, ?, 'T1', ?, 'bibliotecario', 1)")
    ->execute([$matriculaBib, 'Bibliotecário API', 'apibib' . $suffix . '@teste.local', password_hash('123456', PASSWORD_DEFAULT)]);
$bibId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO usuarios (matricula, nome, email, turma, senha_hash, tipo, ativo) VALUES (?, ?, ?, 'T1', ?, 'aluno', 1)")
    ->execute([$matriculaAluno, 'Aluno API', 'apialu' . $suffix . '@teste.local', password_hash('123456', PASSWORD_DEFAULT)]);
$alunoId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO obras (titulo, autor, categoria) VALUES (?, 'Autor API', 'Testes')")
    ->execute(['Obra API ' . $suffix]);
$obraId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO exemplares (obra_id, tombo, status, localizacao) VALUES (?, ?, 'disponivel', 'Estante A')")
    ->execute([$obraId, 'A' . $suffix]);
$exemplarId = (int)$db->lastInsertId();

echo "== Autenticação ==\n";

[$status, $corpo] = chamar('GET', 'v1/saude');
check($status === 200 && $corpo['sucesso'] === true, 'rota pública de saúde responde');
check(isset($corpo['dados']['versao']), 'resposta de saúde informa a versão');

[$status, $corpo] = chamar('POST', 'v1/auth/login', ['matricula' => $matriculaAluno, 'senha' => '123456']);
check($status === 200 && isset($corpo['dados']['token']), 'login devolve token de acesso');
$tokenAluno = $corpo['dados']['token'] ?? '';
check(strlen($tokenAluno) === 96, 'token possui 48 bytes em hexadecimal');
check(($corpo['dados']['usuario']['tipo'] ?? '') === 'aluno', 'login devolve o perfil do usuário');

[$status] = chamar('POST', 'v1/auth/login', ['matricula' => $matriculaAluno, 'senha' => 'errada']);
check($status === 401, 'senha incorreta responde 401');

[$status] = chamar('POST', 'v1/auth/login', ['matricula' => '', 'senha' => '']);
check($status === 422, 'dados ausentes respondem 422');

[$status, $corpo] = chamar('POST', 'v1/auth/login', ['matricula' => $matriculaBib, 'senha' => '123456']);
$tokenBib = $corpo['dados']['token'] ?? '';
check($tokenBib !== '' && $tokenBib !== $tokenAluno, 'tokens de usuários diferentes são distintos');

echo "\n== Proteção das rotas ==\n";
[$status] = chamar('GET', 'v1/catalogo');
check($status === 401, 'rota sem token responde 401');

[$status] = chamar('GET', 'v1/catalogo', [], 'token-invalido');
check($status === 401, 'token inválido responde 401');

[$status, $corpo] = chamar('GET', 'v1/auth/eu', [], $tokenAluno);
check($status === 200 && $corpo['dados']['matricula'] === $matriculaAluno, 'perfil do usuário autenticado');

[$status] = chamar('GET', 'v1/admin/alunos', [], $tokenAluno);
check($status === 403, 'aluno não acessa rota administrativa');

[$status] = chamar('GET', 'v1/rota-inexistente', [], $tokenAluno);
check($status === 404, 'rota inexistente responde 404');

echo "\n== Catálogo ==\n";
[$status, $corpo] = chamar('GET', 'v1/catalogo', [], $tokenAluno, ['q' => 'Obra API ' . $suffix]);
check($status === 200 && count($corpo['dados']) === 1, 'catálogo filtra por busca');
check(($corpo['meta']['total'] ?? 0) === 1, 'metadados de paginação informam o total');
check(($corpo['dados'][0]['disponiveis'] ?? 0) === 1, 'obra informa exemplares disponíveis');

[$status, $corpo] = chamar('GET', 'v1/obras/' . $obraId, [], $tokenAluno);
check($status === 200 && count($corpo['dados']['exemplares']) === 1, 'detalhe da obra lista exemplares');

[$status] = chamar('GET', 'v1/obras/99999999', [], $tokenAluno);
check($status === 404, 'obra inexistente responde 404');

echo "\n== Reserva e fila ==\n";
[$status, $corpo] = chamar('POST', 'v1/reservas', ['obra_id' => $obraId], $tokenAluno);
check($status === 201 && $corpo['dados']['status'] === 'confirmada', 'aluno reserva exemplar disponível');
$reservaId = (int)($corpo['dados']['reserva_id'] ?? 0);

[$status] = chamar('POST', 'v1/reservas', ['obra_id' => $obraId], $tokenAluno);
check($status === 409, 'reserva duplicada é bloqueada');

[$status, $corpo] = chamar('GET', 'v1/meus/reservas', [], $tokenAluno);
check($status === 200 && count($corpo['dados']) === 1, 'aluno lista as próprias reservas');

[$status] = chamar('POST', 'v1/reservas', [], $tokenAluno);
check($status === 422, 'reserva sem obra_id responde 422');

// Segundo aluno entra na fila; o primeiro cancela e o segundo é promovido.
$db->prepare("INSERT INTO usuarios (matricula, nome, email, turma, senha_hash, tipo, ativo) VALUES (?, ?, ?, 'T1', ?, 'aluno', 1)")
    ->execute(['API' . $suffix . 'B', 'Aluno API B', 'apib' . $suffix . '@teste.local', password_hash('123456', PASSWORD_DEFAULT)]);
$alunoBId = (int)$db->lastInsertId();
[, $corpo] = chamar('POST', 'v1/auth/login', ['matricula' => 'API' . $suffix . 'B', 'senha' => '123456']);
$tokenAlunoB = $corpo['dados']['token'];

$db->prepare("UPDATE exemplares SET status = 'emprestado' WHERE obra_id = ?")->execute([$obraId]);
[$status, $corpo] = chamar('POST', 'v1/fila', ['obra_id' => $obraId], $tokenAlunoB);
check($status === 201 && $corpo['dados']['posicao'] === 1, 'segundo aluno entra na fila de espera');
$reservaFilaId = (int)($corpo['dados']['reserva_id'] ?? 0);

[$status, $corpo] = chamar('POST', 'v1/reservas/' . $reservaId . '/cancelar', [], $tokenAluno);
check($status === 200 && $corpo['dados']['status'] === 'cancelada', 'aluno cancela a própria reserva');
check(($corpo['dados']['proximo_promovido'] ?? null) === $reservaFilaId, 'cancelamento promove o próximo da fila');

$promovida = $db->query("SELECT status, exemplar_id FROM reservas WHERE id = " . $reservaFilaId)->fetch();
check($promovida['status'] === 'confirmada' && $promovida['exemplar_id'] !== null, 'reserva promovida fica confirmada com exemplar');

[$status] = chamar('POST', 'v1/reservas/' . $reservaFilaId . '/cancelar', [], $tokenAluno);
check($status === 409, 'aluno não cancela reserva de outro usuário');

echo "\n== Empréstimo, renovação e devolução ==\n";
// A renovação é bloqueada enquanto outro aluno espera pela mesma obra.
$db->exec("DELETE FROM reservas WHERE usuario_id = $alunoBId");
$db->prepare("UPDATE exemplares SET status = 'disponivel' WHERE id = ?")->execute([$exemplarId]);

[$status, $corpo] = chamar('POST', 'v1/admin/emprestimos', ['aluno_id' => $alunoId, 'exemplar_id' => $exemplarId], $tokenBib);
check($status === 201 && isset($corpo['dados']['emprestimo_id']), 'bibliotecário registra empréstimo via API');
$emprestimoId = (int)($corpo['dados']['emprestimo_id'] ?? 0);

[$status] = chamar('POST', 'v1/admin/emprestimos', ['aluno_id' => $alunoId, 'exemplar_id' => $exemplarId], $tokenBib);
check($status === 409, 'empréstimo de exemplar indisponível é bloqueado');

[$status, $corpo] = chamar('GET', 'v1/meus/emprestimos', [], $tokenAluno);
check($status === 200 && count($corpo['dados']['ativos']) === 1, 'aluno vê o empréstimo ativo');

[$status, $corpo] = chamar('POST', 'v1/emprestimos/' . $emprestimoId . '/renovar', [], $tokenAluno);
check($status === 200, 'aluno renova o próprio empréstimo');
$renovado = $db->query("SELECT data_prevista_devolucao, renovado FROM emprestimos WHERE id = " . $emprestimoId)->fetch();
check((int)$renovado['renovado'] === 1, 'empréstimo marcado como renovado');

[$status] = chamar('POST', 'v1/emprestimos/' . $emprestimoId . '/renovar', [], $tokenAluno);
check($status === 409, 'segunda renovação é bloqueada pelo limite');

[$status, $corpo] = chamar('POST', 'v1/admin/devolucoes', ['emprestimo_id' => $emprestimoId], $tokenBib);
check($status === 200 && $corpo['dados']['dias_atraso'] === 0, 'bibliotecário registra devolução');

[$status] = chamar('POST', 'v1/admin/devolucoes', ['emprestimo_id' => $emprestimoId], $tokenBib);
check($status === 409, 'devolução de empréstimo já encerrado é bloqueada');

echo "\n== Administração ==\n";
[$status, $corpo] = chamar('GET', 'v1/admin/alunos', [], $tokenBib);
check($status === 200 && count($corpo['dados']) >= 2, 'bibliotecário lista os alunos');

[$status, $corpo] = chamar('GET', 'v1/admin/reservas', [], $tokenBib);
check($status === 200, 'bibliotecário lista as reservas');

[$status, $corpo] = chamar('GET', 'v1/admin/emprestimos', [], $tokenBib);
check($status === 200, 'bibliotecário lista os empréstimos');

[$status, $corpo] = chamar('GET', 'v1/admin/relatorios', [], $tokenBib);
check($status === 200 && isset($corpo['dados']['totais']['obras']), 'relatórios trazem indicadores');

[$status, $corpo] = chamar('POST', 'v1/admin/alunos', [
    'matricula' => 'NOVO' . $suffix,
    'nome' => 'Aluno Criado pela API',
    'senha' => 'senha123',
    'turma' => '9Z',
], $tokenBib);
check($status === 201, 'bibliotecário cadastra aluno via API');
if ($status !== 201) { echo "   -> debug: " . json_encode($corpo) . "\n"; }

[$status] = chamar('POST', 'v1/admin/alunos', ['matricula' => 'NOVO' . $suffix, 'nome' => 'Duplicado', 'senha' => 'senha123'], $tokenBib);
check($status === 422, 'matrícula duplicada é rejeitada');

echo "\n== Encerramento da sessão da API ==\n";
[$status] = chamar('POST', 'v1/auth/logout', [], $tokenAluno);
check($status === 200, 'logout da API responde 200');

[$status] = chamar('GET', 'v1/auth/eu', [], $tokenAluno);
check($status === 401, 'token revogado deixa de funcionar');

// ---------------------------------------------------------------
// Limpeza
// ---------------------------------------------------------------
$ids = [$alunoId, $alunoBId, $bibId];
$in = implode(',', array_map('intval', $ids));

$db->exec("DELETE FROM reservas WHERE usuario_id IN ($in)");
$db->exec("DELETE FROM emprestimos WHERE usuario_id IN ($in) OR bibliotecario_id IN ($in)");
$db->exec("DELETE FROM api_tokens WHERE usuario_id IN ($in)");
$db->exec("DELETE FROM notificacoes WHERE usuario_id IN ($in)");
$db->exec("DELETE FROM logs WHERE usuario_id IN ($in)");
$db->exec("DELETE FROM exemplares WHERE obra_id = $obraId");
$db->exec("DELETE FROM obras WHERE id = $obraId");
$db->exec("DELETE FROM usuarios WHERE id IN ($in)");
$db->exec("DELETE FROM usuarios WHERE matricula LIKE 'NOVO$suffix'");

echo "\nResultado: {$passed} aprovados; {$failed} falharam.\n";
exit($failed > 0 ? 1 : 0);
