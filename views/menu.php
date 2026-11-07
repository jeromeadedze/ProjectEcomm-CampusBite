<?php
// Translated from the theme's cb-collection section: the full menu, one kitchen, or one category
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';

$controller = new ProductController();

$brandId = (int)($_GET['brand'] ?? 0);
$catId = (int)($_GET['cat'] ?? 0);

$kitchen = null;
if ($brandId > 0) {
    $kitchen = $controller->getBrandById($brandId);
    // Paused kitchens are hidden from shoppers
    if (!$kitchen || !$kitchen['is_active']) {
        $_SESSION['error'] = "That kitchen isn't available right now.";
        header("Location: menu.php");
        exit;
    }
}

$category = null;
if ($catId > 0) {
    $category = $controller->getCategoryById($catId);
    if (!$category) {
        $_SESSION['error'] = "That category doesn't exist.";
        header("Location: menu.php" . ($kitchen ? "?brand=$brandId" : ''));
        exit;
    }
}

$total = $controller->countAvailableProducts($brandId, $catId);
$perPage = ProductController::DISHES_PER_PAGE;
$pages = max(1, (int)ceil($total / $perPage));
$page = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
$dishes = $controller->getAvailableProductsPage($brandId, $catId, $page);
$chipCategories = $controller->getCategoriesWithDishes($brandId);

// Links keep the kitchen and category the shopper picked
$listingUrl = function ($params) {
    $params = array_filter($params, function ($value) {
        return $value;
    });
    return 'menu.php' . ($params ? '?' . http_build_query($params) : '');
};

$pageHeading = $category && !$kitchen ? $category['cat_name'] : null;

require_once __DIR__ . '/layout/header.php';

if ($kitchen) {
    $inZoneB = $kitchen['zone'] === 'Zone B';
    $fee = $controller->deliveryFeeForZone($kitchen['zone']);
    $kitchenDishTotal = $controller->countAvailableProducts($brandId, 0);
}
?>

<?php if ($kitchen): ?>
    <div class="cb-hero">
        <div>
            <a class="cb-hero-back" href="<?php echo $ROOT_DIR; ?>/views/menu.php">‹ All kitchens</a>
            <h1><?php echo htmlspecialchars($kitchen['brand_name']); ?></h1>
            <?php if (!empty($kitchen['location_desc'])): ?>
                <p><?php echo htmlspecialchars($kitchen['location_desc']); ?></p>
            <?php endif; ?>
            <div class="cb-hero-tags">
                <span><?php echo $inZoneB ? 'Off-campus' : 'On campus'; ?></span>
                <span>Delivery GH₵ <?php echo number_format($fee, 2); ?></span>
                <span><?php echo $kitchenDishTotal; ?> dish<?php echo $kitchenDishTotal === 1 ? '' : 'es'; ?></span>
            </div>
        </div>
        <div class="cb-hero-media">
            <span class="cb-hero-emoji" aria-hidden="true"><i class="bi bi-shop"></i></span>
        </div>
    </div>
<?php elseif (!$category): ?>
    <div class="cb-row"><h2>Kitchens</h2></div>
    <div class="cb-vendors">
        <?php foreach ($controller->getBrandsWithCounts() as $kitchenCard): ?>
            <?php (function ($kitchen) use ($ROOT_DIR) { require __DIR__ . '/layout/kitchen_card.php'; })($kitchenCard); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!$kitchen): ?>
    <div class="cb-row">
        <h2><?php echo $category ? 'All ' . htmlspecialchars($category['cat_name']) . ' dishes' : 'All dishes'; ?></h2>
        <span class="cb-muted" style="font-size:13px"><?php echo $total; ?> dish<?php echo $total === 1 ? '' : 'es'; ?></span>
    </div>
<?php endif; ?>

<?php if (count($chipCategories) > 1 || $category): ?>
    <div class="cb-chips" role="group" aria-label="Filter by category">
        <a class="cb-chip <?php echo !$category ? 'is-on' : ''; ?>" href="<?php echo $listingUrl(['brand' => $brandId]); ?>" <?php echo !$category ? 'aria-current="true"' : ''; ?>>All</a>
        <?php foreach ($chipCategories as $chip): ?>
            <?php $chipOn = $catId === (int)$chip['cat_id']; ?>
            <a class="cb-chip <?php echo $chipOn ? 'is-on' : ''; ?>" href="<?php echo $listingUrl(['brand' => $brandId, 'cat' => $chip['cat_id']]); ?>" <?php echo $chipOn ? 'aria-current="true"' : ''; ?>><?php echo htmlspecialchars($chip['cat_name']); ?></a>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div style="height:24px"></div>
<?php endif; ?>

<?php if (empty($dishes)): ?>
    <p class="cb-empty">No dishes here yet.</p>
<?php else: ?>
    <div class="cb-grid">
        <?php $showKitchen = !$kitchen; ?>
        <?php foreach ($dishes as $dish): ?>
            <?php require __DIR__ . '/layout/dish_card.php'; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="cb-pager" aria-label="Pages">
            <?php if ($page > 1): ?>
                <a href="<?php echo $listingUrl(['brand' => $brandId, 'cat' => $catId, 'page' => $page - 1]); ?>">‹ Previous</a>
            <?php endif; ?>
            <span>Page <?php echo $page; ?> of <?php echo $pages; ?></span>
            <?php if ($page < $pages): ?>
                <a href="<?php echo $listingUrl(['brand' => $brandId, 'cat' => $catId, 'page' => $page + 1]); ?>">Next ›</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php if ($kitchen): ?>
    <?php $otherKitchens = array_filter($controller->getBrandsWithCounts(), function ($other) use ($brandId) {
        return (int)$other['brand_id'] !== $brandId;
    }); ?>
    <?php if (!empty($otherKitchens)): ?>
        <div class="cb-row"><h2>Other kitchens</h2><a class="cb-viewall" href="<?php echo $ROOT_DIR; ?>/views/menu.php">All dishes ›</a></div>
        <div class="cb-vendors">
            <?php foreach ($otherKitchens as $other): ?>
                <?php (function ($kitchen) use ($ROOT_DIR) { require __DIR__ . '/layout/kitchen_card.php'; })($other); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
