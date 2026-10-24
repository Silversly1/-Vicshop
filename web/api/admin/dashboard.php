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

$db = getDB();

$productCount = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$orderCount = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalRevenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'completed'")->fetchColumn();

echo json_encode([
    'success' => true,
    'stats' => [
        'products' => $productCount,
        'orders' => $orderCount,
        'users' => $userCount,
        'revenue' => $totalRevenue
    ]
]);
