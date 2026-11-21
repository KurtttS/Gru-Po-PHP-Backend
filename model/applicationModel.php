
<?php

class ApplicationModel {

    private $conn;

    public function __construct($db) {
    $this->conn = $db;
    $this->conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );
}

    public function getJobDetails($jobId) {

        $sql = "SELECT
                    j.id AS job_id,
                    j.title AS job_title,
                    j.category,
                    j.location,
                    j.employment_type AS job_type,
                    j.salary,
                    j.description AS job_description,
                    j.requirements,

                    c.id AS company_id,
                    c.company_name,
                    c.company_address,
                    c.description AS company_description

                FROM job_listings j

                INNER JOIN companies c
                    ON j.company_id = c.id

                WHERE j.id = :job_id

                LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(":job_id", $jobId, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    
    // FETCH RESUME
    public function getResume($userId) {
        $sql = "SELECT
                    pu.id AS pwd_user_id,
                    pu.name,
                    u.email,
                    pu.phone,
                    pu.location AS address,
                    r.id AS resume_id,
                    r.title,
                    r.education,
                    r.experience,
                    r.skills,
                    r.certifications
                FROM pwd_users pu
                INNER JOIN users u
                    ON pu.user_id = u.id
                LEFT JOIN resumes r
                    ON pu.id = r.pwd_user_id
                WHERE pu.user_id = :user_id
                ORDER BY r.id DESC
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    // SAVE OR UPDATE RESUME
    public function saveResume($userId, $education, $experience, $skills, $certifications) {

        // Get the PWD user's ID
        $sql = "SELECT id
                FROM pwd_users
                WHERE user_id = :user_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $pwdUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pwdUser) {
            return false;
        }

        $pwdUserId = $pwdUser['id'];

        // Check if a resume already exists
        $sql = "SELECT id
                FROM resumes
                WHERE pwd_user_id = :pwd_user_id
                ORDER BY id DESC
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':pwd_user_id', $pwdUserId, PDO::PARAM_INT);
        $stmt->execute();

        $existingResume = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingResume) {

            // UPDATE existing resume
            $sql = "UPDATE resumes
                    SET education = :education,
                        experience = :experience,
                        skills = :skills,
                        certifications = :certifications
                    WHERE id = :resume_id";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindValue(':resume_id', $existingResume['id'], PDO::PARAM_INT);

        } else {

            // CREATE new resume
            $sql = "INSERT INTO resumes
                        (pwd_user_id, title, education, experience, skills, certifications)
                    VALUES
                        (:pwd_user_id, 'Resume', :education, :experience, :skills, :certifications)";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':pwd_user_id', $pwdUserId, PDO::PARAM_INT);
        }

        $stmt->bindValue(':education', $education);
        $stmt->bindValue(':experience', $experience);
        $stmt->bindValue(':skills', $skills);
        $stmt->bindValue(':certifications', $certifications);

        return $stmt->execute();
    }

}
?>
