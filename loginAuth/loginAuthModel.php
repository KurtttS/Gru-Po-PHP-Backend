
<?php

class LoginAuthModel {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Find account using email
    public function login($email, $password) {

        $sql = "SELECT id, email, password_hash, role
                FROM users
                WHERE email = :email
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':email' => $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check password
        if ($user && password_verify($password, $user['password_hash'])) {

            return [
                "id" => $user['id'],
                "email" => $user['email'],
                "role" => $user['role']
            ];
        }

        return false;
    }
}
