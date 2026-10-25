<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$prodCtrl = new ProductController();
$categories = $prodCtrl->getAllCategories();
$brands = $prodCtrl->getAllBrands();

$pageTitle = "Featured Campus Meals";
$products = [];
$activeBrandId = null;
$activeCatId = null;

if (isset($_GET['brand'])) {
    $activeBrandId = (int)$_GET['brand'];
    $products = $prodCtrl->getProductsByBrand($activeBrandId);
    $selectedBrand = $prodCtrl->getBrandById($activeBrandId);
    $pageTitle = $selectedBrand ? $selectedBrand['brand_name'] . " Menu" : "Kitchen Menu";
} elseif (isset($_GET['cat'])) {
    $activeCatId = (int)$_GET['cat'];
    $products = $prodCtrl->getProductsByCategory($activeCatId);
    $selectedCat = $prodCtrl->getCategoryById($activeCatId);
    $pageTitle = $selectedCat ? $selectedCat['cat_name'] : "Meals by Category";
} else {
    $products = $prodCtrl->getFeaturedProducts(16);
}
?>

<!-- Hero Section -->
<section class="cb-hero-banner">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <div class="d-flex gap-2 mb-3">
                <span class="cb-zone-tag cb-zone-a"><i class="bi bi-shield-check"></i> Zone A: Campus Hubs GH₵ 4.00</span>
                <span class="cb-zone-tag cb-zone-b"><i class="bi bi-scooter"></i> Zone B: Berekuso GH₵ 7.00</span>
            </div>
            <h1 class="cb-hero-title">Skip The Queue. Hot Meals Delivered Straight to Your Hostel or Lab.</h1>
            <p class="cb-hero-subtitle">
                Pre-order from Hallmark, Akonnor, Munchees, Peckish & Eat Healthy. Fast delivery across Ashesi University by verified fellow student couriers.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="#menu-feed" class="cb-btn-primary btn">
                    <i class="bi bi-bag-heart-fill me-1"></i> Order Now
                </a>
                <a href="<?php echo $ROOT_DIR; ?>/views/pass.php" class="btn btn-outline-dark rounded-pill px-3">
                    <i class="bi bi-stars text-warning me-1"></i> Get CampusBite Pass (GH₵ 35/mo)
                </a>
            </div>
        </div>
        <div class="col-lg-4 text-center d-none d-lg-block">
            <div class="p-3 bg-white rounded-4 shadow-sm border border-warning-subtle">
                <div class="text-start">
                    <span class="badge bg-danger-subtle text-danger mb-2"><i class="bi bi-clock-history"></i> Lunch Rush Pre-Order</span>
                    <h6 class="fw-bold mb-1">Peak Queue Protection</h6>
                    <p class="small text-muted mb-2">Avoid 12:00 PM – 1:30 PM lines between lectures. Order 15 mins ahead.</p>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 78%"></div>
                    </div>
                    <small class="text-muted mt-1 d-block text-end" style="font-size: 0.75rem;">78% queue time saved today</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Kitchens / Vendors Quick Filter -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="fw-bold m-0"><i class="bi bi-shop text-warning me-2"></i>Campus & Berekuso Kitchens</h5>
    <span class="text-muted small">Choose your kitchen</span>
</div>
<div class="cb-vendor-scroll">
    <a href="<?php echo $ROOT_DIR; ?>/index.php" class="cb-vendor-chip <?php echo empty($activeBrandId) && empty($activeCatId) ? 'active' : ''; ?>">
        <i class="bi bi-grid-fill"></i> All Menus
    </a>
    <?php foreach ($brands as $b): ?>
        <a href="<?php echo $ROOT_DIR; ?>/index.php?brand=<?php echo $b['brand_id']; ?>" 
           class="cb-vendor-chip <?php echo ($activeBrandId == $b['brand_id']) ? 'active' : ''; ?>">
            <i class="bi bi-fire"></i> <?php echo htmlspecialchars($b['brand_name']); ?>
            <span class="badge rounded-pill <?php echo ($b['zone'] === 'Zone B') ? 'bg-warning text-dark' : 'bg-success text-white'; ?>" style="font-size: 0.7rem;">
                <?php echo $b['zone']; ?>
            </span>
        </a>
    <?php endforeach; ?>
</div>

<!-- Category Tabs -->
<div class="d-flex gap-2 overflow-x-auto pb-2 mb-4">
    <?php foreach ($categories as $c): ?>
        <a href="<?php echo $ROOT_DIR; ?>/index.php?cat=<?php echo $c['cat_id']; ?>" 
           class="btn btn-sm <?php echo ($activeCatId == $c['cat_id']) ? 'btn-dark' : 'btn-light border'; ?> rounded-pill px-3 text-nowrap">
            <i class="bi <?php echo htmlspecialchars($c['cat_icon'] ?? 'bi-tag'); ?> me-1"></i>
            <?php echo htmlspecialchars($c['cat_name']); ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Meal Grid Header -->
<div class="d-flex justify-content-between align-items-center mb-3" id="menu-feed">
    <div>
        <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($pageTitle); ?></h3>
        <p class="text-muted small mb-0"><?php echo count($products); ?> item<?php echo count($products) === 1 ? '' : 's'; ?> available right now</p>
    </div>
</div>

<!-- Products Feed -->
<?php if (empty($products)): ?>
    <div class="text-center py-5 bg-white rounded-4 border">
        <i class="bi bi-emoji-neutral text-muted" style="font-size: 3rem;"></i>
        <h5 class="fw-bold mt-3">No meals found</h5>
        <p class="text-muted">No items found matching this filter. Try viewing all menus.</p>
        <a href="<?php echo $ROOT_DIR; ?>/index.php" class="btn btn-outline-dark rounded-pill px-4">View All</a>
    </div>
<?php else: ?>
    <div class="row g-4 cb-product-grid">
        <?php foreach ($products as $p): ?>
            <?php include __DIR__ . '/layout/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Campus Delivery Guarantee & Value Proposition -->
<section class="mt-5 p-4 bg-white rounded-4 border shadow-sm">
    <div class="row g-4 text-center text-md-start">
        <div class="col-md-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning-subtle p-3 text-warning-emphasis fs-4">
                    <i class="bi bi-lightning-charge-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">No Lunch Queues</h6>
                    <p class="text-muted small mb-0">Pre-order between classes, walk up and grab your hot food immediately.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success-subtle p-3 text-success fs-4">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Student Couriers With Access</h6>
                    <p class="text-muted small mb-0">Verified Ashesi students can deliver right into Walter Sisulu and Maathai dorms.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary-subtle p-3 text-primary fs-4">
                    <i class="bi bi-phone-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Familiar Mobile Money</h6>
                    <p class="text-muted small mb-0">Pay smoothly via MTN MoMo, Telecel Cash or Card processed through Paystack.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
