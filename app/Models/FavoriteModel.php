<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Config;

class FavoriteModel extends Model
{
    public function getUserFavorites(int $userId, ?int $limit = null): array
    {
        $placeholder = Config::defaultPlaceholderImage();
        $placeholderBasename = basename($placeholder);

        $sql = "SELECT i.id, i.name, i.price, i.image_path
                FROM user_favorites uf
                JOIN items i ON uf.item_id = i.id
                WHERE uf.user_id = ?
                ORDER BY uf.item_id DESC";
        if ($limit !== null) {
            $sql .= " LIMIT " . (int) $limit;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        $items = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $path = $row['image_path'] ? trim($row['image_path']) : '';
            if ($path === '' || ($placeholderBasename !== '' && $path === $placeholderBasename)) {
                $path = $placeholder;
            } elseif (strpos($path, 'images/') !== 0 && strpos($path, 'http') !== 0) {
                $path = 'images/' . $path;
            }
            $items[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'price' => (float) $row['price'],
                'img' => $path,
            ];
        }
        return $items;
    }

    public function isFavorite(int $userId, int $productId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM user_favorites WHERE user_id = ? AND item_id = ? LIMIT 1");
        $stmt->execute([$userId, $productId]);
        return $stmt->fetch() !== false;
    }

    public function addFavorite(int $userId, int $productId): bool
    {
        if ($this->isFavorite($userId, $productId)) {
            return true;
        }
        $stmt = $this->pdo->prepare("INSERT INTO user_favorites (user_id, item_id) VALUES (?, ?)");
        $stmt->execute([$userId, $productId]);
        return true;
    }

    public function removeFavorite(int $userId, int $productId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM user_favorites WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$userId, $productId]);
        return $stmt->rowCount() > 0;
    }
}
