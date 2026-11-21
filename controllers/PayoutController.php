<?php
require_once __DIR__ . "/../classes/PayoutClass.php";
require_once __DIR__ . "/OrderController.php";

/**
 * Kitchen payouts. A kitchen earns its meal sales minus CampusBite's commission, plus delivery fees,
 * on every delivered order (the Money page's "Kitchen is owed", over all time). What CampusBite has
 * paid out is recorded here, and the balance is what it still owes.
 */
class PayoutController
{
    const METHODS = ['Mobile Money', 'Bank transfer', 'Cash'];
    const MAX_AMOUNT = 100000;

    private $payoutModel;
    private $orderCtrl;

    public function __construct()
    {
        $this->payoutModel = new PayoutClass();
        $this->orderCtrl = new OrderController();
    }

    /**
     * Earned to date, paid out and balance for each kitchen given, keyed by brand_id.
     * Earned counts delivered orders only; cancelled orders never count.
     */
    public function getBalances($kitchens)
    {
        $report = $this->orderCtrl->getMoneyReport('2000-01-01', date('Y-m-d'), $kitchens, false);
        $paid = $this->payoutModel->getTotalsByKitchen();
        $balances = [];
        foreach ($report['kitchens'] as $k) {
            $id = (int)$k['brand_id'];
            $paidOut = round((float)($paid[$id]['paid'] ?? 0), 2);
            $balances[$id] = [
                'brand_id' => $id,
                'brand_name' => $k['brand_name'],
                'earned' => $k['owed'],
                'paid' => $paidOut,
                'balance' => round($k['owed'] - $paidOut, 2),
                'payouts' => (int)($paid[$id]['payouts'] ?? 0),
                'last_paid_on' => $paid[$id]['last_paid_on'] ?? null,
                'pending_value' => $k['pending_value'],
            ];
        }
        return $balances;
    }

    public function getBalance($kitchen)
    {
        $balances = $this->getBalances([$kitchen]);
        return $balances[(int)$kitchen['brand_id']];
    }

    public function getPayouts($brandId = null, $limit = null)
    {
        return $this->payoutModel->getPayouts($brandId, $limit);
    }

    /**
     * Checks the Record payout form. Returns ['error' => message or null, 'values' => cleaned input].
     * The amount is checked against the balance later, inside the transaction.
     */
    public function checkPayoutForm($input)
    {
        $values = [
            'amount' => trim((string)($input['amount'] ?? '')),
            'method' => (string)($input['method'] ?? ''),
            'reference' => trim((string)($input['reference'] ?? '')),
            'note' => trim((string)($input['note'] ?? '')),
            'paid_on' => (string)($input['paid_on'] ?? ''),
        ];
        $date = DateTime::createFromFormat('Y-m-d', $values['paid_on']);

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $values['amount']) || (float)$values['amount'] <= 0) {
            $error = 'Enter the amount paid in cedis, for example 120 or 120.50.';
        } elseif ((float)$values['amount'] > self::MAX_AMOUNT) {
            $error = 'A single payout can be at most GH₵ ' . number_format(self::MAX_AMOUNT) . '.';
        } elseif (!in_array($values['method'], self::METHODS, true)) {
            $error = 'Choose how the kitchen was paid.';
        } elseif ($values['method'] !== 'Cash' && $values['reference'] === '') {
            $error = 'Add the transaction reference, so the kitchen can match the payment.';
        } elseif (mb_strlen($values['reference']) > 100) {
            $error = 'The reference can be at most 100 characters.';
        } elseif (mb_strlen($values['note']) > 255) {
            $error = 'The note can be at most 255 characters.';
        } elseif (!$date || $date->format('Y-m-d') !== $values['paid_on']) {
            $error = 'Choose the date the kitchen was paid.';
        } elseif ($values['paid_on'] > date('Y-m-d')) {
            $error = "The payment date can't be in the future.";
        } else {
            $error = null;
        }
        return ['error' => $error, 'values' => $values];
    }

    /**
     * Records a payout if it fits within the kitchen's balance. The kitchen's row is locked while the
     * balance is worked out, so two admins recording at once can't pay out more than is owed.
     * Returns ['ok' => bool, 'message' => string].
     */
    public function recordPayout($kitchen, $values, $adminId)
    {
        $amount = round((float)$values['amount'], 2);
        try {
            $this->payoutModel->beginTransaction();
            $this->payoutModel->lockKitchen((int)$kitchen['brand_id']);
            $balance = $this->getBalance($kitchen)['balance'];
            if ($amount > $balance + 0.004) {
                $this->payoutModel->rollBack();
                return ['ok' => false, 'message' => $balance > 0
                    ? "{$kitchen['brand_name']} is owed GH₵ " . number_format($balance, 2) . ", so the payout can't be more than that."
                    : "{$kitchen['brand_name']} isn't owed anything right now."];
            }
            $this->payoutModel->addPayout((int)$kitchen['brand_id'], $amount, $values['method'],
                $values['reference'] !== '' ? $values['reference'] : null,
                $values['note'] !== '' ? $values['note'] : null,
                $values['paid_on'], $adminId);
            $this->payoutModel->commit();
        } catch (PDOException $e) {
            $this->payoutModel->rollBack();
            error_log('Payout failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => "The payout couldn't be saved. Try again."];
        }
        return ['ok' => true, 'message' => 'Recorded GH₵ ' . number_format($amount, 2) . " paid to {$kitchen['brand_name']}."];
    }
}
?>
