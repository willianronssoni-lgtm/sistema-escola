<?php
declare(strict_types=1);
session_start();
define('MAX_EMPRESTIMOS_SIMULTANEOS', 3);
require_once __DIR__ . '/../app/helpers/functions.php';

$passed = 0;
$failed = 0;
function check(bool $condition, string $name): void {
    global $passed, $failed;
    if ($condition) { $passed++; echo "PASS: {$name}\n"; }
    else { $failed++; echo "FAIL: {$name}\n"; }
}

check(e('<script>alert("x")</script>') === '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', 'e() escapa HTML');
check(format_date('2025-01-02') === '02/01/2025', 'format_date formata data');
check(format_date(null) === '-', 'format_date trata valor nulo');
check(status_badge('disponivel') === '<span class="badge bg-success">Disponível</span>', 'status_badge identifica exemplar disponível');
check(status_badge('status_desconhecido') === '<span class="badge bg-secondary">Status_desconhecido</span>', 'status_badge possui fallback');
$_SESSION = [];
$token = csfr_token();
check(strlen($token) === 64, 'token CSRF possui 256 bits em hexadecimal');
check(csfr_verify($token), 'token CSRF válido é aceito');
check(!csfr_verify('token-invalido'), 'token CSRF inválido é rejeitado');
$_SESSION['usuario'] = ['id'=>7,'tipo'=>'aluno'];
check(usuario_id() === 7 && is_aluno() && !is_bibliotecario(), 'papel de aluno é reconhecido');
$_SESSION['usuario']['tipo'] = 'bibliotecario';
check(is_bibliotecario() && !is_aluno(), 'papel de bibliotecário é reconhecido');

echo "\nResultado: {$passed} aprovados; {$failed} falharam.\n";
exit($failed > 0 ? 1 : 0);
