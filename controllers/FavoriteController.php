<?php
require_once __DIR__ . "/../classes/FavoriteClass.php";

class FavoriteController
{
    private $favoriteModel;

    public function __construct()
    {
        $this->favoriteModel = new FavoriteClass();
    }

    /**
     * Saves the meal if it isn't saved yet, otherwise removes it. Returns true when it is now saved.
     */
    public function toggleFavorite($customerId, $productId)
    {
        if ($this->favoriteModel->isFavorite($customerId, $productId)) {
            $this->favoriteModel->removeFavorite($customerId, $productId);
            return false;
        }
        $this->favoriteModel->addFavorite($customerId, $productId);
        return true;
    }

    public function getFavoriteIds($customerId)
    {
        return $this->favoriteModel->getFavoriteIds($customerId);
    }

    public function getFavoriteProducts($customerId)
    {
        return $this->favoriteModel->getFavoriteProducts($customerId);
    }
}
?>
