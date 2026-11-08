<?php
require_once __DIR__ . "/../core/db_class.php";

class ProductClass extends Database
{
    // ==========================================
    // BRANDS / VENDORS
    // ==========================================
    public function getAllBrands()
    {
        $sql = "SELECT * FROM brands WHERE is_active = 1 ORDER BY brand_name ASC";
        return $this->fetchAll($sql);
    }

    // Includes paused kitchens, which shoppers never see
    public function getBrandsForAdmin()
    {
        $sql = "SELECT b.*, COUNT(p.product_id) AS product_count
                FROM brands b
                LEFT JOIN products p ON p.product_brand = b.brand_id
                GROUP BY b.brand_id
                ORDER BY b.brand_name ASC";
        return $this->fetchAll($sql);
    }

    public function brandNameExists($name, $excludeId = 0)
    {
        $sql = "SELECT brand_id FROM brands WHERE LOWER(brand_name) = LOWER(?) AND brand_id != ?";
        return (bool)$this->fetchOne($sql, [$name, $excludeId]);
    }

    public function countProductsByBrand($id)
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM products WHERE product_brand = ?", [$id]);
        return (int)$row['total'];
    }

    public function getBrandById($id)
    {
        $sql = "SELECT * FROM brands WHERE brand_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    public function addBrand($name, $zone = 'Zone A', $location = '', $commission = 0.10, $isActive = 1)
    {
        $sql = "INSERT INTO brands (brand_name, zone, location_desc, commission_rate, is_active) VALUES (?, ?, ?, ?, ?)";
        return $this->execute($sql, [$name, $zone, $location, $commission, $isActive]);
    }

    public function updateBrand($id, $name, $zone = 'Zone A', $location = '', $commission = 0.10, $isActive = 1)
    {
        $sql = "UPDATE brands SET brand_name = ?, zone = ?, location_desc = ?, commission_rate = ?, is_active = ? WHERE brand_id = ?";
        return $this->execute($sql, [$name, $zone, $location, $commission, $isActive, $id]);
    }

    public function deleteBrand($id)
    {
        $sql = "DELETE FROM brands WHERE brand_id = ?";
        return $this->execute($sql, [$id]);
    }

    // ==========================================
    // CATEGORIES
    // ==========================================
    public function getAllCategories()
    {
        $sql = "SELECT * FROM categories ORDER BY cat_name ASC";
        return $this->fetchAll($sql);
    }

    public function getCategoriesForAdmin()
    {
        $sql = "SELECT c.*, COUNT(p.product_id) AS product_count
                FROM categories c
                LEFT JOIN products p ON p.product_cat = c.cat_id
                GROUP BY c.cat_id
                ORDER BY c.cat_name ASC";
        return $this->fetchAll($sql);
    }

    public function categoryNameExists($name, $excludeId = 0)
    {
        $sql = "SELECT cat_id FROM categories WHERE LOWER(cat_name) = LOWER(?) AND cat_id != ?";
        return (bool)$this->fetchOne($sql, [$name, $excludeId]);
    }

    public function countProductsByCategory($id)
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM products WHERE product_cat = ?", [$id]);
        return (int)$row['total'];
    }

    public function getCategoryById($id)
    {
        $sql = "SELECT * FROM categories WHERE cat_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    public function addCategory($name, $icon = 'bi-egg-fried')
    {
        $sql = "INSERT INTO categories (cat_name, cat_icon) VALUES (?, ?)";
        return $this->execute($sql, [$name, $icon]);
    }

    public function updateCategory($id, $name, $icon = 'bi-egg-fried')
    {
        $sql = "UPDATE categories SET cat_name = ?, cat_icon = ? WHERE cat_id = ?";
        return $this->execute($sql, [$name, $icon, $id]);
    }

    public function deleteCategory($id)
    {
        $sql = "DELETE FROM categories WHERE cat_id = ?";
        return $this->execute($sql, [$id]);
    }

    // ==========================================
    // PRODUCTS / FOOD ITEMS
    // ==========================================
    public function getAllProducts()
    {
        $sql = "SELECT p.*, c.cat_name, b.brand_name, b.zone 
                FROM products p
                JOIN categories c ON p.product_cat = c.cat_id
                JOIN brands b ON p.product_brand = b.brand_id
                ORDER BY p.product_id DESC";
        return $this->fetchAll($sql);
    }

    public function getFeaturedProducts($limit = 12)
    {
        $sql = "SELECT p.*, c.cat_name, b.brand_name, b.zone 
                FROM products p
                JOIN categories c ON p.product_cat = c.cat_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.is_available = 1 AND b.is_active = 1
                ORDER BY p.product_id DESC
                LIMIT " . intval($limit);
        return $this->fetchAll($sql);
    }

    public function getProductById($id)
    {
        $sql = "SELECT p.*, c.cat_name, b.brand_name, b.zone, b.location_desc, b.is_active AS brand_active
                FROM products p
                JOIN categories c ON p.product_cat = c.cat_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    public function getProductsByCategory($cat_id)
    {
        $sql = "SELECT p.*, c.cat_name, b.brand_name, b.zone 
                FROM products p
                JOIN categories c ON p.product_cat = c.cat_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_cat = ? AND p.is_available = 1 AND b.is_active = 1
                ORDER BY p.product_id DESC";
        return $this->fetchAll($sql, [$cat_id]);
    }

    public function getProductsByBrand($brand_id)
    {
        $sql = "SELECT p.*, c.cat_name, b.brand_name, b.zone 
                FROM products p
                JOIN categories c ON p.product_cat = c.cat_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_brand = ? AND p.is_available = 1 AND b.is_active = 1
                ORDER BY p.product_id DESC";
        return $this->fetchAll($sql, [$brand_id]);
    }

    public function searchProducts($query)
    {
        // Escape LIKE wildcards so "%" or "_" in a search are matched literally
        $term = "%" . addcslashes($query, '%_\\') . "%";
        $sql = "SELECT p.*, c.cat_name, b.brand_name, b.zone 
                FROM products p
                JOIN categories c ON p.product_cat = c.cat_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE (p.product_title LIKE ? OR p.product_desc LIKE ? OR p.product_keywords LIKE ? OR b.brand_name LIKE ?)
                AND p.is_available = 1 AND b.is_active = 1
                ORDER BY p.product_id DESC";
        return $this->fetchAll($sql, [$term, $term, $term, $term]);
    }

    // Includes sold-out meals and paused kitchens, with how many past orders contain each meal
    public function getProductsForAdmin()
    {
        $sql = "SELECT p.*, c.cat_name, b.brand_name, b.zone, b.is_active AS brand_active,
                       (SELECT COUNT(*) FROM orderdetails od WHERE od.product_id = p.product_id) AS order_count
                FROM products p
                JOIN categories c ON p.product_cat = c.cat_id
                JOIN brands b ON p.product_brand = b.brand_id
                ORDER BY b.brand_name ASC, p.product_title ASC";
        return $this->fetchAll($sql);
    }

    public function productTitleExists($title, $brand, $excludeId = 0)
    {
        $sql = "SELECT product_id FROM products WHERE LOWER(product_title) = LOWER(?) AND product_brand = ? AND product_id != ?";
        return (bool)$this->fetchOne($sql, [$title, $brand, $excludeId]);
    }

    public function countOrdersForProduct($id)
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM orderdetails WHERE product_id = ?", [$id]);
        return (int)$row['total'];
    }

    public function addProduct($cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime = 15, $isAvailable = 1)
    {
        $sql = "INSERT INTO products (product_cat, product_brand, product_title, product_price, product_desc, product_image, product_keywords, prep_time_minutes, is_available)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->execute($sql, [$cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime, $isAvailable]);
    }

    public function updateProduct($id, $cat, $brand, $title, $price, $desc, $image = null, $keywords = '', $prepTime = 15, $isAvailable = 1)
    {
        if ($image) {
            $sql = "UPDATE products SET product_cat = ?, product_brand = ?, product_title = ?, product_price = ?, product_desc = ?, product_image = ?, product_keywords = ?, prep_time_minutes = ?, is_available = ? WHERE product_id = ?";
            return $this->execute($sql, [$cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime, $isAvailable, $id]);
        }
        $sql = "UPDATE products SET product_cat = ?, product_brand = ?, product_title = ?, product_price = ?, product_desc = ?, product_keywords = ?, prep_time_minutes = ?, is_available = ? WHERE product_id = ?";
        return $this->execute($sql, [$cat, $brand, $title, $price, $desc, $keywords, $prepTime, $isAvailable, $id]);
    }

    public function deleteProduct($id)
    {
        $sql = "DELETE FROM products WHERE product_id = ?";
        return $this->execute($sql, [$id]);
    }

    // ==========================================
    // SHOPPER LISTINGS (available meals in active kitchens only)
    // ==========================================
    public function getBrandsWithCounts()
    {
        $sql = "SELECT b.*, COUNT(p.product_id) AS dish_count
                FROM brands b
                LEFT JOIN products p ON p.product_brand = b.brand_id AND p.is_available = 1
                WHERE b.is_active = 1
                GROUP BY b.brand_id
                ORDER BY b.brand_name ASC";
        return $this->fetchAll($sql);
    }

    // Categories that have at least one meal shoppers can order
    public function getCategoriesWithDishes($brandId = 0)
    {
        $sql = "SELECT c.*, COUNT(p.product_id) AS dish_count
                FROM categories c
                JOIN products p ON p.product_cat = c.cat_id AND p.is_available = 1
                JOIN brands b ON p.product_brand = b.brand_id AND b.is_active = 1";
        $params = [];
        if ($brandId > 0) {
            $sql .= " WHERE b.brand_id = ?";
            $params[] = $brandId;
        }
        $sql .= " GROUP BY c.cat_id ORDER BY c.cat_name ASC";
        return $this->fetchAll($sql, $params);
    }

    public function countAvailableProducts($brandId = 0, $catId = 0)
    {
        [$where, $params] = $this->availableFilter($brandId, $catId);
        $sql = "SELECT COUNT(*) AS total
                FROM products p
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE $where";
        $row = $this->fetchOne($sql, $params);
        return (int)$row['total'];
    }

    public function getAvailableProductsPage($brandId, $catId, $limit, $offset)
    {
        [$where, $params] = $this->availableFilter($brandId, $catId);
        $sql = "SELECT p.*, c.cat_name, b.brand_name, b.zone
                FROM products p
                JOIN categories c ON p.product_cat = c.cat_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE $where
                ORDER BY p.product_id DESC
                LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        return $this->fetchAll($sql, $params);
    }

    private function availableFilter($brandId, $catId)
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
        return [$where, $params];
    }
}
?>
