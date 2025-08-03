<?php
// Database configuration for LNHS Documents Request Portal
// Compatible with XAMPP/phpMyAdmin

class Database {
    private $host = 'localhost';
    private $db_name = 'lnhs_portal';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
    
    public function closeConnection() {
        $this->conn = null;
    }
}

// Function to get database connection
function getDBConnection() {
    $database = new Database();
    return $database->getConnection();
}

// Test database connection
function testConnection() {
    try {
        $db = getDBConnection();
        if ($db) {
            return true;
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}
?>