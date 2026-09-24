<?php
/**
 * Class Database - Singleton PDO Connection
 * Sistem Informasi Restoran Dapur Ina Aina
 */

require_once __DIR__ . '/../config/database.php';

class Database {
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->connection->exec("SET NAMES utf8mb4");
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'status'  => 'error',
                'message' => 'Koneksi database gagal: ' . $e->getMessage()
            ]));
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }
}
