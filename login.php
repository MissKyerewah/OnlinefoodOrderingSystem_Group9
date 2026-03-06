<?php
require_once __DIR__ . '/includes/auth.php';
requireGuest();

$error = $success = '';
if (isset($_GET['registered'])) $success = 'Account created! Please sign in.';
if (isset($_GET['logged_out'])) $success = 'You have been signed out. See you soon!';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = findUserByEmail(trim($_POST['email'] ?? ''));
    if (!$user || !password_verify($_POST['password'] ?? '', $user['password'])) {
        $error = 'Invalid email or password. Please try again.';
    } else {
        $_SESSION['user'] = $user;
        header('Location: dashboard.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - FeastFlow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth">
    <div class="left-panel left-panel-login">
        <a href="index.html" class="panel-logo">Feast<span style="color:#FF8A65">Flow</span></a>
        <div class="panel-title">Welcome Back!</div>
        <p class="panel-desc">Sign in to continue ordering from your favourite restaurants.</p>
        <div class="order-examples">
            <div class="order-example">Margherita Pizza - $14.99</div>
            <div class="order-example">BBQ Chicken Burger - $12.49</div>
            <div class="order-example">Sushi Platter - $22.99</div>
        </div>
    </div>
    <div class="right-panel">
        <a href="index.html" class="back-link">Back to Home</a>
        <div class="form-header">
            <h1>Sign In</h1>
            <p>New to FeastFlow? <a href="register.php">Create an account</a></p>
        </div>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Your password" required>
            </div>
            <div class="form-extras">
                <label class="remember-me"><input type="checkbox" name="remember"> Remember me</label>
                <a href="#" class="forgot-link">Forgot password?</a>
            </div>
            <button type="submit" class="submit-btn">Sign In</button>
        </form>
        <div class="divider">or</div>
        <div class="demo-box">
            <strong>First time here?</strong>
            Register with your email and upload a profile photo to get started instantly.
            <br><br>
            <a href="register.php" style="color:var(--orange);font-weight:600;text-decoration:none;">Create Free Account</a>
        </div>
    </div>
</body>
</html>
