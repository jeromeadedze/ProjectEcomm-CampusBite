<?php
require_once __DIR__ . "/../classes/CustomerClass.php";
require_once __DIR__ . "/../core/mailer.php";

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
    /**
     * Checks and saves name, phone and drop point. Returns ['values' => ..., 'error' => message or null].
     */
    public function updateProfile($id, $input)
    {
        // Kitchen staff have no delivery spot, so they keep whatever is stored (usually nothing)
        $account = $this->customerModel->getCustomerById($id);
        $needsHostel = (int)$account['user_role'] !== 4;

        $values = [
            'customer_name' => trim($input['customer_name'] ?? ''),
            'customer_contact' => trim($input['customer_contact'] ?? ''),
            'campus_hostel' => $needsHostel ? trim($input['campus_hostel'] ?? '') : ($account['campus_hostel'] ?? null),
        ];

        $error = null;
        if (mb_strlen($values['customer_name']) < 2 || mb_strlen($values['customer_name']) > 100) {
            $error = 'Enter your full name.';
        } elseif (!preg_match(self::PHONE_RULE, $values['customer_contact'])) {
            $error = 'Enter a valid phone number, such as 0241234567.';
        } elseif ($needsHostel && !array_key_exists($values['campus_hostel'], self::HOSTELS)) {
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

    // How long a reset link works, and how many can be asked for per hour
    const RESET_MINUTES = 60;
    const RESETS_PER_ACCOUNT_HOUR = 3;
    const RESETS_PER_IP_HOUR = 10;

    /**
     * Emails a password reset link if the address belongs to an account. Returns the link (only shown on screen
     * in local test mode) or null. The page says the same thing either way, so nobody can use it to find out
     * which emails have accounts.
     */
    public function requestPasswordReset($email, $ip)
    {
        $user = filter_var($email, FILTER_VALIDATE_EMAIL) ? $this->customerModel->getCustomerByEmail($email) : null;
        if (!$user
            || $this->customerModel->countRecentResets('customer_id', $user['customer_id'], 60) >= self::RESETS_PER_ACCOUNT_HOUR
            || $this->customerModel->countRecentResets('request_ip', $ip, 60) >= self::RESETS_PER_IP_HOUR) {
            return null;
        }

        // Only the newest link works
        $this->customerModel->closeResets($user['customer_id']);
        $token = bin2hex(random_bytes(32));
        if (!$this->customerModel->addPasswordReset($user['customer_id'], hash('sha256', $token), self::RESET_MINUTES, $ip)) {
            return null;
        }
        $link = app_url('views/reset_password.php?token=' . $token);
        $first = explode(' ', trim($user['customer_name']))[0];
        $lines = ["Hi $first,", "Someone asked to reset the password for your CampusBite account. Choose a new password with the button below. The link works once, for the next " . self::RESET_MINUTES . " minutes."];
        $foot = "If you didn't ask for this, ignore this email. Your password stays the same.";
        send_email($user['customer_email'], $user['customer_name'], 'Reset your CampusBite password',
            email_html('Reset your password', $lines, 'Choose a new password', $link, $foot),
            email_text('Reset your password', $lines, 'Choose a new password', $link, $foot));
        return $link;
    }

    // The account behind a reset link's token, or null if the link is wrong, used or expired
    public function findPasswordReset($token)
    {
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return $this->customerModel->getValidReset(hash('sha256', $token)) ?: null;
    }

    /**
     * Sets a new password from a reset link. Returns an error message, or null on success.
     * The link is claimed first, so it can't be used twice, and every other link for the account stops working.
     */
    public function resetPassword($token, $new, $confirm)
    {
        $reset = $this->findPasswordReset($token);
        if (!$reset) {
            return 'This reset link has expired or was already used. Ask for a new one.';
        }
        if (!preg_match(self::PASSWORD_RULE, $new)) {
            return 'Use at least 8 characters with an uppercase letter, a lowercase letter, a number and a symbol.';
        }
        if ($new !== $confirm) {
            return "The passwords don't match.";
        }
        try {
            $this->customerModel->beginTransaction();
            if (!$this->customerModel->claimReset($reset['reset_id'])) {
                $this->customerModel->rollBack();
                return 'This reset link has expired or was already used. Ask for a new one.';
            }
            $this->customerModel->updatePassword($reset['customer_id'], $new);
            $this->customerModel->closeResets($reset['customer_id']);
            $this->customerModel->commit();
        } catch (PDOException $e) {
            $this->customerModel->rollBack();
            error_log('Password reset failed: ' . $e->getMessage());
            return "Your password couldn't be changed. Try again.";
        }

        $first = explode(' ', trim($reset['customer_name']))[0];
        $lines = ["Hi $first,", 'The password for your CampusBite account was just changed with a reset link.'];
        $foot = "If you didn't do this, reset your password again straight away and contact CampusBite support.";
        send_email($reset['customer_email'], $reset['customer_name'], 'Your CampusBite password was changed',
            email_html('Your password was changed', $lines, 'Sign in', app_url('views/login.php'), $foot),
            email_text('Your password was changed', $lines, 'Sign in', app_url('views/login.php'), $foot));
        return null;
    }

    /**
     * Creates a sign-in for a kitchen's staff. Returns ['values' => form values (no password), 'error' => message or null].
     */
    public function createKitchenAccount($input, $kitchenExists)
    {
        $values = [
            'brand_id' => (int)($input['brand_id'] ?? 0),
            'customer_name' => trim($input['customer_name'] ?? ''),
            'customer_email' => trim($input['customer_email'] ?? ''),
            'customer_contact' => trim($input['customer_contact'] ?? ''),
        ];
        $pass = $input['customer_pass'] ?? '';

        $error = null;
        if (!$kitchenExists($values['brand_id'])) {
            $error = 'Choose the kitchen this account will run.';
        } elseif (mb_strlen($values['customer_name']) < 2 || mb_strlen($values['customer_name']) > 100) {
            $error = "Enter the staff member's full name.";
        } elseif (!filter_var($values['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address for the kitchen login.';
        } elseif ($this->customerModel->emailExists($values['customer_email'])) {
            $error = 'That email already has an account. Use a different one for the kitchen login.';
        } elseif (!preg_match(self::PHONE_RULE, $values['customer_contact'])) {
            $error = 'Enter a valid phone number, such as 0241234567.';
        } elseif (!preg_match(self::PASSWORD_RULE, $pass)) {
            $error = 'The starting password needs at least 8 characters with an uppercase letter, a lowercase letter, a number and a symbol.';
        } elseif (!$this->customerModel->createKitchenAccount($values['customer_name'], $values['customer_email'], $pass, $values['customer_contact'], $values['brand_id'])) {
            $error = "The kitchen login couldn't be created. Try again.";
        }

        return ['values' => $values, 'error' => $error];
    }

    public function getKitchenAccounts()
    {
        return $this->customerModel->getKitchenAccounts();
    }

    public function removeKitchenAccess($id)
    {
        return $this->customerModel->removeKitchenAccess($id);
    }

    public function getAllCustomers()
    {
        return $this->customerModel->getAllCustomers();
    }

    const ACCOUNTS_PER_PAGE = 25;
    // The account groups the admin can filter by, with their chip labels
    const ACCOUNT_GROUPS = ['customers' => 'Customers', 'suspended' => 'Suspended', 'kitchen' => 'Kitchen staff', 'admin' => 'Admins'];

    /**
     * Admin account search: ?q= (name, email or phone), ?group= and ?page=. Returns one page of accounts,
     * the per-group counts for the chips and paging details.
     */
    public function searchAccountsForAdmin($input)
    {
        $f = [
            'q' => mb_substr(trim((string)($input['q'] ?? '')), 0, 100),
            'group' => array_key_exists($input['group'] ?? '', self::ACCOUNT_GROUPS) ? $input['group'] : '',
        ];
        $counts = $this->customerModel->countAccountGroups($f);
        $total = $f['group'] !== '' ? $counts[$f['group']] : $counts['all_accounts'];
        $pages = max(1, (int)ceil($total / self::ACCOUNTS_PER_PAGE));
        $page = min(max(1, (int)($input['page'] ?? 1)), $pages);
        $accounts = $this->customerModel->searchAccounts($f, self::ACCOUNTS_PER_PAGE, ($page - 1) * self::ACCOUNTS_PER_PAGE);
        return ['filters' => $f, 'accounts' => $accounts, 'counts' => $counts, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /**
     * Suspends a customer account: it is signed out on its next page load and can't sign in again until
     * reinstated. Only customer accounts (role 2) can be suspended. Returns an error message, or null.
     */
    public function suspendCustomer($id, $reason)
    {
        $account = $this->customerModel->getCustomerById($id);
        $reason = trim((string)$reason);
        if (!$account) {
            return "That account doesn't exist.";
        }
        if ((int)$account['user_role'] !== 2) {
            return 'Only customer accounts can be suspended. Remove kitchen access from the Kitchens tab instead.';
        }
        if (!empty($account['is_suspended'])) {
            return "{$account['customer_name']} is already suspended.";
        }
        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 255) {
            return 'Give a reason for the suspension (3 to 255 characters). The customer sees it if they try to sign in.';
        }
        return $this->customerModel->setSuspended($id, true, $reason) ? null : "The account couldn't be suspended. Try again.";
    }

    public function reinstateCustomer($id)
    {
        $account = $this->customerModel->getCustomerById($id);
        if (!$account || empty($account['is_suspended'])) {
            return "That account isn't suspended.";
        }
        return $this->customerModel->setSuspended($id, false) ? null : "The account couldn't be reinstated. Try again.";
    }
}
?>
