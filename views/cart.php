<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/CartController.php';

$cartCtrl = new CartController();
$cartIp = get_ip();
$userId = get_user_id();
$cartItems = $cartCtrl->getCartItems($cartIp, $userId);
$subtotal = $cartCtrl->getCartTotal($cartIp, $userId);
$deliveryFee = $cartCtrl->calculateDeliveryFee($cartItems);
$grandTotal = $subtotal + $deliveryFee;
?>

<div class="row g-4 my-3">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold m-0"><i class="bi bi-cart3 text-warning me-2"></i>Your Order Bag</h3>
            <span class="text-muted small"><?php echo count($cartItems); ?> unique items</span>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="card border-0 shadow-sm rounded-4 text-center py-5">
                <div class="py-4">
                    <i class="bi bi-bag-x text-muted" style="font-size: 3.5rem;"></i>
                    <h5 class="fw-bold mt-3">Your order bag is empty</h5>
                    <p class="text-muted">Hungry? Explore what's cooking at Hallmark, Akonnor, Munchees and Peckish!</p>
                    <a href="<?php echo $ROOT_DIR; ?>/index.php" class="cb-btn-primary btn px-4">Browse Campus Menus</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <table class="cb-table mb-0">
                    <thead>
                        <tr>
                            <th>Meal & Kitchen</th>
                            <th>Price</th>
                            <th style="width: 140px;">Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <?php $lineTotal = (float)$item['product_price'] * (int)$item['qty']; ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php 
                                            $thumb = !empty($item['product_image']) && file_exists(__DIR__ . '/../images/products/' . $item['product_image'])
                                                ? $ROOT_DIR . '/images/products/' . htmlspecialchars($item['product_image'])
                                                : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=120&q=80';
                                        ?>
                                        <img src="<?php echo $thumb; ?>" class="rounded-3" style="width: 50px; height: 50px; object-fit: cover;" alt="">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($item['product_title']); ?></h6>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-shop"></i> <?php echo htmlspecialchars($item['brand_name']); ?>
                                                <span class="badge bg-light text-secondary border ms-1"><?php echo htmlspecialchars($item['zone'] ?? 'Zone A'); ?></span>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-semibold">GH₵ <?php echo number_format($item['product_price'], 2); ?></td>
                                <td>
                                    <form action="<?php echo $ROOT_DIR; ?>/actions/update_qty_action.php" method="POST" class="d-flex align-items-center gap-1 m-0">
                                        <input type="hidden" name="p_id" value="<?php echo $item['p_id']; ?>">
                                        <input type="number" name="qty" value="<?php echo $item['qty']; ?>" min="1" max="20" class="form-control form-control-sm text-center rounded-pill" style="width: 60px;">
                                        <button type="submit" class="btn btn-sm btn-light border rounded-circle" title="Update">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="fw-bold text-dark">GH₵ <?php echo number_format($lineTotal, 2); ?></td>
                                <td>
                                    <a href="<?php echo $ROOT_DIR; ?>/actions/remove_from_cart_action.php?p_id=<?php echo $item['p_id']; ?>" class="btn btn-sm btn-outline-danger border-0 rounded-circle" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Summary & Checkout Form -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 90px;">
            <h5 class="fw-bold mb-3">Order Summary</h5>

            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Subtotal</span>
                <span class="fw-semibold">GH₵ <?php echo number_format($subtotal, 2); ?></span>
            </div>

            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Campus Delivery Fee</span>
                <span class="fw-semibold text-success">GH₵ <?php echo number_format($deliveryFee, 2); ?></span>
            </div>

            <div class="alert alert-light border rounded-3 p-2 small mb-3 text-muted">
                <i class="bi bi-info-circle me-1"></i> Zone A: GH₵ 4.00 (On-Campus) | Zone B: GH₵ 7.00 (Berekuso).
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <span class="fw-bold fs-5">Grand Total</span>
                <span class="fw-bold fs-4 text-danger">GH₵ <?php echo number_format($grandTotal, 2); ?></span>
            </div>

            <?php if (!empty($cartItems)): ?>
                <?php if (is_logged_in()): ?>
                    <a href="<?php echo $ROOT_DIR; ?>/views/checkout.php" class="cb-btn-primary btn btn-lg w-100 py-3 text-center">
                        <i class="bi bi-credit-card-fill me-2"></i> Proceed to Checkout
                    </a>
                <?php else: ?>
                    <a href="<?php echo $ROOT_DIR; ?>/views/login.php?redirect=checkout" class="btn btn-dark btn-lg w-100 py-3 rounded-pill text-center">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Sign In to Complete Order
                    </a>
                    <small class="text-center d-block text-muted mt-2">New student? <a href="<?php echo $ROOT_DIR; ?>/views/register.php" class="text-primary fw-semibold">Sign up</a> in 30 seconds</small>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
