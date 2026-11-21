<?php
class ResumeController {
    private $model;

    public function __construct(ResumeModel $resumeModel) {
        $this->model = $resumeModel;
    }

    private function send(int $code, string $status, string $message, array $extra = []): void {
        http_response_code($code);
        echo json_encode(array_merge([
            'status' => $status,
            'message' => $message
        ], $extra));
    }

    private function readJson(): ?array {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->send(405, 'error', 'Only POST requests are allowed.');
            return null;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->send(400, 'error', 'Invalid JSON data.');
            return null;
        }

        return $data;
    }

    private function userId(array $data): ?int {
        $userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT);
        if ($userId === false || $userId === null || $userId < 1) {
            $this->send(400, 'error', 'A valid user_id is required.');
            return null;
        }

        return $userId;
    }

    private function resumeId(array $data): ?int {
        $resumeId = filter_var($data['resume_id'] ?? null, FILTER_VALIDATE_INT);
        if ($resumeId === false || $resumeId === null || $resumeId < 1) {
            $this->send(400, 'error', 'A valid resume_id is required.');
            return null;
        }

        return $resumeId;
    }

    private function resumeData(array $data): ?array {
        $title = trim($data['title'] ?? '');
        $targetRole = trim($data['target_role'] ?? '');
        $summary = trim($data['summary'] ?? '');
        $skills = $data['skills'] ?? null;
        $experience = $data['experience'] ?? null;
        $education = $data['education'] ?? null;
        $certifications = trim($data['certifications'] ?? '');
        $accommodations = trim($data['accommodations_requested'] ?? '');
        $isPrimary = $data['is_primary'] ?? false;

        if ($title === '' || $targetRole === '' || $summary === '') {
            $this->send(400, 'error', 'Title, target_role, and summary are required.');
            return null;
        }

        if (mb_strlen($title) > 160 || mb_strlen($targetRole) > 160 ||
            mb_strlen($certifications) > 255) {
            $this->send(400, 'error', 'One or more resume fields are too long.');
            return null;
        }

        if (!is_array($skills) || !is_array($experience) || !is_array($education)) {
            $this->send(400, 'error', 'Skills, experience, and education must be JSON arrays or objects.');
            return null;
        }

        if (!is_bool($isPrimary) && $isPrimary !== 0 && $isPrimary !== 1 &&
            $isPrimary !== '0' && $isPrimary !== '1') {
            $this->send(400, 'error', 'is_primary must be a boolean value.');
            return null;
        }

        return [
            'title' => $title,
            'target_role' => $targetRole,
            'summary' => $summary,
            'skills' => $skills,
            'experience' => $experience,
            'education' => $education,
            'certifications' => $certifications,
            'accommodations_requested' => $accommodations,
            'is_primary' => filter_var($isPrimary, FILTER_VALIDATE_BOOLEAN)
        ];
    }

    public function toCreateResume(): void {
        $data = $this->readJson();
        if ($data === null) return;

        $userId = $this->userId($data);
        if ($userId === null) return;
        $resume = $this->resumeData($data);
        if ($resume === null) return;

        try {
            $resumeId = $this->model->createResume($userId, $resume);
            $this->send(201, 'success', 'Resume created successfully.', ['resume_id' => $resumeId]);
        } catch (JsonException $e) {
            $this->send(400, 'error', 'Resume fields contain invalid text data.');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->send(500, 'error', 'Database error while creating resume.');
        }
    }

    public function toFetchResumes(): void {
        $data = $this->readJson();
        if ($data === null) return;

        $userId = $this->userId($data);
        if ($userId === null) return;

        try {
            $resumes = $this->model->fetchResumes($userId);
            $this->send(200, 'success', count($resumes) > 0
                ? 'Resumes fetched successfully.'
                : 'No resumes found.', ['resumes' => $resumes]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->send(500, 'error', 'Database error while fetching resumes.');
        } catch (JsonException $e) {
            error_log($e->getMessage());
            $this->send(500, 'error', 'Stored resume data is invalid.');
        }
    }

    public function toFetchResume(): void {
        $data = $this->readJson();
        if ($data === null) return;

        $userId = $this->userId($data);
        if ($userId === null) return;
        $resumeId = $this->resumeId($data);
        if ($resumeId === null) return;

        try {
            $resume = $this->model->fetchResume($resumeId, $userId);
            if ($resume === null) {
                $this->send(404, 'error', 'Resume not found.');
                return;
            }
            $this->send(200, 'success', 'Resume fetched successfully.', ['resume' => $resume]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->send(500, 'error', 'Database error while fetching resume.');
        } catch (JsonException $e) {
            error_log($e->getMessage());
            $this->send(500, 'error', 'Stored resume data is invalid.');
        }
    }

    public function toUpdateResume(): void {
        $data = $this->readJson();
        if ($data === null) return;

        $userId = $this->userId($data);
        if ($userId === null) return;
        $resumeId = $this->resumeId($data);
        if ($resumeId === null) return;
        $resume = $this->resumeData($data);
        if ($resume === null) return;

        try {
            if (!$this->model->updateResume($resumeId, $userId, $resume)) {
                $this->send(404, 'error', 'Resume not found.');
                return;
            }
            $this->send(200, 'success', 'Resume updated successfully.');
        } catch (JsonException $e) {
            $this->send(400, 'error', 'Resume fields contain invalid text data.');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->send(500, 'error', 'Database error while updating resume.');
        }
    }

    public function toDeleteResume(): void {
        $data = $this->readJson();
        if ($data === null) return;

        $userId = $this->userId($data);
        if ($userId === null) return;
        $resumeId = $this->resumeId($data);
        if ($resumeId === null) return;

        try {
            if (!$this->model->deleteResume($resumeId, $userId)) {
                $this->send(404, 'error', 'Resume not found.');
                return;
            }
            $this->send(200, 'success', 'Resume deleted successfully.');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->send(500, 'error', 'Database error while deleting resume.');
        }
    }
}
?>