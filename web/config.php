<?php
// Start session for cart & login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration (SQLite for zero-setup)
define('DB_PATH', __DIR__ . '/database.sqlite');

// Stripe configuration - replace with your own keys
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_publishable_key_here');
define('STRIPE_SECRET_KEY', 'sk_test_your_secret_key_here');

// Site configuration
define('SITE_NAME', 'VICS');

// Autoloader for classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/includes/' . $class . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Database connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            initializeDB($pdo);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

function initializeDB($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            is_admin INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    // Migration for existing databases: add is_admin column if missing
    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('is_admin', $cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin INTEGER DEFAULT 0");
    }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            price REAL NOT NULL,
            image TEXT,
            stock INTEGER DEFAULT 0,
            category TEXT DEFAULT 'General',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            customer_name TEXT NOT NULL,
            customer_email TEXT NOT NULL,
            customer_address TEXT NOT NULL,
            total REAL NOT NULL,
            status TEXT DEFAULT 'pending',
            stripe_payment_id TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL,
            price REAL NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )
    ");
    $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($count == 0) {
        $products = [
            ['Aurora Headphones', 'Immersive wireless headphones with 40hr battery', 149.00, '🎧', 25, 'Audio'],
            ['Nebula Watch', 'Sleek smartwatch with ECG and GPS', 299.00, '⌚', 18, 'Wearables'],
            ['Void Backpack', 'Minimalist anti-theft backpack with USB-C', 89.00, '🎒', 40, 'Bags'],
            ['Pulse Speaker', 'Crimson 360° sound portable speaker', 79.00, '🔊', 50, 'Audio'],
            ['Cyber Keyboard', 'Hot-swappable mechanical keyboard', 199.00, '⌨️', 30, 'Accessories'],
            ['Lens Webcam', '4K HDR webcam with auto-framing', 129.00, '📹', 35, 'Accessories'],
            ['Halo Monitor', '32" 4K curved gaming monitor', 599.00, '🖥️', 12, 'Displays'],
            ['Vortex Chair', 'Ergonomic mesh office chair', 449.00, '🪑', 8, 'Furniture'],
        ];
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image, stock, category) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($products as $p) {
            $stmt->execute($p);
        }

        $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE email = 'admin1@gmail.com'")->fetchColumn();
        if ($adminCount == 0) {
            $adminStmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)");
            $adminStmt->execute(['admin', 'admin1@gmail.com', hashPassword('admin1')]);
        }
    }
}

// Auth helper functions
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
        redirect('login.php');
    }
}

function requireAdmin() {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || empty($user['is_admin'])) {
        redirect('index.php');
    }
}

// Render shared header
function renderHeader($page = '') {
    $user = getCurrentUser();
    $isAdmin = $user && !empty($user['is_admin']);
?>
    <header class="site-header">
        <div class="container header-inner">
            <a href="index.php" class="logo">VICS</a>
            <nav class="main-nav">
                <a href="index.php"<?php echo $page === 'index' ? ' class="active"' : ''; ?>>Home</a>
                <a href="cart.php" class="cart-link"<?php echo $page === 'cart' ? ' class="active"' : ''; ?>>Cart <span class="cart-count"><?php echo getCartCount(); ?></span></a>
                <?php if ($isAdmin): ?>
                    <a href="admin.php"<?php echo $page === 'admin' ? ' class="active"' : ''; ?>>Admin</a>
                <?php endif; ?>
                <?php if ($user): ?>
                    <span class="btn-small">Hi, <?php echo sanitize($user['username']); ?></span>
                    <a href="logout.php" class="btn-small">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn-small">Login</a>
                    <a href="register.php" class="btn-small">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
<?php
}

// Render shared footer
function renderFooter() {
?>
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> VICS. All rights reserved.</p>
        </div>
    </footer>
<?php
}

// Helper functions
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function formatPrice($amount) {
    return '$' . number_format($amount, 2);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function addToCart($productId, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

function removeFromCart($productId) {
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
    }
}

function getCartItems() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [];
    }
    $db = getDB();
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $items = [];
    while ($row = $stmt->fetch()) {
        $row['quantity'] = $_SESSION['cart'][$row['id']];
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $items[] = $row;
    }
    return $items;
}

function getCartCount() {
    if (!isset($_SESSION['cart'])) {
        return 0;
    }
    return array_sum($_SESSION['cart']);
}

function getCartTotal() {
    $items = getCartItems();
    $total = 0;
    foreach ($items as $item) {
        $total += $item['subtotal'];
    }
    return $total;
}

function clearCart() {
    $_SESSION['cart'] = [];
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    if (!isLoggedIn()) return false;
    $user = getCurrentUser();
    return $user && intval($user['is_admin'] ?? 0) === 1;
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
