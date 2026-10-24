<?php
require_once __DIR__ . '/config.php';

$user = getCurrentUser();
$isAdmin = $user && !empty($user['is_admin']);
$cartCount = getCartCount();

$productId = intval($_GET['id'] ?? 0);
$productModel = new Product();
$product = $productModel->getById($productId);

if (!$product) {
    http_response_code(404);
    echo "<h1>Product not found</h1><a href='index.php'>Back to shop</a>";
    exit;
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = 'product.php?id=' . $productId;
        redirect('user-login.php');
    }
    $quantity = intval($_POST['quantity'] ?? 1);
    if ($quantity > intval($product['stock'])) {
        $quantity = intval($product['stock']);
    }
    addToCart($productId, $quantity);
    redirect('cart.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($product['name']); ?> - VICS Store</title>
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
        <a href="index.php" class="back-link">&#8592; Back to Shop</a>

        <div class="product-detail">
            <div class="product-gallery">
                <div class="main-image" id="mainImage"><?php if (strpos($product['image'], 'uploads/') === 0): ?><img src="<?php echo sanitize($product['image']); ?>" alt=""><?php else: echo sanitize($product['image']); endif; ?></div>
                <div class="product-meta" id="productMeta">
                    <span class="meta-item">Category: <strong><?php echo sanitize($product['category']); ?></strong></span>
                    <span class="meta-item">Stock: <strong><?php echo $product['stock']; ?></strong> available</span>
                </div>
            </div>
            <div class="product-detail-info">
                <h1><?php echo sanitize($product['name']); ?></h1>
                <div class="detail-price"><?php echo formatPrice($product['price']); ?></div>
                <p class="detail-desc"><?php echo sanitize($product['description']); ?></p>
                <form method="POST">
                    <input type="hidden" name="add_to_cart" value="1">
                    <div class="quantity-selector">
                        <label for="quantity">Quantity:</label>
                        <div class="qty-input">
                            <button type="button" class="qty-btn minus">-</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo intval($product['stock']); ?>">
                            <button type="button" class="qty-btn plus">+</button>
                        </div>
                    </div>
                    <div class="detail-actions">
                        <?php if ($user && intval($product['stock']) > 0): ?>
                            <button type="submit" class="btn btn-primary">Add to Cart</button>
                        <?php elseif (!$user): ?>
                            <button class="btn btn-primary" disabled>Login to Buy</button>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>Out of Stock</button>
                        <?php endif; ?>
                        <a href="cart.php" class="btn btn-secondary">View Cart</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> VICS Store. All rights reserved.</p>
        </div>
    </footer>

    <script>
    document.querySelector('.minus').onclick = function() {
        const i = document.getElementById('quantity');
        if (i.value > 1) i.value--;
    };
    document.querySelector('.plus').onclick = function() {
        const i = document.getElementById('quantity');
        const max = parseInt(i.max);
        if (i.value < max) i.value++;
    };
    </script>
</body>
</html>
