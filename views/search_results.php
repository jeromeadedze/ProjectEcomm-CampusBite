<?php
// Search results, in the theme's layout: meal title, kitchen, description and keywords are all searched
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';

$controller = new ProductController();
$query = trim($_GET['q'] ?? '');
$query = mb_substr($query, 0, 100);
$results = $query !== '' ? $controller->searchProducts($query) : [];
$resultCount = count($results);

$pageHeading = $query !== '' ? 'Results for "' . $query . '"' : 'Search';

require_once __DIR__ . '/layout/header.php';

$suggestedKitchens = array_slice(array_filter($controller->getBrandsWithCounts(), function ($kitchen) {
    return (int)$kitchen['dish_count'] > 0;
}), 0, 6);
?>

<?php if ($query === ''): ?>
    <p class="cb-sub">Type a meal, ingredient or kitchen into the search box above.</p>
<?php else: ?>
    <p class="cb-sub"><?php echo $resultCount; ?> meal<?php echo $resultCount === 1 ? '' : 's'; ?> found</p>
<?php endif; ?>

<?php if ($resultCount > 0): ?>
    <div class="cb-grid">
        <?php foreach ($results as $dish): ?>
            <?php require __DIR__ . '/layout/dish_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <?php if ($query !== ''): ?>
        <p class="cb-empty">No meals or kitchens match "<?php echo htmlspecialchars($query); ?>". Try a shorter word, like "jollof" or "banku".</p>
    <?php endif; ?>

    <?php if (!empty($suggestedKitchens)): ?>
        <div class="cb-row"><h2>Browse a kitchen instead</h2><a class="cb-viewall" href="<?php echo $ROOT_DIR; ?>/views/menu.php">All dishes ›</a></div>
        <div class="cb-vendors">
            <?php foreach ($suggestedKitchens as $kitchenCard): ?>
                <?php (function ($kitchen) use ($ROOT_DIR) { require __DIR__ . '/layout/kitchen_card.php'; })($kitchenCard); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
