<?php

namespace App\Models;

use App\Core\Model;

/**
 * Review model: read/write user_item review fields.
 */
class Review extends Model
{
    private const STATUS_COMPLETED = 'Completed';
    private const STATUS_CONFIRMED = 'Confirmed';

    /**
     * Get featured reviews (e.g. for homepage).
     *
     * @param int $limit Limit
     * @return array
     */
    public function getFeaturedReviews(int $limit = 6): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT ui.*, u.name AS user_name, i.name AS product_name, i.image_path AS product_image
             FROM user_item ui
             JOIN users u ON ui.user_id = u.id
             JOIN items i ON ui.item_id = i.id
             WHERE ui.is_reviewed = 1 AND ui.review_content IS NOT NULL AND ui.review_rating >= 4
             ORDER BY ui.review_date DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Create or update review.
     *
     * @param int $userId User ID
     * @param int $itemId Item ID
     * @param string $title Title
     * @param string $content Content
     * @param int $rating Rating
     * @return bool
     */
    public function addReview(int $userId, int $itemId, string $title, string $content, int $rating): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE user_item
             SET review_title = ?, review_content = ?, review_rating = ?, review_date = NOW(), is_reviewed = 1, status = ?
             WHERE user_id = ? AND item_id = ? AND status = ?"
        );
        return $stmt->execute([$title, $content, $rating, self::STATUS_COMPLETED, $userId, $itemId, self::STATUS_CONFIRMED]);
    }
}
