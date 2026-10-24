<?php
require_once __DIR__ . '/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (!$email || !$password) {
        $error = 'Email and password are required';
    } else {
        $userModel = new User();
        $user = $userModel->login($email, $password);

        if (!$user) {
            $error = 'Invalid email or password';
        } elseif ($role === 'admin' && empty($user['is_admin'])) {
            $error = 'Admin access required';
        } elseif ($role === 'user' && !empty($user['is_admin'])) {
            $error = 'Use admin login instead';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = !empty($user['is_admin']) ? 'admin' : 'user';
            redirect('index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - VICS Store</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>VICS</h1>
                <p>Welcome back, customer</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="role" value="user">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary auth-btn">Sign In</button>
            </form>
            <div class="auth-footer">
                New to VICS? <a href="user-register.php">Create an account</a>
            </div>
            <div class="auth-alt-link">
                <a href="admin-login.php">Admin login →</a>
            </div>
        </div>
    </div>
</body>
</html>
