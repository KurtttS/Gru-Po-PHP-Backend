
<?php

class CompanyJobController {

    private $model;

    public function __construct($companyJobModel) {
        $this->model = $companyJobModel;
    }


    // ==========================================
    // POST A NEW JOB
    // ==========================================

    public function toPostJob() {

        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");

        // Check request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);

            echo json_encode([
                "status" => "error",
                "message" => "Only POST requests are allowed."
            ]);

            return;
        }

        // Receive JSON data from Android
        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data)) {
            http_response_code(400);

            echo json_encode([
                "status" => "error",
                "message" => "Invalid JSON data."
            ]);

            return;
        }

        // Extract fields
        $userId = filter_var(
            $data['user_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $jobTitle = trim($data['job_title'] ?? $data['title'] ?? '');
        $category = trim($data['category'] ?? '');
        $jobType = trim($data['job_type'] ?? $data['employment_type'] ?? '');
        $salary = trim((string)($data['salary'] ?? ''));
        $location = trim($data['location'] ?? '');
        $description = trim($data['description'] ?? '');
        $requirements = trim($data['requirements'] ?? '');

        // Validate user ID
        if ($userId === false || $userId === null || $userId < 1) {
            http_response_code(400);

            echo json_encode([
                "status" => "error",
                "message" => "A valid user_id is required."
            ]);

            return;
        }

        // Validate required fields
        if (
            $jobTitle === '' ||
            $category === '' ||
            $jobType === '' ||
            $salary === '' ||
            $location === '' ||
            $description === '' ||
            $requirements === ''
        ) {
            http_response_code(400);

            echo json_encode([
                "status" => "error",
                "message" => "All fields are required."
            ]);

            return;
        }

        try {

            $result = $this->model->postJob(
                (string)$userId,
                $jobTitle,
                $category,
                $jobType,
                $salary,
                $location,
                $description,
                $requirements
            );

            if ($result) {
                http_response_code(201);

                echo json_encode([
                    "status" => "success",
                    "message" => "Job posted successfully!"
                ]);

            } else {
                http_response_code(404);

                echo json_encode([
                    "status" => "error",
                    "message" => "Company profile not found."
                ]);
            }

        } catch (InvalidArgumentException $e) {

            http_response_code(400);

            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);

        } catch (PDOException $e) {

            error_log($e->getMessage());

            http_response_code(500);

            echo json_encode([
                "status" => "error",
                "message" => "Database error while posting job."
            ]);
        }
    }


    // ==========================================
    // FETCH ALL COMPANY JOBS
    // ==========================================

    public function toFetchCompanyJobs() {

        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");

        // Check request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);

            echo json_encode([
                "status" => "error",
                "message" => "Only POST requests are allowed."
            ]);

            return;
        }

        // Receive JSON data from Android
        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data)) {
            http_response_code(400);

            echo json_encode([
                "status" => "error",
                "message" => "Invalid JSON data."
            ]);

            return;
        }

        // Validate user ID
        $userId = filter_var(
            $data['user_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($userId === false || $userId === null || $userId < 1) {
            http_response_code(400);

            echo json_encode([
                "status" => "error",
                "message" => "A valid user_id is required."
            ]);

            return;
        }

        try {

            // Fetch all jobs belonging to this company
            $jobs = $this->model->fetchCompanyJobs(
                (string)$userId
            );

            echo json_encode([
                "status" => "success",
                "message" => count($jobs) > 0
                    ? "Company jobs fetched successfully."
                    : "No jobs posted yet.",
                "jobs" => $jobs
            ]);

        } catch (PDOException $e) {

            error_log($e->getMessage());

            http_response_code(500);

            echo json_encode([
                "status" => "error",
                "message" => "Database error while fetching company jobs."
            ]);
        }
    }

    public function toFetchCompanyStats() {

        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                "status" => "error",
                "message" => "Only POST requests are allowed."
            ]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Invalid JSON data."
            ]);
            return;
        }

        $userId = filter_var(
            $data['user_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($userId === false || $userId === null || $userId < 1) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "A valid user_id is required."
            ]);
            return;
        }

        try {
            // Call the model function
            $stats = $this->model->fetchCompanyStats((string) $userId);

            echo json_encode([
                "status" => "success",
                "message" => "Company statistics fetched successfully.",
                "stats" => $stats
            ]);

        } catch (PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);

            echo json_encode([
                "status" => "error",
                "message" => "Database error while fetching statistics."
            ]);
        }
    }


    public function toFetchJobDetails() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                "status" => "error",
                "message" => "Only POST requests are allowed."
            ]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $jobId = filter_var(
            $data['job_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $userId = filter_var(
            $data['user_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$jobId || !$userId || $jobId < 1 || $userId < 1) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Valid job_id and user_id are required."
            ]);
            return;
        }

        try {
            $job = $this->model->getJobById($jobId, (string)$userId);

            if (!$job) {
                http_response_code(404);
                echo json_encode([
                    "status" => "error",
                    "message" => "Job not found or you do not have permission to access it."
                ]);
                return;
            }

            echo json_encode([
                "status" => "success",
                "message" => "Job details fetched successfully.",
                "job" => $job
            ]);

        } catch (PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Database error while fetching job details."
            ]);
        }
    }

    public function toUpdateJob() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                "status" => "error",
                "message" => "Only POST requests are allowed."
            ]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Invalid JSON data."
            ]);
            return;
        }

        $jobId = filter_var(
            $data['job_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $userId = filter_var(
            $data['user_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $title = trim($data['title'] ?? '');
        $category = trim($data['category'] ?? '');
        $employmentType = trim($data['employment_type'] ?? '');
        $salary = trim($data['salary'] ?? '');
        $location = trim($data['location'] ?? '');
        $description = trim($data['description'] ?? '');
        $requirements = trim($data['requirements'] ?? '');

        if (!$jobId || !$userId || $jobId < 1 || $userId < 1) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Valid job_id and user_id are required."
            ]);
            return;
        }

        if (
            $title === '' ||
            $category === '' ||
            $employmentType === '' ||
            $location === '' ||
            $description === ''
        ) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Please complete all required job fields."
            ]);
            return;
        }

        try {
            // Verify that this job belongs to the company.
            $existingJob = $this->model->getJobById(
                $jobId,
                (string)$userId
            );

            if (!$existingJob) {
                http_response_code(404);
                echo json_encode([
                    "status" => "error",
                    "message" => "Job not found or you do not have permission to edit it."
                ]);
                return;
            }

            $this->model->updateJob(
                $jobId,
                (string)$userId,
                $title,
                $category,
                $employmentType,
                $salary,
                $location,
                $description,
                $requirements
            );

            echo json_encode([
                "status" => "success",
                "message" => "Job updated successfully."
            ]);

        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);

        } catch (PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Database error while updating job."
            ]);
        }
    }

}
?>
