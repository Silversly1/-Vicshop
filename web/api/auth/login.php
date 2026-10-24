<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = sanitize($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = $input['role'] ?? 'user'; // 'user' or 'admin'

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email and password are required']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !verifyPassword($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    exit;
}

// Role-based access control
$isAdmin = !empty($user['is_admin']);

if ($role === 'admin' && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

if ($role === 'user' && $isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Use admin login instead']);
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['user_role'] = $isAdmin ? 'admin' : 'user';

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'is_admin' => $isAdmin,
        'role' => $isAdmin ? 'admin' : 'user'
    ]
]);
