<?php
/**
 * Configuração do banco de dados - Biblioteca Escolar
 * PDO com prepared statements para segurança
 */

declare(strict_types=1);

class Database {
    private static ?PDO $instance = null;
    
    private const HOST = 'localhost';
    private const DBNAME = 'biblioteca_escolar';
    private const USER = 'root';
    private const PASS = '';
    private const CHARSET = 'utf8mb4';
    
    private function __construct() {}
    private function __clone() {}
    public function __wakeup() {}

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . self::HOST . ";dbname=" . self::DBNAME . ";charset=" . self::CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::CHARSET,
            ];
            
            try {
                self::$instance = new PDO($dsn, self::USER, self::PASS, $options);
            } catch (PDOException $e) {
                // Em produção, logar o erro e mostrar mensagem genérica
                if (defined('DEBUG') && DEBUG) {
                    throw new Exception("Erro de conexão: " . $e->getMessage());
                }
                throw new Exception("Erro ao conectar ao banco de dados. Tente novamente mais tarde.");
            }
        }
        return self::$instance;
    }
    
    public static function close(): void {
        self::$instance = null;
    }
}

// Helper para obter a conexão
function db(): PDO {
    return Database::getInstance();
}