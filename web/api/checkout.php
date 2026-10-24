<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$cartItems = getCartItems();
$total = getCartTotal();

if (empty($cartItems)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$name = sanitize($input['name'] ?? '');
$email = sanitize($input['email'] ?? '');
$address = sanitize($input['address'] ?? '');

if (empty($name) || empty($email) || empty($address)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

$user = getCurrentUser();

$order = new Order();
$orderItems = array_map(fn($i) => ['id' => $i['id'], 'quantity' => $i['quantity'], 'price' => $i['price']], $cartItems);
$orderId = $order->create([
    'name' => $name,
    'email' => $email,
    'address' => $address
], $orderItems, $total);

if (!$orderId) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Order creation failed']);
    exit;
}

clearCart();

echo json_encode([
    'success' => true,
    'order_id' => $orderId,
    'total' => $total
]);
