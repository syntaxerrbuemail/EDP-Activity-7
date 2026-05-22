<?php
/**
 * Database Connection Configuration
 * Client Aid Management System (CAMS)
 */

class Database {
    private $host = 'localhost';
    private $port = '5222';
    private $dbname = 'client_aid_db';
    private $username = 'root';
    private $password = ''; 
    public $pdo;

    public function __construct() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (\PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}

$database = new Database();
$pdo = $database->getConnection();
?>
