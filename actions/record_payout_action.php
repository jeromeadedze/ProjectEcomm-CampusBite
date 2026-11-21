<?php
// Admin: records money paid out to a kitchen (Mobile Money, bank transfer or cash), up to what it's owed
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../controllers/PayoutController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/admin/money.php");
    exit;
}
require_csrf();

$brandId = (int)($_POST['brand_id'] ?? 0);
$kitchen = $brandId > 0 ? (new ProductController())->getBrandById($brandId) : null;
if (!$kitchen) {
    $_SESSION['error'] = "That kitchen no longer exists.";
    header("Location: ../views/admin/money.php#payouts");
    exit;
}

$payoutCtrl = new PayoutController();
$checked = $payoutCtrl->checkPayoutForm($_POST);
$result = $checked['error'] ? ['ok' => false, 'message' => $checked['error']]
                            : $payoutCtrl->recordPayout($kitchen, $checked['values'], get_user_id());

if ($result['ok']) {
    $_SESSION['success'] = $result['message'];
    header("Location: ../views/admin/money.php#payouts");
} else {
    $_SESSION['error'] = $result['message'];
    $_SESSION['payout_form'] = $checked['values'];
    header("Location: ../views/admin/money.php?pay=" . $brandId . "#record-payout");
}
exit;
