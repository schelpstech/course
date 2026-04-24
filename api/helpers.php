<?php

/**
 * --------------------------------------
 * GLOBAL HELPERS
 * --------------------------------------
 */

function redirect($url)
{
    header("Location: $url");
    exit;
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        redirect('index.php');
    }
}

function hasRole($role)
{
    return ($_SESSION['role'] ?? null) === $role;
}

function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function setFlash($key, $message)
{
    $_SESSION['flash'][$key] = $message;
}

function getFlash($key)
{
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}