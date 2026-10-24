<?php
require_once __DIR__ . '/config.php';

requireAdmin();

$user = getCurrentUser();
$cartCount = getCartCount();

$orderModel = new Order();
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderModel->updateStatus(intval($_POST['order_id']), sanitize($_POST['status']));
    header("Location: admin-orders.php");
    exit;
}

$orders = $orderModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - VICS Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <header class="site-header admin-header">
        <div class="container header-inner">
            <a href="admin.php" class="logo">VICS Admin</a>
            <nav class="main-nav">
                <a href="admin.php" class="nav-link">Dashboard</a>
                <a href="admin-products.php" class="nav-link">Products</a>
                <a href="admin-orders.php" class="nav-link active">Orders</a>
                <span class="nav-link btn-small">Hi, <?php echo sanitize($user['username']); ?></span>
                <a href="admin-logout.php" class="nav-link btn-small">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="admin-page-header">
            <div>
                <h1 class="section-title">Manage Orders</h1>
                <p class="page-subtitle">Review and update order statuses</p>
            </div>
            <a href="admin.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="admin-section">
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Order #</th><th>Customer</th><th>Email</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7" class="empty-state">No orders found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td>#<?php echo $o['id']; ?></td>
                                    <td><?php echo sanitize($o['customer_name']); ?></td>
                                    <td><?php echo sanitize($o['customer_email']); ?></td>
                                    <td><?php echo formatPrice($o['total']); ?></td>
                                    <td><span class="badge badge-<?php echo $o['status']; ?>"><?php echo ucfirst($o['status']); ?></span></td>
                                    <td><?php echo sanitize($o['created_at']); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;margin:0;">
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
                        <?php endif; ?>
                    </tbody>
                </table>
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
