<?php
require_once __DIR__ . '/env.php';

defineFromEnv('DB_HOST', 'DB_HOST', 'localhost');
defineFromEnv('DB_NAME', 'DB_NAME', '');
defineFromEnv('DB_USER', 'DB_USER', '');
defineFromEnv('DB_PASS', 'DB_PASS', '');

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = defined('DB_HOST') ? DB_HOST : envValue('DB_HOST', 'localhost');
        $this->db_name = defined('DB_NAME') ? DB_NAME : envValue('DB_NAME', '');
        $this->username = defined('DB_USER') ? DB_USER : envValue('DB_USER', '');
        $this->password = defined('DB_PASS') ? DB_PASS : envValue('DB_PASS', '');
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
