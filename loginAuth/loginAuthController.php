
<?php

class LoginAuthController {

    private $loginModel;

    public function __construct($model) {
        $this->loginModel = $model;
    }

    public function toLogin() {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);

            echo json_encode([
                "status" => "error",
                "message" => "Only POST method is allowed."
            ]);

            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data)) {
            http_response_code(400);

            echo json_encode([
                "status" => "error",
                "message" => "Invalid request data."
            ]);

            return;
        }

        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);

            echo json_encode([
                "status" => "error",
                "message" => "Please enter email and password."
            ]);

            return;
        }

        try {

            $user = $this->loginModel->login($email, $password);

            if ($user) {

                echo json_encode([
                    "status" => "success",
                    "message" => "Login successful!",
                    "user" => $user
                ]);

            } else {

                http_response_code(401);

                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid email or password."
                ]);
            }

        } catch (PDOException $e) {

            http_response_code(500);

            error_log($e->getMessage());

            echo json_encode([
                "status" => "error",
                "message" => "Database error."
            ]);
        }
    }
}
