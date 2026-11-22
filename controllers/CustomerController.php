<?php
require_once __DIR__ . "/../classes/CustomerClass.php";
require_once __DIR__ . "/../core/mailer.php";
require_once __DIR__ . "/DeliveryController.php";

class CustomerController
{
    // Drop points (the customer's saved one is campus_hostel) come from DeliveryController::DROP_POINTS

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
            'customer_email' => trim($input['customer_email'] ?? '') ?: $account['customer_email'],
        ];
        // A new sign-in email needs the current password, so nobody at an unlocked screen can take the account over
        $emailChanged = strcasecmp($values['customer_email'], $account['customer_email']) !== 0;

        $error = null;
        if (mb_strlen($values['customer_name']) < 2 || mb_strlen($values['customer_name']) > 100) {
            $error = 'Enter your full name.';
        } elseif (!preg_match(self::PHONE_RULE, $values['customer_contact'])) {
            $error = 'Enter a valid phone number, such as 0241234567.';
        } elseif ($needsHostel && !DeliveryController::isDropPoint($values['campus_hostel'])) {
            $error = 'Choose your delivery spot from the list.';
        } elseif ($emailChanged && (!filter_var($values['customer_email'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['customer_email']) > 100)) {
            $error = 'Enter a valid email address.';
        } elseif ($emailChanged && $this->customerModel->emailExists($values['customer_email'])) {
            $error = 'That email already has a CampusBite account. Use a different one.';
        } elseif ($emailChanged && !password_verify($input['email_pass'] ?? '', $account['customer_pass'])) {
            $error = 'Enter your current password to change your sign-in email.';
        } elseif (!$this->customerModel->updateProfile($id, $values['customer_name'], $values['customer_contact'], $values['campus_hostel'], $values['customer_email'])) {
            $error = "Your details couldn't be saved. Try again.";
        }

        return ['values' => $values, 'error' => $error, 'emailChanged' => $emailChanged && $error === null];
    }

    // Saves the customer's usual drop point (ticked at checkout); only drop points on the list
    public function updateDropPoint($id, $dropPoint)
    {
        return DeliveryController::isDropPoint($dropPoint) && $this->customerModel->updateDropPoint($id, $dropPoint);
    }

    /**
     * Emails a new kitchen login its sign-in details, at the person's own email address.
     * Returns true when the email was sent (or saved, in log mode).
     */
    public function sendKitchenWelcome($toEmail, $name, $kitchenName, $loginEmail, $password, $kitchenRole)
    {
        $first = explode(' ', trim($name))[0];
        $what = $kitchenRole === 'staff'
            ? "You've been added to the $kitchenName team on CampusBite. You'll take orders, reply to customers and mark meals sold out."
            : "You're now the kitchen admin for $kitchenName on CampusBite. You'll see your sales, manage your menu and prices, and add your staff.";
        $lines = ["Hi $first,", $what, "Sign in with the details below. You'll choose your own password the first time you sign in."];
        $rows = [['Sign-in email', $loginEmail], ['Starting password', $password]];
        $foot = "You can change your sign-in email to this address under Settings, so CampusBite alerts reach you here.";
        return send_email($toEmail, $name, "Your CampusBite kitchen sign-in",
            email_html('Welcome to CampusBite', $lines, 'Sign in', app_url('views/login.php'), $foot, $rows),
            email_text('Welcome to CampusBite', $lines, 'Sign in', app_url('views/login.php'), $foot, $rows));
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
    // Sign-in emails made for kitchen logins (kitchen.name@campusbite.com). Not real inboxes, and not allowed for sign-ups.
    public static function isMadeUpEmail($email)
    {
        return (bool)preg_match('/@' . preg_quote(self::LOGIN_DOMAIN, '/') . '$/i', (string)$email);
    }

    public function requestPasswordReset($email, $ip)
    {
        $user = filter_var($email, FILTER_VALIDATE_EMAIL) ? $this->customerModel->getCustomerByEmail($email) : null;
        // Kitchen logins' @campusbite.com sign-in emails have no inbox: their admin resets them instead (Staff / Users page).
        // The page gives the same answer either way, so it still can't be used to find out who has an account.
        if (!$user || self::isMadeUpEmail($user['customer_email'])
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
    /**
     * Creates a kitchen login. The CampusBite admin creates each kitchen's admin; a kitchen admin creates their own staff.
     * $kitchenRole: 'admin' or 'staff'.
     */
    // ---------- Kitchen logins made for you: email and starting password ----------

    const LOGIN_DOMAIN = 'campusbite.com';
    // Words left out of the kitchen part of the email, so "Munchees Grills" gives "munchees"
    const KITCHEN_FILLER_WORDS = ['cafeteria', 'kitchen', 'kitchens', 'grill', 'grills', 'spot', 'lounge', 'berekuso', 'restaurant',
                                  'canteen', 'cafe', 'the', 'and', 'of'];

    // "Eat Healthy Spot" -> "eathealthy", "Hi5 Lounge & Kitchen" -> "hi5"
    public static function kitchenSlug($brandName)
    {
        $words = array_values(array_filter(preg_split('/[^a-z0-9]+/', strtolower($brandName))));
        $kept = array_values(array_filter($words, function ($w) { return !in_array($w, self::KITCHEN_FILLER_WORDS, true); }));
        return substr(implode('', $kept ?: $words) ?: 'kitchen', 0, 20);
    }

    // "Kwame Boateng" -> "kwame"
    public static function firstNameSlug($fullName)
    {
        $first = preg_split('/\s+/', trim($fullName))[0] ?? '';
        return substr(preg_replace('/[^a-z0-9]/', '', strtolower($first)) ?: 'staff', 0, 20);
    }

    // kitchen.firstname@campusbite.com, with a number added if that address is taken
    public function suggestKitchenEmail($brandName, $fullName)
    {
        $base = self::kitchenSlug($brandName) . '.' . self::firstNameSlug($fullName);
        $email = $base . '@' . self::LOGIN_DOMAIN;
        for ($n = 2; $this->customerModel->emailExists($email); $n++) {
            $email = $base . $n . '@' . self::LOGIN_DOMAIN;
        }
        return $email;
    }

    // "Munchees@Kwame47": easy to read out, and meets the password rules (upper, lower, number, symbol, 8+).
    // Easy to guess too, which is why these accounts must set their own password at first sign-in.
    public static function startingPassword($brandName, $fullName)
    {
        return ucfirst(self::kitchenSlug($brandName)) . '@' . ucfirst(self::firstNameSlug($fullName)) . random_int(10, 99);
    }

    /**
     * Creates a kitchen login. The CampusBite admin creates each kitchen's admin; a kitchen admin creates their own staff.
     * $kitchenRole: 'admin' or 'staff'. $mustChange: the password was made for them, so they set their own at first sign-in.
     */
    public function createKitchenAccount($input, $kitchenExists, $kitchenRole = 'admin', $mustChange = false)
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
        } elseif (!$this->customerModel->createKitchenAccount($values['customer_name'], $values['customer_email'], $pass, $values['customer_contact'], $values['brand_id'], $kitchenRole, $mustChange)) {
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

    public function getKitchenTeam($brandId)
    {
        return $this->customerModel->getKitchenTeam($brandId);
    }

    // Returns true if a staff login of this kitchen was removed
    public function removeKitchenStaff($id, $brandId)
    {
        return (int)$this->customerModel->removeKitchenStaff($id, $brandId) > 0;
    }

    /**
     * Kitchen logins don't reset their own passwords by email (their sign-in emails are made up): the kitchen admin
     * resets a staff member's from the Staff page, and the CampusBite admin resets any kitchen login from Users.
     * $brandId = the kitchen admin's kitchen (staff of that kitchen only), or null for the CampusBite admin.
     * Returns ['error' => message|null, 'person' => row, 'password' => the new starting password].
     */
    public function resetKitchenPassword($id, $brandId = null)
    {
        $person = $this->customerModel->getCustomerById((int)$id);
        if (!$person || (int)$person['user_role'] !== 4) {
            return ['error' => "That kitchen login doesn't exist.", 'person' => null, 'password' => null];
        }
        require_once __DIR__ . '/ProductController.php';
        $kitchen = (new ProductController())->getBrandById((int)$person['brand_id']);
        $password = self::startingPassword($kitchen['brand_name'] ?? 'Campus', $person['customer_name']);
        $changed = $this->customerModel->resetKitchenPassword((int)$id, $password, $brandId, $brandId !== null);
        if (!$changed) {
            return ['error' => $brandId !== null ? "You can only reset passwords for your own staff." : "That password couldn't be reset. Try again.",
                    'person' => $person, 'password' => null];
        }
        return ['error' => null, 'person' => $person, 'password' => $password];
    }

    public function getAllCustomers()
    {
        return $this->customerModel->getAllCustomers();
    }
}
?>
