<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $db = getDB();
    $category = $_GET['category'] ?? null;
    if ($category && $category !== 'All') {
        $stmt = $db->prepare("SELECT * FROM products WHERE category = ? ORDER BY created_at DESC");
        $stmt->execute([$category]);
    } else {
        $stmt = $db->query("SELECT * FROM products ORDER BY created_at DESC");
    }
    $products = $stmt->fetchAll();
    echo json_encode(['success' => true, 'products' => $products]);
    exit;
}

if ($method === 'POST') {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || empty($user['is_admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO products (name, description, price, image, stock, category) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        sanitize($input['name'] ?? ''),
        sanitize($input['description'] ?? ''),
        floatval($input['price'] ?? 0),
        sanitize($input['image'] ?? '📦'),
        intval($input['stock'] ?? 0),
        sanitize($input['category'] ?? 'General')
    ]);
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
