<?php
require_once __DIR__ . "/../core/db_class.php";

// Money CampusBite has paid out to kitchens
class PayoutClass extends Database
{
    public function addPayout($brandId, $amount, $method, $reference, $note, $paidOn, $recordedBy)
    {
        $sql = "INSERT INTO payouts (brand_id, amount, method, reference, note, paid_on, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        return $this->execute($sql, [$brandId, $amount, $method, $reference, $note, $paidOn, $recordedBy]);
    }

    // Locks the kitchen's row until the transaction ends, so two payouts for one kitchen are recorded one after the other
    public function lockKitchen($brandId)
    {
        return $this->fetchOne("SELECT brand_id FROM brands WHERE brand_id = ? FOR UPDATE", [$brandId]);
    }

    // Total paid out per kitchen, with the date of the latest payout: [brand_id => row]
    public function getTotalsByKitchen()
    {
        $rows = $this->fetchAll("SELECT brand_id, COALESCE(SUM(amount), 0) AS paid, COUNT(*) AS payouts, MAX(paid_on) AS last_paid_on
                                 FROM payouts GROUP BY brand_id");
        $byKitchen = [];
        foreach ($rows as $row) {
            $byKitchen[(int)$row['brand_id']] = $row;
        }
        return $byKitchen;
    }

    // Payouts newest first, for one kitchen or all of them
    public function getPayouts($brandId = null, $limit = null)
    {
        $sql = "SELECT po.*, b.brand_name, c.customer_name AS recorded_by_name
                FROM payouts po
                JOIN brands b ON b.brand_id = po.brand_id
                LEFT JOIN customer c ON c.customer_id = po.recorded_by"
             . ($brandId ? " WHERE po.brand_id = ?" : "")
             . " ORDER BY po.paid_on DESC, po.payout_id DESC"
             . ($limit ? " LIMIT " . (int)$limit : "");
        return $this->fetchAll($sql, $brandId ? [$brandId] : []);
    }
}
?>
