<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$prodCtrl = new ProductController();
$query = trim($_GET['q'] ?? '');
$results = !empty($query) ? $prodCtrl->searchProducts($query) : [];
?>

<div class="my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold mb-0">Search Results for "<span class="text-warning-emphasis"><?php echo htmlspecialchars($query); ?></span>"</h3>
            <p class="text-muted small mb-0"><?php echo count($results); ?> meal<?php echo count($results) === 1 ? '' : 's'; ?> found</p>
        </div>
        <a href="<?php echo $ROOT_DIR; ?>/index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left"></i> All Menus
        </a>
    </div>

    <?php if (empty($results)): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mt-3">No matching meals or kitchens found</h5>
            <p class="text-muted">Try searching for keywords like "Jollof", "Banku", "Shawarma", "Peckish", or "Munchees".</p>
            <a href="<?php echo $ROOT_DIR; ?>/index.php" class="cb-btn-primary btn px-4 mx-auto">Explore All Food</a>
        </div>
    <?php else: ?>
        <div class="row g-4 cb-product-grid">
            <?php foreach ($results as $p): ?>
                <?php include __DIR__ . '/layout/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
