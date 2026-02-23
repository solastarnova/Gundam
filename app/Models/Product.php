<?php

namespace App\Models;

use App\Core\Model;

/**
 * Product model: items table (find, getFeatured, search).
 */
class Product extends Model
{
    /**
     * Get product by ID.
     *
     * @param int $id Product ID
     * @return array|null Product row or null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get featured products (random).
     *
     * @param int $limit Limit
     * @return array
     */
    public function getFeatured(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM items ORDER BY RAND() LIMIT ?");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get distinct categories from items.category.
     *
     * @return list<string>
     */
    public function getCategories(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT category FROM items WHERE category IS NOT NULL AND TRIM(category) != '' ORDER BY category");
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];
        return array_values(array_map('trim', array_filter($rows)));
    }

    /**
     * Search products by keyword.
     *
     * @param string $keyword Keyword
     * @return array
     */
    public function search(string $keyword): array
    {
        $term = '%' . $keyword . '%';
        $stmt = $this->pdo->prepare("SELECT * FROM items WHERE name LIKE ? OR description LIKE ?");
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll() ?: [];
    }
}
