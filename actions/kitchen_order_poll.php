<?php
// Kitchen portal: answers "has a new order come in?" for main.js, which asks every 20 seconds while a kitchen page is open.
// Read-only GET; returns JSON with the number of new (Placed) orders and the kitchen's newest order id.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/OrderController.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

if (!is_kitchen_staff()) {
    http_response_code(401);
    echo json_encode(['error' => 'Sign in with a kitchen account.']);
    exit;
}
// Nothing here writes to the session, so release it and let the kitchen's other requests run alongside
$kitchenId = get_kitchen_id();
session_write_close();

echo json_encode((new OrderController())->getKitchenNewOrderState($kitchenId));
