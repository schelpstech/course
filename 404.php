<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>

    <link href="assets/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;

            background: linear-gradient(135deg,
                    #f8fafc,
                    #eef2ff);

            font-family: "Segoe UI", sans-serif;
        }

        .error-card {
            max-width: 650px;
            width: 100%;

            background: #fff;

            border-radius: 24px;

            padding: 50px;

            text-align: center;

            box-shadow:
                0 20px 40px rgba(0, 0, 0, .08);
        }

        .logo {
            width: 90px;
            margin-bottom: 20px;
        }

        .error-code {
            font-size: 120px;
            font-weight: 800;
            line-height: 1;
            color: #0d6efd;
            margin-bottom: 10px;
        }

        .error-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #212529;
        }

        .error-text {
            color: #6c757d;
            font-size: 16px;
            margin-bottom: 30px;
        }

        .btn-custom {
            min-width: 170px;
            border-radius: 10px;
            padding: 10px 20px;
        }

        .footer-note {
            margin-top: 30px;
            color: #adb5bd;
            font-size: 13px;
        }

        @media(max-width:768px) {

            .error-card {
                padding: 30px;
                margin: 20px;
            }

            .error-code {
                font-size: 80px;
            }

            .error-title {
                font-size: 24px;
            }
        }

        .error-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('uploads/logo/logo.png') no-repeat center;
            background-size: 250px;
            opacity: .03;
            pointer-events: none;
        }
    </style>

</head>

<body>

    <div class="error-card">

        <!-- LOGO -->
        <img
            src="uploads/logo/logo.png"
            alt="Institution Logo"
            class="logo">

        <!-- 404 -->
        <div class="error-code">
            404
        </div>

        <h1 class="error-title">
            Page Not Found
        </h1>

        <p class="error-text">
            The page you are looking for may have been moved,
            removed, renamed, or is temporarily unavailable.
        </p>

        <div class="d-flex justify-content-center gap-3 flex-wrap">

            <a href="dashboard.php"
                class="btn btn-primary btn-custom">

                Go to Dashboard

            </a>

            <button
                onclick="history.back()"
                class="btn btn-outline-secondary btn-custom">

                Go Back

            </button>

        </div>

        <div class="footer-note">
            Course Registration Portal
        </div>

    </div>

</body>

</html>