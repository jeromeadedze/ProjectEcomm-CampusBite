<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';

if (is_logged_in()) {
    header("Location: " . $ROOT_DIR . "/index.php");
    exit;
}
?>

<div class="row justify-content-center my-5">
    <div class="col-md-5 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="text-center mb-4">
                <div class="d-inline-flex p-3 rounded-circle bg-warning-subtle text-warning-emphasis mb-2 fs-3">
                    <i class="bi bi-person-fill"></i>
                </div>
                <h3 class="fw-bold">Welcome Back</h3>
                <p class="text-muted small">Sign in to order hot food across Ashesi campus</p>
            </div>

            <form action="<?php echo $ROOT_DIR; ?>/actions/login_action.php" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Ashesi Email Address</label>
                    <input type="email" name="customer_email" class="form-control rounded-pill px-3" placeholder="name@ashesi.edu.gh" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-muted">Password</label>
                    <input type="password" name="customer_pass" class="form-control rounded-pill px-3" placeholder="••••••••" required>
                </div>
                <button type="submit" class="cb-btn-primary btn w-100 py-2 mb-3">
                    Sign In
                </button>
            </form>

            <div class="text-center small text-muted">
                Don't have an account? 
                <a href="<?php echo $ROOT_DIR; ?>/views/register.php" class="text-danger fw-semibold">Sign up</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
