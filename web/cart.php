<?php
require_once __DIR__ . '/config.php';

$user = getCurrentUser();
$isAdmin = $user && !empty($user['is_admin']);
$cartCount = getCartCount();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $pid => $qty) {
            $_SESSION['cart'][intval($pid)] = intval($qty);
        }
        redirect('cart.php');
    }
    if (isset($_POST['remove_from_cart'])) {
        removeFromCart(intval($_POST['product_id']));
        redirect('cart.php');
    }
    if (isset($_POST['clear_cart'])) {
        clearCart();
        redirect('cart.php');
    }
}

$cartItems = getCartItems();
$cartTotal = getCartTotal();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - VICS Store</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="index.php" class="logo">VICS</a>
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Shop</a>
                <a href="cart.php" class="nav-link cart-link active">Cart <span class="cart-count"><?php echo $cartCount; ?></span></a>
                <?php if ($isAdmin): ?>
                    <a href="admin.php" class="nav-link">Admin</a>
                <?php endif; ?>
                <?php if ($user): ?>
                    <span class="nav-link btn-small">Hi, <?php echo sanitize($user['username']); ?></span>
                    <a href="user-logout.php" class="nav-link btn-small">Logout</a>
                <?php else: ?>
                    <a href="user-login.php" class="nav-link btn-small">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1 class="section-title">Your Shopping Cart</h1>

        <?php if (empty($cartItems)): ?>
            <div class="empty-state">
                <div class="icon">🛒</div>
                <h2>Your cart is empty</h2>
                <p>Add some products to get started!</p>
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div>
                    <form method="POST" class="cart-items-card">
                        <div class="cart-table-wrap">
                        <table class="cart-table">
                            <thead>
                                <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="cart-item">
                                                <div class="cart-item-img"><?php if (strpos($item['image'], 'uploads/') === 0): ?><img src="<?php echo sanitize($item['image']); ?>" alt=""><?php else: echo sanitize($item['image']); endif; ?></div>
                                                <div class="cart-item-info">
                                                    <h4><?php echo sanitize($item['name']); ?></h4>
                                                    <span class="price"><?php echo formatPrice($item['price']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo formatPrice($item['price']); ?></td>
                                        <td>
                                            <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo intval($item['quantity']); ?>" min="1" max="99" class="qty-input-sm">
                                        </td>
                                        <td><?php echo formatPrice($item['subtotal']); ?></td>
                                        <td>
                                            <form method="POST" style="margin:0;">
                                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="remove_from_cart" value="1">
                                                <button type="submit" class="remove-btn">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <p class="cart-scroll-hint">Swipe the table sideways to see all columns.</p>
                        <div class="cart-table-foot">
                            <button type="submit" name="update_cart" class="btn btn-primary">Update Cart</button>
                        </div>
                    </form>
                </div>
                <div class="cart-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-row"><span>Subtotal</span><span><?php echo formatPrice($cartTotal); ?></span></div>
                    <div class="summary-row"><span>Shipping</span><span>Free</span></div>
                    <div class="summary-row total"><span>Total</span><span><?php echo formatPrice($cartTotal); ?></span></div>
                    <div class="cart-actions">
                        <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
                        <a href="payment.php" class="btn btn-primary">Proceed to Payment</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> VICS Store. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
