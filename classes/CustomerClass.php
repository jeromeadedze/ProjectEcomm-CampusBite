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
        $sql = "SELECT customer_id, customer_name, customer_email, customer_contact, customer_city, campus_hostel, user_role, has_pass, pass_expiry FROM customer ORDER BY customer_id DESC";
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

    public function setRole($id, $role)
    {
        return $this->execute("UPDATE customer SET user_role = ? WHERE customer_id = ?", [$role, $id]);
    }

    public function getCouriers()
    {
        return $this->fetchAll("SELECT customer_id, customer_name, customer_contact FROM customer WHERE user_role = 3 ORDER BY customer_name ASC");
    }

    public function updatePassStatus($id, $hasPass, $expiryDate)
    {
        $sql = "UPDATE customer SET has_pass = ?, pass_expiry = ? WHERE customer_id = ?";
        return $this->execute($sql, [$hasPass, $expiryDate, $id]);
    }
}
?>
