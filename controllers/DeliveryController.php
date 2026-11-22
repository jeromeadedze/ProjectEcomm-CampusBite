<?php
/**
 * Delivery fees by drop point.
 *
 * Every drop point is in one area: on campus, Berekuso town, or further out. A kitchen delivers cheaply
 * to its own area (campus kitchens, Zone A, to the campus halls; town kitchens, Zone B, to the town hostels)
 * and charges the far fee everywhere else. "Further out" spots are far for every kitchen, except where a kitchen
 * lists one as nearby in cb_brands.near_drop_points (Yummu Yum: Queenstar).
 * This matches the team's delivery pricing table (Nov 2026).
 *
 * The fee is always worked out on the server from the drop point the student picked, never taken from the form.
 */
class DeliveryController
{
    const FEE_NEAR = 5;
    const FEE_FAR = 10;

    const AREAS = [
        'campus' => 'On campus',
        'town' => 'Berekuso town',
        'outskirts' => 'Further out',
    ];

    // Drop point => area. The names are what students pick and what kitchens see on the order.
    const DROP_POINTS = [
        'Maathai Hall' => 'campus',
        'Tawiah Hall' => 'campus',
        'Hall 2C' => 'campus',
        'Hall 2D' => 'campus',
        'Hall 2E' => 'campus',
        'Sutherland Hall' => 'campus',
        'Sisulu Hall' => 'campus',
        'Oteng Korankye II Hall' => 'campus',
        'Amu Hall' => 'campus',
        'Engineering & FabLab Complex' => 'campus',
        'Faculty / Admin Office' => 'campus',
        'Dufie Annex' => 'town',
        'Dufie Platinum' => 'town',
        'New Hosanna' => 'town',
        'Old Hosanna' => 'town',
        'Tanko Apartments' => 'town',
        'Masere' => 'town',
        'Columbiana' => 'town',
        'Charlotte Court' => 'outskirts',
        'Ceewus' => 'outskirts',
        'Hillside Village' => 'outskirts',
        'Goodnews Chalet' => 'outskirts',
        'Queenstar' => 'outskirts', // nearby only for Yummu Yum (its near_drop_points)
    ];

    // The area each kitchen zone delivers to for the near fee
    const KITCHEN_AREA = [
        'Zone A' => 'campus',
        'Zone B' => 'town',
    ];

    public static function isDropPoint($name)
    {
        return is_string($name) && array_key_exists($name, self::DROP_POINTS);
    }

    /**
     * The fee from a kitchen to $dropPoint; null when the drop point isn't on the list.
     * $kitchen is a kitchen row (or any row with its `zone` and `near_drop_points`), or just a zone string.
     */
    public static function feeFor($kitchen, $dropPoint)
    {
        if (!self::isDropPoint($dropPoint)) {
            return null;
        }
        $zone = is_array($kitchen) ? ($kitchen['zone'] ?? 'Zone A') : (string)$kitchen;
        $home = self::KITCHEN_AREA[$zone] ?? 'campus';
        $near = self::DROP_POINTS[$dropPoint] === $home
            || in_array($dropPoint, self::extraNearPoints(is_array($kitchen) ? ($kitchen['near_drop_points'] ?? '') : ''), true);
        return (float)($near ? self::FEE_NEAR : self::FEE_FAR);
    }

    // A kitchen's extra nearby drop points: cb_brands.near_drop_points, comma-separated, e.g. "Queenstar"
    public static function extraNearPoints($list)
    {
        return array_values(array_filter(array_map('trim', explode(',', (string)$list)), [self::class, 'isDropPoint']));
    }

    // Drop points grouped by area, in list order: ['campus' => [...], 'town' => [...], 'outskirts' => [...]]
    public static function grouped()
    {
        $groups = array_fill_keys(array_keys(self::AREAS), []);
        foreach (self::DROP_POINTS as $name => $area) {
            $groups[$area][] = $name;
        }
        return $groups;
    }

    // Where a kitchen delivers for the near fee, for labels such as "GH₵ 5 to campus halls"
    public static function nearLabel($kitchen)
    {
        $zone = is_array($kitchen) ? ($kitchen['zone'] ?? 'Zone A') : (string)$kitchen;
        $label = ($zone === 'Zone B') ? 'Berekuso town hostels' : 'campus halls';
        $extra = is_array($kitchen) ? self::extraNearPoints($kitchen['near_drop_points'] ?? '') : [];
        return $label . ($extra ? ' and ' . implode(', ', $extra) : '');
    }

    /**
     * The signed-in customer's saved drop point (Settings), if it's on the list; null otherwise.
     * Read once per request.
     */
    public static function viewerDropPoint()
    {
        static $cached = false;
        if ($cached !== false) {
            return $cached;
        }
        $cached = null;
        if (function_exists('is_logged_in') && is_logged_in() && function_exists('can_order') && can_order()) {
            require_once __DIR__ . '/CustomerController.php';
            $user = (new CustomerController())->getCustomerById(get_user_id());
            if ($user && self::isDropPoint($user['campus_hostel'] ?? null)) {
                $cached = $user['campus_hostel'];
            }
        }
        return $cached;
    }

    /**
     * What to show on a kitchen or meal: the exact fee when we know where the customer usually gets food,
     * otherwise the range. $kitchen as for feeFor(). Returns ['fee' => float|null, 'text' => 'GH₵ 5.00 delivery' | 'GH₵ 5–10 delivery'].
     */
    public static function feeTag($kitchen)
    {
        $fee = self::feeFor($kitchen, self::viewerDropPoint());
        if ($fee !== null) {
            return ['fee' => $fee, 'text' => 'GH₵ ' . number_format($fee, 2) . ' delivery'];
        }
        return ['fee' => null, 'text' => 'GH₵ ' . self::FEE_NEAR . '–' . self::FEE_FAR . ' delivery'];
    }
}
?>
