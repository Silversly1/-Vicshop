<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

requireLogin();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $items = getCartItems();
    $total = getCartTotal();
    echo json_encode(['success' => true, 'items' => $items, 'count' => getCartCount(), 'total' => $total]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = intval($input['product_id'] ?? 0);
    $quantity = intval($input['quantity'] ?? 1);
    
    if ($productId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
        exit;
    }
    
    addToCart($productId, $quantity);
    echo json_encode(['success' => true, 'count' => getCartCount()]);
    exit;
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = intval($input['product_id'] ?? 0);
    removeFromCart($productId);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
