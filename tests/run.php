<?php
/**
 * Testes unitários (sem banco de dados).
 * Execução: php tests/run.php
 */
declare(strict_types=1);

session_start();
define('MAX_EMPRESTIMOS_SIMULTANEOS', 3);
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/biblioteca/public');
require_once __DIR__ . '/../app/helpers/functions.php';

$passed = 0;
$failed = 0;
function check(bool $condition, string $name): void {
    global $passed, $failed;
    if ($condition) { $passed++; echo "PASS: {$name}\n"; }
    else { $failed++; echo "FAIL: {$name}\n"; }
}

// --------------------------------------------------
// Escape e formatação
// --------------------------------------------------
check(e('<script>alert("x")</script>') === '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', 'e() escapa HTML');
check(e("'") === '&#039;', 'e() escapa aspas simples');
check(format_date('2025-01-02') === '02/01/2025', 'format_date formata data');
check(format_date(null) === '-', 'format_date trata valor nulo');
check(format_datetime('2025-01-02 10:30') === '02/01/2025 10:30', 'format_datetime formata data e hora');
check(nome_abreviado('Maria Silva Santos') === 'Maria S.', 'nome_abreviado usa primeiro e último nome');
check(nome_abreviado('Ana') === 'Ana', 'nome_abreviado mantém nome único');

// --------------------------------------------------
// Badges de status
// --------------------------------------------------
check(status_badge('disponivel') === '<span class="badge bg-success">Disponível</span>', 'status_badge identifica exemplar disponível');
check(status_badge('status_desconhecido') === '<span class="badge bg-secondary">Status_desconhecido</span>', 'status_badge possui fallback');

// --------------------------------------------------
// CSRF
// --------------------------------------------------
$_SESSION = [];
$token = csfr_token();
check(strlen($token) === 64, 'token CSRF possui 256 bits em hexadecimal');
check(csfr_verify($token), 'token CSRF válido é aceito');
check(!csfr_verify('token-invalido'), 'token CSRF inválido é rejeitado');
check(!csfr_verify($token . 'x'), 'token CSRF adulterado é rejeitado');

// --------------------------------------------------
// Papéis e sessão
// --------------------------------------------------
$_SESSION['usuario'] = ['id' => 7, 'tipo' => 'aluno'];
check(usuario_id() === 7 && is_aluno() && !is_bibliotecario(), 'papel de aluno é reconhecido');
$_SESSION['usuario']['tipo'] = 'bibliotecario';
check(is_bibliotecario() && !is_aluno(), 'papel de bibliotecário é reconhecido');
unset($_SESSION['usuario']);
check(usuario_logado() === null && usuario_id() === null, 'usuário deslogado não expõe dados');

// --------------------------------------------------
// Mensagens flash (proteção contra XSS)
// --------------------------------------------------
$_SESSION = [];
flash('sucesso', 'Mensagem <b>com</b> marcação');
$html = flash_html();
check(str_contains($html, '&lt;b&gt;'), 'flash escapa marcação HTML na mensagem');
check(!str_contains($html, '<b>'), 'flash não injeta HTML na mensagem');
check(get_flashes() === [], 'flash consumido é removido da sessão');

// --------------------------------------------------
// Tempo restante
// --------------------------------------------------
check(tempo_restante(date('Y-m-d H:i:s', strtotime('+2 hours'))) !== 'Expirado', 'tempo_restante conta prazo futuro');
check(tempo_restante(date('Y-m-d H:i:s', strtotime('-1 hour'))) === 'Expirado', 'tempo_restante sinaliza prazo vencido');

// --------------------------------------------------
// URL (depende de APP_URL definido na configuração)
// --------------------------------------------------
check(url('catalogo.php') === rtrim(APP_URL, '/') . '/catalogo.php', 'url() monta caminhos relativos ao APP_URL');
check(url('catalogo.php?categoria=1') === rtrim(APP_URL, '/') . '/catalogo.php?categoria=1', 'url() preserva parâmetros de consulta');
check(url() === rtrim(APP_URL, '/') . '/', 'url() sem argumento aponta para a raiz da aplicação');

echo "\nResultado: {$passed} aprovados; {$failed} falharam.\n";
exit($failed > 0 ? 1 : 0);
