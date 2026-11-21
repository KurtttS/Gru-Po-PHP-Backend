<?php
class CompanyProfileModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function fetchCompanyProfile(string $userId): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT c.company_name, c.phone, c.description
             FROM users u
             INNER JOIN companies c ON c.user_id = u.id
             WHERE u.id = :user_id"
        );
        $stmt->execute(['user_id' => $userId]);

        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($company === false) {
            return null;
        }

        $defaults = [
            'phone' => 'Insert phone number',
            'description' => 'Insert company description'
        ];

        foreach ($defaults as $field => $default) {
            if (!isset($company[$field]) || trim((string) $company[$field]) === '') {
                $company[$field] = $default;
            }
        }

        return $company;
    }

    //Update company profile
    public function updateCompanyProfile(
        string $userId,
        string $companyName,
        string $email,
        string $phone,
        string $description
    ): bool {

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                "SELECT u.id
                 FROM users u
                 INNER JOIN companies c ON c.user_id = u.id
                 WHERE u.id = :user_id
                 LIMIT 1"
            );
            $stmt->execute(['user_id' => $userId]);

            if ($stmt->fetchColumn() === false) {
                $this->pdo->rollBack();
                return false;
            }

            // Update email in users table
            $stmt = $this->pdo->prepare(
                "UPDATE users
                SET email = :email
                WHERE id = :user_id"
            );

            $stmt->execute([
                'email' => $email,
                'user_id' => $userId
            ]);

            // Update company details in companies table
            $stmt = $this->pdo->prepare(
                "UPDATE companies
                SET company_name = :company_name,
                    phone = :phone,
                    description = :description
                WHERE user_id = :user_id"
            );

            $stmt->execute([
                'company_name' => $companyName,
                'phone' => $phone,
                'description' => $description,
                'user_id' => $userId
            ]);

            // Save both updates
            $this->pdo->commit();

            return true;

        } catch (PDOException $e) {

            // Undo changes if an error occurs
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }


    
}
?>
