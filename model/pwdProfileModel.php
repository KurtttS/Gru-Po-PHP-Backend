<?php
class PwdProfileModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function fetchProfile(string $userId): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT p.name, p.headline, p.location, p.phone, p.bio, u.email
             FROM pwd_users p
             INNER JOIN users u ON u.id = p.user_id
             WHERE p.user_id = :user_id
             LIMIT 1"
        );
        $stmt->execute(['user_id' => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        return $profile ?: null;
    }

    public function emailTakenByOther(string $email, string $userId): bool {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM users WHERE email = :email AND id <> :user_id LIMIT 1"
        );
        $stmt->execute(['email' => $email, 'user_id' => $userId]);
        return $stmt->fetchColumn() !== false;
    }

    // Returns false if this user has no PWD profile
    public function updateProfile(
        string $userId,
        string $name,
        string $email,
        string $phone,
        string $headline,
        string $location,
        string $bio
    ): bool {
        try {
            $this->pdo->beginTransaction();

            $check = $this->pdo->prepare("SELECT id FROM pwd_users WHERE user_id = :user_id LIMIT 1");
            $check->execute(['user_id' => $userId]);

            if ($check->fetchColumn() === false) {
                $this->pdo->rollBack();
                return false;
            }

            $stmt = $this->pdo->prepare("UPDATE users SET email = :email WHERE id = :user_id");
            $stmt->execute(['email' => $email, 'user_id' => $userId]);

            $stmt = $this->pdo->prepare(
                "UPDATE pwd_users
                 SET name = :name,
                     phone = :phone,
                     headline = :headline,
                     location = :location,
                     bio = :bio
                 WHERE user_id = :user_id"
            );
            $stmt->execute([
                'name'     => $name,
                'phone'    => $phone,
                'headline' => $headline,
                'location' => $location,
                'bio'      => $bio,
                'user_id'  => $userId
            ]);

            $this->pdo->commit();
            return true;

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
?>