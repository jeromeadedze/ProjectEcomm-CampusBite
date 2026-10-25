<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';

if (is_logged_in()) {
    header("Location: " . $ROOT_DIR . "/index.php");
    exit;
}
?>

<div class="row justify-content-center my-4">
    <div class="col-md-7 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="text-center mb-4">
                <div class="d-inline-flex p-3 rounded-circle bg-light text-dark mb-2 fs-3">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h3 class="fw-bold">Create CampusBite Account</h3>
                <p class="text-muted small">Join Ashesi's fastest campus food ordering platform</p>
            </div>

            <form action="<?php echo $ROOT_DIR; ?>/actions/register_action.php" method="POST">
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-muted">Full Name</label>
                        <input type="text" name="customer_name" class="form-control rounded-pill px-3" placeholder="e.g. Jerome Adedze" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-muted">Email Address (Ashesi preferred)</label>
                        <input type="email" name="customer_email" class="form-control rounded-pill px-3" placeholder="student@ashesi.edu.gh" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Password</label>
                        <input type="password" name="customer_pass" class="form-control rounded-pill px-3" placeholder="Minimum 6 characters" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Phone Number (MTN MoMo / Telecel)</label>
                        <input type="tel" name="customer_contact" class="form-control rounded-pill px-3" placeholder="024XXXXXXX" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-muted">Campus Residence / Hostel (Default drop point)</label>
                        <select name="campus_hostel" class="form-select rounded-pill px-3">
                            <option value="Walter Sisulu Hall">Walter Sisulu Hall (On-Campus)</option>
                            <option value="Wangari Maathai Hall">Wangari Maathai Hall (On-Campus)</option>
                            <option value="Charlotte Court">Charlotte Court (Berekuso)</option>
                            <option value="Masere Hostel">Masere Hostel (Berekuso)</option>
                            <option value="Dufie Hostel">Dufie Hostel (Berekuso)</option>
                            <option value="Engineering & FabLab Complex">Engineering & FabLab Complex</option>
                            <option value="Faculty / Admin Office">Faculty / Admin Office</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="cb-btn-primary btn w-100 py-3 mb-3">
                    <i class="bi bi-person-check-fill me-1"></i> Register Account
                </button>
            </form>

            <div class="text-center small text-muted">
                Already registered? 
                <a href="<?php echo $ROOT_DIR; ?>/views/login.php" class="text-danger fw-semibold">Sign in here</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
