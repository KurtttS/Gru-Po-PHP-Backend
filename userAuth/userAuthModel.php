<?php
class UserAuthModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() !== false;
    }

    // Step 1: Create base user record and return the generated ID
    public function createUser($email, $password, $role = 'pwd') {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (:email, :password_hash, :role)");
        $stmt->execute([
            'email' => $email,
            'password_hash' => $hashedPassword,
            'role' => $role
        ]);

        return $this->pdo->lastInsertId();
    }

    // Step 2: Insert into `pwd_users` table mapping user_id and profile data
    public function createPwdProfile($userId, $name, $headline, $location, $phone) {
        $stmt = $this->pdo->prepare("INSERT INTO pwd_users (user_id, name, headline, location, phone) VALUES (:user_id, :name, :headline, :location, :phone)");
        $stmt->execute([
            'user_id'  => $userId,
            'name'     => $name,
            'headline' => $headline,
            'location' => $location,
            'phone'    => $phone
        ]);
        return true;
    }
}
?>