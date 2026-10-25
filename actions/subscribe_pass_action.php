<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/CustomerClass.php';

require_login();

$userId = get_user_id();
$custClass = new CustomerClass();

// 30 days pass
$expiry = date('Y-m-d', strtotime('+30 days'));
$custClass->updatePassStatus($userId, 1, $expiry);
$_SESSION['has_pass'] = 1;

$_SESSION['success'] = "Congratulations! You have activated CampusBite Pass! Enjoy free Zone A deliveries.";
header("Location: ../views/pass.php");
exit;
?>
