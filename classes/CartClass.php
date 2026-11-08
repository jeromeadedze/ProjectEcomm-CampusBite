<?php
require_once __DIR__ . "/../core/db_class.php";

/**
 * A cart belongs to exactly one owner:
 *  - a signed-in customer: rows where c_id = customer id
 *  - a guest: rows where ip_add = the session's cart token and c_id IS NULL
 * Rows are never matched by "c_id OR ip_add", so one person's actions can't touch another's cart.
 */
class CartClass extends Database
{
    const MAX_QTY = 20; // per meal, matches the max on the quantity inputs

    private function owner($token, $c_id)
    {
        if ($c_id) {
            return ["c.c_id = ?", [$c_id]];
        }
        return ["c.ip_add = ? AND c.c_id IS NULL", [$token]];
    }

    public function getCartItems($token, $c_id = null)
    {
        [$where, $params] = $this->owner($token, $c_id);
        $sql = "SELECT c.*, p.product_title, p.product_price, p.product_image, p.is_available,
                       b.brand_name, b.zone, b.is_active AS brand_active
                FROM cart c
                JOIN products p ON c.p_id = p.product_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE $where";
        return $this->fetchAll($sql, $params);
    }

    /**
     * Adds a meal, or raises its quantity if it is already in the cart (capped at MAX_QTY).
     * A non-empty note replaces the meal's existing note; an empty one keeps it.
     */
    public function addToCart($p_id, $token, $c_id = null, $qty = 1, $notes = null)
    {
        $notes = ($notes === null || trim($notes) === '') ? null : trim($notes);
        $existing = $this->checkItemInCart($p_id, $token, $c_id);
        if ($existing) {
            $newQty = min(self::MAX_QTY, (int)$existing['qty'] + $qty);
            [$where, $params] = $this->owner($token, $c_id);
            $sql = "UPDATE cart c SET c.qty = ?, c.notes = COALESCE(?, c.notes) WHERE c.p_id = ? AND $where";
            return $this->execute($sql, array_merge([$newQty, $notes, $p_id], $params));
        }
        $sql = "INSERT INTO cart (p_id, ip_add, c_id, qty, notes) VALUES (?, ?, ?, ?, ?)";
        return $this->execute($sql, [$p_id, $token, $c_id, min(self::MAX_QTY, $qty), $notes]);
    }

    public function checkItemInCart($p_id, $token, $c_id = null)
    {
        [$where, $params] = $this->owner($token, $c_id);
        return $this->fetchOne("SELECT * FROM cart c WHERE c.p_id = ? AND $where", array_merge([$p_id], $params));
    }

    public function updateCartQty($p_id, $token, $qty, $c_id = null)
    {
        if ($qty <= 0) {
            return $this->removeFromCart($p_id, $token, $c_id);
        }
        [$where, $params] = $this->owner($token, $c_id);
        return $this->execute("UPDATE cart c SET c.qty = ? WHERE c.p_id = ? AND $where", array_merge([$qty, $p_id], $params));
    }

    public function removeFromCart($p_id, $token, $c_id = null)
    {
        [$where, $params] = $this->owner($token, $c_id);
        return $this->execute("DELETE c FROM cart c WHERE c.p_id = ? AND $where", array_merge([$p_id], $params));
    }

    public function clearCart($token, $c_id = null)
    {
        [$where, $params] = $this->owner($token, $c_id);
        return $this->execute("DELETE c FROM cart c WHERE $where", $params);
    }

    /**
     * Moves what a guest added before signing in into the customer's own cart
     */
    public function mergeGuestCart($token, $c_id)
    {
        foreach ($this->getCartItems($token, null) as $item) {
            $this->addToCart($item['p_id'], $token, $c_id, (int)$item['qty'], $item['notes']);
        }
        return $this->clearCart($token, null);
    }
}
?>
