<?php

if (php_sapi_name() !== 'cli') {

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'lax'
    ]);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}


if (!isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
}

if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
}


ob_start(); // keep this AFTER session logic

$envPath = dirname(__FILE__) . '/app.env';

if (file_exists($envPath)) {
    foreach (parse_ini_file($envPath) as $key => $value) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

function env_value($key, $default = null)
{
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

/**
 * --------------------------------------
 * APP SETTINGS
 * --------------------------------------
 */
date_default_timezone_set('Africa/Lagos');

define('BASE_URL', env_value('BASE_URL', 'http://localhost'));
define('APP_KEY', env_value('APP_KEY'));


/**
 * --------------------------------------
 * DATABASE CONFIG
 * --------------------------------------
 */
define('DB_HOST', env_value('DB_HOST', 'localhost'));
define('DB_NAME', env_value('DB_NAME'));
define('DB_USER', env_value('DB_USER'));
define('DB_PASS', env_value('DB_PASS'));

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
$rbac = new Rbac($db, $model);
$resultService = new ResultService($db, $model, $rbac);
$paystack = new paystack();
$mailservice = new mailservice();

// Optional Services
$qrcode = class_exists('QRCodeGenerator') ? new QRCodeGenerator() : null;
$admission = new Admission($db, $model, $utility, $qrcode, $mailservice);
