<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '../..';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

require_admin();

$prodCtrl = new ProductController();
$brands = $prodCtrl->getAllBrands();
?>

<div class="my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold m-0"><i class="bi bi-shop text-warning me-2"></i>Partner Kitchens & Vendors</h3>
            <span class="text-muted small">Manage Ashesi on-campus & Berekuso corridor food partners</span>
        </div>
        <button class="cb-btn-primary btn" data-bs-toggle="modal" data-bs-target="#addBrandModal">
            <i class="bi bi-plus-lg me-1"></i> Add Kitchen
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <table class="cb-table mb-0">
            <thead>
                <tr>
                    <th>Kitchen / Brand</th>
                    <th>Delivery Zone</th>
                    <th>Location Details</th>
                    <th>Commission</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($brands as $b): ?>
                    <tr>
                        <td class="fw-bold fs-6">
                            <i class="bi bi-fire text-warning me-2"></i><?php echo htmlspecialchars($b['brand_name']); ?>
                        </td>
                        <td>
                            <span class="badge <?php echo ($b['zone'] === 'Zone B') ? 'bg-warning text-dark' : 'bg-success text-white'; ?> rounded-pill">
                                <?php echo htmlspecialchars($b['zone']); ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?php echo htmlspecialchars($b['location_desc'] ?? 'Campus Ground'); ?></td>
                        <td class="fw-semibold"><?php echo ((float)$b['commission_rate'] * 100); ?>%</td>
                        <td>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Active</span>
                        </td>
                        <td>
                            <a href="<?php echo $ROOT_DIR; ?>/actions/delete_brand_action.php?id=<?php echo $b['brand_id']; ?>" 
                               class="btn btn-sm btn-outline-danger rounded-circle"
                               onclick="return confirm('Delete this kitchen vendor?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Register Kitchen / Food Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo $ROOT_DIR; ?>/actions/add_brand_action.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kitchen Name</label>
                        <input type="text" name="brand_name" class="form-control rounded-pill" placeholder="e.g. Hallmark Cafeteria, Peckish" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Delivery Zone</label>
                        <select name="zone" class="form-select rounded-pill">
                            <option value="Zone A">Zone A (On-Campus - GH₵ 4.00 flat)</option>
                            <option value="Zone B">Zone B (Berekuso Corridor - GH₵ 7.00 flat)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Location / Landmark</label>
                        <input type="text" name="location_desc" class="form-control rounded-pill" placeholder="e.g. Canteen Hall, Ground Floor">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Commission Rate (e.g. 0.08 for 8%, 0.10 for 10%)</label>
                        <input type="number" step="0.01" name="commission_rate" class="form-control rounded-pill" value="0.10" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="cb-btn-primary btn px-4">Save Kitchen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
