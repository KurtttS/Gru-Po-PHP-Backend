<?php
class CompanyProfileController {
    private $model;

    public function __construct($companyProfileModel) {
        $this->model = $companyProfileModel;
    }

    public function tofetchCompanyProfile() {
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
        $userId = is_array($data) ? filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT) : false;

        if ($userId === false || $userId === null || $userId < 1) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "A valid user_id is required."
            ]);
            return;
        }

        $userId = (string) $userId;

        try {
            $company = $this->model->fetchCompanyProfile($userId);

            if ($company === null) {
                http_response_code(404);
                echo json_encode([
                    "status" => "error",
                    "message" => "Company profile not found."
                ]);
                return;
            }

            echo json_encode([
                "status" => "success",
                "message" => "Company profile fetched successfully.",
                "company" => $company
            ]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Database error."
            ]);
        }
    }


    public function toUpdateCompanyProfile() {

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

        // Extract and validate fields
        $userId = filter_var(
            $data['user_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $companyName = trim($data['company_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $description = trim($data['description'] ?? '');

        // Check user ID
        if ($userId === false || $userId === null || $userId < 1) {
            http_response_code(400);

            echo json_encode([
                "status" => "error",
                "message" => "A valid user_id is required."
            ]);

            return;
        }

        // Check empty fields
        if (
            empty($companyName) ||
            empty($email) ||
            empty($phone) ||
            empty($description)
        ) {
            http_response_code(400);

            echo json_encode([
                "status" => "error",
                "message" => "All fields are required."
            ]);

            return;
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);

            echo json_encode([
                "status" => "error",
                "message" => "Invalid email address."
            ]);

            return;
        }

        try {

            $result = $this->model->updateCompanyProfile(
                (string) $userId,
                $companyName,
                $email,
                $phone,
                $description
            );

            if ($result) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Company profile updated successfully."
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    "status" => "error",
                    "message" => "Company profile not found."
                ]);
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());

            http_response_code(500);

            echo json_encode([
                "status" => "error",
                "message" => "Failed to update company profile."
            ]);
        }
    }

}
?>
