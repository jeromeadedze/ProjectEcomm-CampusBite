<?php
/**
 * Shown once, right after a kitchen login is created or its forgotten password is reset ('reset' => true):
 * the email and starting password to pass on.
 * The details are in the session only until this card has been shown.
 */
$newLogin = $_SESSION['new_login'] ?? null;
unset($_SESSION['new_login']);
if ($newLogin): $isReset = !empty($newLogin['reset']); ?>
<section class="cb-card nl-card" id="new-login-done" aria-labelledby="nl-title">
    <div class="nl-head">
        <span class="nl-icon" aria-hidden="true"><i class="bi <?php echo $isReset ? 'bi-key' : 'bi-person-check'; ?>"></i></span>
        <div>
            <?php if ($isReset): ?>
                <h2 id="nl-title">New password for <?php echo htmlspecialchars($newLogin['name']); ?></h2>
                <p>Give this to them in person or by message. Their old password no longer works. This is the only time the new one is shown here, and they'll choose their own password when they sign in with it.</p>
            <?php else: ?>
                <h2 id="nl-title">Share these sign-in details with <?php echo htmlspecialchars($newLogin['name']); ?></h2>
                <p><?php if (!empty($newLogin['sent_to'])): ?>We've emailed these to <strong><?php echo htmlspecialchars($newLogin['sent_to']); ?></strong>. <?php else: ?>The email to them couldn't be sent, so pass these on yourself. <?php endif; ?>This is the only time the password is shown here. They'll choose their own password the first time they sign in.</p>
            <?php endif; ?>
        </div>
    </div>
    <dl class="nl-creds">
        <div>
            <dt>Email</dt>
            <dd><code id="nl-email"><?php echo htmlspecialchars($newLogin['email']); ?></code><button type="button" class="nl-copy" data-copy="nl-email">Copy</button></dd>
        </div>
        <div>
            <dt><?php echo $isReset ? 'New password' : 'Starting password'; ?></dt>
            <dd><code id="nl-pass"><?php echo htmlspecialchars($newLogin['password']); ?></code><button type="button" class="nl-copy" data-copy="nl-pass">Copy</button></dd>
        </div>
    </dl>
    <button type="button" class="cb-btn cb-btn-quiet nl-copy-all" data-copy-all>Copy both</button>
</section>
<script>
(function () {
    function copy(text, btn) {
        var done = function () { var t = btn.textContent; btn.textContent = 'Copied'; setTimeout(function () { btn.textContent = t; }, 1500); };
        try { navigator.clipboard.writeText(text).then(done, function () {}); } catch (e) {}
    }
    document.querySelectorAll('[data-copy]').forEach(function (b) {
        b.addEventListener('click', function () { copy(document.getElementById(b.dataset.copy).textContent, b); });
    });
    var all = document.querySelector('[data-copy-all]');
    all && all.addEventListener('click', function () {
        copy('CampusBite kitchen sign-in\nEmail: ' + document.getElementById('nl-email').textContent + '\nPassword: ' + document.getElementById('nl-pass').textContent + '\nYou will set your own password when you first sign in.', all);
    });
})();
</script>
<?php endif; ?>
