<?php
require_once __DIR__ . '/config.php';

$user = getCurrentUser();
$isAdmin = $user && !empty($user['is_admin']);
$cartCount = getCartCount();

if (!isLoggedIn()) {
    redirect('user-login.php');
}

$orderId = intval($_GET['order_id'] ?? 0);
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $cardName = sanitize($_POST['card_name'] ?? '');
    $expiryMonth = intval($_POST['expiry_month'] ?? 0);
    $expiryYear = intval($_POST['expiry_year'] ?? 0);
    $cvv = sanitize($_POST['cvv'] ?? '');

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

    if (!$orderId || !$cardNumber || !$cardName || !$expiryMonth || !$expiryYear || !$cvv) {
        $error = 'All payment fields are required';
    } elseif (strlen($cardNumber) !== 16 || !ctype_digit($cardNumber)) {
        $error = 'Card number must be 16 digits';
    } elseif (strlen($cvv) < 3 || !ctype_digit($cvv)) {
        $error = 'CVV must be at least 3 digits';
    } else {
        $orderModel = new Order();
        $orderData = $orderModel->getById($orderId);
        if (!$orderData) {
            $error = 'Order not found';
        } else {
            $result = stripeCharge($orderData['total'], $cardNumber, $expiryMonth, $expiryYear, $cvv, $cardName);
            if ($result['ok']) {
                $orderModel->updateStripePaymentId($orderId, $result['id']);
                $orderModel->updateStatus($orderId, 'completed');
                clearCart();
                $success = true;
            } else {
                $error = $result['error'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Payment - VICS Store</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="cart.php" class="logo">VICS</a>
            <nav class="main-nav">
                <a href="cart.php" class="nav-link">Back to Cart</a>
                <span class="nav-link btn-small">Hi, <?php echo sanitize($user['username']); ?></span>
                <a href="user-logout.php" class="nav-link btn-small">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="page-header">
            <div>
                <h1 class="section-title">Card Payment</h1>
                <p class="page-subtitle">Secure checkout &mdash; your details are never stored</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">Payment successful! Redirecting to confirmation...</div>
            <?php header("refresh:2;url=order-confirmed.php?order_id=$orderId"); ?>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" class="payment-form payment-container">
                <div class="form-group">
                    <label for="orderId">Order ID</label>
                    <input type="text" id="orderId" name="order_id" value="<?php echo $orderId; ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="cardName">Name on Card</label>
                    <input type="text" id="cardName" name="card_name" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label for="cardNumber">Card Number <span id="brandLabel"></span></label>
                    <input type="text" id="cardNumber" name="card_number" placeholder="1234 5678 9012 3456" required maxlength="19">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiryMonth">Expiry Month (MM)</label>
                        <input type="number" id="expiryMonth" name="expiry_month" placeholder="MM" min="1" max="12" required>
                    </div>
                    <div class="form-group">
                        <label for="expiryYear">Expiry Year (YYYY)</label>
                        <input type="number" id="expiryYear" name="expiry_year" placeholder="YYYY" min="2020" max="2099" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="cvv">CVV</label>
                    <input type="text" id="cvv" name="cvv" placeholder="123" required maxlength="4">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Pay & Complete Order</button>
            </form>
            <p class="demo-note">Demo mode: any valid-format card works. Use card number 4242424242424242 for test success.</p>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> VICS Store. All rights reserved.</p>
        </div>
    </footer>

<script>
document.getElementById('cardNumber').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '');
    if (v.length > 16) v = v.slice(0, 16);
    this.value = v.match(/.{1,4}/g)?.join(' ') || v;
    validate();
});
document.getElementById('cvv').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0,4);
    validate();
});
document.getElementById('expiryMonth').addEventListener('input', validate);
document.getElementById('expiryYear').addEventListener('input', validate);
document.getElementById('cardName').addEventListener('input', validate);

function detectBrand(n) {
    n = n.replace(/\D/g,'');
    if (/^4/.test(n)) return 'Visa';
    if (/^(5[1-5]|2[2-7])/.test(n)) return 'Mastercard';
    if (/^3[47]/.test(n)) return 'Amex';
    if (/^6(011|5|4[4-9])/.test(n)) return 'Discover';
    return '';
}
function luhn(n) {
    n = n.replace(/\D/g,'');
    let sum = 0, alt = false;
    for (let i = n.length-1; i >= 0; i--) { let d = +n[i]; if (alt) { d *= 2; if (d > 9) d -= 9; } sum += d; alt = !alt; }
    return n.length >= 12 && sum % 10 === 0;
}
function validate() {
    const num = document.getElementById('cardNumber').value;
    const brand = detectBrand(num);
    const brandEl = document.getElementById('brandLabel');
    if (brandEl) brandEl.textContent = brand;
    const cvv = document.getElementById('cvv').value;
    const expM = +document.getElementById('expiryMonth').value;
    const expY = +document.getElementById('expiryYear').value;
    let ok = luhn(num);
    if (brand === 'Amex' && cvv.length !== 4) ok = false;
    else if (cvv.length < 3) ok = false;
    if (!expM || expM < 1 || expM > 12) ok = false;
    if (!expY || expY < 2024) ok = false;
    const btn = document.querySelector('button[type=submit]');
    if (btn) btn.disabled = !ok;
}
validate();
</script>
</body>
</html>
