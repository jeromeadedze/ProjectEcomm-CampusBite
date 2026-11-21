<?php
require_once __DIR__ . "/../core/db_class.php";

class CustomerClass extends Database
{
    public function registerCustomer($name, $email, $pass, $country, $city, $contact, $hostel = null)
    {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO customer (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, campus_hostel, user_role) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 2)";
        return $this->execute($sql, [$name, $email, $hashed, $country, $city, $contact, $hostel]);
    }

    public function emailExists($email)
    {
        $sql = "SELECT customer_id FROM customer WHERE customer_email = ?";
        return (bool) $this->fetchOne($sql, [$email]);
    }

    public function getCustomerByEmail($email)
    {
        $sql = "SELECT * FROM customer WHERE customer_email = ?";
        return $this->fetchOne($sql, [$email]);
    }

    public function getCustomerById($id)
    {
        $sql = "SELECT * FROM customer WHERE customer_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    public function getAllCustomers()
    {
        $sql = "SELECT c.customer_id, c.customer_name, c.customer_email, c.customer_contact, c.customer_city, c.campus_hostel, c.user_role, c.has_pass, c.pass_expiry, b.brand_name
                FROM customer c LEFT JOIN brands b ON c.brand_id = b.brand_id
                ORDER BY c.customer_id DESC";
        return $this->fetchAll($sql);
    }

    public function updateProfile($id, $name, $contact, $hostel)
    {
        $sql = "UPDATE customer SET customer_name = ?, customer_contact = ?, campus_hostel = ? WHERE customer_id = ?";
        return $this->execute($sql, [$name, $contact, $hostel, $id]);
    }

    public function updatePassword($id, $newPass)
    {
        $sql = "UPDATE customer SET customer_pass = ? WHERE customer_id = ?";
        return $this->execute($sql, [password_hash($newPass, PASSWORD_DEFAULT), $id]);
    }

    // ---------- Forgot password ----------

    // Stores a reset link's token (as a hash) that works for $minutes
    public function addPasswordReset($customerId, $tokenHash, $minutes, $ip)
    {
        $sql = "INSERT INTO password_resets (customer_id, token_hash, expires_at, request_ip)
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), ?)";
        return $this->execute($sql, [$customerId, $tokenHash, (int)$minutes, $ip]);
    }

    // How many reset links were asked for in the last $minutes, for one account or from one IP address
    public function countRecentResets($column, $value, $minutes)
    {
        $column = $column === 'request_ip' ? 'request_ip' : 'customer_id';
        $row = $this->fetchOne("SELECT COUNT(*) AS n FROM password_resets
                                WHERE $column = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)", [$value, (int)$minutes]);
        return (int)$row['n'];
    }

    // The account a reset token belongs to, if the link is unused and hasn't expired
    public function getValidReset($tokenHash)
    {
        $sql = "SELECT r.reset_id, r.customer_id, c.customer_name, c.customer_email
                FROM password_resets r
                JOIN customer c ON c.customer_id = r.customer_id
                WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW()";
        return $this->fetchOne($sql, [$tokenHash]);
    }

    // Marks one reset link used. Returns false if it was already used or has expired, so a link works only once.
    public function claimReset($resetId)
    {
        return $this->executeCount("UPDATE password_resets SET used_at = NOW()
                                    WHERE reset_id = ? AND used_at IS NULL AND expires_at > NOW()", [$resetId]) === 1;
    }

    // Cancels every other unused link for the account
    public function closeResets($customerId)
    {
        return $this->execute("UPDATE password_resets SET used_at = NOW() WHERE customer_id = ? AND used_at IS NULL", [$customerId]);
    }

    // ---------- Kitchen accounts (role 4) ----------

    public function createKitchenAccount($name, $email, $pass, $contact, $brandId)
    {
        $sql = "INSERT INTO customer (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, user_role, brand_id)
                VALUES (?, ?, ?, 'Ghana', 'Berekuso', ?, 4, ?)";
        return $this->execute($sql, [$name, $email, password_hash($pass, PASSWORD_DEFAULT), $contact, $brandId]);
    }

    public function getKitchenAccounts()
    {
        $sql = "SELECT c.customer_id, c.customer_name, c.customer_email, c.customer_contact, c.brand_id, b.brand_name
                FROM customer c
                LEFT JOIN brands b ON c.brand_id = b.brand_id
                WHERE c.user_role = 4
                ORDER BY b.brand_name ASC, c.customer_name ASC";
        return $this->fetchAll($sql);
    }

    // Turns a kitchen account back into a regular customer account
    public function removeKitchenAccess($id)
    {
        return $this->execute("UPDATE customer SET user_role = 2, brand_id = NULL WHERE customer_id = ? AND user_role = 4", [$id]);
    }

    public function updatePassStatus($id, $hasPass, $expiryDate)
    {
        $sql = "UPDATE customer SET has_pass = ?, pass_expiry = ? WHERE customer_id = ?";
        return $this->execute($sql, [$hasPass, $expiryDate, $id]);
    }
}
?>
