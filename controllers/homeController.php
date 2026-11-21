<?php

class HomeController {

    private $model;

    public function __construct(HomeModel $homeModel) {
        $this->model = $homeModel;
    }

    public function toFetchFourJobs(): void {
        $this->requirePostRequest();

        try {
            $jobs = $this->model->fetchFourJobs();

            echo json_encode([
                'status' => 'success',
                'message' => count($jobs) > 0
                    ? 'Home jobs fetched successfully.'
                    : 'No jobs found.',
                'jobs' => $jobs
            ]);
        } catch (PDOException $e) {
            $this->respondDatabaseError($e);
        }
    }

    public function toFetchAllJobs(): void {
        $this->requirePostRequest();

        try {
            $jobs = $this->model->fetchAllJobs();

            echo json_encode([
                'status' => 'success',
                'message' => count($jobs) > 0
                    ? 'Jobs fetched successfully.'
                    : 'No jobs found.',
                'jobs' => $jobs
            ]);
        } catch (PDOException $e) {
            $this->respondDatabaseError($e);
        }
    }

    public function toSearchJobs(): void {
        $this->requirePostRequest();

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid JSON data.'
            ]);
            return;
        }

        $searchTerm = $data['search'] ?? $data['query'] ?? $data['keyword'] ?? '';
        if (!is_string($searchTerm)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'The search term must be a string.'
            ]);
            return;
        }
        $searchTerm = trim($searchTerm);

        try {
            $jobs = $this->model->searchJobs($searchTerm);

            echo json_encode([
                'status' => 'success',
                'message' => count($jobs) > 0
                    ? 'Jobs found successfully.'
                    : 'No matching jobs found.',
                'jobs' => $jobs
            ]);
        } catch (PDOException $e) {
            $this->respondDatabaseError($e);
        }
    }

    private function requirePostRequest(): void {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Methods: POST');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'status' => 'error',
                'message' => 'Only POST requests are allowed.'
            ]);
            exit();
        }
    }

    private function respondDatabaseError(PDOException $e): void {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error while fetching jobs.'
        ]);
    }
}
?>