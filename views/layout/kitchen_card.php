<?php
/**
 * One kitchen card, translated from the theme's cb-vendor-card snippet.
 * Expects $kitchen (a brand row with dish_count).
 */
$kitchenInZoneB = $kitchen['zone'] === 'Zone B';
$kitchenFee = ProductController::DELIVERY_FEES[$kitchen['zone']] ?? ProductController::DELIVERY_FEES['Zone A'];
$kitchenDishes = (int)$kitchen['dish_count'];
?>
<a class="cb-vcard" href="<?php echo $ROOT_DIR; ?>/views/menu.php?brand=<?php echo (int)$kitchen['brand_id']; ?>">
    <span class="cb-vcard-icon" aria-hidden="true"><i class="bi bi-shop"></i></span>
    <b><?php echo htmlspecialchars($kitchen['brand_name']); ?></b>
    <span><?php echo $kitchenDishes; ?> dish<?php echo $kitchenDishes === 1 ? '' : 'es'; ?></span>
    <span>Delivery GH₵ <?php echo number_format($kitchenFee, 2); ?></span>
    <em class="cb-zone <?php echo $kitchenInZoneB ? 'cb-zone-b' : 'cb-zone-a'; ?>"><?php echo $kitchenInZoneB ? 'Off-campus' : 'On campus'; ?></em>
</a>
