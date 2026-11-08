<?php
// Meals the shopper saved with the heart on a dish card
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/FavoriteController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';

require_login();
if (is_admin()) {
    header("Location: " . $ROOT_DIR . "/views/admin/product.php");
    exit;
}

$productCtrl = new ProductController();
$saved = (new FavoriteController())->getFavoriteProducts(get_user_id());

// Sold-out meals stay saved but can't be ordered, so they're listed separately
$orderable = array_values(array_filter($saved, [$productCtrl, 'isOrderable']));
$unavailable = array_values(array_filter($saved, function ($meal) use ($productCtrl) {
    return !$productCtrl->isOrderable($meal);
}));

require_once __DIR__ . '/layout/header.php';
?>

<?php if (empty($saved)): ?>
    <div class="cb-card cb-bill-empty">
        <span class="cb-bill-empty-icon" aria-hidden="true"><i class="bi bi-heart"></i></span>
        <h2>No favourites yet</h2>
        <p>Tap the heart on any meal to save it here for quick reordering.</p>
        <a class="cb-btn" href="<?php echo $ROOT_DIR; ?>/views/menu.php">Find something tasty</a>
    </div>
<?php else: ?>
    <p class="cb-sub"><?php echo count($saved); ?> saved meal<?php echo count($saved) === 1 ? '' : 's'; ?></p>

    <?php if (!empty($orderable)): ?>
        <div class="cb-grid">
            <?php foreach ($orderable as $dish): ?>
                <?php require __DIR__ . '/layout/dish_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($unavailable)): ?>
        <div class="cb-row"><h2>Not available right now</h2></div>
        <section class="cb-card">
            <ul class="cb-bill-items">
                <?php foreach ($unavailable as $meal): ?>
                    <li class="cb-bill-item is-unavailable">
                        <span class="cb-menu-img"><i class="bi bi-egg-fried" aria-hidden="true"></i></span>
                        <div class="cb-bill-item-main">
                            <b><?php echo htmlspecialchars($meal['product_title']); ?></b>
                            <small><?php echo htmlspecialchars($meal['brand_name']); ?> · sold out or kitchen paused</small>
                        </div>
                        <form action="<?php echo $ROOT_DIR; ?>/actions/toggle_favorite_action.php" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="product_id" value="<?php echo (int)$meal['product_id']; ?>">
                            <button type="submit" class="cb-btn cb-btn-quiet">Remove</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
