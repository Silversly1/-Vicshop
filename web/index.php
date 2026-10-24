<?php
require_once __DIR__ . '/config.php';

$user = getCurrentUser();
$isAdmin = $user && !empty($user['is_admin']);
$cartCount = getCartCount();

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = 'index.php';
        redirect('user-login.php');
    }
    $productId = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity'] ?? 1);
    addToCart($productId, $quantity);
    redirect('index.php');
}

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_cart'])) {
    $productId = intval($_POST['product_id']);
    removeFromCart($productId);
    redirect('index.php');
}

// Get products and categories
$productModel = new Product();
$products = $productModel->getAll($_GET['category'] ?? null);
$categories = $productModel->getCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - VICS Store</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="index.php" class="logo">VICS</a>
            <nav class="main-nav">
                <a href="index.php" class="nav-link active">Shop</a>
                <a href="cart.php" class="nav-link cart-link">Cart <span class="cart-count"><?php echo $cartCount; ?></span></a>
                <?php if ($isAdmin): ?>
                    <a href="admin.php" class="nav-link">Admin</a>
                <?php endif; ?>
                <?php if ($user): ?>
                    <span class="nav-link btn-small">Hi, <?php echo sanitize($user['username']); ?></span>
                    <a href="user-logout.php" class="nav-link btn-small">Logout</a>
                <?php else: ?>
                    <a href="user-login.php" class="nav-link btn-small">Login</a>
                    <a href="user-register.php" class="nav-link btn-small">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="shop-section">
            <div class="shop-header">
                <h1>Our Products</h1>
                <div class="shop-actions">
                    <form method="GET" class="shop-filter-form">
                        <select name="category" class="select-filter" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : ''; ?>><?php echo sanitize($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <input type="text" id="searchInput" class="search-filter" placeholder="Search products...">
                </div>
            </div>

            <div class="product-grid" id="productGrid">
                <?php if (empty($products)): ?>
                    <div class="empty-state" style="grid-column:1/-1;">No products found.</div>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <div class="product-card">
                            <div class="product-image"><?php if (strpos($p['image'], 'uploads/') === 0): ?><img src="<?php echo sanitize($p['image']); ?>" alt=""><?php else: echo sanitize($p['image']); endif; ?></div>
                            <div class="product-info">
                                <h3><?php echo sanitize($p['name']); ?></h3>
                                <p class="product-desc"><?php echo sanitize($p['description']); ?></p>
                                <span class="price"><?php echo formatPrice($p['price']); ?></span>
                                <span class="stock-badge <?php echo $p['stock'] > 0 ? 'stock-in' : 'stock-out'; ?>">
                                    <?php echo $p['stock'] > 0 ? 'In Stock (' . $p['stock'] . ')' : 'Out of Stock'; ?>
                                </span>
                                <div class="product-actions">
                                    <?php if ($p['stock'] > 0 && $user): ?>
                                        <form method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <button type="submit" class="btn btn-primary add-to-cart">Add to Cart</button>
                                        </form>
<?php elseif (!$user): ?>
                                        <a href="user-login.php" class="btn btn-primary">Login to Buy</a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>Out of Stock</button>
                                    <?php endif; ?>
                                    <a href="product.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> VICS Store. All rights reserved.</p>
        </div>
    </footer>

    <script>
    document.getElementById('searchInput').addEventListener('input', function() {
        const term = this.value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            const name = card.querySelector('h3').textContent.toLowerCase();
            const desc = card.querySelector('.product-desc').textContent.toLowerCase();
            card.style.display = (name.includes(term) || desc.includes(term)) ? '' : 'none';
        });
    });
    </script>
</body>
</html>
