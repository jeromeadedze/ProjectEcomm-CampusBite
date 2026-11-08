<?php
require_once __DIR__ . "/../core/db_class.php";

class FavoriteClass extends Database
{
    public function isFavorite($customerId, $productId)
    {
        return (bool)$this->fetchOne("SELECT 1 FROM favorites WHERE customer_id = ? AND product_id = ?", [$customerId, $productId]);
    }

    public function addFavorite($customerId, $productId)
    {
        return $this->execute("INSERT IGNORE INTO favorites (customer_id, product_id) VALUES (?, ?)", [$customerId, $productId]);
    }

    public function removeFavorite($customerId, $productId)
    {
        return $this->execute("DELETE FROM favorites WHERE customer_id = ? AND product_id = ?", [$customerId, $productId]);
    }

    public function getFavoriteIds($customerId)
    {
        $rows = $this->fetchAll("SELECT product_id FROM favorites WHERE customer_id = ?", [$customerId]);
        return array_map('intval', array_column($rows, 'product_id'));
    }

    // Saved meals, newest first, with whether each can be ordered right now
    public function getFavoriteProducts($customerId)
    {
        $sql = "SELECT p.*, c.cat_name, b.brand_name, b.zone, b.is_active AS brand_active
                FROM favorites f
                JOIN products p ON f.product_id = p.product_id
                JOIN categories c ON p.product_cat = c.cat_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE f.customer_id = ?
                ORDER BY f.created_at DESC";
        return $this->fetchAll($sql, [$customerId]);
    }
}
?>
