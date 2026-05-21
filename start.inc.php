<?php

if (php_sapi_name() !== 'cli') {

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}


// SESSION TIMEOUT CONFIG
define('SESSION_TIMEOUT', 5 * 60); // 5 minutes
define('WARNING_TIME', 2 * 60);     // 2 minutes warning

if (!isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
}

if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
}

// check inactivity timeout
if ((time() - $_SESSION['LAST_ACTIVITY']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();

    header("Location: /admin/login.php?expired=1");
    exit;
}

// update last activity
$_SESSION['LAST_ACTIVITY'] = time();

// calculate remaining time for frontend
$_SESSION['SESSION_EXPIRES_AT'] = $_SESSION['LAST_ACTIVITY'] + SESSION_TIMEOUT;


ob_start(); // keep this AFTER session logic

/**
 * --------------------------------------
 * APP SETTINGS
 * --------------------------------------
 */
date_default_timezone_set('Africa/Lagos');

define('BASE_URL', 'http://localhost');
define('APP_KEY', 'a3f1c2e97b04d56f8a1230bc4e78d9f0123456789abcdef0fedcba9876543210');

/**
 * --------------------------------------
 * DATABASE CONFIG
 * --------------------------------------
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'supr3m3port@l');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * --------------------------------------
 * AUTOLOAD CLASSES
 * --------------------------------------
 */
spl_autoload_register(function ($class) {

    $folders = ['classes/', 'models/', 'services/'];

    foreach ($folders as $folder) {
        $file = __DIR__ . '/' . $folder . $class . '.class.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

/**
 * --------------------------------------
 * DATABASE CONNECTION
 * --------------------------------------
 */
try {

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    $db = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // MySQL session settings
    $db->exec("SET time_zone = '+01:00'");

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

/**
 * --------------------------------------
 * INITIALIZE CORE CLASSES
 * --------------------------------------
 */
$model   = new model($db);
$utility = new utility($model);
$paystack = new paystack();

// Optional Services
$qrcode = class_exists('QRCodeGenerator') ? new QRCodeGenerator() : null;
$mail   = class_exists('MailService') ? new MailService() : null;
