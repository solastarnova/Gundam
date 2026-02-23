<?php

namespace App\Models;

use App\Core\Model;

class CartModel extends Model
{
    private const CART_STATUS = 'Added to cart';

    private int $maxQuantity;

    private string $placeholderImage;

    public function __construct($pdo = null)
    {
        parent::__construct($pdo);
        $config = require __DIR__ . '/../../config/app.php';
        $this->maxQuantity = (int) ($config['cart_max_quantity'] ?? 99);
        $fullPath = (string) ($config['placeholder_image'] ?? 'images/placeholder.jpg');
        $this->placeholderImage = basename($fullPath) ?: 'placeholder.jpg';
    }

    public function getMaxQuantity(): int
    {
        return $this->maxQuantity;
    }

    /**
     * Get cart item list.
     *
     * @return list<array{cart_item_id: int, id: int, name: string, price: float, qty: int, image_path: string}>
     */
    public function getCartItems(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT ui.id AS cart_item_id, ui.item_id AS id, ui.quantity AS qty, i.name, i.price, i.image_path
            FROM user_item ui
            JOIN items i ON ui.item_id = i.id
            WHERE ui.user_id = ? AND ui.status = ?
            ORDER BY ui.date_time DESC
        ");
        $stmt->execute([$userId, self::CART_STATUS]);
        $items = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $items[] = [
                'cart_item_id' => (int) $row['cart_item_id'],
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'price' => (float) $row['price'],
                'qty' => (int) $row['qty'],
                'image_path' => $row['image_path'] ? trim($row['image_path']) : $this->placeholderImage,
            ];
        }
        return $items;
    }

    /** Get total cart item count. */
    public function getCartItemsCount(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(quantity), 0) AS total FROM user_item
            WHERE user_id = ? AND status = ?
        ");
        $stmt->execute([$userId, self::CART_STATUS]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    }

    /** Add to cart (merge quantity if item exists). */
    public function addToCart(int $userId, int $productId, int $quantity = 1): bool
    {
        $quantity = max(1, min($quantity, $this->maxQuantity));
        $stmt = $this->pdo->prepare("SELECT id, quantity FROM user_item WHERE user_id = ? AND item_id = ? AND status = ? LIMIT 1");
        $stmt->execute([$userId, $productId, self::CART_STATUS]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $newQty = min($this->maxQuantity, (int) $row['quantity'] + $quantity);
            $up = $this->pdo->prepare("UPDATE user_item SET quantity = ?, date_time = NOW() WHERE id = ?");
            $up->execute([$newQty, $row['id']]);
        } else {
            $ins = $this->pdo->prepare("INSERT INTO user_item (user_id, item_id, quantity, status, date_time) VALUES (?, ?, ?, ?, NOW())");
            $ins->execute([$userId, $productId, $quantity, self::CART_STATUS]);
        }
        return true;
    }

    /** Update cart item quantity. */
    public function updateCartItemQuantity(int $userId, int $cartItemId, int $quantity): bool
    {
        if ($quantity < 1) {
            return $this->removeFromCart($userId, $cartItemId);
        }
        $quantity = min($quantity, $this->maxQuantity);
        $stmt = $this->pdo->prepare("UPDATE user_item SET quantity = ?, date_time = NOW() WHERE id = ? AND user_id = ? AND status = ?");
        $stmt->execute([$quantity, $cartItemId, $userId, self::CART_STATUS]);
        return $stmt->rowCount() > 0;
    }

    /** Remove item from cart. */
    public function removeFromCart(int $userId, int $cartItemId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM user_item WHERE id = ? AND user_id = ? AND status = ?");
        $stmt->execute([$cartItemId, $userId, self::CART_STATUS]);
        return $stmt->rowCount() > 0;
    }

    /** Clear cart (e.g. after checkout). */
    public function clearCart(int $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM user_item WHERE user_id = ? AND status = ?");
        $stmt->execute([$userId, self::CART_STATUS]);
        return true;
    }

    /** Calculate cart subtotal. */
    public function calculateSubtotal(array $cartItems): float
    {
        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $price = (float) ($item['price'] ?? 0);
            $qty = (int) ($item['qty'] ?? $item['quantity'] ?? 1);
            $subtotal += $price * $qty;
        }
        return $subtotal;
    }
}
