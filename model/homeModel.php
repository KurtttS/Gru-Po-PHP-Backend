<?php

class HomeModel {

    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function fetchFourJobs(): array {
        $stmt = $this->pdo->prepare(
            "SELECT
                jl.id,
                jl.title,
                jl.title AS job_title,
                jl.category,
                jl.location,
                jl.employment_type,
                jl.employment_type AS job_type,
                jl.salary,
                jl.description,
                jl.requirements,
                jl.created_at,
                c.company_name
             FROM job_listings jl
             INNER JOIN companies c ON jl.company_id = c.id
             ORDER BY jl.created_at DESC, jl.id DESC
             LIMIT 4"
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchAllJobs(): array {
        $stmt = $this->pdo->prepare(
            "SELECT
                jl.id,
                jl.title,
                jl.title AS job_title,
                jl.category,
                jl.location,
                jl.employment_type,
                jl.employment_type AS job_type,
                jl.salary,
                jl.description,
                jl.requirements,
                jl.created_at,
                c.company_name
             FROM job_listings jl
             INNER JOIN companies c ON jl.company_id = c.id
             ORDER BY jl.created_at DESC, jl.id DESC"
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchJobs(string $searchTerm): array {
        if ($searchTerm === '') {
            return $this->fetchAllJobs();
        }

        $searchPattern = '%' . strtr($searchTerm, [
            '!' => '!!',
            '%' => '!%',
            '_' => '!_'
        ]) . '%';

        $stmt = $this->pdo->prepare(
            "SELECT
                jl.id,
                jl.title,
                jl.title AS job_title,
                jl.category,
                jl.location,
                jl.employment_type,
                jl.employment_type AS job_type,
                jl.salary,
                jl.description,
                jl.requirements,
                jl.created_at,
                c.company_name
             FROM job_listings jl
             INNER JOIN companies c ON jl.company_id = c.id
             WHERE jl.title LIKE :title ESCAPE '!'
                OR jl.category LIKE :category ESCAPE '!'
                OR jl.location LIKE :location ESCAPE '!'
                OR jl.description LIKE :description ESCAPE '!'
                OR c.company_name LIKE :company_name ESCAPE '!'
             ORDER BY jl.created_at DESC, jl.id DESC"
        );
        $stmt->execute([
            'title' => $searchPattern,
            'category' => $searchPattern,
            'location' => $searchPattern,
            'description' => $searchPattern,
            'company_name' => $searchPattern
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>