<?php
require_once __DIR__ . "/../classes/ProductClass.php";

class ProductController
{
    // Where a kitchen is. It delivers for the near fee to its own area (DeliveryController has the fees).
    const ZONES = [
        'Zone A' => 'Zone A: on campus (GH₵ 5 to the campus halls)',
        'Zone B' => 'Zone B: Berekuso town (GH₵ 5 to the town hostels)',
    ];

    const DISHES_PER_PAGE = 24;

    // Labels a kitchen can tick on a meal, stored in cb_products.labels
    const LABELS = [
        'spicy' => 'Spicy',
        'mild' => 'Mild',
        'vegetarian' => 'Vegetarian',
        'vegan' => 'Vegan',
        'healthy' => 'Healthy',
    ];
    const LABEL_ICONS = [
        'spicy' => 'bi-fire',
        'mild' => 'bi-emoji-smile',
        'vegetarian' => 'bi-flower1',
        'vegan' => 'bi-tree',
        'healthy' => 'bi-heart-pulse',
        'quick' => 'bi-lightning-charge',
    ];
    // Meals ready in this many minutes or fewer count as Quick (worked out from prep time, never ticked)
    const QUICK_MINUTES = ProductClass::QUICK_MINUTES;
    // Every filter chip on the menu: the stored labels plus Quick
    const LABEL_FILTERS = self::LABELS + ['quick' => 'Quick'];

    // Menu price chips: "Up to GH₵ 15" and so on (menu.php?max=). Picked so each one leaves out a real part of the menu.
    const PRICE_LIMITS = [15, 20, 30];

    // Menu sort orders shoppers can pick (menu.php?sort=); '' is the default, newest first. SQL in ProductClass::SORT_ORDERS.
    const SORTS = [
        '' => 'Newest',
        'popular' => 'Most ordered',
        'price_low' => 'Cheapest',
        'price_high' => 'Priciest',
        'quickest' => 'Quickest',
    ];

    const CATEGORY_ICONS = [
        'bi-egg-fried' => 'Fried egg',
        'bi-fire' => 'Flame',
        'bi-basket' => 'Basket',
        'bi-heart-pulse' => 'Healthy',
        'bi-cake2' => 'Cake',
        'bi-cup-hot' => 'Hot cup',
        'bi-cup-straw' => 'Cold drink',
        'bi-cup' => 'Cup',
        'bi-segmented-nav' => 'Skewers',
    ];

    const IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    const MAX_IMAGE_BYTES = 2097152; // 2 MB
    const MAX_PRICE = 10000;
    const MAX_PREP_MINUTES = 240;

    private $productModel;

    public function __construct()
    {
        $this->productModel = new ProductClass();
    }

    // Brands / Kitchens
    public function getAllBrands()
    {
        return $this->productModel->getAllBrands();
    }

    public function getBrandById($id)
    {
        return $this->productModel->getBrandById($id);
    }

    public function getBrandsForAdmin()
    {
        return $this->productModel->getBrandsForAdmin();
    }

    public function countProductsByBrand($id)
    {
        return $this->productModel->countProductsByBrand($id);
    }

    /**
     * Clean and check submitted kitchen fields. Returns the form values and any error.
     */
    public function checkBrandForm($input, $excludeId = 0)
    {
        $values = [
            'brand_name' => trim($input['brand_name'] ?? ''),
            'zone' => trim($input['zone'] ?? ''),
            'location_desc' => trim($input['location_desc'] ?? ''),
            'brand_phone' => trim($input['brand_phone'] ?? ''),
            'commission_percent' => trim($input['commission_percent'] ?? ''),
            'is_active' => ($input['is_active'] ?? '1') === '0' ? 0 : 1,
        ];

        $error = null;
        if ($values['brand_name'] === '') {
            $error = "Enter the kitchen's name.";
        } elseif (mb_strlen($values['brand_name']) > 100) {
            $error = "Kitchen names can be up to 100 characters.";
        } elseif (!array_key_exists($values['zone'], self::ZONES)) {
            $error = "Choose a delivery zone from the list.";
        } elseif (mb_strlen($values['location_desc']) > 255) {
            $error = "Keep the location to 255 characters or fewer.";
        } elseif ($values['brand_phone'] !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $values['brand_phone'])) {
            $error = "Enter a valid phone number for the kitchen.";
        } elseif (!ctype_digit($values['commission_percent']) || (int)$values['commission_percent'] > 100) {
            $error = "Commission must be a whole number from 0 to 100.";
        } elseif ($this->productModel->brandNameExists($values['brand_name'], $excludeId)) {
            $error = "A kitchen called \"{$values['brand_name']}\" already exists.";
        }

        return ['values' => $values, 'error' => $error];
    }

    public function addBrand($name, $zone = 'Zone A', $location = '', $commission = 0.10, $isActive = 1, $phone = null)
    {
        return $this->productModel->addBrand($name, $zone, $location, $commission, $isActive, $phone);
    }

    public function updateBrandTerms($id, $commission, $isActive)
    {
        return $this->productModel->updateBrandTerms($id, $commission, $isActive);
    }

    /**
     * Check the admin's edit of a kitchen: only the commission and Active or Paused can change.
     */
    public function checkBrandTerms($input)
    {
        $values = [
            'commission_percent' => trim($input['commission_percent'] ?? ''),
            'is_active' => ($input['is_active'] ?? '1') === '0' ? 0 : 1,
        ];
        $error = null;
        if (!ctype_digit($values['commission_percent']) || (int)$values['commission_percent'] > 100) {
            $error = "Commission must be a whole number from 0 to 100.";
        }
        return ['values' => $values, 'error' => $error];
    }

    public function updateBrandInfo($id, $location, $isActive, $phone = null)
    {
        return $this->productModel->updateBrandInfo($id, $location, $isActive, $phone);
    }

    // ── Opening hours (Sprint 33) ────────────────────────────────────────────────────────────────────────
    // Kitchens' hours are Ghana time, whatever time zone the server's PHP is set to (XAMPP defaults to Berlin).
    const TIMEZONE = 'Africa/Accra';

    /**
     * Checks the opening and closing times typed on Kitchen info ("08:00", as an <input type="time"> sends them).
     * Returns ['opens' => 'HH:MM' or null, 'closes' => ..., 'error' => message or null]. Both empty means no set hours.
     */
    public static function checkOpeningHours($opens, $closes)
    {
        $opens = is_string($opens) ? trim($opens) : '';
        $closes = is_string($closes) ? trim($closes) : '';
        if ($opens === '' && $closes === '') {
            return ['opens' => null, 'closes' => null, 'error' => null];
        }
        if ($opens === '' || $closes === '') {
            return ['opens' => null, 'closes' => null, 'error' => 'Set both an opening and a closing time, or leave both empty.'];
        }
        $isTime = function ($t) { return preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $t) === 1; };
        if (!$isTime($opens) || !$isTime($closes)) {
            return ['opens' => null, 'closes' => null, 'error' => 'Enter the times as hours and minutes, such as 08:00 and 21:00.'];
        }
        $opens = substr($opens, 0, 5);
        $closes = substr($closes, 0, 5);
        if ($opens === $closes) {
            return ['opens' => null, 'closes' => null, 'error' => "Opening and closing times can't be the same."];
        }
        return ['opens' => $opens, 'closes' => $closes, 'error' => null];
    }

    public function setOpeningHours($id, $opens, $closes)
    {
        return $this->productModel->setOpeningHours($id, $opens, $closes);
    }

    /**
     * Whether a kitchen (a brand row) is inside its opening hours right now.
     * Returns ['has_hours' => bool, 'open' => bool, 'text' => 'Open until 9:00 PM' / 'Closed · opens 8:00 AM' / ''].
     * No set hours counts as open. Pausing ("Taking orders" off) is separate: is_active still hides the kitchen.
     * $now is for tests; normally it's the current Ghana time.
     */
    public static function openingStatus($kitchen, $now = null)
    {
        $opens = $kitchen['opens_at'] ?? null;
        $closes = $kitchen['closes_at'] ?? null;
        if (!$opens || !$closes) {
            return ['has_hours' => false, 'open' => true, 'text' => ''];
        }
        $now = $now ?: new DateTimeImmutable('now', new DateTimeZone(self::TIMEZONE));
        $t = $now->format('H:i');
        $o = substr($opens, 0, 5);
        $c = substr($closes, 0, 5);
        $overnight = $c < $o; // e.g. 18:00 to 02:00
        $open = $overnight ? ($t >= $o || $t < $c) : ($t >= $o && $t < $c);
        $show = function ($hm) { return DateTime::createFromFormat('H:i', $hm)->format('g:i A'); };
        if ($open) {
            return ['has_hours' => true, 'open' => true, 'text' => 'Open until ' . $show($c)];
        }
        // Closed: it opens later today, or (after closing on a same-day schedule) tomorrow
        $tomorrow = !$overnight && $t >= $c;
        return ['has_hours' => true, 'open' => false, 'text' => 'Closed · opens ' . ($tomorrow ? 'tomorrow ' : '') . $show($o)];
    }

    /** "8:00 AM – 9:00 PM" for Kitchen info, or null when no hours are set */
    public static function openingHoursLabel($kitchen)
    {
        if (empty($kitchen['opens_at']) || empty($kitchen['closes_at'])) {
            return null;
        }
        $show = function ($hms) { return DateTime::createFromFormat('H:i', substr($hms, 0, 5))->format('g:i A'); };
        return $show($kitchen['opens_at']) . ' – ' . $show($kitchen['closes_at']);
    }

    /**
     * A kitchen sets where Paystack pays it: a Mobile Money number or a Ghana bank account. The account is created
     * (or updated) as the kitchen's Paystack subaccount first, and only saved here once Paystack accepts it.
     * Returns ['error' => message or null, 'values' => what was typed, to refill the form].
     */
    public function savePayoutAccount($brandId, $input)
    {
        require_once __DIR__ . '/../core/paystack.php';
        $kitchen = $this->productModel->getBrandById($brandId);
        $values = [
            'payout_bank_code' => trim((string)($input['payout_bank_code'] ?? '')),
            'payout_account_number' => preg_replace('/\s+/', '', (string)($input['payout_account_number'] ?? '')),
            'payout_account_name' => trim(preg_replace('/\s+/', ' ', (string)($input['payout_account_name'] ?? ''))),
        ];
        // One list of networks and banks; which one was picked decides the account type
        $momo = paystack_payout_options('mobile_money');
        $banks = paystack_payout_options('bank');
        $options = $momo + $banks;
        $isMomo = isset($momo[$values['payout_bank_code']]);
        $values['payout_type'] = $isMomo ? 'mobile_money' : 'bank';

        $error = null;
        if (!$kitchen) {
            $error = "That kitchen doesn't exist.";
        } elseif (!paystack_configured() || !$options) {
            $error = "Paystack couldn't be reached to check the account. Try again in a minute.";
        } elseif (!isset($options[$values['payout_bank_code']])) {
            $error = 'Choose your Mobile Money network or bank.';
        } elseif ($isMomo && !preg_match('/^0\d{9}$/', $values['payout_account_number'])) {
            $error = 'Enter the Mobile Money number as 10 digits starting with 0, e.g. 0241234567.';
        } elseif (!$isMomo && !preg_match('/^\d{8,20}$/', $values['payout_account_number'])) {
            $error = 'Enter the bank account number: 8 to 20 digits, no spaces or dashes.';
        } elseif (mb_strlen($values['payout_account_name']) < 2 || mb_strlen($values['payout_account_name']) > 100) {
            $error = 'Enter the name the account is registered in, as the network or bank has it.';
        }
        if ($error) {
            return ['error' => $error, 'values' => $values];
        }

        $saved = paystack_save_subaccount($kitchen['paystack_subaccount'] ?? null, $kitchen['brand_name'], $values['payout_bank_code'],
            $values['payout_account_number'], round((float)$kitchen['commission_rate'] * 100, 2), (string)($kitchen['brand_phone'] ?? ''));
        if (!$saved['ok']) {
            return ['error' => 'Paystack didn\'t accept that account: ' . rtrim($saved['message'], '.') . '. Check the details and try again.', 'values' => $values];
        }
        $this->productModel->setPayoutAccount($brandId, $values['payout_type'], $values['payout_bank_code'], $options[$values['payout_bank_code']],
            $values['payout_account_number'], $saved['account_name'] ?: $values['payout_account_name'], $saved['code']);
        return ['error' => null, 'values' => $values];
    }

    /** "MTN Mobile Money ·· 4567" style label for a kitchen's payout account, or null when none is set */
    public static function payoutLabel($kitchen)
    {
        if (empty($kitchen['paystack_subaccount'])) {
            return null;
        }
        $bank = $kitchen['payout_bank_name'] ?: $kitchen['payout_bank_code'];
        $suffix = $kitchen['payout_type'] === 'mobile_money' ? ' Mobile Money' : '';
        return $bank . $suffix . ' ·· ' . substr((string)$kitchen['payout_account_number'], -4);
    }

    public function getProductsForKitchen($brandId)
    {
        return $this->productModel->getProductsForKitchen($brandId);
    }

    public function deleteBrand($id)
    {
        return $this->productModel->deleteBrand($id);
    }

    // Categories
    public function getAllCategories()
    {
        return $this->productModel->getAllCategories();
    }

    public function getCategoryById($id)
    {
        return $this->productModel->getCategoryById($id);
    }

    public function getCategoriesForAdmin()
    {
        return $this->productModel->getCategoriesForAdmin();
    }

    public function countProductsByCategory($id)
    {
        return $this->productModel->countProductsByCategory($id);
    }

    /**
     * Clean and check submitted category fields. Returns the form values and any error.
     */
    public function checkCategoryForm($input, $excludeId = 0)
    {
        $values = [
            'cat_name' => trim($input['cat_name'] ?? ''),
            'cat_icon' => trim($input['cat_icon'] ?? ''),
        ];

        $error = null;
        if ($values['cat_name'] === '') {
            $error = "Enter the category's name.";
        } elseif (mb_strlen($values['cat_name']) > 100) {
            $error = "Category names can be up to 100 characters.";
        } elseif (!preg_match('/^bi-[a-z0-9-]+$/', $values['cat_icon']) || strlen($values['cat_icon']) > 50) {
            $error = "Choose an icon from the list.";
        } elseif ($this->productModel->categoryNameExists($values['cat_name'], $excludeId)) {
            $error = "A category called \"{$values['cat_name']}\" already exists.";
        }

        return ['values' => $values, 'error' => $error];
    }

    public function addCategory($name, $icon = 'bi-egg-fried')
    {
        return $this->productModel->addCategory($name, $icon);
    }

    public function updateCategory($id, $name, $icon = 'bi-egg-fried')
    {
        return $this->productModel->updateCategory($id, $name, $icon);
    }

    public function deleteCategory($id)
    {
        return $this->productModel->deleteCategory($id);
    }

    // Products / Food Items
    public function getAllProducts()
    {
        return $this->productModel->getAllProducts();
    }

    public function getFeaturedProducts($limit = 12)
    {
        return $this->productModel->getFeaturedProducts($limit);
    }

    public function getProductById($id)
    {
        return $this->productModel->getProductById($id);
    }

    public function getProductsByCategory($cat_id)
    {
        return $this->productModel->getProductsByCategory($cat_id);
    }

    public function getProductsByBrand($brand_id)
    {
        return $this->productModel->getProductsByBrand($brand_id);
    }

    /**
     * Shoppers can only order a meal that is available from an active kitchen
     */
    public function isOrderable($product)
    {
        return $product && (int)$product['is_available'] === 1 && (int)$product['brand_active'] === 1;
    }

    public function searchProducts($query)
    {
        return $this->productModel->searchProducts($query);
    }

    public function countOrdersForProduct($id)
    {
        return $this->productModel->countOrdersForProduct($id);
    }

    /**
     * Clean and check submitted meal fields. Returns the form values and any error.
     * $file is the entry from $_FILES (or null); a new image is optional.
     */
    public function checkProductForm($input, $file = null, $excludeId = 0)
    {
        $values = [
            'product_title' => trim($input['product_title'] ?? ''),
            'product_price' => trim($input['product_price'] ?? ''),
            'product_brand' => trim($input['product_brand'] ?? ''),
            'product_cat' => trim($input['product_cat'] ?? ''),
            'product_desc' => trim($input['product_desc'] ?? ''),
            'product_keywords' => trim($input['product_keywords'] ?? ''),
            'prep_time' => trim($input['prep_time'] ?? ''),
            'is_available' => ($input['is_available'] ?? '1') === '0' ? 0 : 1,
            'labels' => self::labelsFromInput($input['labels'] ?? []),
        ];

        $error = null;
        if ($values['product_title'] === '') {
            $error = "Enter the meal's name.";
        } elseif (mb_strlen($values['product_title']) > 200) {
            $error = "Meal names can be up to 200 characters.";
        } elseif (!is_numeric($values['product_price']) || (float)$values['product_price'] <= 0) {
            $error = "Enter a price above 0.";
        } elseif ((float)$values['product_price'] > self::MAX_PRICE) {
            $error = "The price can't be more than GH₵ " . number_format(self::MAX_PRICE) . ".";
        } elseif (!ctype_digit($values['product_brand']) || !$this->productModel->getBrandById((int)$values['product_brand'])) {
            $error = "Choose a kitchen from the list.";
        } elseif (!ctype_digit($values['product_cat']) || !$this->productModel->getCategoryById((int)$values['product_cat'])) {
            $error = "Choose a category from the list.";
        } elseif (!ctype_digit($values['prep_time']) || (int)$values['prep_time'] < 1 || (int)$values['prep_time'] > self::MAX_PREP_MINUTES) {
            $error = "Prep time must be a whole number from 1 to " . self::MAX_PREP_MINUTES . " minutes.";
        } elseif (mb_strlen($values['product_desc']) > 2000) {
            $error = "Keep the description to 2000 characters or fewer.";
        } elseif (mb_strlen($values['product_keywords']) > 200) {
            $error = "Keep the keywords to 200 characters or fewer.";
        } elseif (in_array('spicy', $values['labels'], true) && in_array('mild', $values['labels'], true)) {
            $error = "A meal can be Spicy or Mild, not both.";
        } elseif ($this->productModel->productTitleExists($values['product_title'], (int)$values['product_brand'], $excludeId)) {
            $error = "That kitchen already has a meal called \"{$values['product_title']}\".";
        } else {
            $error = $this->checkImageUpload($file);
        }

        return ['values' => $values, 'error' => $error];
    }

    /**
     * Returns an error message if an uploaded image is unusable, or null if there is none or it is fine.
     */
    public function checkImageUpload($file)
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            return "The image is too large. Use one under 2 MB.";
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return "The image couldn't be uploaded. Try again.";
        }
        if ($file['size'] > self::MAX_IMAGE_BYTES) {
            return "The image is too large. Use one under 2 MB.";
        }
        if (!isset(self::IMAGE_TYPES[$this->detectImageType($file['tmp_name'])])) {
            return "Upload a JPG, PNG, WebP or GIF image.";
        }
        return null;
    }

    private function detectImageType($path)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($finfo, $path);
        finfo_close($finfo);
        return $type;
    }

    /**
     * Moves a checked upload into images/products and returns the new file name, or null if there is no upload.
     */
    public function saveProductImage($file)
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $extension = self::IMAGE_TYPES[$this->detectImageType($file['tmp_name'])];
        $name = bin2hex(random_bytes(8)) . '.' . $extension;
        $folder = __DIR__ . '/../images/products/';
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
        return move_uploaded_file($file['tmp_name'], $folder . $name) ? $name : null;
    }

    public function deleteProductImage($name)
    {
        // Only plain file names we generated; never a path
        if ($name && preg_match('/^[a-z0-9]+\.[a-z]+$/i', $name)) {
            $path = __DIR__ . '/../images/products/' . $name;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function addProduct($cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime = 15, $isAvailable = 1)
    {
        return $this->productModel->addProduct($cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime, $isAvailable);
    }

    public function updateProduct($id, $cat, $brand, $title, $price, $desc, $image = null, $keywords = '', $prepTime = 15, $isAvailable = 1)
    {
        return $this->productModel->updateProduct($id, $cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime, $isAvailable);
    }

    public function deleteProduct($id)
    {
        return $this->productModel->deleteProduct($id);
    }

    /**
     * The ticked labels from a form, keeping only known ones, in LABELS order.
     * Anything else sent (a made-up label, a string instead of a list) is dropped.
     */
    public static function labelsFromInput($raw)
    {
        $raw = is_array($raw) ? $raw : [];
        return array_values(array_filter(array_keys(self::LABELS), function ($key) use ($raw) {
            return in_array($key, $raw, true);
        }));
    }

    // A meal row's labels as a list of keys, e.g. ['spicy', 'healthy']
    public static function labelsOf($product)
    {
        return self::labelsFromInput(explode(',', (string)($product['labels'] ?? '')));
    }

    // Labels to show on a meal: its stored labels, plus Quick when it's ready fast enough
    public static function displayLabels($product)
    {
        $keys = self::labelsOf($product);
        if ((int)($product['prep_time_minutes'] ?? 99) <= self::QUICK_MINUTES) {
            $keys[] = 'quick';
        }
        return $keys;
    }

    public function setProductLabels($productId, $labels)
    {
        return $this->productModel->setLabels($productId, self::labelsFromInput($labels));
    }

    public function lastProductId()
    {
        return $this->productModel->lastProductId();
    }

    // Shopper listings
    public function getBrandsWithCounts()
    {
        return $this->productModel->getBrandsWithCounts();
    }

    public function getCategoriesWithDishes($brandId = 0)
    {
        return $this->productModel->getCategoriesWithDishes($brandId);
    }

    public function countAvailableProducts($brandId = 0, $catId = 0, $label = '', $maxPrice = 0)
    {
        return $this->productModel->countAvailableProducts($brandId, $catId, $this->cleanLabelFilter($label), $this->cleanMaxPrice($maxPrice));
    }

    public function getAvailableProductsPage($brandId, $catId, $page, $label = '', $sort = '', $maxPrice = 0)
    {
        $offset = (max(1, (int)$page) - 1) * self::DISHES_PER_PAGE;
        return $this->productModel->getAvailableProductsPage($brandId, $catId, self::DISHES_PER_PAGE, $offset,
            $this->cleanLabelFilter($label), $this->cleanSort($sort), $this->cleanMaxPrice($maxPrice));
    }

    // A price limit from the address bar (menu.php?max=), or 0 (any price) when it isn't one of PRICE_LIMITS
    public function cleanMaxPrice($max)
    {
        if (!is_string($max) && !is_int($max)) {
            return 0; // e.g. ?max[]=x
        }
        return ctype_digit((string)$max) && in_array((int)$max, self::PRICE_LIMITS, true) ? (int)$max : 0;
    }

    // A sort from the address bar, or '' (newest first) when it isn't one of SORTS
    public function cleanSort($sort)
    {
        return is_string($sort) && isset(self::SORTS[$sort]) ? $sort : '';
    }

    // A label from the address bar, or '' when it isn't one of the menu's filter chips
    public function cleanLabelFilter($label)
    {
        return is_string($label) && isset(self::LABEL_FILTERS[$label]) ? $label : '';
    }

}
?>
