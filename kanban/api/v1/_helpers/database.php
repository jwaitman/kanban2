<?php
// api/v1/_helpers/database.php
require_once __DIR__ . '/../../../config/database.php';

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        $this->conn->set_charset(DB_CHARSET);
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}

/**
 * Procedural wrapper to get the database connection.
 * This allows legacy or procedural code to easily get the singleton connection.
 */
function connect_to_database() {
    return Database::getInstance()->getConnection();
}
