<?php
require_once __DIR__ . "/../classes/CartClass.php";

class CartController
{
    private $cartModel;

    public function __construct()
    {
        $this->cartModel = new CartClass();
    }

    public function getCartItems($ip, $c_id = null)
    {
        return $this->cartModel->getCartItems($ip, $c_id);
    }

    public function addToCart($p_id, $ip, $c_id = null, $qty = 1)
    {
        return $this->cartModel->addToCart($p_id, $ip, $c_id, $qty);
    }

    public function updateCartQty($p_id, $ip, $qty, $c_id = null)
    {
        return $this->cartModel->updateCartQty($p_id, $ip, $qty, $c_id);
    }

    public function removeFromCart($p_id, $ip, $c_id = null)
    {
        return $this->cartModel->removeFromCart($p_id, $ip, $c_id);
    }

    public function clearCart($ip, $c_id = null)
    {
        return $this->cartModel->clearCart($ip, $c_id);
    }

    public function getCartCount($ip, $c_id = null)
    {
        $items = $this->getCartItems($ip, $c_id);
        $count = 0;
        foreach ($items as $item) {
            $count += (int)$item['qty'];
        }
        return $count;
    }

    public function getCartTotal($ip, $c_id = null)
    {
        $items = $this->getCartItems($ip, $c_id);
        $total = 0;
        foreach ($items as $item) {
            $total += ((float)$item['product_price'] * (int)$item['qty']);
        }
        return $total;
    }

    /**
     * Determines delivery fee based on vendor zones present in cart
     * Zone A (On-campus) = 4.00
     * If any item is from Zone B (Berekuso) = 7.00
     */
    public function calculateDeliveryFee($items, $hasPass = false)
    {
        if (empty($items)) return 0.00;
        if ($hasPass) return 0.00; // Free Zone A deliveries with Pass

        $hasZoneB = false;
        foreach ($items as $item) {
            if (isset($item['zone']) && $item['zone'] === 'Zone B') {
                $hasZoneB = true;
                break;
            }
        }
        return $hasZoneB ? 7.00 : 4.00;
    }
}
?>
