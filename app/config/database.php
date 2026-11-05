<?php
/**
 * Configuração do banco de dados - Biblioteca Escolar.
 * Credenciais fornecidas por variáveis de ambiente.
 */
declare(strict_types=1);

class Database {
    private static ?PDO $instance = null;
    private function __construct() {}
    private function __clone() {}
    public function __wakeup() {}

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: '127.0.0.1');
            $dbname = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'biblioteca_escolar');
            $user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
            $pass = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: '');
            $port = getenv("DB_PORT") ?: (getenv("MYSQLPORT") ?: "3306");
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log('Falha na conexão com o banco da Biblioteca Escolar: ' . $e->getMessage());
                if (defined('DEBUG') && DEBUG) {
                    throw new Exception('Erro de conexão: ' . $e->getMessage(), 0, $e);
                }
                throw new Exception('Não foi possível conectar ao banco de dados. Verifique a configuração do servidor.');
            }
        }
        return self::$instance;
    }
    public static function close(): void { self::$instance = null; }
}
function db(): PDO { return Database::getInstance(); }
