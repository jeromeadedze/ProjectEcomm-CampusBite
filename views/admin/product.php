<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '../..';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

require_admin();

$prodCtrl = new ProductController();
$products = $prodCtrl->getAllProducts();
$brands = $prodCtrl->getAllBrands();
$categories = $prodCtrl->getAllCategories();
?>

<div class="my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold m-0"><i class="bi bi-egg-fried text-warning me-2"></i>Campus Meals Management</h3>
            <span class="text-muted small">Manage cafeteria & vendor menu items</span>
        </div>
        <button class="cb-btn-primary btn" data-bs-toggle="modal" data-bs-target="#addMealModal">
            <i class="bi bi-plus-lg me-1"></i> Add New Meal
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <table class="cb-table mb-0">
            <thead>
                <tr>
                    <th>Meal Title</th>
                    <th>Kitchen / Brand</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Prep Time</th>
                    <th>Availability</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td class="fw-bold"><?php echo htmlspecialchars($p['product_title']); ?></td>
                        <td>
                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($p['brand_name']); ?></span>
                            <small class="text-muted ms-1"><?php echo htmlspecialchars($p['zone'] ?? ''); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($p['cat_name']); ?></td>
                        <td class="fw-bold text-danger">GH₵ <?php echo number_format($p['product_price'], 2); ?></td>
                        <td>~<?php echo $p['prep_time_minutes'] ?? 15; ?> mins</td>
                        <td>
                            <?php if ($p['is_available']): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Available</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Sold Out</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo $ROOT_DIR; ?>/actions/delete_product_action.php?id=<?php echo $p['product_id']; ?>" 
                               class="btn btn-sm btn-outline-danger rounded-circle" 
                               onclick="return confirm('Are you sure you want to remove this meal from the menu?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Meal Modal -->
<div class="modal fade" id="addMealModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Add New Campus Meal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo $ROOT_DIR; ?>/actions/add_product_action.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Meal Title</label>
                            <input type="text" name="product_title" class="form-control rounded-pill" placeholder="e.g. Hallmark Special Fried Yam + Fish" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Price (GH₵)</label>
                            <input type="number" step="0.5" name="product_price" class="form-control rounded-pill" placeholder="30.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kitchen / Vendor</label>
                            <select name="product_brand" class="form-select rounded-pill" required>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?php echo $b['brand_id']; ?>"><?php echo htmlspecialchars($b['brand_name']); ?> (<?php echo $b['zone']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Food Category</label>
                            <select name="product_cat" class="form-select rounded-pill" required>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo $c['cat_id']; ?>"><?php echo htmlspecialchars($c['cat_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description</label>
                            <textarea name="product_desc" class="form-control rounded-4" rows="3" placeholder="Ingredients, sides, sauces included..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Search Keywords</label>
                            <input type="text" name="product_keywords" class="form-control rounded-pill" placeholder="yam, fish, hallmark, spicy">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Estimated Prep Time (Mins)</label>
                            <input type="number" name="prep_time" class="form-control rounded-pill" value="15">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Meal Image</label>
                            <input type="file" name="product_image" class="form-control rounded-pill" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="cb-btn-primary btn px-4">Save Meal to Menu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
