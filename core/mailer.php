<?php
// Sends CampusBite's emails. Settings come from core/mail_config.php (see mail_config.example.php).
// With no config, or MAIL_MODE 'log', emails are saved to storage/mail/ instead of being sent.

if (is_file(__DIR__ . '/mail_config.php')) {
    require_once __DIR__ . '/mail_config.php';
}

function mail_mode() {
    return (defined('MAIL_MODE') && MAIL_MODE === 'smtp') ? 'smtp' : 'log';
}

// True when emails are only saved (log mode) and the request comes from this computer, so it's safe to show
// a link from an email on screen. Never true on a real server sending mail.
function mail_shows_links() {
    return mail_mode() === 'log' && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
}

/**
 * Full address of a page on this site, for links inside emails, e.g. app_url('views/login.php').
 * Uses APP_URL when it's set; otherwise (log mode only) works it out from this request.
 */
function app_url($path) {
    if (defined('APP_URL') && APP_URL !== '') {
        $base = rtrim(APP_URL, '/');
    } else {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $host = preg_replace('/[^A-Za-z0-9.:\-\[\]]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        // The project folder: everything before /actions/ or /views/ in the script's path
        $dir = preg_replace('#/(actions|views)/.*$#', '', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = $dir === ($_SERVER['SCRIPT_NAME'] ?? '') ? rtrim(dirname($dir), '/\\') : $dir;
        $base = ($https ? 'https' : 'http') . '://' . $host . $dir;
    }
    return $base . '/' . ltrim($path, '/');
}

/**
 * The HTML for an email: a white card with the CampusBite name, a heading, paragraphs and an optional red button.
 * $paragraphs are plain text and get escaped here.
 */
function email_html($heading, array $paragraphs, $buttonText = null, $buttonUrl = null, $footnote = null) {
    $e = function ($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };
    $body = '';
    foreach ($paragraphs as $p) {
        $body .= '<p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#0F0F0F">' . nl2br($e($p)) . '</p>';
    }
    if ($buttonText && $buttonUrl) {
        $body .= '<p style="margin:22px 0"><a href="' . $e($buttonUrl) . '" style="display:inline-block;background:#E3001B;color:#FFFFFF;text-decoration:none;font-weight:600;font-size:15px;padding:13px 26px;border-radius:999px">' . $e($buttonText) . '</a></p>'
               . '<p style="margin:0 0 14px;font-size:12px;line-height:1.6;color:#6B6B6B">If the button doesn\'t work, copy this address into your browser:<br><span style="word-break:break-all">' . $e($buttonUrl) . '</span></p>';
    }
    if ($footnote) {
        $body .= '<p style="margin:18px 0 0;font-size:12px;line-height:1.6;color:#6B6B6B">' . $e($footnote) . '</p>';
    }
    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>'
         . '<body style="margin:0;padding:24px 12px;background:#F2F2F2;font-family:Helvetica,Arial,sans-serif">'
         . '<div style="max-width:520px;margin:0 auto;background:#FFFFFF;border-radius:20px;padding:28px 26px">'
         . '<p style="margin:0 0 20px;font-size:20px;font-weight:800;letter-spacing:-0.3px;color:#0F0F0F">CAMPUS<span style="color:#E3001B">BITE</span></p>'
         . '<h1 style="margin:0 0 16px;font-size:21px;line-height:1.3;color:#0F0F0F">' . $e($heading) . '</h1>'
         . $body
         . '</div><p style="text-align:center;font-size:11px;color:#8A8A8A;margin:16px 0 0">CampusBite · Ashesi University and Berekuso kitchens</p>'
         . '</body></html>';
}

// The plain-text copy of the same email, for mail apps that don't show HTML
function email_text($heading, array $paragraphs, $buttonText = null, $buttonUrl = null, $footnote = null) {
    $text = $heading . "\n\n" . implode("\n\n", $paragraphs);
    if ($buttonText && $buttonUrl) {
        $text .= "\n\n" . $buttonText . ":\n" . $buttonUrl;
    }
    if ($footnote) {
        $text .= "\n\n" . $footnote;
    }
    return $text . "\n\n-- \nCampusBite";
}

/**
 * Sends one email. Returns true when it was sent (or saved, in log mode). Failures are logged, never shown,
 * so a mail problem doesn't stop whatever the person was doing.
 */
function send_email($toEmail, $toName, $subject, $html, $text) {
    $clean = function ($s) { return trim(str_replace(["\r", "\n"], ' ', (string)$s)); };
    $toEmail = $clean($toEmail);
    $toName = $clean($toName);
    $subject = $clean($subject);
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        error_log('Email not sent: bad address ' . $toEmail);
        return false;
    }
    if (mail_mode() === 'log') {
        return mail_save_copy($toEmail, $subject, $html);
    }
    if (!defined('APP_URL') || APP_URL === '') {
        error_log('Email not sent: set APP_URL in core/mail_config.php');
        return false;
    }
    try {
        smtp_send($toEmail, $toName, $subject, mail_build_message($toEmail, $toName, $subject, $html, $text));
        return true;
    } catch (Exception $e) {
        error_log('Email to ' . $toEmail . ' failed: ' . $e->getMessage());
        return false;
    }
}

// Log mode: keeps the email as an HTML file in storage/mail/ (git-ignored) so it can be opened in a browser
function mail_save_copy($toEmail, $subject, $html) {
    $dir = __DIR__ . '/../storage/mail';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        error_log('Email not saved: storage/mail could not be created');
        return false;
    }
    // Saved emails hold reset links, so the web server must never hand them out (Apache, as on XAMPP and most hosts)
    $guard = __DIR__ . '/../storage/.htaccess';
    if (!is_file($guard)) {
        file_put_contents($guard, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n");
    }
    $name = date('Y-m-d_His') . '_' . bin2hex(random_bytes(3)) . '.html';
    $note = '<!-- To: ' . htmlspecialchars($toEmail) . ' | Subject: ' . htmlspecialchars($subject) . " -->\n";
    return file_put_contents($dir . '/' . $name, $note . $html) !== false;
}

function mail_encode_header($s) {
    return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
}

// A person's name for From/To: plain names stay readable, anything with punctuation that means something in an address is encoded
function mail_encode_name($s) {
    return preg_match("/^[A-Za-z0-9 .'\-]+$/", $s) ? $s : '=?UTF-8?B?' . base64_encode($s) . '?=';
}

// A MIME message with a plain-text and an HTML part, both base64 encoded
function mail_build_message($toEmail, $toName, $subject, $html, $text) {
    $boundary = 'cb_' . bin2hex(random_bytes(12));
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'CampusBite';
    $domain = substr(strrchr(MAIL_FROM, '@'), 1) ?: 'localhost';
    $headers = [
        'Date: ' . date('r'),
        'From: ' . mail_encode_name($fromName) . ' <' . MAIL_FROM . '>',
        'To: ' . ($toName !== '' ? mail_encode_name($toName) . ' ' : '') . '<' . $toEmail . '>',
        'Subject: ' . mail_encode_header($subject),
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];
    $part = function ($type, $content) use ($boundary) {
        return "--$boundary\r\nContent-Type: $type; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
             . chunk_split(base64_encode($content), 76, "\r\n");
    };
    return implode("\r\n", $headers) . "\r\n\r\n"
         . $part('text/plain', $text) . $part('text/html', $html) . "--$boundary--\r\n";
}

/**
 * A small SMTP client: STARTTLS or SSL, AUTH LOGIN, one recipient. Throws an Exception on any failure.
 */
function smtp_send($toEmail, $toName, $subject, $message) {
    $secure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . SMTP_HOST . ':' . (int)SMTP_PORT;
    $socket = @stream_socket_client($remote, $errno, $errstr, 15);
    if (!$socket) {
        throw new Exception("can't connect to $remote ($errstr)");
    }
    stream_set_timeout($socket, 15);

    $read = function () use ($socket) {
        $reply = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $reply .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $reply;
    };
    // $label is what an error names the command as, so the login and the message itself never end up in the log
    $command = function ($line, $expect, $label = null) use ($socket, $read) {
        if ($line !== null) {
            fwrite($socket, $line . "\r\n");
        }
        $reply = $read();
        if (!in_array((int)substr($reply, 0, 3), (array)$expect, true)) {
            throw new Exception("SMTP said \"" . trim($reply) . "\" to " . ($label ?? (string)$line));
        }
        return $reply;
    };

    try {
        $host = preg_replace('/[^A-Za-z0-9.\-]/', '', parse_url(defined('APP_URL') ? APP_URL : '', PHP_URL_HOST) ?: 'localhost');
        $command(null, 220);
        $command("EHLO $host", 250);
        if ($secure === 'tls') {
            $command('STARTTLS', 220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                throw new Exception('TLS could not be started');
            }
            $command("EHLO $host", 250);
        }
        if (defined('SMTP_USER') && SMTP_USER !== '') {
            $command('AUTH LOGIN', 334);
            $command(base64_encode(SMTP_USER), 334, 'the username');
            $command(base64_encode(SMTP_PASS), 235, 'the password');
        }
        $command('MAIL FROM:<' . MAIL_FROM . '>', 250);
        $command('RCPT TO:<' . $toEmail . '>', [250, 251]);
        $command('DATA', 354);
        // Lines starting with a dot get a second dot, so they aren't read as the end of the message
        $command(preg_replace('/^\./m', '..', $message) . "\r\n.", 250, 'the message');
        // The email is accepted at this point, so a bad reply to QUIT doesn't count as a failure
        fwrite($socket, "QUIT\r\n");
    } finally {
        fclose($socket);
    }
}
