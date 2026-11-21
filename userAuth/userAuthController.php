<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/userAuthModel.php';

function respond(int $status, bool $success, string $message, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, false, 'Method not allowed');
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$email    = strtolower(trim($data['email'] ?? ''));
$password = $data['password'] ?? '';

$name     = trim($data['name'] ?? '');
$headline = trim($data['headline'] ?? 'Job Seeker');
$location = trim($data['location'] ?? 'BCD');
$phone    = trim($data['phone'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, false, 'Invalid email address');
}

if (strlen($password) < 8) {
    respond(400, false, 'Password must be at least 8 characters');
}

if ($name === '') {
    respond(400, false, 'Name is required');
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $model = new UserAuthModel($pdo);

    if ($model->emailExists($email)) {
        respond(409, false, 'Email already registered');
    }

    $pdo->beginTransaction();

    // Create the main user account.
    $userId = $model->createUser($email, $password, 'pwd');

    // Create the PWD-specific profile.
    $model->createPwdProfile(
        $userId,
        $name,
        $headline,
        $location,
        $phone
    );

    $pdo->commit();

    // Return the same essential account details used by login.
    respond(201, true, 'Account created and linked successfully', [
        'user' => [
            'id' => (string) $userId,
            'email' => $email,
            'role' => 'pwd'
        ]
    ]);

} catch (Exception $e) {

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log the actual error instead of exposing database details to the app.
    error_log($e->getMessage());

    respond(500, false, 'An error occurred while creating your account');
}
?>