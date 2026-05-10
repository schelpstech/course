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
    <link rel="icon" href="https://owutech-edu.org/assets/images/logo.png" type="image/png">
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
                    <a href="https://forms.gle/zqeYzXokThnPfotaA" target="_blank">Need Support? Click here</a>
                </div>

            </div>
        </div>

    </div>
    <div class="announcement-bar">
        <div class="announcement-content">
            📢 Course Registration is ongoing now and closes by 11:59PM on 17th May 2026.
            <span id="countdown"></span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./assets/js/loginplus.js"></script>


    <?php if (!empty($_SESSION['toast'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: '<?= $_SESSION['toast']['type']; ?>',
                    title: '<?= $_SESSION['toast']['message']; ?>',
                    showConfirmButton: false,
                    timer: 4000
                });
            });
        </script>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>
    <div id="loadingOverlay">
        <div class="loader-box">
            <div class="spinner"></div>
            <p>Please wait, logging you in...</p>
        </div>
    </div>

    <!--Start of Tawk.to Script-->
    <script type="text/javascript">
        var Tawk_API = Tawk_API || {},
            Tawk_LoadStart = new Date();
        (function() {
            var s1 = document.createElement("script"),
                s0 = document.getElementsByTagName("script")[0];
            s1.async = true;
            s1.src = 'https://embed.tawk.to/6a010b37d752791c35102b3f/1joa17kr4';
            s1.charset = 'UTF-8';
            s1.setAttribute('crossorigin', '*');
            s0.parentNode.insertBefore(s1, s0);
        })();
    </script>
    <!--End of Tawk.to Script-->
</body>

</html>