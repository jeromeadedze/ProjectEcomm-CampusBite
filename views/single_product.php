<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$prodId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$prodCtrl = new ProductController();
$product = $prodCtrl->getProductById($prodId);

if (!$product) {
    header("Location: " . $ROOT_DIR . "/index.php");
    exit;
}

$imgUrl = !empty($product['product_image']) && file_exists(__DIR__ . '/../images/products/' . $product['product_image'])
    ? $ROOT_DIR . '/images/products/' . htmlspecialchars($product['product_image'])
    : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80';
?>

<div class="row g-4 my-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <img src="<?php echo $imgUrl; ?>" class="img-fluid w-100" style="max-height: 440px; object-fit: cover;" alt="<?php echo htmlspecialchars($product['product_title']); ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge <?php echo ($product['zone'] === 'Zone B') ? 'bg-warning text-dark' : 'bg-success text-white'; ?> rounded-pill px-3 py-2">
                    <i class="bi bi-geo-alt-fill me-1"></i> <?php echo htmlspecialchars($product['zone'] ?? 'Zone A'); ?> (<?php echo ($product['zone'] === 'Zone B') ? 'GH₵ 7.00 Delivery' : 'GH₵ 4.00 Delivery'; ?>)
                </span>
                <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                    <i class="bi bi-clock-history me-1"></i> ~<?php echo $product['prep_time_minutes'] ?? 15; ?> mins prep
                </span>
            </div>

            <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($product['product_title']); ?></h1>
            <p class="text-warning-emphasis fw-semibold mb-3">
                <i class="bi bi-shop me-1"></i> <?php echo htmlspecialchars($product['brand_name']); ?> 
                <span class="text-muted fw-normal ms-1">(<?php echo htmlspecialchars($product['location_desc'] ?? 'Campus Kitchen'); ?>)</span>
            </p>

            <h2 class="text-danger fw-bold mb-4">GH₵ <?php echo number_format($product['product_price'], 2); ?></h2>

            <p class="text-secondary leading-relaxed mb-4">
                <?php echo nl2br(htmlspecialchars($product['product_desc'] ?? 'Delicious meal freshly prepared by our verified campus kitchen partner.')); ?>
            </p>

            <form action="<?php echo $ROOT_DIR; ?>/actions/add_to_cart_action.php" method="POST" class="bg-white p-4 rounded-4 border shadow-sm">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                
                <div class="row g-3 align-items-center mb-3">
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold small text-muted">Quantity</label>
                        <input type="number" name="qty" class="form-control rounded-pill text-center fw-bold" value="1" min="1" max="20" required>
                    </div>
                    <div class="col-sm-8">
                        <label class="form-label fw-semibold small text-muted">Special Request / Dietary Note</label>
                        <input type="text" name="notes" class="form-control rounded-pill" placeholder="e.g. Extra shito, less pepper, no onions">
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="cb-btn-primary btn btn-lg py-3">
                        <i class="bi bi-bag-plus-fill me-2"></i> Add Meal to Order
                    </button>
                    <a href="<?php echo $ROOT_DIR; ?>/index.php" class="btn btn-light rounded-pill py-2 text-muted">
                        <i class="bi bi-arrow-left me-1"></i> Back to Campus Menus
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
