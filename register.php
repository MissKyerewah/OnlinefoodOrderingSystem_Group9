<?php
require_once __DIR__ . '/includes/auth.php';
requireGuest();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$name) $errors[] = 'Full name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($pass) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($pass !== $confirm) $errors[] = 'Passwords do not match.';
    if (findUserByEmail($email)) $errors[] = 'An account with this email already exists.';

    $profilePic = 'default.png';
    if (!empty($_FILES['profile_pic']['name'])) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($_FILES['profile_pic']['type'], $allowed)) $errors[] = 'Profile picture must be JPG, PNG, GIF, or WEBP.';
        elseif ($_FILES['profile_pic']['size'] > 3 * 1024 * 1024) $errors[] = 'Profile picture must be under 3MB.';
        else {
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('prof_', true) . '.' . $ext;
            $uploadDir = __DIR__ . '/uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadDir . $filename)) {
                $profilePic = $filename;
            } else {
                $errors[] = 'Failed to upload profile picture.';
            }
        }
    }

    if (empty($errors)) {
        $user = registerUser($name, $email, $pass, $phone, $profilePic);
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
    <title>Create Account - FeastFlow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth">
    <div class="left-panel left-panel-register">
        <a href="index.html" class="panel-logo">FeastFlow</a>
        <div class="panel-title">Join the Feast!</div>
        <p class="panel-desc">Create your free account and start ordering delicious food delivered right to your door.</p>
        <div class="panel-perks">
            <div class="perk">Free account forever</div>
            <div class="perk">200+ menu items to choose from</div>
            <div class="perk">Track your orders live</div>
            <div class="perk">Fast 20-30 minute delivery</div>
        </div>
    </div>
    <div class="right-panel">
        <a href="index.html" class="back-link">Back to Home</a>
        <div class="form-header">
            <h1>Create Account</h1>
            <p>Already have an account? <a href="login.php">Sign in</a></p>
        </div>
        <?php if (!empty($errors)): ?>
        <div class="errors"><?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" id="registerForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+1 555 000 0000">
                </div>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" placeholder="Min. 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" placeholder="Re-enter password" required>
                </div>
            </div>
            <div class="form-group">
                <label>Profile Picture</label>
                <div class="upload-area" id="uploadArea" onclick="document.getElementById('profileInput').click()">
                    <img id="previewImg" class="upload-preview" alt="Preview">
                    <div id="uploadPrompt">
                        <div class="upload-icon">[+]</div>
                        <div class="upload-text"><strong>Click to upload</strong> or drag &amp; drop<br>JPG, PNG, WEBP (max 3MB)</div>
                    </div>
                    <input type="file" id="profileInput" name="profile_pic" accept="image/*">
                </div>
            </div>
            <button type="submit" class="submit-btn">Create My Account</button>
        </form>
    </div>
    <script>
        const input = document.getElementById('profileInput');
        const preview = document.getElementById('previewImg');
        const prompt = document.getElementById('uploadPrompt');
        const area = document.getElementById('uploadArea');
        input.addEventListener('change', e => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = ev => { preview.src = ev.target.result; preview.style.display = 'block'; prompt.style.display = 'none'; };
            reader.readAsDataURL(file);
        });
        area.addEventListener('dragover', e => { e.preventDefault(); area.classList.add('dragover'); });
        area.addEventListener('dragleave', () => area.classList.remove('dragover'));
        area.addEventListener('drop', e => {
            e.preventDefault(); area.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const dt = new DataTransfer(); dt.items.add(file);
                input.files = dt.files; input.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>
