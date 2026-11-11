<?php
/**
 * Ponto de entrada da API REST.
 *
 * A rota pode ser informada de três formas:
 *   /api/v1/catalogo                (com o .htaccess de reescrita)
 *   /api/index.php/v1/catalogo      (PATH_INFO)
 *   /api/index.php?rota=v1/catalogo  (parâmetro de consulta)
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/config/config.php';

// Descobre o caminho da rota.
$rota = $_GET['rota'] ?? '';

if ($rota === '') {
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');

    // Remove o caminho do script (/api/index.php) do REQUEST_URI.
    if ($script !== '' && str_contains($uri, $script)) {
        $rota = substr($uri, strpos($uri, $script) + strlen($script));
    } else {
        $posicao = strpos($uri, '/api/');
        $rota = $posicao !== false ? substr($uri, $posicao + 4) : '';
    }
}

$rota = '/' . ltrim(rawurldecode((string)$rota), '/');

$metodo = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
$corpo = $metodo === 'GET' ? [] : api_corpo_json();

// O corpo pode ser enviado como JSON ou como formulário.
if ($_POST) {
    $corpo = array_merge($corpo, $_POST);
}

$token = api_token_da_requisicao(
    $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
    $_GET
);

$resposta = api_dispatch($metodo, $rota, $corpo, $_GET, $token);

http_response_code($resposta['status']);
foreach ($resposta['headers'] as $nome => $valor) {
    header($nome . ': ' . $valor);
}

echo json_encode($resposta['body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
