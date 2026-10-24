<?php
require_once __DIR__ . '/config.php';

$user = getCurrentUser();
$isAdmin = $user && !empty($user['is_admin']);
$cartCount = getCartCount();

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    redirect('user-login.php');
}

$cartItems = getCartItems();
$cartTotal = getCartTotal();

if (empty($cartItems)) {
    redirect('cart.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $address = sanitize($_POST['address'] ?? '');

    if (!$name || !$email || !$address) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        $orderModel = new Order();
        $orderId = $orderModel->create(
            ['name' => $name, 'email' => $email, 'address' => $address],
            $cartItems,
            $cartTotal
        );
        if ($orderId) {
            clearCart();
            redirect('order-confirmed.php?order_id=' . $orderId);
        } else {
            $error = 'Failed to create order. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - VICS Store</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="cart.php" class="logo">VICS</a>
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Shop</a>
                <a href="cart.php" class="nav-link cart-link">Cart <span class="cart-count"><?php echo $cartCount; ?></span></a>
                <span class="nav-user">
                    <span class="nav-link btn-small">Hi, <?php echo sanitize($user['username']); ?></span>
                    <a href="user-logout.php" class="nav-link btn-small">Logout</a>
                </span>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="page-header">
            <div>
                <h1 class="section-title">Checkout</h1>
                <p class="page-subtitle">Enter your shipping details to continue</p>
            </div>
            <a href="cart.php" class="btn btn-secondary">&larr; Back to Cart</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="checkout-grid">
            <div class="checkout-form">
                <h2 class="panel-title">Shipping Details</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required value="<?php echo sanitize($user['username']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required value="<?php echo sanitize($user['email']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Shipping Address</label>
                        <textarea id="address" name="address" rows="3" required placeholder="Street, City, State, ZIP"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Proceed to Payment</button>
                </form>
            </div>
            <div class="order-summary">
                <h3>Order Summary</h3>
                <?php foreach ($cartItems as $item): ?>
                    <div class="order-item">
                        <div class="order-item-info">
                            <span class="order-item-name"><?php echo sanitize($item['name']); ?></span>
                            <span class="order-item-qty">x<?php echo intval($item['quantity']); ?></span>
                        </div>
                        <span class="order-item-price"><?php echo formatPrice($item['subtotal']); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="summary-row total">
                    <span>Total</span><span><?php echo formatPrice($cartTotal); ?></span>
                </div>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> VICS Store. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
