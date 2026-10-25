<?php
require_once __DIR__ . "/../classes/CustomerClass.php";

class CustomerController
{
    private $customerModel;

    public function __construct()
    {
        $this->customerModel = new CustomerClass();
    }

    public function registerCustomer($name, $email, $pass, $country, $city, $contact, $hostel = null)
    {
        return $this->customerModel->registerCustomer($name, $email, $pass, $country, $city, $contact, $hostel);
    }

    public function loginCustomer($email, $pass)
    {
        $user = $this->customerModel->getCustomerByEmail($email);
        if ($user && password_verify($pass, $user['customer_pass'])) {
            return $user;
        }
        return false;
    }

    public function getCustomerById($id)
    {
        return $this->customerModel->getCustomerById($id);
    }

    public function getAllCustomers()
    {
        return $this->customerModel->getAllCustomers();
    }
}
?>
