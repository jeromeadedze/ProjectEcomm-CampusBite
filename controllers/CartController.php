<?php
require_once __DIR__ . "/../classes/CartClass.php";
require_once __DIR__ . "/ProductController.php";
require_once __DIR__ . "/DeliveryController.php";

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
     * The kitchens the meals in a bill come from (a bill should only ever have one)
     */
    public function kitchenIds($items)
    {
        return array_values(array_unique(array_map(function ($item) {
            return (int)$item['product_brand'];
        }, $items)));
    }

    /**
     * The kitchen the shopper's basket is from: ['id' => brand_id, 'name' => brand_name], or null when it's empty.
     * "Order again" uses it to ask before emptying another kitchen's basket.
     */
    public function basketKitchen($token, $c_id = null)
    {
        $items = $this->getCartItems($token, $c_id);
        return $items ? ['id' => (int)$items[0]['product_brand'], 'name' => $items[0]['brand_name']] : null;
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
     * The delivery fee for this bill to $dropPoint (DeliveryController: GH₵ 5 to the kitchen's own area, GH₵ 10 elsewhere).
     * One fee per order. Returns null when the drop point isn't known yet, so pages can show the range instead.
     * Bills from before the one-kitchen rule may still mix kitchens: the dearest fee applies.
     */
    public function calculateDeliveryFee($items, $hasPass = false, $dropPoint = null)
    {
        if (empty($items)) return 0.00;
        if ($hasPass) return 0.00; // CampusBite Pass (switched off for now)
        if (!DeliveryController::isDropPoint($dropPoint)) return null;

        $fee = 0.0;
        foreach ($items as $item) {
            $fee = max($fee, DeliveryController::feeFor($item, $dropPoint)); // the item carries its kitchen's zone and near_drop_points
        }
        return $fee;
    }
}
?>
