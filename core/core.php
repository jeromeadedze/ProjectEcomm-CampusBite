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
 * Start a signed-in session for a customer row
 */
function log_in_customer($user) {
    require_once __DIR__ . '/../classes/CartClass.php';
    session_regenerate_id(true);
    // Keep anything the guest put in their cart before signing in
    (new CartClass())->mergeGuestCart(get_cart_token(), $user['customer_id']);
    $_SESSION['customer_id'] = $user['customer_id'];
    $_SESSION['customer_name'] = $user['customer_name'];
    $_SESSION['customer_email'] = $user['customer_email'];
    $_SESSION['user_role'] = (int)$user['user_role'];
    $_SESSION['has_pass'] = has_active_pass($user) ? 1 : 0;
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
 * Sends the user back to the page they came from, but only if it is on this site.
 * Anything else (another site, a missing Referer) goes to $fallback.
 */
function redirect_back($fallback) {
    $ref = parse_url($_SERVER['HTTP_REFERER'] ?? '');
    $host = $ref['host'] ?? '';
    if (isset($ref['port'])) {
        $host .= ':' . $ref['port'];
    }
    if ($host !== '' && $host === ($_SERVER['HTTP_HOST'] ?? '') && !empty($ref['path'])) {
        $fallback = $ref['path'] . (isset($ref['query']) ? '?' . $ref['query'] : '');
    }
    header("Location: " . $fallback);
    exit;
}

/**
 * Random key that ties a guest's cart to their browser session.
 * Replaces the old IP-based key: on campus Wi-Fi many students share one IP,
 * and the X-Forwarded-For header could be faked to read someone else's cart.
 * The value is stored in the cart table's ip_add column.
 */
function get_cart_token() {
    if (empty($_SESSION['cart_token'])) {
        $_SESSION['cart_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['cart_token'];
}

/**
 * CampusBite Pass is on hold: hidden from the UI and gives no discount.
 * Set to true to bring it back; the pass page, action and checkout logic are all still in place.
 */
const PASS_ENABLED = false;

/**
 * True when a customer row has a pass that has not expired yet (always false while the pass is on hold)
 */
function has_active_pass($user) {
    return PASS_ENABLED
        && !empty($user['has_pass'])
        && !empty($user['pass_expiry'])
        && $user['pass_expiry'] >= date('Y-m-d');
}

/**
 * CSRF protection: every POST form carries this token and every POST action checks it
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Stops a POST request that did not come from one of our own forms
 */
function require_csrf() {
    $sent = $_POST['csrf_token'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        $_SESSION['error'] = "Your session expired. Please try that again.";
        $root = defined('ROOT_DIR') ? ROOT_DIR : '..';
        header("Location: " . $root . "/index.php");
        exit;
    }
}
?>
