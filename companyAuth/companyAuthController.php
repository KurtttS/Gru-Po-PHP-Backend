<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/companyAuthModel.php';

class companyAuthController {
    private $model;

    public function __construct($companyAuthModel) {
        $this->model = $companyAuthModel;
    }

    // Register
    public function toRegisterCompany() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Must use standard POST form
            $companyname = isset($_POST['companyname']) ? trim($_POST['companyname']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            $phonenum = isset($_POST['phonenum']) ? trim($_POST['phonenum']) : '';
            $address = isset($_POST['address']) ? trim($_POST['address']) : '';
            $dti = isset($_POST['dti']) ? trim($_POST['dti']) : '';
            $bir = isset($_POST['bir']) ? trim($_POST['bir']) : '';

            if (empty($companyname) || empty($email) || empty($password) || empty($phonenum)
            || empty($address) || empty($dti) || empty($bir)) {
                http_response_code(400); 
                echo json_encode([
                    "status" => "error",
                    "message" => "Please enter all required fields."
                ]);
                exit();
            }

            // Call model method
            $result = $this->model->registerCompany($companyname, $email, $password, $phonenum, $address, $dti, $bir);

            // If $result is an array and has an 'id', it truly succeeded!
            if (is_array($result) && isset($result['id'])) {
                // Successful register
                http_response_code(200); 
                echo json_encode([
                    "status" => "success",
                    "message" => "Registration successful.",
                    "user" => [
                        "id" => $result['id'],
                        "username" => $result['username'],
                        "email" => $result['email'],
                        "role" => $result['role']
                    ]
                ]);
            } else {
                // Failed register - $result holds the SQL error message string
                http_response_code(401); 
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid registration.",
                    "sql_error" => $result 
                ]);
            }

        } else {
            // Block if not POST
            http_response_code(405);
            echo json_encode([
                "status" => "error",
                "message" => "Only POST requests are allowed."
            ]);
        }
    }
}
?>