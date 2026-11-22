<?php

class ApplyModel {

    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function createApplication(int $jobId, int $userId, ?int $resumeId = null): array {
        $job = $this->pdo->prepare("SELECT id FROM job_listings WHERE id = :job_id LIMIT 1");
        $job->execute(['job_id' => $jobId]);
        if ($job->fetchColumn() === false) {
            return ['ok' => false, 'reason' => 'job_not_found'];
        }

        $pwd = $this->pdo->prepare(
            "SELECT pu.id
             FROM pwd_users pu
             INNER JOIN users u ON u.id = pu.user_id
             WHERE pu.user_id = :user_id AND u.role = 'pwd'
             LIMIT 1"
        );
        $pwd->execute(['user_id' => $userId]);
        $pwdUserId = $pwd->fetchColumn();
        if ($pwdUserId === false) {
            return ['ok' => false, 'reason' => 'pwd_profile_not_found'];
        }

        $existing = $this->pdo->prepare(
            "SELECT id FROM applications
             WHERE job_id = :job_id AND pwd_user_id = :user_id
             LIMIT 1"
        );
        $existing->execute([
            'job_id' => $jobId,
            'user_id' => $userId
        ]);
        $existingApplication = $existing->fetchColumn();
        if ($existingApplication !== false) {
            return [
                'ok' => false,
                'reason' => 'already_applied',
                'application_id' => (int)$existingApplication
            ];
        }

        if ($resumeId !== null) {
            $resume = $this->pdo->prepare(
                "SELECT id FROM resumes
                 WHERE id = :resume_id AND pwd_user_id = :pwd_user_id
                 LIMIT 1"
            );
            $resume->execute([
                'resume_id' => $resumeId,
                'pwd_user_id' => $pwdUserId
            ]);
        } else {
            $resume = $this->pdo->prepare(
                "SELECT id FROM resumes
                 WHERE pwd_user_id = :pwd_user_id
                 ORDER BY id DESC LIMIT 1"
            );
            $resume->execute(['pwd_user_id' => $pwdUserId]);
        }

        $resolvedResumeId = $resume->fetchColumn();
        if ($resolvedResumeId === false) {
            if ($resumeId !== null) {
                return ['ok' => false, 'reason' => 'resume_not_found'];
            }

            $createResume = $this->pdo->prepare(
                "INSERT INTO resumes (pwd_user_id, title, skills, experience, education, certifications)
                 VALUES (:pwd_user_id, 'Default Resume', 'Not specified', 'Not specified', 'Not specified', 'Not specified')"
            );
            $createResume->execute(['pwd_user_id' => $pwdUserId]);
            $resolvedResumeId = (int)$this->pdo->lastInsertId();
        }

        $insert = $this->pdo->prepare(
            "INSERT INTO applications (job_id, pwd_user_id, resume_id, status)
             VALUES (:job_id, :user_id, :resume_id, 'submitted')"
        );
        try {
            $insert->execute([
                'job_id' => $jobId,
                'user_id' => $userId,
                'resume_id' => $resolvedResumeId
            ]);
        } catch (PDOException $e) {
            if (!isset($e->errorInfo[1]) || (int)$e->errorInfo[1] !== 1062) {
                throw $e;
            }

            $existing->execute([
                'job_id' => $jobId,
                'user_id' => $userId
            ]);
            $existingApplication = $existing->fetchColumn();
            if ($existingApplication === false) {
                throw $e;
            }

            return [
                'ok' => false,
                'reason' => 'already_applied',
                'application_id' => (int)$existingApplication
            ];
        }

        return [
            'ok' => true,
            'application_id' => (int)$this->pdo->lastInsertId()
        ];
    }

    public function checkApplicationStatus(int $jobId, int $userId): array {
        $stmt = $this->pdo->prepare(
            "SELECT id, status, applied_at
             FROM applications
             WHERE job_id = :job_id AND pwd_user_id = :user_id
             LIMIT 1"
        );
        $stmt->execute([
            'job_id' => $jobId,
            'user_id' => $userId
        ]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'has_applied' => (bool)$application,
            'application_id' => $application ? (int)$application['id'] : null,
            'status' => $application ? $application['status'] : null,
            'applied_at' => $application ? $application['applied_at'] : null
        ];
    }

    public function getUserApplications(int $userId): array {
        $sql = "SELECT
                    a.id AS application_id,
                    a.job_id,
                    a.status AS application_status,
                    a.applied_at,
                    j.title AS job_title,
                    j.category,
                    j.location,
                    j.employment_type AS job_type,
                    j.salary,
                    c.company_name
                FROM applications a
                INNER JOIN users u
                    ON u.id = a.pwd_user_id
                INNER JOIN job_listings j
                    ON j.id = a.job_id
                INNER JOIN companies c
                    ON c.id = j.company_id
                WHERE u.id = :user_id
                ORDER BY a.applied_at DESC, a.id DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    public function getApplicantsByCompanyUser(int $companyUserId): array {
        $stmt = $this->pdo->prepare(
            "SELECT
                a.id AS application_id,
                a.status AS application_status,
                a.applied_at,
                jl.id AS job_id,
                jl.title AS job_title,
                applicant.id AS user_id,
                pu.id AS pwd_user_id,
                pu.name,
                applicant.email,
                pu.phone,
                pu.location,
                pu.headline,
                pu.bio,
                r.id AS resume_id,
                r.title AS resume_title,
                r.education,
                r.experience,
                r.skills,
                r.certifications
             FROM applications a
             INNER JOIN job_listings jl ON jl.id = a.job_id
             INNER JOIN companies c ON c.id = jl.company_id
             INNER JOIN users company_user
                ON company_user.id = c.user_id AND company_user.role = 'company'
             INNER JOIN users applicant ON applicant.id = a.pwd_user_id
             LEFT JOIN pwd_users pu ON pu.user_id = applicant.id
             LEFT JOIN resumes r ON r.id = a.resume_id
             WHERE c.user_id = :company_user_id
             ORDER BY a.applied_at DESC, a.id DESC"
        );
        $stmt->execute(['company_user_id' => $companyUserId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteApplication(int $userId, ?int $applicationId = null, ?int $jobId = null): array {
        if ($applicationId !== null) {
            $delete = $this->pdo->prepare(
                "DELETE FROM applications
                 WHERE id = :application_id AND pwd_user_id = :user_id"
            );
            $delete->execute([
                'application_id' => $applicationId,
                'user_id' => $userId
            ]);
        } elseif ($jobId !== null) {
            $delete = $this->pdo->prepare(
                "DELETE FROM applications
                 WHERE job_id = :job_id AND pwd_user_id = :user_id"
            );
            $delete->execute([
                'job_id' => $jobId,
                'user_id' => $userId
            ]);
        } else {
            return ['ok' => false, 'reason' => 'missing_identifier'];
        }

        if ($delete->rowCount() === 0) {
            return ['ok' => false, 'reason' => 'application_not_found'];
        }

        return ['ok' => true];
    }
}
?>