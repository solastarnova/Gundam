<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\CartModel;

/** 處理購物車頁面與購物車相關 API。 */
class CartController extends Controller
{
    private CartModel $cartModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new CartModel();
    }

    public function index(): void
    {
        $isLoggedIn = isset($_SESSION['user_id']);
        $this->render('cart/index', [
            'title' => $this->titleWithSite('cart'),
            'isLoggedIn' => $isLoggedIn,
            'head_extra_css' => [],
        ]);
    }

    public function getCount(): void
    {
        $this->setupJsonApi();

        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => true, 'count' => 0, 'isLoggedIn' => false]);
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $count = $this->cartModel->getCartItemsCount($userId);
        $this->json(['success' => true, 'count' => $count, 'isLoggedIn' => true]);
    }

    public function getItems(): void
    {
        $this->setupJsonApi();

        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => true, 'items' => []]);
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $items = $this->cartModel->getCartItems($userId);
        $this->json(['success' => true, 'items' => $items]);
    }

    public function add(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $productId = (int) ($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? $_GET['quantity'] ?? 1);
        if ($productId <= 0) {
            $msg = Config::get('messages.cart.invalid_product_id');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }
        $maxQty = $this->cartModel->getMaxQuantity();
        if ($quantity < 1 || $quantity > $maxQty) {
            $msg = sprintf(Config::get('messages.cart.quantity_range'), $maxQty);
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }
        $this->cartModel->addToCart($userId, $productId, $quantity);
        $this->json(['success' => true, 'message' => Config::get('messages.cart.added')]);
    }

    public function update(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $cartItemId = (int) ($_POST['cart_item_id'] ?? $_GET['cart_item_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? $_GET['quantity'] ?? 1);
        if ($cartItemId <= 0) {
            $msg = Config::get('messages.cart.invalid_item');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }
        $maxQty = $this->cartModel->getMaxQuantity();
        if ($quantity < 1 || $quantity > $maxQty) {
            $msg = sprintf(Config::get('messages.cart.quantity_range'), $maxQty);
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }
        if ($this->cartModel->updateCartItemQuantity($userId, $cartItemId, $quantity)) {
            $this->json(['success' => true, 'message' => Config::get('messages.cart.quantity_updated')]);
        } else {
            $msg = Config::get('messages.cart.operation_failed');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
        }
    }

    public function remove(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $cartItemId = (int) ($_POST['cart_item_id'] ?? $_GET['cart_item_id'] ?? 0);
        if ($cartItemId <= 0) {
            $msg = Config::get('messages.cart.invalid_item');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }
        if ($this->cartModel->removeFromCart($userId, $cartItemId)) {
            $this->json(['success' => true, 'message' => Config::get('messages.cart.item_removed')]);
        } else {
            $msg = Config::get('messages.cart.operation_failed');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
        }
    }
}
