<?php
/**
 * Tabs linking the admin pages to each other.
 */
$adminTabs = [
    'product' => ['label' => 'Menu Items', 'path' => '/views/admin/product.php'],
    'brand' => ['label' => 'Kitchens', 'path' => '/views/admin/brand.php'],
    'category' => ['label' => 'Categories', 'path' => '/views/admin/category.php'],
    'orders' => ['label' => 'Orders', 'path' => '/views/admin/orders.php'],
    'customers' => ['label' => 'Customers', 'path' => '/views/customers.php'],
];
?>
<nav class="cb-admin-tabs" aria-label="Admin">
    <?php foreach ($adminTabs as $tabPage => $tab): ?>
        <a href="<?php echo $ROOT_DIR . $tab['path']; ?>" <?php echo $currentPage === $tabPage ? 'class="is-active" aria-current="page"' : ''; ?>><?php echo $tab['label']; ?></a>
    <?php endforeach; ?>
</nav>
