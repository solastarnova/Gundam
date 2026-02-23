<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CartModel;

class CartController extends Controller
{
    private CartModel $cartModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new CartModel();
    }

    /** Cart page (guest allowed, login prompted). */
    public function index(): void
    {
        $isLoggedIn = isset($_SESSION['user_id']);
        $this->render('cart/index', [
            'title' => '購物車 - ' . $this->getSiteName(),
            'isLoggedIn' => $isLoggedIn,
            'head_extra_css' => [],
        ]);
    }

    /** Get cart item count. */
    public function getCount(): void
    {
        $this->setupJsonApi();

        if (!isset($_SESSION['user_id'])) {
            $this->json(['count' => 0, 'isLoggedIn' => false]);
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $count = $this->cartModel->getCartItemsCount($userId);
        $this->json(['count' => $count, 'isLoggedIn' => true]);
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
            $this->json(['success' => false, 'message' => '無效的商品ID'], 400);
            return;
        }
        if ($quantity < 1 || $quantity > $this->cartModel->getMaxQuantity()) {
            $this->json(['success' => false, 'message' => '數量必須在 1-' . $this->cartModel->getMaxQuantity() . ' 之間'], 400);
            return;
        }
        $this->cartModel->addToCart($userId, $productId, $quantity);
        $this->json(['success' => true, 'message' => '已加入購物車']);
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
            $this->json(['success' => false, 'message' => '無效的購物車項目'], 400);
            return;
        }
        if ($quantity < 1 || $quantity > $this->cartModel->getMaxQuantity()) {
            $this->json(['success' => false, 'message' => '數量必須在 1-' . $this->cartModel->getMaxQuantity() . ' 之間'], 400);
            return;
        }
        if ($this->cartModel->updateCartItemQuantity($userId, $cartItemId, $quantity)) {
            $this->json(['success' => true, 'message' => '已更新數量']);
        } else {
            $this->json(['success' => false, 'message' => '操作失敗'], 400);
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
            $this->json(['success' => false, 'message' => '無效的購物車項目'], 400);
            return;
        }
        if ($this->cartModel->removeFromCart($userId, $cartItemId)) {
            $this->json(['success' => true, 'message' => '已移除商品']);
        } else {
            $this->json(['success' => false, 'message' => '操作失敗'], 400);
        }
    }
}
