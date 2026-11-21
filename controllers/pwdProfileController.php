<?php
class PwdProfileController {
    private $model;

    public function __construct($pwdProfileModel) {
        $this->model = $pwdProfileModel;
    }

    private function send(int $code, string $status, string $message, array $extra = []): void {
        http_response_code($code);
        echo json_encode(array_merge(["status" => $status, "message" => $message], $extra));
    }

    private function readJson(): ?array {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->send(405, "error", "Only POST requests are allowed.");
            return null;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (!is_array($data)) {
            $this->send(400, "error", "Invalid JSON data.");
            return null;
        }
        return $data;
    }

    // ---------- GET PROFILE ----------
    public function toFetchPwdProfile() {
        $data = $this->readJson();
        if ($data === null) return;

        $userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT);
        if ($userId === false || $userId === null || $userId < 1) {
            $this->send(400, "error", "A valid user_id is required.");
            return;
        }

        try {
            $profile = $this->model->fetchProfile((string) $userId);

            if ($profile === null) {
                $this->send(404, "error", "Profile not found.");
                return;
            }

            $this->send(200, "success", "Profile fetched successfully.", ["profile" => $profile]);

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->send(500, "error", "Database error.");
        }
    }

    // ---------- UPDATE PROFILE ----------
    public function toUpdatePwdProfile() {
        $data = $this->readJson();
        if ($data === null) return;

        $userId   = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT);
        $name     = trim($data['name'] ?? '');
        $email    = strtolower(trim($data['email'] ?? ''));
        $phone    = trim($data['phone'] ?? '');
        $headline = trim($data['headline'] ?? '');
        $location = trim($data['location'] ?? '');
        $bio      = trim($data['bio'] ?? '');

        if ($userId === false || $userId === null || $userId < 1) {
            $this->send(400, "error", "A valid user_id is required.");
            return;
        }
        if ($name === '') {
            $this->send(400, "error", "Name is required.");
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->send(400, "error", "Invalid email address.");
            return;
        }
        if (mb_strlen($name) > 255 || mb_strlen($headline) > 200 ||
            mb_strlen($location) > 120 || mb_strlen($phone) > 40) {
            $this->send(400, "error", "One or more fields are too long.");
            return;
        }

        try {
            if ($this->model->emailTakenByOther($email, (string) $userId)) {
                $this->send(409, "error", "Email is already used by another account.");
                return;
            }

            $ok = $this->model->updateProfile(
                (string) $userId, $name, $email, $phone, $headline, $location, $bio
            );

            if ($ok) {
                $this->send(200, "success", "Profile updated successfully.");
            } else {
                $this->send(404, "error", "Profile not found.");
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->send(500, "error", "Failed to update profile.");
        }
    }
}
?>