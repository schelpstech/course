<?php
require_once './start.inc.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ./controller/router.php?pageid=" . $utility->secureEncode('studentDashboard'));
    exit;
}
$error = '';
$pageName = 'LoginPage';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Course Portal</title>
    <!-- [Meta] -->
    <meta name="description" content="Course Registration and Management Portal" />
    <meta name="keywords" content="Course Registration, Student Portal, University Management System" />
    <meta name="author" content="Owutech Solutions" />
    <meta name="theme-color" content="#1e293b" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style-index.css" />
</head>
<script>
    function togglePassword() {
        const input = document.getElementById('password');
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>

<body>

    <div class="container">

        <div class="left-panel">

            <div class="brand">
                <img src="assets/images/logo.png" alt="School Logo">
                <h1>Course Portal</h1>
            </div>

            <h2>Empowering Learning Digitally</h2>
            <p>Access courses, manage progress, and stay connected with your academic journey.</p>

        </div>

        <div class="right-panel">
            <div class="form-box">

                <div class="logo-top">
                    <img src="assets/images/logo.png" alt="School Logo">
                </div>
                <h2>Welcome Back</h2>

                <form method="POST" action="./api/login.php">

                    <div class="input-group">
                        <input type="email" name="email" required>
                        <label>Email address</label>
                    </div>

                    <div class="input-group password-group">
                        <input type="password" name="password" id="password" required>
                        <label>Password</label>
                        <span class="toggle-password" onclick="togglePassword()">👁</span>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('authenticateUser'); ?>">
                    <button class="btn" id="loginBtn" type="submit">
                        <span class="btn-text">Login</span>
                        <span class="spinner"></span>
                    </button>

                </form>

                <div class="link">
                    <a href="reset-password.php">Forgot Password?</a>
                </div>

            </div>
        </div>

    </div>
    <?php if (!empty($_SESSION['toast'])): ?>
        <div id="toast" class="toast <?= $_SESSION['toast']['type']; ?>">
            <?= $_SESSION['toast']['message']; ?>
        </div>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>
    <script>
        window.addEventListener('load', function() {
            const toast = document.getElementById('toast');
            if (toast) {
                setTimeout(() => toast.classList.add('show'), 100);

                setTimeout(() => {
                    toast.classList.remove('show');
                }, 4000);
            }
        });
    </script>
</body>

</html>