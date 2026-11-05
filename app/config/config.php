<?php
/**
 * Configurações gerais do sistema
 */

declare(strict_types=1);

// Iniciar sessão com cookies mais seguros.
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443);

    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $https ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_start();
}

// Configurações da aplicação: podem ser sobrescritas por variáveis de ambiente.
define('APP_NAME', getenv('APP_NAME') ?: 'Biblioteca Escolar');
define('APP_VERSION', '1.0.0');
define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost/biblioteca/public', '/'));

define('PRAZO_EMPRESTIMO_DIAS', 15);
define('PRAZO_RENOVACAO_DIAS', 15);
define('MAX_RENOVACOES', 1);
define('PRAZO_RESERVA_HORAS', 48);
define('MAX_EMPRESTIMOS_SIMULTANEOS', 3);

define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');
define('VIEWS_PATH', BASE_PATH . '/views');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('CAPAS_PATH', UPLOADS_PATH . '/capas');
define('SQL_PATH', BASE_PATH . '/sql');

define('MAX_FILE_SIZE', 2 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Segurança: depuração desativada por padrão. Ative DEBUG=1 apenas em desenvolvimento.
define('DEBUG', filter_var(getenv('DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN));

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo');

require_once APP_PATH . '/config/database.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/helpers/view.php';
require_once APP_PATH . '/auth/auth.php';
