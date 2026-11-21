<?php
class companyAuthModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function registerCompany($companyname, $email, $password, $phonenum, $address, $dti, $bir) {
        try {
            $this->pdo->beginTransaction();

            // Users record
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $userstmt = $this->pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (:email, :password_hash, :role)");
            $role = 'company';
            $userstmt->execute([
                'email' => $email,
                'password_hash' => $hashedPassword,
                'role' => $role
            ]);
            
            $newId = $this->pdo->lastInsertId();

            // Companies record
            $companystmt = $this->pdo->prepare("INSERT INTO companies (user_id, company_name, phone, company_address) VALUES (:user_id, :company_name, :phone, :company_address)");
            $companystmt->execute([
                'user_id' => $newId,
                'company_name' => $companyname,
                'phone' => $phonenum,
                'company_address' => $address
            ]);
            
            $companyID = $this->pdo->lastInsertId();

            // Documents records (DTI and BIR)
            $documentstmt = $this->pdo->prepare("INSERT INTO company_documents (company_id, document_type, document_picture) VALUES (:company_id, :document_type, :document_picture)");

            // DTI
            $documentstmt->execute([
                'company_id' => $companyID,
                'document_type' => 'DTI',
                'document_picture' => $dti
            ]);

            // BIR
            $documentstmt->execute([
                'company_id' => $companyID,
                'document_type' => 'BIR',
                'document_picture' => $bir
            ]);

            $this->pdo->commit();

            // Return success data
            return [
                "id" => $newId,
                "username" => $companyname,
                "email" => $email,
                "role" => "Company"
            ];

        } catch (Exception $e) {
            // Roll back changes if any query fails
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log($e->getMessage());
            return $e->getMessage();
        }
    }
}
?>