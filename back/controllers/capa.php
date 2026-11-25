<?php
/**
 * Servidor das capas dos livros.
 *
 * As capas ficam em uploads/capas, fora da raiz pública do servidor web.
 * Este script entrega o arquivo de forma controlada, com validação de
 * extensão e bloqueio de travessia de diretório.
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';

$arquivo = basename((string)($_GET['arquivo'] ?? ''));

if ($arquivo === '') {
    http_response_code(400);
    exit;
}

$extensao = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
if (!in_array($extensao, ALLOWED_EXTENSIONS, true)) {
    http_response_code(404);
    exit;
}

$caminho = CAPAS_PATH . '/' . $arquivo;
$real = realpath($caminho);
$baseReal = realpath(CAPAS_PATH);

if ($real === false || $baseReal === false || !str_starts_with($real, $baseReal . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    exit;
}

$mimes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
];

header('Content-Type: ' . ($mimes[$extensao] ?? 'application/octet-stream'));
header('Content-Length: ' . (string)filesize($real));
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');

readfile($real);
