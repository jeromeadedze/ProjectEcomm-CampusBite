<?php
// Kitchen portal: the kitchen's own meals, prices and availability
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

require_kitchen();

$controller = new ProductController();
$kitchenId = get_kitchen_id();
$kitchen = $controller->getBrandById($kitchenId);
$meals = $controller->getProductsForKitchen($kitchenId);
$categories = $controller->getAllCategories();

// Only this kitchen's meals can be edited here
$editMeal = isset($_GET['edit_id']) ? $controller->getProductById((int)$_GET['edit_id']) : null;
if ($editMeal && (int)$editMeal['product_brand'] !== $kitchenId) {
    $editMeal = null;
}

$form = $_SESSION['product_form'] ?? null;
unset($_SESSION['product_form']);
if (!$form) {
    $form = $editMeal
        ? [
            'product_title' => $editMeal['product_title'],
            'product_price' => $editMeal['product_price'],
            'product_cat' => $editMeal['product_cat'],
            'product_desc' => $editMeal['product_desc'] ?? '',
            'product_keywords' => $editMeal['product_keywords'] ?? '',
            'prep_time' => $editMeal['prep_time_minutes'],
            'is_available' => (int)$editMeal['is_available'],
        ]
        : ['product_title' => '', 'product_price' => '', 'product_cat' => '', 'product_desc' => '', 'product_keywords' => '', 'prep_time' => 15, 'is_available' => 1];
}

$pageHeading = $kitchen['brand_name'] . ' menu';
require_once __DIR__ . '/../layout/header.php';
?>

<section class="cb-card">
    <h2><?php echo $editMeal ? 'Edit ' . htmlspecialchars($editMeal['product_title']) : 'Add a meal'; ?></h2>
    <?php if ($editMeal): ?>
        <p class="cb-card-lead">New prices apply to new orders only. Past orders keep the price the student paid.</p>
    <?php endif; ?>
    <form class="cb-admin-form" action="<?php echo $ROOT_DIR; ?>/actions/kitchen_save_meal_action.php" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <?php if ($editMeal): ?>
            <input type="hidden" name="product_id" value="<?php echo (int)$editMeal['product_id']; ?>">
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
            <label for="product_cat">Category</label>
            <select id="product_cat" name="product_cat" required>
                <option value="" disabled <?php echo $form['product_cat'] === '' ? 'selected' : ''; ?>>Choose a category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo (int)$category['cat_id']; ?>" <?php echo (string)$form['product_cat'] === (string)$category['cat_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['cat_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cb-field">
            <label for="prep_time">Prep time (minutes)</label>
            <input type="number" id="prep_time" name="prep_time" min="1" max="<?php echo ProductController::MAX_PREP_MINUTES; ?>" step="1" value="<?php echo htmlspecialchars((string)$form['prep_time']); ?>" required>
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
            <label for="is_available">Availability</label>
            <select id="is_available" name="is_available">
                <option value="1" <?php echo (int)$form['is_available'] === 1 ? 'selected' : ''; ?>>Available</option>
                <option value="0" <?php echo (int)$form['is_available'] === 0 ? 'selected' : ''; ?>>Sold out</option>
            </select>
        </div>
        <div class="cb-field cb-field-full">
            <label for="product_image">Photo</label>
            <input type="file" id="product_image" name="product_image" accept="image/jpeg,image/png,image/webp,image/gif" aria-describedby="image_hint">
            <p class="cb-field-hint" id="image_hint">JPG, PNG, WebP or GIF, up to 2 MB.<?php echo $editMeal && !empty($editMeal['product_image']) ? ' Leave empty to keep the current photo.' : ''; ?></p>
        </div>
        <div class="cb-admin-actions">
            <button type="submit" class="cb-btn"><?php echo $editMeal ? 'Save changes' : 'Add meal'; ?></button>
            <?php if ($editMeal): ?><a class="cb-btn cb-btn-quiet" href="kitchen_menu.php">Cancel</a><?php endif; ?>
        </div>
    </form>
</section>

<section class="cb-card">
    <h2>Your meals</h2>
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead>
                <tr><th>Meal</th><th>Category</th><th class="num">Price</th><th class="num">Prep</th><th class="num">Sold</th><th>Status</th><th><span class="visually-hidden">Actions</span></th></tr>
            </thead>
            <tbody>
                <?php if (empty($meals)): ?>
                    <tr><td colspan="7" class="cb-table-empty">No meals yet. Add your first one above.</td></tr>
                <?php endif; ?>
                <?php foreach ($meals as $meal): ?>
                    <?php $available = (int)$meal['is_available'] === 1; ?>
                    <tr <?php echo $editMeal && (int)$editMeal['product_id'] === (int)$meal['product_id'] ? 'class="is-editing"' : ''; ?>>
                        <td class="cb-table-name"><?php echo htmlspecialchars($meal['product_title']); ?></td>
                        <td><?php echo htmlspecialchars($meal['cat_name']); ?></td>
                        <td class="num">GH₵ <?php echo number_format($meal['product_price'], 2); ?></td>
                        <td class="num"><?php echo (int)$meal['prep_time_minutes']; ?> min</td>
                        <td class="num"><?php echo (int)$meal['meals_sold']; ?></td>
                        <td><span class="cb-dot-status <?php echo $available ? '' : 'off'; ?>"><?php echo $available ? 'Available' : 'Sold out'; ?></span></td>
                        <td>
                            <div class="cb-table-actions">
                                <a href="kitchen_menu.php?edit_id=<?php echo (int)$meal['product_id']; ?>" aria-label="Edit <?php echo htmlspecialchars($meal['product_title']); ?>">Edit</a>
                                <form action="<?php echo $ROOT_DIR; ?>/actions/kitchen_toggle_meal_action.php" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="product_id" value="<?php echo (int)$meal['product_id']; ?>">
                                    <button type="submit"><?php echo $available ? 'Mark sold out' : 'Back in stock'; ?></button>
                                </form>
                                <?php if ((int)$meal['meals_sold'] === 0): ?>
                                    <form action="<?php echo $ROOT_DIR; ?>/actions/delete_product_action.php" method="POST" data-confirm="Delete <?php echo htmlspecialchars($meal['product_title']); ?>? This can't be undone.">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo (int)$meal['product_id']; ?>">
                                        <button type="submit" aria-label="Delete <?php echo htmlspecialchars($meal['product_title']); ?>">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
