<?php
require_once __DIR__ . '/config.php';

$user = getCurrentUser();
$isAdmin = $user && !empty($user['is_admin']);
$cartCount = getCartCount();

if (!isLoggedIn()) {
    redirect('user-login.php');
}

$orderId = intval($_GET['order_id'] ?? 0);
$orderModel = new Order();
$order = $orderModel->getById($orderId);

if (!$order || ($user && $order['user_id'] !== $user['id'] && !$isAdmin)) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - VICS Store</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="index.php" class="logo">VICS</a>
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Shop</a>
                <a href="cart.php" class="nav-link cart-link">Cart <span class="cart-count"><?php echo $cartCount; ?></span></a>
                <?php if ($isAdmin): ?>
                    <a href="admin.php" class="nav-link">Admin</a>
                <?php endif; ?>
                <span class="nav-link btn-small">Hi, <?php echo sanitize($user['username']); ?></span>
                <a href="user-logout.php" class="nav-link btn-small">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="confirmation">
            <div class="checkmark">&#10003;</div>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your purchase. Your order #<?php echo $orderId; ?> has been placed successfully.</p>
            <a href="index.php" class="btn btn-primary btn-lg">Continue Shopping</a>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> VICS Store. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
