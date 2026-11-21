<?php
class Database {
    private $host = "localhost";        
    private $user = "root";            
    private $password = "";            
    private $db_name = "kayawork"; 
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->user, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            header("Content-Type: application/json");
            echo json_encode(array("success" => false, "message" => "Database Connection Failed: " . $e->getMessage()));
            exit();
        }

        return $this->conn;
    }
}
?>