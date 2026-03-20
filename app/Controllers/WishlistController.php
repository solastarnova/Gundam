<?php

namespace App\Controllers;

use App\Core\Config;
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
        $this->json(['success' => true, 'isFavorite' => $isFavorite, 'isLoggedIn' => $isLoggedIn]);
    }

    public function getItems(): void
    {
        $this->setupJsonApi();

        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => true, 'items' => []]);
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $items = $this->favoriteModel->getUserFavorites($userId);
        $this->json(['success' => true, 'items' => $items]);
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
            $msg = Config::get('messages.wishlist.invalid_product_id');
            $this->json(['success' => false, 'isFavorite' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }
        $isFavorite = $this->favoriteModel->isFavorite($userId, $productId);
        if ($isFavorite) {
            $success = $this->favoriteModel->removeFavorite($userId, $productId);
            $this->json([
                'success' => $success,
                'message' => Config::get('messages.wishlist.unfavorited'),
                'action' => 'removed',
                'isFavorite' => false,
            ]);
        } else {
            $this->favoriteModel->addFavorite($userId, $productId);
            $this->json([
                'success' => true,
                'message' => Config::get('messages.wishlist.favorited'),
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
            $msg = Config::get('messages.wishlist.invalid_product_id');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }
        if ($this->favoriteModel->removeFavorite($userId, $productId)) {
            $this->json(['success' => true, 'message' => Config::get('messages.wishlist.removed')]);
        } else {
            $msg = Config::get('messages.wishlist.operation_failed');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
        }
    }
}
