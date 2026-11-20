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
