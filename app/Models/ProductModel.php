<?php

namespace App\Models;

use App\Core\Model;

/** 提供商品目錄查詢相關資料存取。 */
class ProductModel extends Model
{
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getFeatured(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM items ORDER BY RAND() LIMIT ?");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /** 依 listed_at（後台設定）與 id 取得最新商品。 */
    public function getNewArrivals(int $limit = 8): array
    {
        $limit = max(1, $limit);
        $stmt = $this->pdo->prepare(
            'SELECT * FROM items ORDER BY listed_at DESC, id DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    /** 取得首頁推薦商品（依 recommended_sort 與 id 排序）。 */
    public function getRecommendedHome(int $limit = 8): array
    {
        $limit = max(1, $limit);
        $stmt = $this->pdo->prepare(
            'SELECT * FROM items WHERE is_recommended = 1 ORDER BY recommended_sort ASC, id DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function getCategories(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT category FROM items WHERE category IS NOT NULL AND TRIM(category) != '' ORDER BY category");
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];
        return array_values(array_map('trim', array_filter($rows)));
    }

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

    /**
     * 提供 AI 系統提示用商品資料（限制筆數以控制提示長度）。
     *
     * @return list<array<string, mixed>>
     */
    public function getCatalogForChat(int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->pdo->prepare(
            'SELECT id, name, description, category, price, stock_quantity FROM items ORDER BY id ASC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
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
