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

    public function getTotalCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM items");
        return (int)$stmt->fetchColumn();
    }

    public function getLowStock(int $threshold = 10, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM items WHERE stock_quantity < ? ORDER BY stock_quantity ASC LIMIT ?"
        );
        $stmt->bindValue(1, $threshold, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get paginated product list for admin (optional search).
     *
     * @param array $filters
     * @param int   $page
     * @param int   $perPage
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    public function getListForAdmin(array $filters, int $page, int $perPage): array
    {
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        $offset = (max(1, $page) - 1) * $perPage;

        $where = '';
        $params = [];
        if ($search !== '') {
            $where = ' WHERE name LIKE ? OR category LIKE ?';
            $params = ["%{$search}%", "%{$search}%"];
        }

        $countSql = "SELECT COUNT(*) FROM items" . $where;
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = "SELECT * FROM items" . $where . " ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $idx = 1;
        foreach ($params as $v) {
            $stmt->bindValue($idx++, $v);
        }
        $stmt->bindValue($idx++, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue($idx++, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return ['total' => $total, 'rows' => $rows];
    }
}
