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

    // Same rules as sign-up (js/validate.js and register_action.php)
    const PASSWORD_RULE = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/';
    const PHONE_RULE = '/^[0-9+\-\s]{7,15}$/';

    // Roles an admin can switch an account between (admins are only made in the database)
    const ASSIGNABLE_ROLES = [2 => 'Student / Staff', 3 => 'Courier'];

    /**
     * Checks and saves name, phone and drop point. Returns ['values' => ..., 'error' => message or null].
     */
    public function updateProfile($id, $input)
    {
        $values = [
            'customer_name' => trim($input['customer_name'] ?? ''),
            'customer_contact' => trim($input['customer_contact'] ?? ''),
            'campus_hostel' => trim($input['campus_hostel'] ?? ''),
        ];

        $error = null;
        if (mb_strlen($values['customer_name']) < 2 || mb_strlen($values['customer_name']) > 100) {
            $error = 'Enter your full name.';
        } elseif (!preg_match(self::PHONE_RULE, $values['customer_contact'])) {
            $error = 'Enter a valid phone number, such as 0241234567.';
        } elseif (!array_key_exists($values['campus_hostel'], self::HOSTELS)) {
            $error = 'Choose your delivery spot from the list.';
        } elseif (!$this->customerModel->updateProfile($id, $values['customer_name'], $values['customer_contact'], $values['campus_hostel'])) {
            $error = "Your details couldn't be saved. Try again.";
        }

        return ['values' => $values, 'error' => $error];
    }

    /**
     * Changes the password after checking the current one. Returns an error message, or null on success.
     */
    public function changePassword($id, $current, $new, $confirm)
    {
        $user = $this->customerModel->getCustomerById($id);
        if (!$user || !password_verify($current, $user['customer_pass'])) {
            return 'Your current password is incorrect.';
        }
        if (!preg_match(self::PASSWORD_RULE, $new)) {
            return 'Use at least 8 characters with an uppercase letter, a lowercase letter, a number and a symbol.';
        }
        if ($new !== $confirm) {
            return "The new passwords don't match.";
        }
        if (password_verify($new, $user['customer_pass'])) {
            return 'Choose a password different from your current one.';
        }
        return $this->customerModel->updatePassword($id, $new) ? null : "Your password couldn't be changed. Try again.";
    }

    public function setRole($id, $role)
    {
        return array_key_exists($role, self::ASSIGNABLE_ROLES) ? $this->customerModel->setRole($id, $role) : false;
    }

    public function getCouriers()
    {
        return $this->customerModel->getCouriers();
    }

    public function getAllCustomers()
    {
        return $this->customerModel->getAllCustomers();
    }
}
?>
