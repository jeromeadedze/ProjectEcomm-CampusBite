<?php
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

require_admin();

$controller = new ProductController();
$categories = $controller->getCategoriesForAdmin();

$editCategory = isset($_GET['edit_id']) ? $controller->getCategoryById((int)$_GET['edit_id']) : null;

// A rejected submission comes back with what was typed; otherwise show the saved category or the defaults
$form = $_SESSION['category_form'] ?? null;
unset($_SESSION['category_form']);

if (!$form) {
    $form = $editCategory
        ? ['cat_name' => $editCategory['cat_name'], 'cat_icon' => $editCategory['cat_icon']]
        : ['cat_name' => '', 'cat_icon' => 'bi-egg-fried'];
}

// Keep an icon that was saved before this list existed selectable
$iconOptions = ProductController::CATEGORY_ICONS;
if (!empty($form['cat_icon']) && !isset($iconOptions[$form['cat_icon']])) {
    $iconOptions[$form['cat_icon']] = 'Current icon';
}

require_once __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/admin_nav.php';
?>

<section class="cb-card">
    <h2><?php echo $editCategory ? 'Edit ' . htmlspecialchars($editCategory['cat_name']) : 'Add a category'; ?></h2>
    <form class="cb-admin-form" action="<?php echo $ROOT_DIR; ?>/actions/<?php echo $editCategory ? 'update_category_action.php' : 'add_category_action.php'; ?>" method="POST">
        <?php if ($editCategory): ?>
            <input type="hidden" name="cat_id" value="<?php echo (int)$editCategory['cat_id']; ?>">
        <?php endif; ?>
        <div class="cb-field">
            <label for="cat_name">Category name</label>
            <input type="text" id="cat_name" name="cat_name" maxlength="100" value="<?php echo htmlspecialchars($form['cat_name']); ?>" placeholder="Rice &amp; Jollof Bowls" required>
        </div>
        <div class="cb-field">
            <label for="cat_icon">Icon</label>
            <select id="cat_icon" name="cat_icon">
                <?php foreach ($iconOptions as $iconValue => $iconLabel): ?>
                    <option value="<?php echo htmlspecialchars($iconValue); ?>" <?php echo $form['cat_icon'] === $iconValue ? 'selected' : ''; ?>><?php echo htmlspecialchars($iconLabel); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cb-admin-actions">
            <button type="submit" class="cb-btn"><?php echo $editCategory ? 'Save changes' : 'Add category'; ?></button>
            <?php if ($editCategory): ?>
                <a class="cb-btn cb-btn-quiet" href="category.php">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="cb-card">
    <h2>All categories</h2>
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="num">Menu items</th>
                    <th><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="3" class="cb-table-empty">No categories yet. Add the first one above.</td></tr>
                <?php endif; ?>
                <?php foreach ($categories as $category): ?>
                    <?php $isEditing = $editCategory && (int)$editCategory['cat_id'] === (int)$category['cat_id']; ?>
                    <tr <?php echo $isEditing ? 'class="is-editing"' : ''; ?>>
                        <td class="cb-table-name">
                            <i class="bi <?php echo htmlspecialchars($category['cat_icon'] ?? 'bi-egg-fried'); ?>" aria-hidden="true"></i><?php echo htmlspecialchars($category['cat_name']); ?>
                        </td>
                        <td class="num"><?php echo (int)$category['product_count']; ?></td>
                        <td>
                            <div class="cb-table-actions">
                                <a href="category.php?edit_id=<?php echo (int)$category['cat_id']; ?>" aria-label="Edit <?php echo htmlspecialchars($category['cat_name']); ?>">Edit</a>
                                <form action="<?php echo $ROOT_DIR; ?>/actions/delete_category_action.php" method="POST" <?php if ((int)$category['product_count'] === 0): ?>data-confirm="Delete <?php echo htmlspecialchars($category['cat_name']); ?>? This can't be undone."<?php endif; ?>>
                                    <input type="hidden" name="cat_id" value="<?php echo (int)$category['cat_id']; ?>">
                                    <button type="submit" aria-label="Delete <?php echo htmlspecialchars($category['cat_name']); ?>">Delete</button>
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
