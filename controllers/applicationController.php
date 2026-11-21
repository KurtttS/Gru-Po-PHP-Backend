
<?php

class ApplicationController {

    private $model;

    public function __construct($applicationModel) {
        $this->model = $applicationModel;
    }

    public function toGetJobDetails() {

        header("Content-Type: application/json");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Allow-Headers: Content-Type");

        // Receive JSON POST data
        $data = json_decode(file_get_contents("php://input"), true);

        // Get values sent by Android
        $userId = isset($data['user_id']) ? $data['user_id'] : null;
        $jobId = isset($data['job_id']) ? $data['job_id'] : null;

        // Validate required data
        if (
            empty($userId) ||
            !is_numeric($userId) ||
            empty($jobId) ||
            !is_numeric($jobId)
        ) {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "User ID and Job ID are required."
            ]);

            return;
        }

        try {

            // Get job and company details
            $job = $this->model->getJobDetails((int)$jobId);

            if (!$job) {

                http_response_code(404);

                echo json_encode([
                    "success" => false,
                    "message" => "Job not found."
                ]);

                return;
            }

            // Return information to Android
            http_response_code(200);

            echo json_encode([
                "success" => true,
                "message" => "Job details retrieved successfully.",
                "job" => [
                    "job_id" => $job['job_id'],
                    "job_title" => $job['job_title'],
                    "category" => $job['category'],
                    "company_name" => $job['company_name'],
                    "location" => $job['location'],
                    "salary" => $job['salary'],
                    "job_type" => $job['job_type'],
                    "job_description" => $job['job_description'],
                    "requirements" => $job['requirements'],
                    "company_description" => $job['company_description']
                ]
            ]);

        } catch (PDOException $e) {

            http_response_code(500);

            error_log("Get Job Details Error: " . $e->getMessage());

            echo json_encode([
                "success" => false,
                "message" => "Database error occurred."
            ]);
        }
    }
}
?>
