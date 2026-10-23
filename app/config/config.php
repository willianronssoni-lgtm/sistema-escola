<?php
/**
 * Configurações gerais do sistema
 */

declare(strict_types=1);

// Iniciar sessão se não iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configurações de segurança da sessão
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '0'); // Mude para 1 se usar HTTPS
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Strict');
    
    session_start();
}

// Constantes da aplicação
define('APP_NAME', 'Biblioteca Escolar');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/biblioteca/public'); // Ajuste conforme necessário

// Configurações da biblioteca
define('PRAZO_EMPRESTIMO_DIAS', 15);
define('PRAZO_RENOVACAO_DIAS', 15);
define('MAX_RENOVACOES', 1);
define('PRAZO_RESERVA_HORAS', 48);
define('MAX_EMPRESTIMOS_SIMULTANEOS', 3);

// Caminhos
define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');
define('VIEWS_PATH', BASE_PATH . '/views');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('CAPAS_PATH', UPLOADS_PATH . '/capas');
define('SQL_PATH', BASE_PATH . '/sql');

// Upload de capas
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Debug (desative em produção)
define('DEBUG', true);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Carregar autoloaders e helpers
require_once APP_PATH . '/config/database.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/helpers/view.php';
require_once APP_PATH . '/auth/auth.php';