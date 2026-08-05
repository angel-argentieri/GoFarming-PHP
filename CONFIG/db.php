<?php

$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        if (!getenv(trim($key))) {
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

define('PLANT_ID_KEY', getenv('PLANT_ID_KEY') ?: '3qPbDUmVBuoXULHJyHHIX1jrN4TnxPciutckYp8oLuR4TTmhVG');
define('GEMINI_KEY', getenv('GEMINI_KEY') ?: 'AQ.Ab8RN6I8SvLckAyFwOspm-41RiT0tGYVTqhrBAEyH4DedT2Fmw');

class Database {
    private $host = 'localhost';
    private $dbname = 'GoFarmingBD';
    private $username = 'root';
    private $password = '12345678';
    private $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Falha na conexão com o banco.', 'detail' => $e->getMessage()]);
            exit;
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}
