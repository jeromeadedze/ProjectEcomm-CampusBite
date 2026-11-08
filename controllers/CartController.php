<?php
require_once __DIR__ . "/../classes/CartClass.php";
require_once __DIR__ . "/ProductController.php";

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

    public function addToCart($p_id, $ip, $c_id = null, $qty = 1, $notes = null)
    {
        return $this->cartModel->addToCart($p_id, $ip, $c_id, $qty, $notes);
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

    public function mergeGuestCart($token, $c_id)
    {
        return $this->cartModel->mergeGuestCart($token, $c_id);
    }

    /**
     * Zone B if any item comes from a Berekuso kitchen, otherwise Zone A
     */
    public function deliveryZoneFor($items)
    {
        foreach ($items as $item) {
            if (isset($item['zone']) && $item['zone'] === 'Zone B') {
                return 'Zone B';
            }
        }
        return 'Zone A';
    }

    /**
     * Cart items that have sold out, or whose kitchen was paused, since they were added
     */
    public function unavailableItems($items)
    {
        return array_values(array_filter($items, function ($item) {
            return (int)$item['is_available'] !== 1 || (int)$item['brand_active'] !== 1;
        }));
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
     * One fee per order, from ProductController::DELIVERY_FEES:
     * the Zone B fee if any item is from a Zone B (Berekuso) kitchen, otherwise the Zone A fee
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
        return (float)ProductController::DELIVERY_FEES[$hasZoneB ? 'Zone B' : 'Zone A'];
    }
}
?>
