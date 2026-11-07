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

    public function addProduct($cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime = 15)
    {
        return $this->productModel->addProduct($cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime);
    }

    public function updateProduct($id, $cat, $brand, $title, $price, $desc, $image = null, $keywords = '', $prepTime = 15)
    {
        return $this->productModel->updateProduct($id, $cat, $brand, $title, $price, $desc, $image, $keywords, $prepTime);
    }

    public function deleteProduct($id)
    {
        return $this->productModel->deleteProduct($id);
    }
}
?>
