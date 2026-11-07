<?php
require_once __DIR__ . "/../classes/CustomerClass.php";

class CustomerController
{
    // Drop points offered at sign-up. The form and the server check both read this list.
    public const HOSTELS = [
        'Walter Sisulu Hall' => 'Walter Sisulu Hall (On-Campus)',
        'Wangari Maathai Hall' => 'Wangari Maathai Hall (On-Campus)',
        'Charlotte Court' => 'Charlotte Court (Berekuso)',
        'Masere Hostel' => 'Masere Hostel (Berekuso)',
        'Dufie Hostel' => 'Dufie Hostel (Berekuso)',
        'Engineering & FabLab Complex' => 'Engineering & FabLab Complex',
        'Faculty / Admin Office' => 'Faculty / Admin Office',
    ];

    private $customerModel;

    public function __construct()
    {
        $this->customerModel = new CustomerClass();
    }

    /**
     * Creates the account and returns ['success' => bool, 'user' => row, 'error' => message].
     */
    public function registerCustomer($name, $email, $pass, $country, $city, $contact, $hostel = null)
    {
        if ($this->customerModel->emailExists($email)) {
            return ['success' => false, 'error' => 'That email is already registered. Sign in instead.'];
        }

        if (!$this->customerModel->registerCustomer($name, $email, $pass, $country, $city, $contact, $hostel)) {
            return ['success' => false, 'error' => 'We could not create your account. Please try again.'];
        }

        return ['success' => true, 'user' => $this->customerModel->getCustomerByEmail($email)];
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
