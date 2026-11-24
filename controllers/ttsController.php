<?php

/**
 * TtsController
 * Receives requests from TtsActivity (Android), checks the data,
 * calls TtsModel and sends back JSON.
 *
 *   ?action=fetch_tts_phrases  -> toFetchPhrases()   READ
 *   ?action=add_tts_phrase     -> toAddPhrase()      CREATE
 *   ?action=update_tts_phrase  -> toUpdatePhrase()   UPDATE
 *   ?action=delete_tts_phrase  -> toDeletePhrase()   DELETE
 *
 * Response: { "status": "success" | "error", "message": "...", "data": ... }
 */
class TtsController {

    private $model;

    public function __construct(TtsModel $ttsModel) {
        $this->model = $ttsModel;
    }

    // READ   body: { "user_id": 5, "category": "Casual" }
    public function toFetchPhrases(): void {
        if (!$this->requirePostRequest()) return;

        $data = $this->requestData();
        $userId = $this->positiveInt($data['user_id'] ?? null) ?? 0; // 0 = not logged in, suggestions only
        $category = trim((string)($data['category'] ?? ''));

        if (!$this->isValidCategory($category)) {
            $this->respond(400, 'error', 'Please choose a valid category.');
            return;
        }

        try {
            $phrases = $this->model->fetchPhrases($userId, $category);
            $this->respond(200, 'success', 'Phrases fetched successfully.', $phrases);
        } catch (PDOException $e) {
            $this->respondDatabaseError($e);
        }
    }

    // CREATE body: { "user_id": 5, "category": "Casual", "phrase": "Hi!" }
    public function toAddPhrase(): void {
        if (!$this->requirePostRequest()) return;

        $data = $this->requestData();
        $input = $this->validatePhraseInput($data);
        if ($input === null) return;

        try {
            $newPhrase = $this->model->addPhrase($input['user_id'], $input['category'], $input['phrase']);
            $this->respond(201, 'success', 'Phrase saved.', $newPhrase);
        } catch (PDOException $e) {
            $this->respondDatabaseError($e);
        }
    }

    // UPDATE body: { "id": 12, "user_id": 5, "category": "Casual", "phrase": "Hello!" }
    public function toUpdatePhrase(): void {
        if (!$this->requirePostRequest()) return;

        $data = $this->requestData();
        $id = $this->positiveInt($data['id'] ?? null);
        if ($id === null) {
            $this->respond(400, 'error', 'Phrase ID is required.');
            return;
        }

        $input = $this->validatePhraseInput($data);
        if ($input === null) return;

        try {
            $updated = $this->model->updatePhrase($id, $input['user_id'], $input['category'], $input['phrase']);
            if (!$updated) {
                $this->respond(404, 'error', 'Phrase not found or you cannot edit it.');
                return;
            }
            $this->respond(200, 'success', 'Phrase updated.', $this->model->fetchPhraseById($id, $input['user_id']));
        } catch (PDOException $e) {
            $this->respondDatabaseError($e);
        }
    }

    // DELETE body: { "id": 12, "user_id": 5 }
    public function toDeletePhrase(): void {
        if (!$this->requirePostRequest()) return;

        $data = $this->requestData();
        $id = $this->positiveInt($data['id'] ?? null);
        $userId = $this->positiveInt($data['user_id'] ?? null);

        if ($id === null || $userId === null) {
            $this->respond(400, 'error', 'Phrase ID and user ID are required.');
            return;
        }

        try {
            if (!$this->model->deletePhrase($id, $userId)) {
                $this->respond(404, 'error', 'Phrase not found or you cannot delete it.');
                return;
            }
            $this->respond(200, 'success', 'Phrase deleted.', ['id' => $id]);
        } catch (PDOException $e) {
            $this->respondDatabaseError($e);
        }
    }

    // ============================== HELPERS ==============================

    /** Returns ['user_id', 'category', 'phrase'] or null (error already sent). */
    private function validatePhraseInput(array $data): ?array {
        $userId = $this->positiveInt($data['user_id'] ?? null);
        $category = trim((string)($data['category'] ?? ''));
        $phrase = trim(preg_replace('/\s+/u', ' ', (string)($data['phrase'] ?? '')));

        if ($userId === null) {
            $this->respond(401, 'error', 'Please log in to save your own phrases.');
            return null;
        }
        if (!$this->isValidCategory($category)) {
            $this->respond(400, 'error', 'Please choose a valid category.');
            return null;
        }
        if ($phrase === '') {
            $this->respond(400, 'error', 'Phrase cannot be empty.');
            return null;
        }
        if (mb_strlen($phrase) > TtsModel::MAX_PHRASE_LENGTH) {
            $this->respond(400, 'error', 'Phrase must be ' . TtsModel::MAX_PHRASE_LENGTH . ' characters or less.');
            return null;
        }

        return [
            'user_id' => $userId,
            'category' => $category,
            'phrase' => $phrase
        ];
    }

    private function isValidCategory(string $category): bool {
        return in_array($category, TtsModel::CATEGORIES, true);
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

        if (!empty($_POST) && is_array($_POST)) {
            $data = array_merge($data, $_POST);
        }

        return $data;
    }

    private function positiveInt($value): ?int {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        return ($value !== false && $value > 0) ? (int)$value : null;
    }

    private function requirePostRequest(): bool {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=UTF-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond(405, 'error', 'Only POST requests are allowed.');
            return false;
        }
        return true;
    }

    private function respond(int $httpCode, string $status, string $message, $data = null): void {
        http_response_code($httpCode);

        $body = [
            'status' => $status,
            'message' => $message
        ];
        if ($data !== null) {
            $body['data'] = $data;
        }

        echo json_encode($body, JSON_UNESCAPED_UNICODE);
    }

    private function respondDatabaseError(PDOException $e): void {
        error_log($e->getMessage());

        // MySQL 1265 = value not allowed in the ENUM column -> tts_update.sql was not run yet
        $driverCode = $e->errorInfo[1] ?? null;
        if ((int)$driverCode === 1265 || stripos($e->getMessage(), "column 'category'") !== false) {
            $this->respond(500, 'error', "Database not updated: run tts_update.sql in phpMyAdmin first.");
            return;
        }

        // FK error: the user_id does not exist in `users`
        if ((int)$driverCode === 1452) {
            $this->respond(400, 'error', 'User account not found. Please log in again.');
            return;
        }

        $this->respond(500, 'error', 'Database error while handling phrases.');
    }
}
?>
