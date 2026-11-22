<?php

class ApplyController {

    private $model;

    public function __construct(ApplyModel $model) {
        $this->model = $model;
    }

    private function requestData(): array {
        $data = [];
        $input = file_get_contents("php://input");

        if (is_string($input) && trim($input) !== '') {
            $json = json_decode($input, true);
            if (is_array($json)) {
                $data = $json;
            }
        }

        if (!empty($_GET) && is_array($_GET)) {
            $data = array_merge($data, $_GET);
        }

        if (!empty($_POST) && is_array($_POST)) {
            $data = array_merge($data, $_POST);
        }

        return $data;
    }

    private function positiveInt($value): ?int {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        return ($value !== false && $value > 0) ? (int)$value : null;
    }

    public function toCreateApplication() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Only POST requests are allowed."]);
            return;
        }

        $data = $this->requestData();
        $jobId = $this->positiveInt($data['job_id'] ?? null);
        $userId = $this->positiveInt($data['user_id'] ?? null);
        $resumeId = null;
        $hasResumeId = isset($data['resume_id']) &&
            $data['resume_id'] !== '' &&
            $data['resume_id'] !== null;

        if ($hasResumeId) {
            $resumeId = $this->positiveInt($data['resume_id']);
        }

        if (!$jobId || !$userId || ($hasResumeId && !$resumeId)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Valid job_id and user_id are required."
            ]);
            return;
        }

        try {
            $result = $this->model->createApplication($jobId, $userId, $resumeId);

            if (!$result['ok']) {
                $messages = [
                    'job_not_found' => 'Job not found.',
                    'pwd_profile_not_found' => 'PWD profile not found.',
                    'resume_not_found' => 'Resume not found.',
                    'already_applied' => 'You have already applied to this job.'
                ];

                if ($result['reason'] === 'job_not_found') {
                    $status = 404;
                } elseif ($result['reason'] === 'already_applied') {
                    $status = 409;
                } else {
                    $status = 400;
                }
                http_response_code($status);
                echo json_encode([
                    "success" => false,
                    "message" => $messages[$result['reason']] ?? "Unable to submit application."
                ]);
                return;
            }

            http_response_code(201);
            echo json_encode([
                "success" => true,
                "message" => "Application submitted successfully.",
                "application_id" => $result['application_id']
            ]);
        } catch (PDOException $e) {
            error_log("Create application error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Database error while submitting application."
            ]);
        }
    }

    public function toCheckApplicationStatus() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST, GET");

        if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'], true)) {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Only GET or POST requests are allowed."]);
            return;
        }

        $data = $this->requestData();
        $jobId = $this->positiveInt($data['job_id'] ?? $_GET['job_id'] ?? null);
        $userId = $this->positiveInt($data['user_id'] ?? $_GET['user_id'] ?? null);

        if (!$jobId || !$userId) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Valid job_id and user_id are required."
            ]);
            return;
        }

        try {
            $statusInfo = $this->model->checkApplicationStatus($jobId, $userId);
            http_response_code(200);
            echo json_encode(array_merge(["success" => true], $statusInfo));
        } catch (PDOException $e) {
            error_log("Check application status error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Database error while checking application status."
            ]);
        }
    }

    public function toFetchApplicants() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST, GET");

        if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'], true)) {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Only GET or POST requests are allowed."]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->requestData();
            $userId = $this->positiveInt($data['user_id'] ?? null);
        } else {
            $userId = $this->positiveInt($_GET['user_id'] ?? null);
        }

        if (!$userId) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Valid user_id is required."
            ]);
            return;
        }

        try {
            $applicants = $this->model->getApplicantsByCompanyUser($userId);
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "applicants" => $applicants
            ]);
        } catch (PDOException $e) {
            error_log("Fetch applicants error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Database error while fetching applicants."
            ]);
        }
    }

    public function toFetchUserApplications() {
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST, GET");

    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'], true)) {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Only GET or POST requests are allowed."
        ]);
        return;
    }

    $data = $this->requestData();
    $userId = $this->positiveInt($data['user_id'] ?? null);

    if (!$userId) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Valid user_id is required."
        ]);
        return;
    }

    try {
        $applications = $this->model->getUserApplications($userId);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "applications" => $applications
        ]);

    } catch (PDOException $e) {
        error_log("Fetch user applications error: " . $e->getMessage());

        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Database error while fetching applications."
        ]);
    }
}

    public function toDeleteApplication() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Only POST requests are allowed."]);
            return;
        }

        $data = $this->requestData();
        $userId = $this->positiveInt($data['user_id'] ?? null);
        $applicationId = null;
        $jobId = null;
        $hasApplicationId = isset($data['application_id']) &&
            $data['application_id'] !== '' &&
            $data['application_id'] !== null;
        $hasJobId = isset($data['job_id']) &&
            $data['job_id'] !== '' &&
            $data['job_id'] !== null;

        if ($hasApplicationId) {
            $applicationId = $this->positiveInt($data['application_id']);
        }

        if ($hasJobId) {
            $jobId = $this->positiveInt($data['job_id']);
        }

        if (!$userId ||
            (!$applicationId && !$jobId) ||
            ($hasApplicationId && !$applicationId) ||
            ($hasJobId && !$jobId)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Valid user_id and application_id or job_id are required."
            ]);
            return;
        }

        try {
            $result = $this->model->deleteApplication(
                $userId,
                $applicationId,
                $jobId
            );

            if (!$result['ok']) {
                http_response_code(404);
                echo json_encode([
                    "success" => false,
                    "message" => "Application not found."
                ]);
                return;
            }

            echo json_encode([
                "success" => true,
                "message" => "Application deleted successfully."
            ]);
        } catch (PDOException $e) {
            error_log("Delete application error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Database error while deleting application."
            ]);
        }
    }
}
?>