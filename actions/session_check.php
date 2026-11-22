<?php
/**
 * "Am I still signed in?" for js/main.js, asked on clicks, key presses, when the tab comes back and every 10 seconds
 * on every page a signed-in person has open. core.php does the real check (sync_account_session): if the password
 * was changed or reset somewhere else it ends the session and answers 401 { signed_out: true }, and the page goes to
 * the sign-in page straight away instead of waiting for the next page load.
 */
$_SERVER['HTTP_ACCEPT'] = 'application/json'; // so core.php answers a signed-out session with JSON, not a redirect
require_once __DIR__ . '/../core/core.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (!is_logged_in()) {
    // Signed out another way (account deleted, or signed out in another tab)
    http_response_code(401);
    echo json_encode(['ok' => false, 'signed_out' => true]);
    exit;
}
echo json_encode(['ok' => true]);
