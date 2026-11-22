
<?php

class ApplicantScreeningModel {

    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    // Fetch all applicants for every job posted by a company
    public function fetchCompanyApplicants(int $userId): array {

        $sql = "SELECT
                    a.id AS application_id,
                    a.job_id,
                    a.pwd_user_id AS applicant_user_id,
                    a.resume_id,
                    a.status AS application_status,
                    a.applied_at,

                    j.title AS job_title,
                    j.category AS job_category,
                    j.location AS job_location,
                    j.employment_type AS job_type,

                    u.email AS applicant_email,

                    pu.id AS pwd_profile_id,
                    pu.name AS applicant_name,
                    pu.headline,
                    pu.location AS applicant_location,
                    pu.phone AS applicant_phone,
                    pu.bio AS applicant_bio,

                    r.title AS resume_title

                FROM applications a

                INNER JOIN job_listings j
                    ON j.id = a.job_id

                INNER JOIN companies c
                    ON c.id = j.company_id

                INNER JOIN users u
                    ON u.id = a.pwd_user_id

                INNER JOIN pwd_users pu
                    ON pu.user_id = u.id

                LEFT JOIN resumes r
                    ON r.id = a.resume_id
                    AND r.pwd_user_id = pu.id

                WHERE c.user_id = :user_id

                ORDER BY a.applied_at DESC";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchApplicantScreeningDetails(int $applicationId,int $userId): ?array {

        $sql = "SELECT
                    a.id AS application_id,
                    a.status AS application_status,
                    a.applied_at,

                    u.id AS applicant_user_id,
                    u.email AS applicant_email,

                    pu.id AS pwd_profile_id,
                    pu.name AS applicant_name,
                    pu.phone AS applicant_phone,
                    pu.location AS applicant_address,
                    pu.headline AS applicant_headline,
                    pu.bio AS applicant_bio,
                    pu.accommodation_notes,

                    j.id AS job_id,
                    j.title AS job_title,
                    j.category AS job_category,
                    j.location AS job_location,
                    j.employment_type,
                    j.salary,
                    j.description AS job_description,
                    j.requirements AS job_requirements,

                    r.id AS resume_id,
                    r.title AS resume_title,
                    r.education,
                    r.experience,
                    r.skills,
                    r.certifications

                FROM applications a

                INNER JOIN job_listings j
                    ON j.id = a.job_id

                INNER JOIN companies c
                    ON c.id = j.company_id

                INNER JOIN users u
                    ON u.id = a.pwd_user_id

                INNER JOIN pwd_users pu
                    ON pu.user_id = u.id

                INNER JOIN resumes r
                    ON r.id = a.resume_id
                    AND r.pwd_user_id = pu.id

                WHERE a.id = :application_id
                AND c.user_id = :user_id

                LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':application_id' => $applicationId,
            ':user_id' => $userId
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function updateApplicationStatus(int $applicationId, int $userId, string $status): bool {

        $sql = "UPDATE applications a

                INNER JOIN job_listings j
                    ON j.id = a.job_id

                INNER JOIN companies c
                    ON c.id = j.company_id

                SET a.status = :status

                WHERE a.id = :application_id
                AND c.user_id = :user_id
                AND a.status = 'submitted'";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            ':status' => $status,
            ':application_id' => $applicationId,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }

}
