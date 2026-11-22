<?php
/**
 * One-time messages set by action files ($_SESSION['success'] / $_SESSION['error'], e.g. "Meal saved"),
 * shown at the top of the page under its title, in the same style as a form's own message (form_message_html).
 * When the page opens at a section (#payout, #message-12), js/main.js moves the message into that section.
 */
foreach (['error', 'success'] as $pageMsgType) {
    if (!empty($_SESSION[$pageMsgType])) {
        $isError = $pageMsgType === 'error';
        echo '<p class="cb-form-msg cb-page-msg ' . ($isError ? 'is-error' : 'is-ok') . '" role="' . ($isError ? 'alert' : 'status') . '" data-page-msg>'
           . '<i class="bi ' . ($isError ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill') . '" aria-hidden="true"></i>'
           . '<span>' . htmlspecialchars($_SESSION[$pageMsgType]) . '</span>'
           . '<button type="button" class="cb-page-msg-x" aria-label="Dismiss message" data-page-msg-close><i class="bi bi-x-lg" aria-hidden="true"></i></button></p>';
        unset($_SESSION[$pageMsgType]);
    }
}
