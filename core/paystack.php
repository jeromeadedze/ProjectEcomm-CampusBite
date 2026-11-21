<?php
/**
 * Paystack helpers. Keys live in core/paystack_config.php (git-ignored; copy core/paystack_config.example.php).
 * Amounts sent to and received from Paystack are in pesewas (GH₵ 1 = 100 pesewas).
 */
if (file_exists(__DIR__ . '/paystack_config.php')) {
    require_once __DIR__ . '/paystack_config.php';
}

/** True when a secret key that isn't the example placeholder is set */
function paystack_configured() {
    return defined('PAYSTACK_SECRET_KEY') && preg_match('/^sk_(test|live)_[A-Za-z0-9]{10,}$/', PAYSTACK_SECRET_KEY);
}

/**
 * Calls the Paystack API. Returns the decoded JSON, or null if Paystack couldn't be reached.
 */
function paystack_request($method, $path, $body = null) {
    $ch = curl_init('https://api.paystack.co' . $path);
    $headers = ['Authorization: Bearer ' . PAYSTACK_SECRET_KEY, 'Cache-Control: no-cache'];
    $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_CUSTOMREQUEST => $method];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    $options[CURLOPT_HTTPHEADER] = $headers;
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    if ($response === false) {
        error_log('Paystack ' . $path . ' failed: ' . curl_error($ch));
    }
    return $response === false ? null : json_decode($response, true);
}

/** GH₵ to pesewas, without floating-point drift */
function to_pesewas($amount) {
    return (int)round((float)$amount * 100);
}

/** Absolute URL to a file in this site, e.g. site_url('actions/paystack_callback_action.php') */
function site_url($path) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    // The site root is the folder above /actions or /views
    $root = rtrim(preg_replace('#/(actions|views)(/.*)?$#', '', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $root . '/' . ltrim($path, '/');
}

/** A readable name for the channel Paystack reports, e.g. mobile_money -> Mobile Money */
function paystack_channel_label($channel) {
    $labels = ['mobile_money' => 'Mobile Money', 'card' => 'Card', 'bank' => 'Bank', 'bank_transfer' => 'Bank transfer', 'ussd' => 'USSD', 'qr' => 'QR'];
    return 'Paystack - ' . ($labels[$channel] ?? ucfirst((string)$channel));
}

/**
 * Starts a full refund of a verified payment. Returns ['ok' => bool, 'status' => Paystack's refund status,
 * 'id' => Paystack's refund id, 'message' => Paystack's message].
 */
function paystack_refund($reference, $amountPesewas, $note = '') {
    $result = paystack_request('POST', '/refund', [
        'transaction' => $reference,
        'amount' => (int)$amountPesewas,
        'currency' => 'GHS',
        'customer_note' => mb_substr($note, 0, 250),
        'merchant_note' => mb_substr($note, 0, 250),
    ]);
    if (!$result) {
        return ['ok' => false, 'status' => null, 'id' => null, 'message' => "Paystack couldn't be reached."];
    }
    $data = $result['data'] ?? [];
    $ok = !empty($result['status']);
    if (!$ok) {
        error_log('Paystack refund for ' . $reference . ' failed: ' . json_encode($result['message'] ?? $result));
    }
    return [
        'ok' => $ok,
        'status' => $ok ? (string)($data['status'] ?? 'pending') : null,
        'id' => isset($data['id']) ? (string)$data['id'] : null,
        'message' => (string)($result['message'] ?? ''),
    ];
}

/** Paystack's current status for a refund it's still working on, or null if it couldn't be fetched */
function paystack_refund_status($refundId) {
    $result = paystack_request('GET', '/refund/' . rawurlencode($refundId));
    return !empty($result['status']) ? (string)($result['data']['status'] ?? '') : null;
}

// ---------- Split payments: each kitchen is a Paystack subaccount ----------

/** Mobile Money networks or Ghana banks Paystack can pay out to, as [code => name]. Kept for a day per session. */
function paystack_payout_options($type) {
    $type = $type === 'bank' ? 'bank' : 'mobile_money';
    $cached = $_SESSION['paystack_banks'][$type] ?? null;
    if ($cached && $cached['at'] > time() - 86400) {
        return $cached['list'];
    }
    $list = [];
    if (paystack_configured()) {
        $result = paystack_request('GET', '/bank?currency=GHS&perPage=100&type=' . ($type === 'bank' ? 'ghipss' : 'mobile_money'));
        foreach ($result['data'] ?? [] as $bank) {
            if (!empty($bank['code']) && !empty($bank['active'] ?? true)) {
                $list[(string)$bank['code']] = (string)$bank['name'];
            }
        }
    }
    if ($list) {
        $_SESSION['paystack_banks'][$type] = ['at' => time(), 'list' => $list];
    }
    return $list;
}

/**
 * Creates the kitchen's Paystack subaccount, or updates it when $code is set, so Paystack pays the kitchen
 * directly. $percent is CampusBite's default share; each payment also sets a flat transaction_charge.
 * Returns ['ok' => bool, 'code' => subaccount code, 'account_name' => name Paystack resolved, 'message' => text].
 */
function paystack_save_subaccount($code, $businessName, $bankCode, $accountNumber, $percent, $phone = '') {
    $body = [
        'business_name' => mb_substr($businessName, 0, 100),
        'bank_code' => $bankCode,
        'settlement_bank' => $bankCode, // older name for bank_code; Paystack's own examples still use it
        'account_number' => $accountNumber,
        'percentage_charge' => (float)$percent,
        'description' => 'CampusBite kitchen: ' . mb_substr($businessName, 0, 80),
    ];
    if ($phone !== '') {
        $body['primary_contact_phone'] = $phone;
    }
    $result = $code
        ? paystack_request('PUT', '/subaccount/' . rawurlencode($code), $body)
        : paystack_request('POST', '/subaccount', $body);
    if (!$result) {
        return ['ok' => false, 'code' => null, 'account_name' => null, 'message' => "Paystack couldn't be reached. Try again in a minute."];
    }
    if (empty($result['status'])) {
        error_log('Paystack subaccount ' . ($code ?: 'create') . ' failed: ' . json_encode($result['message'] ?? $result));
        return ['ok' => false, 'code' => null, 'account_name' => null, 'message' => (string)($result['message'] ?? 'Paystack refused the account.')];
    }
    $data = $result['data'] ?? [];
    return [
        'ok' => true,
        'code' => (string)($data['subaccount_code'] ?? $code),
        'account_name' => isset($data['account_name']) && trim((string)$data['account_name']) !== '' ? trim((string)$data['account_name']) : null,
        'message' => '',
    ];
}

/**
 * How one order's payment is split. CampusBite keeps its commission on the meals (sent to Paystack as a flat
 * transaction_charge); the kitchen gets the rest, delivery fee included. Paystack's fee comes out of CampusBite's
 * share, unless there is no commission to take it from, in which case the kitchen bears it. Amounts in GH₵.
 */
function paystack_split_for($mealsTotal, $deliveryFee, $commissionRate) {
    $commission = round((float)$mealsTotal * (float)$commissionRate, 2);
    $total = round((float)$mealsTotal + (float)$deliveryFee, 2);
    return [
        'commission' => $commission,
        'kitchen_share' => round($total - $commission, 2),
        'bearer' => $commission > 0 ? 'account' : 'subaccount',
    ];
}
