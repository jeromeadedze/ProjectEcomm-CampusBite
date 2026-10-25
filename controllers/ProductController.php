<?php
require_once __DIR__ . "/../classes/ProductClass.php";

class ProductController
{
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

    public function addBrand($name, $zone = 'Zone A', $location = '', $commission = 0.10)
    {
        return $this->productModel->addBrand($name, $zone, $location, $commission);
    }

    public function updateBrand($id, $name, $zone = 'Zone A', $location = '', $commission = 0.10)
    {
        return $this->productModel->updateBrand($id, $name, $zone, $location, $commission);
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
