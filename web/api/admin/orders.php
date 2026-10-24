<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

requireLogin();
$user = getCurrentUser();
if (!$user || empty($user['is_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$order = new Order();

if ($method === 'GET') {
    $orders = $order->getAll();
    echo json_encode(['success' => true, 'orders' => $orders]);
    exit;
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = intval($input['order_id'] ?? 0);
    $status = sanitize($input['status'] ?? 'pending');
    
    if (!in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        exit;
    }
    
    $order->updateStatus($orderId, $status);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
