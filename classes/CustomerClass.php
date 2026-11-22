<?php
require_once __DIR__ . "/../core/db_class.php";

class CustomerClass extends Database
{
    public function registerCustomer($name, $email, $pass, $country, $city, $contact, $hostel = null)
    {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO cb_customer (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, campus_hostel, user_role) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 2)";
        return $this->execute($sql, [$name, $email, $hashed, $country, $city, $contact, $hostel]);
    }

    public function emailExists($email)
    {
        $sql = "SELECT customer_id FROM cb_customer WHERE customer_email = ?";
        return (bool) $this->fetchOne($sql, [$email]);
    }

    public function getCustomerByEmail($email)
    {
        $sql = "SELECT * FROM cb_customer WHERE customer_email = ?";
        return $this->fetchOne($sql, [$email]);
    }

    public function getCustomerById($id)
    {
        $sql = "SELECT * FROM cb_customer WHERE customer_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    public function getAllCustomers()
    {
        $sql = "SELECT c.customer_id, c.customer_name, c.customer_email, c.customer_contact, c.customer_city, c.campus_hostel, c.user_role, c.has_pass, c.pass_expiry, b.brand_name
                FROM cb_customer c LEFT JOIN cb_brands b ON c.brand_id = b.brand_id
                ORDER BY c.customer_id DESC";
        return $this->fetchAll($sql);
    }

    public function updateDropPoint($id, $dropPoint)
    {
        return $this->execute("UPDATE cb_customer SET campus_hostel = ? WHERE customer_id = ?", [$dropPoint, $id]);
    }

    public function updateProfile($id, $name, $contact, $hostel, $email)
    {
        $sql = "UPDATE cb_customer SET customer_name = ?, customer_contact = ?, campus_hostel = ?, customer_email = ? WHERE customer_id = ?";
        return $this->execute($sql, [$name, $contact, $hostel, $email, $id]);
    }

    // Setting your own password also clears the "must change it" flag from a starting password
    public function updatePassword($id, $newPass)
    {
        $sql = "UPDATE cb_customer SET customer_pass = ?, must_change_password = 0 WHERE customer_id = ?";
        return $this->execute($sql, [password_hash($newPass, PASSWORD_DEFAULT), $id]);
    }

    // ---------- Forgot password ----------

    // Stores a reset link's token (as a hash) that works for $minutes
    public function addPasswordReset($customerId, $tokenHash, $minutes, $ip)
    {
        $sql = "INSERT INTO cb_password_resets (customer_id, token_hash, expires_at, request_ip)
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), ?)";
        return $this->execute($sql, [$customerId, $tokenHash, (int)$minutes, $ip]);
    }

    // How many reset links were asked for in the last $minutes, for one account or from one IP address
    public function countRecentResets($column, $value, $minutes)
    {
        $column = $column === 'request_ip' ? 'request_ip' : 'customer_id';
        $row = $this->fetchOne("SELECT COUNT(*) AS n FROM cb_password_resets
                                WHERE $column = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)", [$value, (int)$minutes]);
        return (int)$row['n'];
    }

    // The account a reset token belongs to, if the link is unused and hasn't expired
    public function getValidReset($tokenHash)
    {
        $sql = "SELECT r.reset_id, r.customer_id, c.customer_name, c.customer_email
                FROM cb_password_resets r
                JOIN cb_customer c ON c.customer_id = r.customer_id
                WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW()";
        return $this->fetchOne($sql, [$tokenHash]);
    }

    // Marks one reset link used. Returns false if it was already used or has expired, so a link works only once.
    public function claimReset($resetId)
    {
        return $this->executeCount("UPDATE cb_password_resets SET used_at = NOW()
                                    WHERE reset_id = ? AND used_at IS NULL AND expires_at > NOW()", [$resetId]) === 1;
    }

    // Cancels every other unused link for the account
    public function closeResets($customerId)
    {
        return $this->execute("UPDATE cb_password_resets SET used_at = NOW() WHERE customer_id = ? AND used_at IS NULL", [$customerId]);
    }

    // ---------- Kitchen accounts (role 4) ----------

    // $kitchenRole: 'admin' (owner/manager) or 'staff' (works the shift)
    // $mustChange: true when $pass is a starting password made for them, so they set their own at first sign-in
    public function createKitchenAccount($name, $email, $pass, $contact, $brandId, $kitchenRole = 'admin', $mustChange = false)
    {
        $sql = "INSERT INTO cb_customer (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, user_role, brand_id, kitchen_role, must_change_password)
                VALUES (?, ?, ?, 'Ghana', 'Berekuso', ?, 4, ?, ?, ?)";
        return $this->execute($sql, [$name, $email, password_hash($pass, PASSWORD_DEFAULT), $contact, $brandId, $kitchenRole === 'staff' ? 'staff' : 'admin', $mustChange ? 1 : 0]);
    }

    // Everyone with a login for one kitchen: admins first, then staff
    public function getKitchenTeam($brandId)
    {
        $sql = "SELECT customer_id, customer_name, customer_email, customer_contact, COALESCE(kitchen_role, 'admin') AS kitchen_role
                FROM cb_customer
                WHERE user_role = 4 AND brand_id = ?
                ORDER BY (COALESCE(kitchen_role, 'admin') = 'admin') DESC, customer_name ASC";
        return $this->fetchAll($sql, [$brandId]);
    }

    // A kitchen admin removing one of their own staff: only staff, only from that kitchen
    public function removeKitchenStaff($id, $brandId)
    {
        $sql = "UPDATE cb_customer SET user_role = 2, brand_id = NULL, kitchen_role = NULL
                WHERE customer_id = ? AND user_role = 4 AND brand_id = ? AND kitchen_role = 'staff'";
        $this->execute($sql, [$id, $brandId]);
        return $this->fetchOne("SELECT ROW_COUNT() AS n")['n'] ?? 0;
    }

    /**
     * Who to alert about a kitchen's orders and customer messages: its staff logins, who handle them.
     * A kitchen with no staff yet falls back to its admin, so the alert still reaches someone.
     */
    public function getKitchenAlertLogins($brandId)
    {
        $team = $this->getKitchenTeam($brandId);
        $staff = array_values(array_filter($team, function ($m) { return $m['kitchen_role'] === 'staff'; }));
        return $staff ?: $team;
    }

    // CampusBite admins, for customer replies to messages sent to CampusBite rather than a kitchen
    public function getAdminLogins()
    {
        return $this->fetchAll("SELECT customer_id, customer_name, customer_email FROM cb_customer WHERE user_role = 1");
    }

    public function getKitchenAccounts()
    {
        $sql = "SELECT c.customer_id, c.customer_name, c.customer_email, c.customer_contact, c.brand_id, b.brand_name, COALESCE(c.kitchen_role, 'admin') AS kitchen_role
                FROM cb_customer c
                LEFT JOIN cb_brands b ON c.brand_id = b.brand_id
                WHERE c.user_role = 4
                ORDER BY b.brand_name ASC, c.customer_name ASC";
        return $this->fetchAll($sql);
    }

    // Turns a kitchen account back into a regular customer account
    public function removeKitchenAccess($id)
    {
        return $this->execute("UPDATE cb_customer SET user_role = 2, brand_id = NULL, kitchen_role = NULL WHERE customer_id = ? AND user_role = 4", [$id]);
    }

    public function updatePassStatus($id, $hasPass, $expiryDate)
    {
        $sql = "UPDATE cb_customer SET has_pass = ?, pass_expiry = ? WHERE customer_id = ?";
        return $this->execute($sql, [$hasPass, $expiryDate, $id]);
    }
}
?>
