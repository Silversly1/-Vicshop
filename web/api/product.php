<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

if ($method === 'GET' && $id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        exit;
    }
    echo json_encode(['success' => true, 'product' => $product]);
    exit;
}

if ($method === 'PUT' && $id) {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || empty($user['is_admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $db = getDB();
    $stmt = $db->prepare("UPDATE products SET name=?, description=?, price=?, image=?, stock=?, category=? WHERE id=?");
    $stmt->execute([
        sanitize($input['name'] ?? ''),
        sanitize($input['description'] ?? ''),
        floatval($input['price'] ?? 0),
        sanitize($input['image'] ?? '📦'),
        intval($input['stock'] ?? 0),
        sanitize($input['category'] ?? 'General'),
        $id
    ]);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'DELETE' && $id) {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || empty($user['is_admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required']);
        exit;
    }
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
