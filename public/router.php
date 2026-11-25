<?php
/**
 * Roteador para o servidor PHP embutido durante desenvolvimento local.
 * Publica front/assets em /assets sem duplicar arquivos em public/.
 */
$caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (str_starts_with($caminho, '/assets/')) {
    $base = realpath(__DIR__ . '/../front/assets');
    $arquivo = $base ? realpath($base . '/' . substr($caminho, strlen('/assets/'))) : false;

    if (!$base || !$arquivo || !is_file($arquivo) || !str_starts_with($arquivo, $base . DIRECTORY_SEPARATOR)) {
        http_response_code(404);
        exit('Arquivo não encontrado.');
    }

    $mime = mime_content_type($arquivo) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    readfile($arquivo);
    exit;
}

return false;
