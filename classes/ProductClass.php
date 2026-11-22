<?php
require_once __DIR__ . "/../core/db_class.php";

class ProductClass extends Database
{
    // Meals ready in this many minutes or fewer count as Quick on the menu
    const QUICK_MINUTES = 15;

    // ==========================================
    // BRANDS / VENDORS
    // ==========================================
    public function getAllBrands()
    {
        $sql = "SELECT * FROM cb_brands WHERE is_active = 1 ORDER BY brand_name ASC";
        return $this->fetchAll($sql);
    }

    // Includes paused kitchens, which shoppers never see
    public function getBrandsForAdmin()
    {
        $sql = "SELECT b.*, COUNT(p.product_id) AS product_count
                FROM cb_brands b
                LEFT JOIN cb_products p ON p.product_brand = b.brand_id
                GROUP BY b.brand_id
                ORDER BY b.brand_name ASC";
        return $this->fetchAll($sql);
    }

    public function brandNameExists($name, $excludeId = 0)
    {
        $sql = "SELECT brand_id FROM cb_brands WHERE LOWER(brand_name) = LOWER(?) AND brand_id != ?";
        return (bool)$this->fetchOne($sql, [$name, $excludeId]);
    }

    public function countProductsByBrand($id)
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM cb_products WHERE product_brand = ?", [$id]);
        return (int)$row['total'];
    }

    public function getBrandById($id)
    {
        $sql = "SELECT * FROM cb_brands WHERE brand_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    public function addBrand($name, $zone = 'Zone A', $location = '', $commission = 0.10, $isActive = 1, $phone = null)
    {
        $sql = "INSERT INTO cb_brands (brand_name, zone, location_desc, brand_phone, commission_rate, is_active) VALUES (?, ?, ?, ?, ?, ?)";
        return $this->execute($sql, [$name, $zone, $location, $phone, $commission, $isActive]);
    }

    // What the admin can change about a kitchen after it's added: CampusBite's commission and whether shoppers can see it
    public function updateBrandTerms($id, $commission, $isActive)
    {
        // An admin pause is remembered so the kitchen can't undo it; setting Active clears it
        return $this->execute("UPDATE cb_brands SET commission_rate = ?, is_active = ?, admin_paused = ? WHERE brand_id = ?", [$commission, $isActive, $isActive ? 0 : 1, $id]);
    }

    // What a kitchen can change about itself from its portal. A kitchen the admin paused stays paused.
    public function updateBrandInfo($id, $location, $isActive, $phone = null)
    {
        return $this->execute("UPDATE cb_brands SET location_desc = ?, is_active = IF(admin_paused = 1, 0, ?), brand_phone = ? WHERE brand_id = ?", [$location, $isActive, $phone, $id]);
    }

    // Opening hours ('HH:MM', Ghana time), both null for no set hours (Sprint 33)
    public function setOpeningHours($id, $opens, $closes)
    {
        return $this->execute("UPDATE cb_brands SET opens_at = ?, closes_at = ? WHERE brand_id = ?", [$opens, $closes, $id]);
    }

    // Where Paystack pays the kitchen (split payments), saved once Paystack has accepted the account
    public function setPayoutAccount($id, $type, $bankCode, $bankName, $number, $name, $subaccount)
    {
        return $this->execute("UPDATE cb_brands SET payout_type = ?, payout_bank_code = ?, payout_bank_name = ?, payout_account_number = ?,
                                                 payout_account_name = ?, paystack_subaccount = ? WHERE brand_id = ?",
            [$type, $bankCode, $bankName, $number, $name, $subaccount, $id]);
    }

    // Every meal a kitchen has, sold out included, with how often each was ordered
    public function getProductsForKitchen($brandId)
    {
        $sql = "SELECT p.*, c.cat_name, c.cat_icon,
                       (SELECT COALESCE(SUM(od.qty), 0) FROM cb_orderdetails od JOIN cb_orders o ON o.order_id = od.order_id
                        WHERE od.product_id = p.product_id AND o.order_status <> 'Cancelled') AS meals_sold
                FROM cb_products p
                JOIN cb_categories c ON p.product_cat = c.cat_id
                WHERE p.product_brand = ?
                ORDER BY p.is_available DESC, p.product_title ASC";
        return $this->fetchAll($sql, [$brandId]);
    }

    public function deleteBrand($id)
    {
        $sql = "DELETE FROM cb_brands WHERE brand_id = ?";
        return $this->execute($sql, [$id]);
    }

    // ==========================================
    // CATEGORIES
    // ==========================================
    public function getAllCategories()
    {
        $sql = "SELECT * FROM cb_categories ORDER BY cat_name ASC";
        return $this->fetchAll($sql);
    }

    public function getCategoriesForAdmin()
    {
        $sql = "SELECT c.*, COUNT(p.product_id) AS product_count
                FROM cb_categories c
                LEFT JOIN cb_products p ON p.product_cat = c.cat_id
                GROUP BY c.cat_id
                ORDER BY c.cat_name ASC";
        return $this->fetchAll($sql);
    }

    public function categoryNameExists($name, $excludeId = 0)
    {
        $sql = "SELECT cat_id FROM cb_categories WHERE LOWER(cat_name) = LOWER(?) AND cat_id != ?";
        return (bool)$this->fetchOne($sql, [$name, $excludeId]);
    }

    public function countProductsByCategory($id)
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM cb_products WHERE product_cat = ?", [$id]);
        return (int)$row['total'];
    }

    public function getCategoryById($id)
    {
        $sql = "SELECT * FROM cb_categories WHERE cat_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    public function addCategory($name, $icon = 'bi-egg-fried')
    {
        $sql = "INSERT INTO cb_categories (cat_name, cat_icon) VALUES (?, ?)";
        return $this->execute($sql, [$name, $icon]);
    }

    public function updateCategory($id, $name, $icon = 'bi-egg-fried')
    {
        $sql = "UPDATE cb_categories SET cat_name = ?, cat_icon = ? WHERE cat_id = ?";
        return $this->execute($sql, [$name, $icon, $id]);
    }

    public function deleteCategory($id)
    {
        $sql = "DELETE FROM cb_categories WHERE cat_id = ?";
        return $this->execute($sql, [$id]);
    }

    // ==========================================
    // PRODUCTS / FOOD ITEMS
    // ==========================================
    public function getAllProducts()
    {
        $sql = "SELECT p.*, c.cat_name, c.cat_icon, b.brand_name, b.zone 
                FROM cb_products p
                JOIN cb_categories c ON p.product_cat = c.cat_id
                JOIN cb_brands b ON p.product_brand = b.brand_id
                ORDER BY p.product_id DESC";
        return $this->fetchAll($sql);
    }

    public function getFeaturedProducts($limit = 12)
    {
        $sql = "SELECT p.*, c.cat_name, c.cat_icon, b.brand_name, b.zone 
                FROM cb_products p
                JOIN cb_categories c ON p.product_cat = c.cat_id
                JOIN cb_brands b ON p.product_brand = b.brand_id
                WHERE p.is_available = 1 AND b.is_active = 1
                ORDER BY p.product_id DESC
                LIMIT " . intval($limit);
        return $this->fetchAll($sql);
    }

    public function getProductById($id)
    {
        $sql = "SELECT p.*, c.cat_name, c.cat_icon, b.brand_name, b.zone, b.near_drop_points, b.location_desc, b.is_active AS brand_active,
                       b.opens_at, b.closes_at
                FROM cb_products p
                JOIN cb_categories c ON p.product_cat = c.cat_id
                JOIN cb_brands b ON p.product_brand = b.brand_id
                WHERE p.product_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    public function getProductsByCategory($cat_id)
    {
        $sql = "SELECT p.*, c.cat_name, c.cat_icon, b.brand_name, b.zone 
                FROM cb_products p
                JOIN cb_categories c ON p.product_cat = c.cat_id
                JOIN cb_brands b ON p.product_brand = b.brand_id
                WHERE p.product_cat = ? AND p.is_available = 1 AND b.is_active = 1
                ORDER BY p.product_id DESC";
        return $this->fetchAll($sql, [$cat_id]);
    }

    public function getProductsByBrand($brand_id)
    {
        $sql = "SELECT p.*, c.cat_name, c.cat_icon, b.brand_name, b.zone 
                FROM cb_products p
                JOIN cb_categories c ON p.product_cat = c.cat_id
                JOIN cb_brands b ON p.product_brand = b.brand_id
                WHERE p.product_brand = ? AND p.is_available = 1 AND b.is_active = 1
                ORDER BY p.product_id DESC";
        return $this->fetchAll($sql, [$brand_id]);
    }

    public function searchProducts($query)
    {
        // Escape LIKE wildcards so "%" or "_" in a search are matched literally
        $term = "%" . addcslashes($query, '%_\\') . "%";
        $sql = "SELECT p.*, c.cat_name, c.cat_icon, b.brand_name, b.zone 
                FROM cb_products p
                JOIN cb_categories c ON p.product_cat = c.cat_id
                JOIN cb_brands b ON p.product_brand = b.brand_id
                WHERE (p.product_title LIKE ? OR p.product_desc LIKE ? OR p.product_keywords LIKE ? OR b.brand_name LIKE ?)
                AND p.is_available = 1 AND b.is_active = 1
                ORDER BY p.product_id DESC";
        return $this->fetchAll($sql, [$term, $term, $term, $term]);
    }

    public function productTitleExists($title, $brand, $excludeId = 0)
    {
        $sql = "SELECT product_id FROM cb_products WHERE LOWER(product_title) = LOWER(?) AND product_brand = ? AND product_id != ?";
        return (bool)$this->fetchOne($sql, [$title, $brand, $excludeId]);
    }

    public function countOrdersForProduct($id)
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM cb_orderdetails WHERE product_id = ?", [$id]);
        return (int)$row['total'];
    }

    public function addProduct($cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime = 15, $isAvailable = 1)
    {
        $sql = "INSERT INTO cb_products (product_cat, product_brand, product_title, product_price, product_desc, product_image, product_keywords, prep_time_minutes, is_available)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->execute($sql, [$cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime, $isAvailable]);
    }

    public function updateProduct($id, $cat, $brand, $title, $price, $desc, $image = null, $keywords = '', $prepTime = 15, $isAvailable = 1)
    {
        if ($image) {
            $sql = "UPDATE cb_products SET product_cat = ?, product_brand = ?, product_title = ?, product_price = ?, product_desc = ?, product_image = ?, product_keywords = ?, prep_time_minutes = ?, is_available = ? WHERE product_id = ?";
            return $this->execute($sql, [$cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime, $isAvailable, $id]);
        }
        $sql = "UPDATE cb_products SET product_cat = ?, product_brand = ?, product_title = ?, product_price = ?, product_desc = ?, product_keywords = ?, prep_time_minutes = ?, is_available = ? WHERE product_id = ?";
        return $this->execute($sql, [$cat, $brand, $title, $price, $desc, $keywords, $prepTime, $isAvailable, $id]);
    }

    public function deleteProduct($id)
    {
        $sql = "DELETE FROM cb_products WHERE product_id = ?";
        return $this->execute($sql, [$id]);
    }

    // ==========================================
    // SHOPPER LISTINGS (available meals in active kitchens only)
    // ==========================================
    // Kitchens shoppers can order from: open, with at least one available meal (a new or sold-out kitchen is left out)
    public function getBrandsWithCounts()
    {
        $sql = "SELECT b.*, COUNT(p.product_id) AS dish_count
                FROM cb_brands b
                JOIN cb_products p ON p.product_brand = b.brand_id AND p.is_available = 1
                WHERE b.is_active = 1
                GROUP BY b.brand_id
                ORDER BY b.brand_name ASC";
        return $this->fetchAll($sql);
    }

    // Categories that have at least one meal shoppers can order
    public function getCategoriesWithDishes($brandId = 0)
    {
        $sql = "SELECT c.*, COUNT(p.product_id) AS dish_count
                FROM cb_categories c
                JOIN cb_products p ON p.product_cat = c.cat_id AND p.is_available = 1
                JOIN cb_brands b ON p.product_brand = b.brand_id AND b.is_active = 1";
        $params = [];
        if ($brandId > 0) {
            $sql .= " WHERE b.brand_id = ?";
            $params[] = $brandId;
        }
        $sql .= " GROUP BY c.cat_id ORDER BY c.cat_name ASC";
        return $this->fetchAll($sql, $params);
    }

    public function countAvailableProducts($brandId = 0, $catId = 0, $label = '', $maxPrice = 0)
    {
        [$where, $params] = $this->availableFilter($brandId, $catId, $label, $maxPrice);
        $sql = "SELECT COUNT(*) AS total
                FROM cb_products p
                JOIN cb_brands b ON p.product_brand = b.brand_id
                WHERE $where";
        $row = $this->fetchOne($sql, $params);
        return (int)$row['total'];
    }

    // Menu sort orders. Only these fixed SQL pieces are ever used; $sort is a key checked by the controller.
    // Each ends with the newest meal first, so meals with the same price or prep time keep a steady order across pages.
    const SORT_ORDERS = [
        '' => 'p.product_id DESC',
        'price_low' => 'p.product_price ASC, p.product_id DESC',
        'price_high' => 'p.product_price DESC, p.product_id DESC',
        'quickest' => 'p.prep_time_minutes ASC, p.product_id DESC',
        // Meals sold in orders that weren't cancelled
        'popular' => "(SELECT COALESCE(SUM(od.qty), 0) FROM cb_orderdetails od JOIN cb_orders o ON o.order_id = od.order_id
                       WHERE od.product_id = p.product_id AND o.order_status <> 'Cancelled') DESC, p.product_id DESC",
    ];

    public function getAvailableProductsPage($brandId, $catId, $limit, $offset, $label = '', $sort = '', $maxPrice = 0)
    {
        [$where, $params] = $this->availableFilter($brandId, $catId, $label, $maxPrice);
        $orderBy = self::SORT_ORDERS[$sort] ?? self::SORT_ORDERS[''];
        $sql = "SELECT p.*, c.cat_name, c.cat_icon, b.brand_name, b.zone
                FROM cb_products p
                JOIN cb_categories c ON p.product_cat = c.cat_id
                JOIN cb_brands b ON p.product_brand = b.brand_id
                WHERE $where
                ORDER BY $orderBy
                LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        return $this->fetchAll($sql, $params);
    }

    // $label is one key of ProductController::LABEL_FILTERS and $maxPrice one of PRICE_LIMITS (or 0 for any price),
    // both already checked by the controller
    private function availableFilter($brandId, $catId, $label = '', $maxPrice = 0)
    {
        $where = "p.is_available = 1 AND b.is_active = 1";
        $params = [];
        if ($brandId > 0) {
            $where .= " AND p.product_brand = ?";
            $params[] = $brandId;
        }
        if ($catId > 0) {
            $where .= " AND p.product_cat = ?";
            $params[] = $catId;
        }
        if ($label === 'quick') {
            // Not a stored label: worked out from the prep time
            $where .= " AND p.prep_time_minutes <= ?";
            $params[] = self::QUICK_MINUTES;
        } elseif ($label === 'vegetarian') {
            // Vegan meals are vegetarian too
            $where .= " AND (FIND_IN_SET('vegetarian', p.labels) OR FIND_IN_SET('vegan', p.labels))";
        } elseif ($label !== '') {
            $where .= " AND FIND_IN_SET(?, p.labels)";
            $params[] = $label;
        }
        if ($maxPrice > 0) {
            $where .= " AND p.product_price <= ?";
            $params[] = $maxPrice;
        }
        return [$where, $params];
    }

    // Saves a meal's labels as a comma list, e.g. "spicy,healthy" (the column is a MySQL SET)
    public function setLabels($productId, $labels)
    {
        return $this->execute("UPDATE cb_products SET labels = ? WHERE product_id = ?", [implode(',', $labels), $productId]);
    }

    // The product_id of the meal just added
    public function lastProductId()
    {
        return (int)$this->getLastInsertId();
    }
}
?>
