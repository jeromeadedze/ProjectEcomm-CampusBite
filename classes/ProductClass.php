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

    public function getBrandById($id)
    {
        $sql = "SELECT * FROM brands WHERE brand_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    public function addBrand($name, $zone = 'Zone A', $location = '', $commission = 0.10)
    {
        $sql = "INSERT INTO brands (brand_name, zone, location_desc, commission_rate) VALUES (?, ?, ?, ?)";
        return $this->execute($sql, [$name, $zone, $location, $commission]);
    }

    public function updateBrand($id, $name, $zone = 'Zone A', $location = '', $commission = 0.10)
    {
        $sql = "UPDATE brands SET brand_name = ?, zone = ?, location_desc = ?, commission_rate = ? WHERE brand_id = ?";
        return $this->execute($sql, [$name, $zone, $location, $commission, $id]);
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
                WHERE p.is_available = 1
                ORDER BY p.product_id DESC
                LIMIT " . intval($limit);
        return $this->fetchAll($sql);
    }

    public function getProductById($id)
    {
        $sql = "SELECT p.*, c.cat_name, b.brand_name, b.zone, b.location_desc
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
                WHERE p.product_cat = ? AND p.is_available = 1
                ORDER BY p.product_id DESC";
        return $this->fetchAll($sql, [$cat_id]);
    }

    public function getProductsByBrand($brand_id)
    {
        $sql = "SELECT p.*, c.cat_name, b.brand_name, b.zone 
                FROM products p
                JOIN categories c ON p.product_cat = c.cat_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE p.product_brand = ? AND p.is_available = 1
                ORDER BY p.product_id DESC";
        return $this->fetchAll($sql, [$brand_id]);
    }

    public function searchProducts($query)
    {
        $term = "%" . $query . "%";
        $sql = "SELECT p.*, c.cat_name, b.brand_name, b.zone 
                FROM products p
                JOIN categories c ON p.product_cat = c.cat_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE (p.product_title LIKE ? OR p.product_desc LIKE ? OR p.product_keywords LIKE ? OR b.brand_name LIKE ?)
                AND p.is_available = 1
                ORDER BY p.product_id DESC";
        return $this->fetchAll($sql, [$term, $term, $term, $term]);
    }

    public function addProduct($cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime = 15)
    {
        $sql = "INSERT INTO products (product_cat, product_brand, product_title, product_price, product_desc, product_image, product_keywords, prep_time_minutes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->execute($sql, [$cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime]);
    }

    public function updateProduct($id, $cat, $brand, $title, $price, $desc, $image = null, $keywords = '', $prepTime = 15)
    {
        if ($image) {
            $sql = "UPDATE products SET product_cat = ?, product_brand = ?, product_title = ?, product_price = ?, product_desc = ?, product_image = ?, product_keywords = ?, prep_time_minutes = ? WHERE product_id = ?";
            return $this->execute($sql, [$cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime, $id]);
        } else {
            $sql = "UPDATE products SET product_cat = ?, product_brand = ?, product_title = ?, product_price = ?, product_desc = ?, product_keywords = ?, prep_time_minutes = ? WHERE product_id = ?";
            return $this->execute($sql, [$cat, $brand, $title, $price, $desc, $keywords, $prepTime, $id]);
        }
    }

    public function deleteProduct($id)
    {
        $sql = "DELETE FROM products WHERE product_id = ?";
        return $this->execute($sql, [$id]);
    }
}
?>
