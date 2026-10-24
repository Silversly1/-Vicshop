<?php
require_once __DIR__ . '/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required';
    } else {
        $userModel = new User();
        $user = $userModel->login($email, $password);

        if (!$user) {
            $error = 'Invalid email or password';
        } elseif (empty($user['is_admin'])) {
            $error = 'Admin access required. Regular customers cannot access the admin panel.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = 'admin';
            header("Location: admin.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - VICS Store</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>VICS</h1>
                <p>Admin Access Only</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Admin Email</label>
                    <input type="email" id="email" name="email" placeholder="admin@VICS.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Admin Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary auth-btn">Admin Sign In</button>
            </form>
            <div class="auth-footer">
                Not an admin? <a href="user-login.php">Customer login</a>
            </div>
        </div>
    </div>
</body>
</html>
