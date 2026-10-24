<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cardNumber = preg_replace('/[\s-]/', '', $input['card_number'] ?? '');
$cardName = sanitize($input['card_name'] ?? '');
$expiry = str_replace(' ', '', $input['expiry'] ?? '');
$cvv = preg_replace('/\D/', '', $input['cvv'] ?? '');
$orderId = intval($input['order_id'] ?? 0);

$errors = [];

function detectCardBrand(string $number): string {
    $number = preg_replace('/\D/', '', $number);
    if (preg_match('/^4/', $number)) return 'Visa';
    if (preg_match('/^(5[1-5]|2[2-7])/', $number)) return 'Mastercard';
    if (preg_match('/^3[47]/', $number)) return 'American Express';
    if (preg_match('/^6(011|5|4[4-9])/', $number)) return 'Discover';
    return 'Card';
}

function luhnCheck(string $number): bool {
    $number = preg_replace('/\D/', '', $number);
    $sum = 0;
    $alt = false;
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $d = intval($number[$i]);
        if ($alt) { $d *= 2; if ($d > 9) $d -= 9; }
        $sum += $d;
        $alt = !$alt;
    }
    return strlen($number) >= 12 && $sum % 10 === 0;
}

$brand = detectCardBrand($cardNumber);
$expectedCvvLen = ($brand === 'American Express') ? 4 : 3;

if (!luhnCheck($cardNumber)) $errors['card_number'] = 'Invalid card number';
if (strlen($cardName) < 2) $errors['card_name'] = 'Name on card required';
if (!preg_match('/^(0[1-9]|1[0-2])\s*\/\s*(\d{2})$/', $expiry, $m)) {
    $errors['expiry'] = 'Expiry must be MM/YY';
} else {
    $month = intval($m[1]);
    $year = 2000 + intval($m[2]);
    $lastDay = mktime(0, 0, 0, $month + 1, 0, $year);
    if ($lastDay < time()) $errors['expiry'] = 'Card expired';
}
if (!preg_match('/^\d{' . $expectedCvvLen . '}$/', $cvv)) {
    $errors['cvv'] = $expectedCvvLen === 4 ? 'Amex CVV must be 4 digits' : 'CVV must be 3 digits';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

function stripeCharge($amount, $cardNumber, $expMonth, $expYear, $cvv, $cardName) {
    $secret = STRIPE_SECRET_KEY;
    $fields = http_build_query([
        'amount' => intval(round($amount * 100)),
        'currency' => 'usd',
        'source[name]' => $cardName,
        'source[number]' => $cardNumber,
        'source[exp_month]' => $expMonth,
        'source[exp_year]' => $expYear,
        'source[cvc]' => $cvv,
        'description' => 'VICS Store order',
    ]);
    $ch = curl_init('https://api.stripe.com/v1/charges');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_USERPWD, $secret . ':');
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($resp, true);
    if ($code >= 200 && $code < 300 && !empty($data['id'])) {
        return ['ok' => true, 'id' => $data['id']];
    }
    return ['ok' => false, 'error' => $data['error']['message'] ?? 'Payment failed'];
}

// parse expiry month/year for charge
preg_match('/^(0[1-9]|1[0-2])\s*\/\s*(\d{2})$/', $expiry, $em);
$expMonth = intval($em[1]);
$expYear = 2000 + intval($em[2]);

$order = new Order();
$user = getCurrentUser();
$orderData = $order->getById($orderId);

if (!$orderData) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

$result = stripeCharge($orderData['total'], $cardNumber, $expMonth, $expYear, $cvv, $cardName);

if ($result['ok']) {
    $order->updateStripePaymentId($orderId, $result['id']);
    $order->updateStatus($orderId, 'completed');
    clearCart();
    echo json_encode(['success' => true, 'order_id' => $orderId, 'message' => 'Payment processed']);
} else {
    http_response_code(402);
    echo json_encode(['success' => false, 'error' => $result['error']]);
}
