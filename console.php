<?php
require_once './start.inc.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: ./dashboard.php");
    exit;
}

$error = '';
$pageName = 'AdminLogin';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Course Portal</title>
    <link rel="icon" href="./assets/images/logo.png" type="image/png">
    <meta name="description" content="Secure Admin Access - Course Management Portal" />
    <meta name="author" content="Owutech Solutions" />
    <meta name="theme-color" content="#0f172a" />

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style-index.css">

    <style>
        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
        }

        .left-panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
        }

        .form-box {
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        .admin-badge {
            background: #ef4444;
            color: #fff;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .security-note {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 10px;
        }
    </style>
</head>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>

<body>

    <div class="container">

        <!-- LEFT PANEL -->
        <div class="left-panel">

            <div class="brand">
                <img src="./assets/images/logo.png" alt="School Logo">
                <h1>Admin Portal</h1>
            </div>

            <h2>System Control Center</h2>
            <p>Manage students, payments, courses, and system operations securely.</p>

        </div>

        <!-- RIGHT PANEL -->
        <div class="right-panel">
            <div class="form-box">

                <div class="logo-top">
                    <img src="./assets/images/logo.png" alt="Logo">
                </div>

                <div class="admin-badge">ADMIN ACCESS</div>

                <h2>Sign in to Dashboard</h2>

                <form method="POST" action="./api/adminLogin_api.php">

                    <div class="input-group">
                        <input type="email" name="email" required>
                        <label>Email address</label>
                    </div>

                    <div class="input-group password-group">
                        <input type="password" name="password" id="password" required>
                        <label>Password</label>
                        <span class="toggle-password" onclick="togglePassword()">👁</span>
                    </div>

                    <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('adminLogin'); ?>">

                    <button class="btn" id="loginBtn" type="submit">
                        <span class="btn-text">Secure Login</span>
                        <span class="spinner"></span>
                    </button>

                </form>

                <div class="security-note">
                    Unauthorized access is prohibited and monitored.
                </div>

            </div>
        </div>

    </div>

    <!-- TOAST -->
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