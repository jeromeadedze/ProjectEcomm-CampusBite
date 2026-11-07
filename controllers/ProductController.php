<?php
require_once __DIR__ . "/../classes/ProductClass.php";

class ProductController
{
    const ZONES = [
        'Zone A' => 'Zone A: on campus (GH₵ 4.00 delivery)',
        'Zone B' => 'Zone B: Berekuso corridor (GH₵ 7.00 delivery)',
    ];

    const CATEGORY_ICONS = [
        'bi-egg-fried' => 'Fried egg',
        'bi-fire' => 'Flame',
        'bi-basket' => 'Basket',
        'bi-heart-pulse' => 'Healthy',
        'bi-cake2' => 'Cake',
        'bi-cup-hot' => 'Hot cup',
        'bi-cup-straw' => 'Cold drink',
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
        } elseif (!ctype_digit($values['commission_percent']) || (int)$values['commission_percent'] > 100) {
            $error = "Commission must be a whole number from 0 to 100.";
        } elseif ($this->productModel->brandNameExists($values['brand_name'], $excludeId)) {
            $error = "A kitchen called \"{$values['brand_name']}\" already exists.";
        }

        return ['values' => $values, 'error' => $error];
    }

    public function addBrand($name, $zone = 'Zone A', $location = '', $commission = 0.10, $isActive = 1)
    {
        return $this->productModel->addBrand($name, $zone, $location, $commission, $isActive);
    }

    public function updateBrand($id, $name, $zone = 'Zone A', $location = '', $commission = 0.10, $isActive = 1)
    {
        return $this->productModel->updateBrand($id, $name, $zone, $location, $commission, $isActive);
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

    public function searchProducts($query)
    {
        return $this->productModel->searchProducts($query);
    }

    public function getProductsForAdmin()
    {
        return $this->productModel->getProductsForAdmin();
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

}
?>
