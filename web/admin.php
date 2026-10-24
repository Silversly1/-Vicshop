<?php
require_once __DIR__ . '/config.php';

requireAdmin();

$user = getCurrentUser();
$cartCount = getCartCount();

$productModel = new Product();
$orderModel = new Order();

$totalOrders = $orderModel->getAll();
$totalRevenue = array_reduce($totalOrders, fn($sum, $o) => $sum + floatval($o['total']), 0);
$pendingOrders = array_reduce($totalOrders, fn($sum, $o) => $sum + ($o['status'] === 'pending' ? 1 : 0), 0);
$totalProducts = count($productModel->getAll());
$recentOrders = array_slice($totalOrders, 0, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - VICS Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <header class="site-header admin-header">
        <div class="container header-inner">
            <a href="admin.php" class="logo">VICS Admin</a>
            <nav class="main-nav">
                <a href="admin.php" class="nav-link active">Dashboard</a>
                <a href="admin-products.php" class="nav-link">Products</a>
                <a href="admin-orders.php" class="nav-link">Orders</a>
                <span class="nav-link btn-small">Hi, <?php echo sanitize($user['username']); ?></span>
                <a href="admin-logout.php" class="nav-link btn-small">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="admin-page-header">
            <div>
                <h1 class="section-title">Admin Dashboard</h1>
                <p class="page-subtitle">Overview of your store activity</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-value"><?php echo count($totalOrders); ?></div><div class="stat-label">Total Orders</div></div>
            <div class="stat-card"><div class="stat-value"><?php echo formatPrice($totalRevenue); ?></div><div class="stat-label">Total Revenue</div></div>
            <div class="stat-card"><div class="stat-value"><?php echo $pendingOrders; ?></div><div class="stat-label">Pending Orders</div></div>
            <div class="stat-card"><div class="stat-value"><?php echo $totalProducts; ?></div><div class="stat-label">Total Products</div></div>
        </div>

        <div class="admin-section">
            <h2>Recent Orders</h2>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td>#<?php echo $o['id']; ?></td>
                                <td><?php echo sanitize($o['customer_name']); ?></td>
                                <td><?php echo formatPrice($o['total']); ?></td>
                                <td><span class="badge badge-<?php echo $o['status']; ?>"><?php echo ucfirst($o['status']); ?></span></td>
                                <td><?php echo sanitize($o['created_at']); ?></td>
                                <td>
                                    <form method="POST" action="admin-orders.php" style="display:inline;margin:0;">
                                        <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $o['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $o['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="completed" <?php echo $o['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $o['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-section">
            <h2>Quick Actions</h2>
            <div class="quick-actions">
                <a href="admin-products.php" class="btn btn-primary">Manage Products</a>
                <a href="admin-orders.php" class="btn btn-primary">Manage Orders</a>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> VICS Store Admin. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
