<?php
require_once './start.inc.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ./controller/router.php?pageid=" . $utility->secureEncode('studentDashboard'));
    exit;
}

// Initialize step
$step = $_SESSION['reset_step'] ?? 1;

// Generate math question
if (!isset($_SESSION['math_q'])) {
    $a = rand(1, 9);
    $b = rand(1, 9);
    $_SESSION['math_q'] = "$a + $b";
    $_SESSION['math_ans'] = $a + $b;
}
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

                <h2>Password Reset</h2>

                <!-- STEP 1 -->
                <?php if ($step == 1): ?>
                    <form method="POST" action="./api/reset/requestOtp.php">

                        <div class="input-group">
                            <input type="email" name="email" required>
                            <label>Email address</label>
                        </div>

                        <div class="input-group">
                            <input type="text" name="math_answer" required>
                            <label>Solve: <?= $_SESSION['math_q']; ?></label>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('requestOtp'); ?>">

                        <button class="btn btn-primary" type="submit">
                            Request OTP
                        </button>

                    </form>
                <?php endif; ?>

                <!-- STEP 2 -->
                <?php if ($step == 2): ?>
                    <form method="POST" action="./api/reset/verifyOtp.php">

                        <div class="input-group">
                            <input type="text" name="otp" required>
                            <label>Enter OTP</label>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('verifyOtp'); ?>">

                        <button class="btn btn-primary" type="submit">
                            Verify OTP
                        </button>

                    </form>
                <?php endif; ?>

                <!-- STEP 3 -->
                <?php if ($step == 3): ?>
                    <form method="POST" action="./api/reset/updatePassword.php">

                        <div class="input-group">
                            <input type="password" name="password" required>
                            <label>New Password</label>
                        </div>

                        <div class="input-group">
                            <input type="password" name="confirm_password" required>
                            <label>Confirm Password</label>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('updatePassword'); ?>">

                        <button class="btn btn-success" type="submit">
                            Reset Password
                        </button>

                    </form>
                <?php endif; ?>

                <div class="link">
                    <a href="./index.php">Back to Login</a>
                </div>

            </div>
        </div>
        </div>
    <div class="announcement-bar">
        <div class="announcement-content">
            📢📢📢Announcement 📢📢📢 Course Registration is ongoing now.
            📢📢📢Announcement 📢📢📢 The School WiFi is now available for all students.
            📢📢📢Announcement 📢📢📢 Pay your school fees on time.
            <span id="countdown"></span>
        </div>
    </div>


    <!-- ===============================
     PREMIUM ANNOUNCEMENT TOAST
=============================== -->
        <div class="toast-container position-fixed top-0 start-0 p-4" style="z-index: 9999;">

        <div id="announcementToast" class="custom-toast">

            <div class="toast-header-custom">
                <span class="toast-icon">📢</span>
                <span class="toast-title">Campus Update</span>

                <button type="button" class="toast-close" data-bs-dismiss="toast">
                    &times;
                </button>
            </div>

            <div class="toast-body-custom">
                <p>
                    The School WiFi is now available for all students.
                </p>

                <p>
                    Connect to <strong>OWUNET</strong> in designated areas.
                </p>

                <p>
                    Username: <strong>owutech</strong><br>
                    Password: <strong>leave blank</strong>
                </p>

                <div class="toast-footer">
                    <span class="badge-soft">Unlimited Access</span>
                    <span class="badge-soft success">High Speed</span>
                </div>

                <!-- ✅ MOVED INSIDE -->
                <div class="toast-checkbox mt-3">
                    <input type="checkbox" id="hideToastCheck">

                    <label for="hideToastCheck">
                        Don’t show again
                    </label>
                </div>
            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

</body>

</html>