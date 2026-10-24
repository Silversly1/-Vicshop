<?php
require_once 'config.php';
$user = new User();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $foundUser = $user->login($email, $password);
        if ($foundUser) {
            $_SESSION['user_id'] = $foundUser['id'];
            $_SESSION['username'] = $foundUser['username'];
            if (!empty($foundUser['is_admin'])) {
                redirect('admin.php');
            }
            redirect($_SESSION['redirect_after_login'] ?? 'index.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VICS</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>VICS</h1>
                <p>Welcome back</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Account created! Please login.</div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary auth-btn">Login</button>
            </form>
            <div class="auth-footer">
                Don't have an account? <a href="register.php">Sign up here</a>
            </div>
        </div>
    </div>
</body>
</html>
