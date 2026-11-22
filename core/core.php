<?php
// ============================================================
// core.php - Global Session & Security Controller
// ============================================================

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * A short fingerprint of the account's password hash. Saved in the session at sign-in; when the password changes
 * (reset by an admin, "Forgot password?", or changed on another device) it no longer matches, and that session ends.
 */
function password_stamp($passwordHash) {
    return substr(hash('sha256', 'cb-session:' . (string)$passwordHash), 0, 20);
}

// Path from the running script back to the site's root, e.g. "../.." for views/kitchen/x.php (core runs before ROOT_DIR is known)
function path_to_root() {
    $root = realpath(__DIR__ . '/..');
    $script = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '';
    if ($root === false || $script === '' || strpos($script, $root) !== 0) {
        return '..';
    }
    $depth = substr_count(trim(substr(dirname($script), strlen($root)), DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR)
           + (dirname($script) === $root ? 0 : 1);
    return $depth === 0 ? '.' : rtrim(str_repeat('../', $depth), '/');
}

/**
 * Signed-in accounts are re-checked against the database on every request, so a deleted account is signed out,
 * removing someone's kitchen access (or moving them to another kitchen) takes effect immediately, and a session
 * whose password has since been changed or reset is signed out and sent to the sign-in page.
 */
function sync_account_session() {
    if (empty($_SESSION['customer_id'])) {
        return;
    }
    require_once __DIR__ . '/db_class.php';
    $row = (new Database())->fetchOne("SELECT user_role, brand_id, kitchen_role, must_change_password, customer_pass FROM cb_customer WHERE customer_id = ?", [$_SESSION['customer_id']]);
    if (!$row) {
        // The account was deleted while signed in (e.g. a kitchen admin removed this staff login)
        $_SESSION = [];
        session_regenerate_id(true);
        set_form_message('login', 'This login was removed, so you were signed out.', 'error');
        return;
    }
    $stamp = password_stamp($row['customer_pass']);
    if (empty($_SESSION['pass_stamp'])) {
        $_SESSION['pass_stamp'] = $stamp; // signed in before this check existed
    } elseif (!hash_equals($_SESSION['pass_stamp'], $stamp)) {
        // The password was changed somewhere else: end this session
        $_SESSION = [];
        session_regenerate_id(true);
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Your password was changed, so you were signed out. Reload the page to sign in again.']);
            exit;
        }
        set_form_message('login', 'Your password was changed, so you were signed out. Sign in with the new password.', 'error');
        header('Location: ' . path_to_root() . '/views/login.php');
        exit;
    }
    $_SESSION['must_change_password'] = (int)$row['must_change_password'];
    if ((int)($_SESSION['user_role'] ?? 0) === 4) {
        $_SESSION['user_role'] = (int)$row['user_role'];
        $_SESSION['brand_id'] = (int)$row['user_role'] === 4 ? (int)$row['brand_id'] : null;
        $_SESSION['kitchen_role'] = (int)$row['user_role'] === 4 ? ($row['kitchen_role'] ?: 'admin') : null;
    }
}
sync_account_session();

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
    // Kitchen accounts (role 4) work on one kitchen
    $_SESSION['brand_id'] = (int)$user['user_role'] === 4 ? (int)$user['brand_id'] : null;
    // Kitchen accounts are the kitchen's admin (sales, staff, prices) or its staff (orders, messages)
    $_SESSION['kitchen_role'] = (int)$user['user_role'] === 4 ? (($user['kitchen_role'] ?? null) ?: 'admin') : null;
    $_SESSION['must_change_password'] = (int)($user['must_change_password'] ?? 0);
    // So this session ends if the password is later changed or reset somewhere else
    $_SESSION['pass_stamp'] = isset($user['customer_pass']) ? password_stamp($user['customer_pass']) : null;
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
 * Check if the user runs a kitchen (user_role = 4) that is linked to a kitchen
 */
function is_kitchen() {
    return isset($_SESSION['user_role']) && (int)$_SESSION['user_role'] === 4 && !empty($_SESSION['brand_id']);
}

/**
 * The kitchen a kitchen account runs, or null for everyone else
 */
function get_kitchen_id() {
    return is_kitchen() ? (int)$_SESSION['brand_id'] : null;
}

/**
 * Admins and kitchen staff manage orders; they don't place them
 */
function can_order() {
    return !is_admin() && !is_kitchen();
}

/**
 * Where a signed-in user lands: admins and kitchens go to their own dashboards, shoppers to the home page
 */
function home_path_for_role() {
    if (is_kitchen()) {
        return is_kitchen_admin() ? '/views/kitchen/kitchen_sales.php' : '/views/kitchen/kitchen_dashboard.php';
    }
    return is_admin() ? '/views/admin/admin_dashboard.php' : '/index.php';
}

/**
 * Where staff go when they open a shopper-only page: kitchens to their orders, admins to their dashboard
 * (orders belong to the kitchens; the admin has no order list)
 */
function staff_orders_path() {
    if (is_kitchen()) {
        return is_kitchen_staff() ? '/views/kitchen/kitchen_orders.php' : '/views/kitchen/kitchen_sales.php';
    }
    return '/views/admin/admin_dashboard.php';
}

/**
 * Kitchen admin: the kitchen's owner or manager. Sees sales, manages staff logins, the menu, prices and payouts.
 * Older kitchen logins with no kitchen_role count as admins.
 */
function is_kitchen_admin() {
    return is_kitchen() && ($_SESSION['kitchen_role'] ?? 'admin') !== 'staff';
}

/**
 * Kitchen staff: works the shift. Handles orders and customer messages and marks meals sold out. Never sees money.
 */
function is_kitchen_staff() {
    return is_kitchen() && ($_SESSION['kitchen_role'] ?? 'admin') === 'staff';
}

/**
 * Pages and actions only a kitchen admin may use (sales, staff, prices, payouts)
 */
function require_kitchen_admin() {
    require_kitchen();
    if (!is_kitchen_admin()) {
        $_SESSION['error'] = "Only your kitchen's admin can open that page.";
        $root = defined('ROOT_DIR') ? ROOT_DIR : '..';
        header("Location: " . $root . home_path_for_role());
        exit;
    }
}

/**
 * Pages and actions only kitchen staff use (orders, customer messages)
 */
function require_kitchen_staff() {
    require_kitchen();
    if (!is_kitchen_staff()) {
        $_SESSION['error'] = "Orders and messages are handled by your kitchen staff.";
        $root = defined('ROOT_DIR') ? ROOT_DIR : '..';
        header("Location: " . $root . home_path_for_role());
        exit;
    }
}

/**
 * Enforce kitchen access: only kitchen accounts linked to a kitchen
 */
function require_kitchen() {
    require_login();
    if (!is_kitchen()) {
        $_SESSION['error'] = "That page is for kitchen accounts.";
        $root = defined('ROOT_DIR') ? ROOT_DIR : '..';
        header("Location: " . $root . "/index.php");
        exit;
    }
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
 * A message that belongs to one form (wrong password, details saved), shown inside that form
 * instead of as a pop-up card. $form names the form ('login', 'profile', ...), $type is 'error' or 'success',
 * and $values refills what was typed. The page reads it once with take_form_message().
 */
function set_form_message($form, $message, $type = 'error', $values = []) {
    $_SESSION['form_message'] = ['form' => $form, 'type' => $type, 'message' => $message, 'values' => $values];
}

function take_form_message($form) {
    $msg = $_SESSION['form_message'] ?? null;
    if (!$msg || $msg['form'] !== $form) {
        return null;
    }
    unset($_SESSION['form_message']);
    return $msg;
}

// The message as a line at the top of its form (role="alert" so screen readers announce errors)
function form_message_html($msg) {
    if (!$msg) {
        return '';
    }
    $isError = $msg['type'] === 'error';
    return '<p class="cb-form-msg ' . ($isError ? 'is-error' : 'is-ok') . '" role="' . ($isError ? 'alert' : 'status') . '">'
         . '<i class="bi ' . ($isError ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill') . '" aria-hidden="true"></i>'
         . '<span>' . htmlspecialchars($msg['message']) . '</span></p>';
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

/**
 * Direct communication helpers for contacting kitchens or customers via call or WhatsApp
 */
// The Bootstrap icon that stands in for a meal with no photo: its category's icon, so a jollof bowl,
// a shawarma and a smoothie don't all look the same
function meal_icon($meal) {
    $icon = is_array($meal) ? ($meal['cat_icon'] ?? '') : '';
    return preg_match('/^bi-[a-z0-9-]+$/', (string)$icon) ? $icon : 'bi-egg-fried';
}

function phone_tel_url($phone) {
    return 'tel:' . preg_replace('/[^0-9+]/', '', (string)$phone);
}

function phone_wa_url($phone, $text = '') {
    $clean = preg_replace('/[^0-9]/', '', (string)$phone);
    if (str_starts_with($clean, '0') && strlen($clean) === 10) {
        $clean = '233' . substr($clean, 1);
    }
    $url = 'https://wa.me/' . $clean;
    if ($text !== '') {
        $url .= '?text=' . rawurlencode($text);
    }
    return $url;
}

/**
 * An account still on the starting password it was given (like Munchees@Kwame47) must set its own before doing
 * anything else: every page except Settings, the password change itself and signing out sends it to the password form.
 */
function require_own_password() {
    if (empty($_SESSION['customer_id']) || empty($_SESSION['must_change_password'])) {
        return;
    }
    $allowed = ['settings.php', 'change_password_action.php', 'logout.php'];
    if (in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), $allowed, true)) {
        return;
    }
    $_SESSION['error'] = "Set your own password to continue. The one you were given is only for your first sign-in.";
    $root = defined('ROOT_DIR') ? ROOT_DIR : '..';
    header("Location: " . $root . "/views/settings.php?edit=password");
    exit;
}
require_own_password();
?>
