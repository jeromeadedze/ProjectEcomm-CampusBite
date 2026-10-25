<?php
require_once __DIR__ . "/../core/db_class.php";

class CartClass extends Database
{
    public function getCartItems($ip, $c_id = null)
    {
        if ($c_id) {
            $sql = "SELECT c.*, p.product_title, p.product_price, p.product_image, b.brand_name, b.zone 
                    FROM cart c
                    JOIN products p ON c.p_id = p.product_id
                    JOIN brands b ON p.product_brand = b.brand_id
                    WHERE c.c_id = ? OR (c.ip_add = ? AND c.c_id IS NULL)";
            return $this->fetchAll($sql, [$c_id, $ip]);
        } else {
            $sql = "SELECT c.*, p.product_title, p.product_price, p.product_image, b.brand_name, b.zone 
                    FROM cart c
                    JOIN products p ON c.p_id = p.product_id
                    JOIN brands b ON p.product_brand = b.brand_id
                    WHERE c.ip_add = ? AND c.c_id IS NULL";
            return $this->fetchAll($sql, [$ip]);
        }
    }

    public function addToCart($p_id, $ip, $c_id = null, $qty = 1)
    {
        $existing = $this->checkItemInCart($p_id, $ip, $c_id);
        if ($existing) {
            $newQty = $existing['qty'] + $qty;
            return $this->updateCartQty($p_id, $ip, $newQty, $c_id);
        } else {
            $sql = "INSERT INTO cart (p_id, ip_add, c_id, qty) VALUES (?, ?, ?, ?)";
            return $this->execute($sql, [$p_id, $ip, $c_id, $qty]);
        }
    }

    public function checkItemInCart($p_id, $ip, $c_id = null)
    {
        if ($c_id) {
            $sql = "SELECT * FROM cart WHERE p_id = ? AND (c_id = ? OR ip_add = ?)";
            return $this->fetchOne($sql, [$p_id, $c_id, $ip]);
        } else {
            $sql = "SELECT * FROM cart WHERE p_id = ? AND ip_add = ? AND c_id IS NULL";
            return $this->fetchOne($sql, [$p_id, $ip]);
        }
    }

    public function updateCartQty($p_id, $ip, $qty, $c_id = null)
    {
        if ($qty <= 0) {
            return $this->removeFromCart($p_id, $ip, $c_id);
        }
        if ($c_id) {
            $sql = "UPDATE cart SET qty = ? WHERE p_id = ? AND (c_id = ? OR ip_add = ?)";
            return $this->execute($sql, [$qty, $p_id, $c_id, $ip]);
        } else {
            $sql = "UPDATE cart SET qty = ? WHERE p_id = ? AND ip_add = ?";
            return $this->execute($sql, [$qty, $p_id, $ip]);
        }
    }

    public function removeFromCart($p_id, $ip, $c_id = null)
    {
        if ($c_id) {
            $sql = "DELETE FROM cart WHERE p_id = ? AND (c_id = ? OR ip_add = ?)";
            return $this->execute($sql, [$p_id, $c_id, $ip]);
        } else {
            $sql = "DELETE FROM cart WHERE p_id = ? AND ip_add = ?";
            return $this->execute($sql, [$p_id, $ip]);
        }
    }

    public function clearCart($ip, $c_id = null)
    {
        if ($c_id) {
            $sql = "DELETE FROM cart WHERE c_id = ? OR ip_add = ?";
            return $this->execute($sql, [$c_id, $ip]);
        } else {
            $sql = "DELETE FROM cart WHERE ip_add = ?";
            return $this->execute($sql, [$ip]);
        }
    }
}
?>
