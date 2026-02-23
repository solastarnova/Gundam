<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FavoriteModel;

class WishlistController extends Controller
{
    private FavoriteModel $favoriteModel;

    public function __construct()
    {
        parent::__construct();
        $this->favoriteModel = new FavoriteModel();
    }

    public function index(): void
    {
        $this->requireUser();
        $this->render('wishlist/index', [
            'title' => '喜愛清單 - ' . $this->getSiteName(),
            'head_extra_css' => [],
        ]);
    }

    /** Check if product is in wishlist; returns { isFavorite, isLoggedIn }. */
    public function check(): void
    {
        $this->setupJsonApi();

        $isLoggedIn = isset($_SESSION['user_id']);
        $isFavorite = false;
        if ($isLoggedIn) {
            $productId = (int) ($_GET['product_id'] ?? $_GET['item_id'] ?? 0);
            if ($productId > 0) {
                $userId = (int) $_SESSION['user_id'];
                $isFavorite = $this->favoriteModel->isFavorite($userId, $productId);
            }
        }
        $this->json(['isFavorite' => $isFavorite, 'isLoggedIn' => $isLoggedIn]);
    }

    public function getItems(): void
    {
        $this->setupJsonApi();

        if (!isset($_SESSION['user_id'])) {
            $this->json(['items' => []]);
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $items = $this->favoriteModel->getUserFavorites($userId);
        $this->json(['items' => $items]);
    }

    public function toggle(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $productId = (int) ($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        if ($productId <= 0) {
            $this->json(['success' => false, 'isFavorite' => false, 'message' => '無效的商品ID'], 400);
            return;
        }
        $isFavorite = $this->favoriteModel->isFavorite($userId, $productId);
        if ($isFavorite) {
            $success = $this->favoriteModel->removeFavorite($userId, $productId);
            $this->json([
                'success' => $success,
                'message' => '已取消收藏',
                'action' => 'removed',
                'isFavorite' => false,
            ]);
        } else {
            $this->favoriteModel->addFavorite($userId, $productId);
            $this->json([
                'success' => true,
                'message' => '已加入收藏',
                'action' => 'added',
                'isFavorite' => true,
            ]);
        }
    }

    public function remove(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $productId = (int) ($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        if ($productId <= 0) {
            $this->json(['success' => false, 'message' => '無效的商品 ID'], 400);
            return;
        }
        if ($this->favoriteModel->removeFavorite($userId, $productId)) {
            $this->json(['success' => true, 'message' => '已移除']);
        } else {
            $this->json(['success' => false, 'message' => '操作失敗'], 400);
        }
    }
}
