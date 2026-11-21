
<?php

class ApplicationModel {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
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
}
?>
