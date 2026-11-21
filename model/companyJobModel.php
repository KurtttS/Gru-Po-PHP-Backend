
<?php

class CompanyJobModel {

    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ==========================================
    // POST A NEW JOB
    // ==========================================

    public function postJob(
        string $userId,
        string $jobTitle,
        string $category,
        string $jobType,
        string $salary,
        string $location,
        string $description,
        string $requirements
    ): bool {

        // Get the company's ID using its user ID
        $companyStmt = $this->pdo->prepare(
            "SELECT id
             FROM companies
             WHERE user_id = :user_id
             LIMIT 1"
        );

        $companyStmt->execute([
            'user_id' => $userId
        ]);

        $companyId = $companyStmt->fetchColumn();

        if ($companyId === false) {
            return false;
        }

        // Validate employment type
        $employmentType = strtolower(
            trim(str_replace(['-', ' '], '_', $jobType))
        );

        $allowedTypes = [
            'full_time',
            'part_time',
            'contract',
            'internship'
        ];

        if (!in_array($employmentType, $allowedTypes, true)) {
            throw new InvalidArgumentException('Invalid job type.');
        }

        // Insert job into job_listings
        $stmt = $this->pdo->prepare(
            "INSERT INTO job_listings (
                company_id,
                title,
                category,
                location,
                employment_type,
                salary,
                description,
                requirements
            ) VALUES (
                :company_id,
                :title,
                :category,
                :location,
                :employment_type,
                :salary,
                :description,
                :requirements
            )"
        );

        return $stmt->execute([
            'company_id' => $companyId,
            'title' => $jobTitle,
            'category' => $category,
            'location' => $location,
            'employment_type' => $employmentType,
            'salary' => $salary,
            'description' => $description,
            'requirements' => $requirements
        ]);
    }


    // ==========================================
    // FETCH ALL JOBS OF A COMPANY
    // ==========================================

    public function fetchCompanyJobs(string $userId): array {

        $stmt = $this->pdo->prepare(
            "SELECT
                jl.id,
                jl.title AS job_title,
                jl.category,
                jl.location,
                jl.employment_type AS job_type,
                jl.salary,
                jl.description,
                jl.requirements,
                jl.created_at
             FROM job_listings jl
             INNER JOIN companies c
                ON jl.company_id = c.id
             WHERE c.user_id = :user_id
             ORDER BY jl.created_at DESC, jl.id DESC"
        );

        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch company dashboard statistics
    public function fetchCompanyStats(string $userId): array {

        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(DISTINCT jl.id) AS active_jobs,

                COUNT(a.id) AS total_applications,

                COUNT(
                    CASE
                        WHEN a.status = 'accepted'
                        THEN a.id
                    END
                ) AS hired_count

            FROM companies c

            LEFT JOIN job_listings jl
                ON jl.company_id = c.id

            LEFT JOIN applications a
                ON a.job_id = jl.id

            WHERE c.user_id = :user_id"
        );

        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getJobById(int $jobId, string $userId): ?array {
        $sql = "
            SELECT
                jl.id,
                jl.title,
                jl.category,
                jl.employment_type,
                jl.salary,
                jl.location,
                jl.description,
                jl.requirements
            FROM job_listings jl
            INNER JOIN companies c ON jl.company_id = c.id
            WHERE jl.id = :job_id
            AND c.user_id = :user_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':job_id' => $jobId,
            ':user_id' => $userId
        ]);

        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        return $job ?: null;
    }


    public function updateJob(
        int $jobId,
        string $userId,
        string $title,
        string $category,
        string $employmentType,
        string $salary,
        string $location,
        string $description,
        string $requirements
    ): bool {

        $allowedTypes = [
            'full_time',
            'part_time',
            'contract',
            'internship'
        ];

        $employmentType = strtolower(str_replace(' ', '_', trim($employmentType)));

        if (!in_array($employmentType, $allowedTypes, true)) {
            throw new InvalidArgumentException("Invalid employment type.");
        }

        $sql = "
            UPDATE job_listings jl
            INNER JOIN companies c ON jl.company_id = c.id
            SET
                jl.title = :title,
                jl.category = :category,
                jl.employment_type = :employment_type,
                jl.salary = :salary,
                jl.location = :location,
                jl.description = :description,
                jl.requirements = :requirements
            WHERE jl.id = :job_id
            AND c.user_id = :user_id
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':title' => $title,
            ':category' => $category,
            ':employment_type' => $employmentType,
            ':salary' => $salary,
            ':location' => $location,
            ':description' => $description,
            ':requirements' => $requirements,
            ':job_id' => $jobId,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }

}
?>
