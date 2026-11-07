<?php
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

require_admin();

$controller = new ProductController();
$products = $controller->getProductsForAdmin();
$brands = $controller->getBrandsForAdmin();
$categories = $controller->getAllCategories();

$editProduct = isset($_GET['edit_id']) ? $controller->getProductById((int)$_GET['edit_id']) : null;

// A rejected submission comes back with what was typed; otherwise show the saved meal or the defaults
$form = $_SESSION['product_form'] ?? null;
unset($_SESSION['product_form']);

if (!$form) {
    $form = $editProduct
        ? [
            'product_title' => $editProduct['product_title'],
            'product_price' => $editProduct['product_price'],
            'product_brand' => $editProduct['product_brand'],
            'product_cat' => $editProduct['product_cat'],
            'product_desc' => $editProduct['product_desc'] ?? '',
            'product_keywords' => $editProduct['product_keywords'] ?? '',
            'prep_time' => $editProduct['prep_time_minutes'],
            'is_available' => (int)$editProduct['is_available'],
        ]
        : ['product_title' => '', 'product_price' => '', 'product_brand' => '', 'product_cat' => '', 'product_desc' => '', 'product_keywords' => '', 'prep_time' => 15, 'is_available' => 1];
}

require_once __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/admin_nav.php';
?>

<section class="cb-card">
    <h2><?php echo $editProduct ? 'Edit ' . htmlspecialchars($editProduct['product_title']) : 'Add a meal'; ?></h2>
    <?php if (empty($brands) || empty($categories)): ?>
        <p class="cb-card-lead">Add at least one <a href="brand.php">kitchen</a> and one <a href="category.php">category</a> before adding meals.</p>
    <?php else: ?>
    <form class="cb-admin-form" action="<?php echo $ROOT_DIR; ?>/actions/<?php echo $editProduct ? 'update_product_action.php' : 'add_product_action.php'; ?>" method="POST" enctype="multipart/form-data">
        <?php if ($editProduct): ?>
            <input type="hidden" name="product_id" value="<?php echo (int)$editProduct['product_id']; ?>">
        <?php endif; ?>
        <div class="cb-field">
            <label for="product_title">Meal name</label>
            <input type="text" id="product_title" name="product_title" maxlength="200" value="<?php echo htmlspecialchars($form['product_title']); ?>" placeholder="Fried yam and fish" required>
        </div>
        <div class="cb-field">
            <label for="product_price">Price (GH₵)</label>
            <input type="number" id="product_price" name="product_price" min="0.01" max="<?php echo ProductController::MAX_PRICE; ?>" step="0.01" value="<?php echo htmlspecialchars((string)$form['product_price']); ?>" placeholder="30.00" required>
        </div>
        <div class="cb-field">
            <label for="product_brand">Kitchen</label>
            <select id="product_brand" name="product_brand" required>
                <option value="" disabled <?php echo $form['product_brand'] === '' ? 'selected' : ''; ?>>Choose a kitchen</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?php echo (int)$brand['brand_id']; ?>" <?php echo (string)$form['product_brand'] === (string)$brand['brand_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($brand['brand_name'] . ($brand['is_active'] ? '' : ' (paused)')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cb-field">
            <label for="product_cat">Category</label>
            <select id="product_cat" name="product_cat" required>
                <option value="" disabled <?php echo $form['product_cat'] === '' ? 'selected' : ''; ?>>Choose a category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo (int)$category['cat_id']; ?>" <?php echo (string)$form['product_cat'] === (string)$category['cat_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['cat_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cb-field cb-field-full">
            <label for="product_desc">Description</label>
            <textarea id="product_desc" name="product_desc" rows="3" maxlength="2000" placeholder="Ingredients, sides and sauces included"><?php echo htmlspecialchars($form['product_desc']); ?></textarea>
        </div>
        <div class="cb-field">
            <label for="product_keywords">Search keywords</label>
            <input type="text" id="product_keywords" name="product_keywords" maxlength="200" value="<?php echo htmlspecialchars($form['product_keywords']); ?>" placeholder="yam, fish, spicy">
        </div>
        <div class="cb-field">
            <label for="prep_time">Prep time (minutes)</label>
            <input type="number" id="prep_time" name="prep_time" min="1" max="<?php echo ProductController::MAX_PREP_MINUTES; ?>" step="1" value="<?php echo htmlspecialchars((string)$form['prep_time']); ?>" required>
        </div>
        <div class="cb-field">
            <label for="product_image">Photo</label>
            <input type="file" id="product_image" name="product_image" accept="image/jpeg,image/png,image/webp,image/gif" aria-describedby="image_hint">
            <p class="cb-field-hint" id="image_hint">
                JPG, PNG, WebP or GIF, up to 2 MB.<?php echo $editProduct && !empty($editProduct['product_image']) ? ' Leave empty to keep the current photo.' : ''; ?>
            </p>
        </div>
        <div class="cb-field">
            <label for="is_available">Availability</label>
            <select id="is_available" name="is_available" aria-describedby="availability_hint">
                <option value="1" <?php echo (int)$form['is_available'] === 1 ? 'selected' : ''; ?>>Available</option>
                <option value="0" <?php echo (int)$form['is_available'] === 0 ? 'selected' : ''; ?>>Sold out</option>
            </select>
            <p class="cb-field-hint" id="availability_hint">Sold-out meals are hidden from shoppers.</p>
        </div>
        <div class="cb-admin-actions">
            <button type="submit" class="cb-btn"><?php echo $editProduct ? 'Save changes' : 'Add meal'; ?></button>
            <?php if ($editProduct): ?>
                <a class="cb-btn cb-btn-quiet" href="product.php">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
</section>

<section class="cb-card">
    <h2>All meals</h2>
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead>
                <tr>
                    <th>Meal</th>
                    <th>Kitchen</th>
                    <th>Category</th>
                    <th class="num">Price</th>
                    <th class="num">Prep</th>
                    <th>Status</th>
                    <th><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="7" class="cb-table-empty">No meals yet. Add the first one above.</td></tr>
                <?php endif; ?>
                <?php foreach ($products as $product): ?>
                    <?php $isEditing = $editProduct && (int)$editProduct['product_id'] === (int)$product['product_id']; ?>
                    <tr <?php echo $isEditing ? 'class="is-editing"' : ''; ?>>
                        <td class="cb-table-name">
                            <?php echo htmlspecialchars($product['product_title']); ?>
                            <?php if ((int)$product['order_count'] > 0): ?>
                                <span class="cb-table-sub">In <?php echo (int)$product['order_count']; ?> past order<?php echo (int)$product['order_count'] === 1 ? '' : 's'; ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($product['brand_name']); ?>
                            <?php if (!$product['brand_active']): ?><span class="cb-table-sub">Paused</span><?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['cat_name']); ?></td>
                        <td class="num">GH₵ <?php echo number_format($product['product_price'], 2); ?></td>
                        <td class="num"><?php echo (int)$product['prep_time_minutes']; ?> min</td>
                        <td>
                            <span class="cb-dot-status <?php echo $product['is_available'] ? '' : 'off'; ?>"><?php echo $product['is_available'] ? 'Available' : 'Sold out'; ?></span>
                        </td>
                        <td>
                            <div class="cb-table-actions">
                                <a href="product.php?edit_id=<?php echo (int)$product['product_id']; ?>" aria-label="Edit <?php echo htmlspecialchars($product['product_title']); ?>">Edit</a>
                                <form action="<?php echo $ROOT_DIR; ?>/actions/delete_product_action.php" method="POST" <?php if ((int)$product['order_count'] === 0): ?>data-confirm="Delete <?php echo htmlspecialchars($product['product_title']); ?>? This can't be undone."<?php endif; ?>>
                                    <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">
                                    <button type="submit" aria-label="Delete <?php echo htmlspecialchars($product['product_title']); ?>">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
