<?php
// Copy this file to core/mail_config.php and fill it in. core/mail_config.php is git-ignored: never commit a real password.
//
// MAIL_MODE 'log'  : nothing is sent. Each email is saved as an HTML file in storage/mail/, and on localhost the
//                    forgot-password page shows the reset link on screen. This is also what happens with no config file.
// MAIL_MODE 'smtp' : emails are sent through the SMTP server below. For Gmail, turn on 2-step verification and create
//                    an app password (Google Account > Security > App passwords); use smtp.gmail.com, port 587, 'tls'.

define("MAIL_MODE", "log");

// Automatically detects whether running locally or on the live Ashesi CS server.
// No trailing slash. Links inside emails (order receipts, password resets, etc.) use this.
$__host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$__is_local = preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/', $__host);

if ($__is_local) {
    define("APP_URL", "http://localhost:8000");
} else {
    // Ashesi CS server (Linux is case-sensitive: ensure casing matches the folder on the server)
    define("APP_URL", "http://169.239.251.102:442/~jerome.adedze/e-commerce-class/CampusBite");
}

define("MAIL_FROM", "no-reply@example.com");
define("MAIL_FROM_NAME", "CampusBite");

define("SMTP_HOST", "smtp.gmail.com");
define("SMTP_PORT", 587);
define("SMTP_SECURE", "tls");   // 'tls' (STARTTLS, usually port 587), 'ssl' (usually port 465), or 'none' for a local test server such as Mailpit
define("SMTP_USER", "you@example.com");
define("SMTP_PASS", "your-app-password");
