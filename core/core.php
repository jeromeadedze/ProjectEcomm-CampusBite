<?php
// ============================================================
// core.php - Global Session & Security Controller
// ============================================================

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently logged in
 */
function is_logged_in() {
    return isset($_SESSION['customer_id']);
}

/**
 * Enforce authentication on protected pages
 */
function require_login() {
    if (!is_logged_in()) {
        $root = defined('ROOT_DIR') ? ROOT_DIR : '..';
        header("Location: " . $root . "/views/login.php");
        exit;
    }
}

/**
 * Check if the user is an administrator (user_role = 1)
 */
function is_admin() {
    return isset($_SESSION['user_role']) && (int)$_SESSION['user_role'] === 1;
}

/**
 * Check if the user is a courier / runner (user_role = 3)
 */
function is_courier() {
    return isset($_SESSION['user_role']) && (int)$_SESSION['user_role'] === 3;
}

/**
 * Enforce admin privileges
 */
function require_admin() {
    if (!is_admin()) {
        $_SESSION['error'] = "Access denied. Admin privileges required.";
        $root = defined('ROOT_DIR') ? ROOT_DIR : '..';
        header("Location: " . $root . "/index.php");
        exit;
    }
}

/**
 * Get current customer/user ID
 */
function get_user_id() {
    return $_SESSION['customer_id'] ?? null;
}

/**
 * Helper to get client IP address for guest cart tracking
 */
function get_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
?>
