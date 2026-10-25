<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '../..';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

require_admin();

$prodCtrl = new ProductController();
$categories = $prodCtrl->getAllCategories();
?>

<div class="my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold m-0"><i class="bi bi-grid-fill text-warning me-2"></i>Food Categories</h3>
            <span class="text-muted small">Organize menu classification</span>
        </div>
        <button class="cb-btn-primary btn" data-bs-toggle="modal" data-bs-target="#addCatModal">
            <i class="bi bi-plus-lg me-1"></i> Add Category
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <table class="cb-table mb-0">
            <thead>
                <tr>
                    <th>Category ID</th>
                    <th>Category Name</th>
                    <th>Icon Class</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td class="fw-bold">#<?php echo $c['cat_id']; ?></td>
                        <td class="fw-semibold">
                            <i class="bi <?php echo htmlspecialchars($c['cat_icon'] ?? 'bi-tag'); ?> text-primary me-2"></i>
                            <?php echo htmlspecialchars($c['cat_name']); ?>
                        </td>
                        <td><code><?php echo htmlspecialchars($c['cat_icon'] ?? 'bi-tag'); ?></code></td>
                        <td>
                            <a href="<?php echo $ROOT_DIR; ?>/actions/delete_category_action.php?id=<?php echo $c['cat_id']; ?>" 
                               class="btn btn-sm btn-outline-danger rounded-circle"
                               onclick="return confirm('Delete this category?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Add Food Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo $ROOT_DIR; ?>/actions/add_category_action.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category Name</label>
                        <input type="text" name="cat_name" class="form-control rounded-pill" placeholder="e.g. Rice & Bowls, Local Dishes, Pastries" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Bootstrap Icon Class</label>
                        <input type="text" name="cat_icon" class="form-control rounded-pill" placeholder="e.g. bi-egg-fried, bi-fire, bi-cup-straw" value="bi-egg-fried">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="cb-btn-primary btn px-4">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
