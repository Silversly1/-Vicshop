<?php
require_once __DIR__ . '/config.php';

requireAdmin();

$user = getCurrentUser();
$cartCount = getCartCount();

$productModel = new Product();
$error = '';
$success = '';
$editId = null;
$editData = null;

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $productModel->delete(intval($_POST['product_id']));
    header("Location: admin-products.php");
    exit;
}

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $image = sanitize($_POST['image'] ?? '📦');
    if (!empty($_FILES['photo']['name'])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fname = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fname)) {
                $image = 'uploads/' . $fname;
            }
        }
    }
    $data = [
        'name' => sanitize($_POST['name'] ?? ''),
        'category' => sanitize($_POST['category'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'price' => floatval($_POST['price'] ?? 0),
        'stock' => intval($_POST['stock'] ?? 0),
        'image' => $image
    ];
    if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
        $productModel->update(intval($_POST['product_id']), $data);
        $success = 'Product updated successfully!';
    } else {
        $productModel->create($data);
        $success = 'Product added successfully!';
    }
    header("Location: admin-products.php");
    exit;
}

// Handle edit
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editData = $productModel->getById($editId);
}

$products = $productModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - VICS Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <header class="site-header admin-header">
        <div class="container header-inner">
            <a href="admin.php" class="logo">VICS Admin</a>
            <nav class="main-nav">
                <a href="admin.php" class="nav-link">Dashboard</a>
                <a href="admin-products.php" class="nav-link active">Products</a>
                <a href="admin-orders.php" class="nav-link">Orders</a>
                <span class="nav-link btn-small">Hi, <?php echo sanitize($user['username']); ?></span>
                <a href="admin-logout.php" class="nav-link btn-small">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="admin-page-header">
            <div>
                <h1 class="section-title">Manage Products</h1>
                <p class="page-subtitle">Add, edit or remove catalog items</p>
            </div>
            <a href="admin.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="admin-section">
            <h2><?php echo $editId ? 'Edit Product' : 'Add New Product'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo $editId; ?>">
                <input type="hidden" name="save_product" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label for="prodName">Product Name</label>
                        <input type="text" id="prodName" name="name" value="<?php echo $editData ? sanitize($editData['name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="prodCategory">Category</label>
                        <input type="text" id="prodCategory" name="category" value="<?php echo $editData ? sanitize($editData['category']) : ''; ?>" required placeholder="e.g. Electronics">
                    </div>
                </div>
                <div class="form-group">
                    <label for="prodDescription">Description</label>
                    <textarea id="prodDescription" name="description" rows="3" required><?php echo $editData ? sanitize($editData['description']) : ''; ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="prodPrice">Price ($)</label>
                        <input type="number" id="prodPrice" name="price" step="0.01" min="0" value="<?php echo $editData ? $editData['price'] : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="prodStock">Stock Quantity</label>
                        <input type="number" id="prodStock" name="stock" min="0" value="<?php echo $editData ? $editData['stock'] : 0; ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="prodImage">Image Emoji / Icon</label>
                    <input type="text" id="prodImage" name="image" value="<?php echo $editData ? sanitize($editData['image']) : '📦'; ?>" placeholder="📦">
                </div>
<div class="form-group">
                    <label for="prodPhoto">Product Photo (upload)</label>
                    <input type="file" id="prodPhoto" name="photo" accept="image/*">
                    <?php if ($editData && !empty($editData['image']) && strpos($editData['image'], 'uploads/') === 0): ?>
                        <div style="margin-top:8px;"><img src="<?php echo sanitize($editData['image']); ?>" alt="" style="max-width:120px;max-height:120px;"></div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $editId ? 'Update Product' : 'Add Product'; ?></button>
                <?php if ($editId): ?>
                    <a href="admin-products.php" class="btn btn-secondary" style="margin-left:0.5rem;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-section">
            <h2>Existing Products</h2>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>#<?php echo $p['id']; ?></td>
                                <td><?php echo sanitize($p['name']); ?></td>
                                <td><?php echo sanitize($p['category']); ?></td>
                                <td><?php echo formatPrice($p['price']); ?></td>
                                <td><?php echo $p['stock']; ?></td>
                                <td>
                                    <a href="admin-products.php?edit=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="POST" style="display:inline;margin:0;">
                                        <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="delete_product" value="1">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
