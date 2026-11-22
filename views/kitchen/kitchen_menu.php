<?php
// Kitchen portal: the kitchen's own meals. The kitchen admin adds meals and sets prices;
// staff only see the list (no prices) and mark meals sold out or back in stock.
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

require_kitchen();

$controller = new ProductController();
$kitchenId = get_kitchen_id();
$kitchen = $controller->getBrandById($kitchenId);
$meals = $controller->getProductsForKitchen($kitchenId);
$categories = $controller->getAllCategories();

$canEdit = is_kitchen_admin();

// Only this kitchen's meals can be edited here, and only by its admin
$editMeal = ($canEdit && isset($_GET['edit_id'])) ? $controller->getProductById((int)$_GET['edit_id']) : null;
if ($editMeal && (int)$editMeal['product_brand'] !== $kitchenId) {
    $editMeal = null;
}

$form = $_SESSION['product_form'] ?? null;
unset($_SESSION['product_form']);
// The form stays folded away unless you're editing, asked to add, or a save just failed (so nothing typed is lost)
$formOpen = $canEdit && ($editMeal || $form !== null || isset($_GET['new']));
// Meals that were ever ordered can't be deleted (past orders point at them), so Delete only shows for the rest
$editSold = 0;
foreach ($meals as $m) {
    if ($editMeal && (int)$m['product_id'] === (int)$editMeal['product_id']) {
        $editSold = (int)$m['meals_sold'];
    }
}
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

<?php if (!$canEdit): ?>
    <p class="cb-sub">Mark a meal sold out the moment it runs out, so students can't order it. Your kitchen admin adds meals and sets prices.</p>
<?php else: ?>
<?php $editPhoto = $editMeal && !empty($editMeal['product_image']) && is_file(__DIR__ . '/../../images/products/' . $editMeal['product_image'])
    ? $ROOT_DIR . '/images/products/' . rawurlencode($editMeal['product_image']) : null; ?>
<div class="mf-bar">
    <p class="cb-sub mf-sub"><?php echo count($meals); ?> meal<?php echo count($meals) === 1 ? '' : 's'; ?> on your menu</p>
    <button type="button" class="cb-btn" id="mf-open" aria-expanded="<?php echo $formOpen ? 'true' : 'false'; ?>" aria-controls="mf-card" <?php echo $formOpen ? 'hidden' : ''; ?>><i class="bi bi-plus-lg" aria-hidden="true"></i> Add a meal</button>
</div>

<section class="cb-card mf-card" id="mf-card" aria-labelledby="mf-title" <?php echo $formOpen ? '' : 'hidden'; ?>>
    <div class="mf-head">
        <div>
            <h2 id="mf-title"><?php echo $editMeal ? 'Edit ' . htmlspecialchars($editMeal['product_title']) : 'Add a meal'; ?></h2>
            <p class="mf-lead"><?php echo $editMeal ? 'New prices apply to new orders only. Past orders keep what the student paid.' : 'It shows on the CampusBite menu as soon as you save.'; ?></p>
        </div>
    </div>
    <form class="mf-form" action="<?php echo $ROOT_DIR; ?>/actions/kitchen_save_meal_action.php" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <?php if ($editMeal): ?>
            <input type="hidden" name="product_id" value="<?php echo (int)$editMeal['product_id']; ?>">
        <?php endif; ?>

        <div class="mf-photo">
            <span class="mf-label">Photo</span>
            <label class="mf-photo-box" for="product_image">
                <span class="mf-photo-img" id="mf-photo-img"><?php if ($editPhoto): ?><img src="<?php echo htmlspecialchars($editPhoto); ?>" alt=""><?php else: ?><i class="bi bi-image" aria-hidden="true"></i><?php endif; ?></span>
                <span class="mf-photo-btn"><i class="bi bi-upload" aria-hidden="true"></i> <?php echo $editPhoto ? 'Change photo' : 'Upload photo'; ?></span>
            </label>
            <input type="file" id="product_image" name="product_image" class="mf-file" accept="image/jpeg,image/png,image/webp,image/gif" aria-describedby="image_hint">
            <p class="cb-field-hint" id="image_hint">JPG, PNG, WebP or GIF, up to 2 MB.<?php echo $editPhoto ? ' Leave it to keep the current photo.' : ' Meals with a photo sell better.'; ?></p>
        </div>

        <div class="mf-main">
            <fieldset class="mf-section">
                <legend>Basics</legend>
                <div class="cb-field mf-wide">
                    <label for="product_title">Meal name</label>
                    <input type="text" id="product_title" name="product_title" maxlength="200" value="<?php echo htmlspecialchars($form['product_title']); ?>" placeholder="Jollof rice with grilled chicken" required>
                </div>
                <div class="cb-field">
                    <label for="product_price">Price</label>
                    <div class="mf-affix"><span>GH₵</span><input type="number" id="product_price" name="product_price" min="0.01" max="<?php echo ProductController::MAX_PRICE; ?>" step="0.01" inputmode="decimal" value="<?php echo htmlspecialchars((string)$form['product_price']); ?>" placeholder="30.00" required></div>
                </div>
                <div class="cb-field">
                    <label for="prep_time">Prep time</label>
                    <div class="mf-affix mf-affix-end"><input type="number" id="prep_time" name="prep_time" min="1" max="<?php echo ProductController::MAX_PREP_MINUTES; ?>" step="1" inputmode="numeric" value="<?php echo htmlspecialchars((string)$form['prep_time']); ?>" required><span>min</span></div>
                </div>
                <div class="cb-field mf-wide">
                    <label for="product_cat">Category</label>
                    <select id="product_cat" name="product_cat" required>
                        <option value="" disabled <?php echo $form['product_cat'] === '' ? 'selected' : ''; ?>>Choose a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int)$category['cat_id']; ?>" <?php echo (string)$form['product_cat'] === (string)$category['cat_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['cat_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </fieldset>

            <fieldset class="mf-section">
                <legend>Details <span>optional</span></legend>
                <div class="cb-field mf-wide">
                    <label for="product_desc">Description</label>
                    <textarea id="product_desc" name="product_desc" rows="3" maxlength="2000" placeholder="What's on the plate: sides, sauces, portion size"><?php echo htmlspecialchars($form['product_desc']); ?></textarea>
                </div>
                <div class="cb-field mf-wide">
                    <label for="product_keywords">Search words</label>
                    <input type="text" id="product_keywords" name="product_keywords" maxlength="200" value="<?php echo htmlspecialchars($form['product_keywords']); ?>" placeholder="chicken, spicy, rice" aria-describedby="kw_hint">
                    <p class="cb-field-hint" id="kw_hint">Words students might type to find it.</p>
                </div>
            </fieldset>
        </div>

        <div class="mf-foot">
            <label class="mf-avail">
                <input type="hidden" name="is_available" value="0">
                <input type="checkbox" name="is_available" value="1" <?php echo (int)$form['is_available'] === 1 ? 'checked' : ''; ?>>
                <span class="mf-switch" aria-hidden="true"><span></span></span>
                <span><b>Available to order</b><small>Switch off if it's sold out today</small></span>
            </label>
            <div class="mf-actions">
                <?php if ($editMeal && $editSold === 0): ?>
                    <button type="submit" class="kc-delete" form="kc-delete-form">Delete meal</button>
                <?php endif; ?>
                <?php if ($editMeal): ?>
                    <a class="cb-btn cb-btn-quiet" href="kitchen_menu.php">Cancel</a>
                <?php else: ?>
                    <button type="button" class="cb-btn cb-btn-quiet" id="mf-cancel">Cancel</button>
                <?php endif; ?>
                <button type="submit" class="cb-btn"><?php echo $editMeal ? 'Save changes' : 'Add to menu'; ?></button>
            </div>
        </div>
    </form>
    <?php if ($editMeal && $editSold === 0): ?>
        <form id="kc-delete-form" action="<?php echo $ROOT_DIR; ?>/actions/delete_product_action.php" method="POST" data-confirm="Delete <?php echo htmlspecialchars($editMeal['product_title']); ?>? This can't be undone.">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="product_id" value="<?php echo (int)$editMeal['product_id']; ?>">
        </form>
    <?php endif; ?>
</section>
<script>
(function () {
    var card = document.getElementById('mf-card'), openBtn = document.getElementById('mf-open'), cancel = document.getElementById('mf-cancel');
    openBtn.addEventListener('click', function () {
        card.hidden = false; openBtn.hidden = true; openBtn.setAttribute('aria-expanded', 'true');
        document.getElementById('product_title').focus();
    });
    if (cancel) {
        cancel.addEventListener('click', function () {
            card.querySelector('form').reset();
            card.hidden = true; openBtn.hidden = false; openBtn.setAttribute('aria-expanded', 'false'); openBtn.focus();
        });
    }
    // Show the chosen photo before saving
    document.getElementById('product_image').addEventListener('change', function () {
        var f = this.files && this.files[0], box = document.getElementById('mf-photo-img');
        if (!f) return;
        if (f.size > 2097152) { box.innerHTML = '<small class="mf-too-big">That photo is over 2 MB. Choose a smaller one.</small>'; this.value = ''; return; }
        var r = new FileReader();
        r.onload = function () { box.innerHTML = '<img src="' + r.result + '" alt="">'; };
        r.readAsDataURL(f);
    });
})();
</script>
<?php endif; ?>

<?php
// Available meals first, then sold out, each alphabetically
usort($meals, function ($a, $b) { return [(int)$b['is_available'], $a['product_title']] <=> [(int)$a['is_available'], $b['product_title']]; });
$noPhoto = count(array_filter($meals, function ($m) { return empty($m['product_image']); }));
?>
<div class="cb-row kc-row"><h2>Your meals <span class="kc-count"><?php echo count($meals); ?></span></h2></div>
<?php if ($canEdit && $noPhoto > 0): ?>
    <p class="cb-warn-note"><i class="bi bi-camera" aria-hidden="true"></i> <?php echo $noPhoto === count($meals) ? 'None of your meals have' : $noPhoto . ' of your meals ' . ($noPhoto === 1 ? 'has' : 'have'); ?> a photo yet, so students only see the category icon. Choose the pencil on a meal to add one.</p>
<?php endif; ?>

<?php if (empty($meals)): ?>
    <p class="cb-empty"><?php echo $canEdit ? 'No meals yet. Choose "Add a meal" to put your first one on the menu.' : 'No meals on the menu yet. Your kitchen admin adds them.'; ?></p>
<?php else: ?>
    <div class="cb-grid kc-grid">
        <?php foreach ($meals as $meal): ?>
            <?php
            $available = (int)$meal['is_available'] === 1;
            $mealImage = !empty($meal['product_image']) && file_exists(__DIR__ . '/../../images/products/' . $meal['product_image'])
                ? $ROOT_DIR . '/images/products/' . rawurlencode($meal['product_image']) : null;
            $editing = $editMeal && (int)$editMeal['product_id'] === (int)$meal['product_id'];
            ?>
            <article class="cb-dish kc-card<?php echo $available ? '' : ' is-off'; ?><?php echo $editing ? ' is-editing' : ''; ?>">
                <?php if ($canEdit): ?>
                    <a class="kc-edit" href="kitchen_menu.php?edit_id=<?php echo (int)$meal['product_id']; ?>" aria-label="Edit <?php echo htmlspecialchars($meal['product_title']); ?>" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                <?php endif; ?>
                <?php if (!$available): ?><span class="kc-off">Sold out</span><?php endif; ?>
                <span class="cb-dish-img">
                    <?php if ($mealImage): ?>
                        <img src="<?php echo htmlspecialchars($mealImage); ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <span class="cb-dish-emoji" aria-hidden="true"><i class="bi <?php echo meal_icon($meal); ?>"></i></span>
                    <?php endif; ?>
                </span>
                <span class="cb-vchip"><?php echo htmlspecialchars($meal['cat_name']); ?></span>
                <p class="cb-dish-name"><?php echo htmlspecialchars($meal['product_title']); ?></p>
                <p class="kc-meta"><?php echo (int)$meal['prep_time_minutes']; ?> min · <?php echo number_format((int)$meal['meals_sold']); ?> sold<?php if (empty($meal['product_image'])): ?> · <span class="kc-nophoto">no photo</span><?php endif; ?></p>
                <div class="cb-dish-foot">
                    <?php if ($canEdit): ?><span class="cb-price">GH₵ <?php echo number_format($meal['product_price'], 2); ?></span><?php endif; ?>
                    <form action="<?php echo $ROOT_DIR; ?>/actions/kitchen_toggle_meal_action.php" method="POST" class="kc-toggle-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="product_id" value="<?php echo (int)$meal['product_id']; ?>">
                        <button type="submit" class="kc-toggle" role="switch" aria-checked="<?php echo $available ? 'true' : 'false'; ?>" aria-label="<?php echo htmlspecialchars($meal['product_title']) . ($available ? ' is available. Mark it sold out' : ' is sold out. Mark it available'); ?>">
                            <span class="mf-switch<?php echo $available ? ' is-on' : ''; ?>" aria-hidden="true"><span></span></span>
                            <span class="<?php echo $canEdit ? 'visually-hidden' : ''; ?>"><?php echo $available ? 'Available' : 'Sold out'; ?></span>
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
