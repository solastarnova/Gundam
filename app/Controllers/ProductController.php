<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\ProductModel;
use App\Models\ReviewModel;
use App\Models\UserModel;
use App\Services\MoneyFormatter;

class ProductController extends Controller
{
    private const DEFAULT_DISPLAY_RATING = 5;

    private ProductModel $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new ProductModel();
    }

    public function list(): void
    {
        $limit = (int) Config::get('product_list_limit', 50);
        $rows = $this->productModel->getFeatured($limit);

        $userModel = new UserModel();
        $memberDiscountPercent = isset($_SESSION['user_id'])
            ? $userModel->getMemberDiscountPercentForUser((int) $_SESSION['user_id'])
            : 0.0;

        $featuredProducts = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $basePrice = (float) ($row['price'] ?? 0);
            $memberPrice = UserModel::getDiscountedPrice($basePrice, $memberDiscountPercent);

            $imgPath = $row['image_path'] ?? '';
            $category = trim((string) ($row['category'] ?? ''));
            $stockQty = (int) ($row['stock_quantity'] ?? 0);
            $featuredProducts[] = [
                'id' => $id,
                'name' => $row['name'] ?? (string) Config::get('messages.view.product_list.unnamed_product', '未命名商品'),
                'image_path' => $imgPath,
                'price' => $memberPrice,
                'original_price' => $basePrice,
                'discount_percent' => $memberDiscountPercent,
                'category' => $category !== '' ? $category : (string) Config::get('messages.view.product_list.category_other', '其他'),
                'stock_quantity' => $stockQty,
                'final_price' => MoneyFormatter::format($memberPrice),
                'formatted_price' => MoneyFormatter::format($memberPrice),
                'rating' => self::DEFAULT_DISPLAY_RATING,
            ];
        }

        $categories = $this->productModel->getCategories();

        $this->render('product/list', [
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
            'title' => $this->titleWithSite('products_list'),
            'head_extra_css' => ['css/item.css'],
        ]);
    }

    public function detail(int $id): void
    {
        $id = (int) $id;
        $item = $this->productModel->find($id);

        if (!$item) {
            http_response_code(404);
            $this->render('errors/404', [
                'title' => $this->titleWithSite('product_not_found'),
            ]);
            return;
        }

        $basePrice = (float) ($item['price'] ?? 0);

        $userModel = new UserModel();
        $memberDiscountPercent = isset($_SESSION['user_id'])
            ? $userModel->getMemberDiscountPercentForUser((int) $_SESSION['user_id'])
            : 0.0;

        $finalPrice = UserModel::getDiscountedPrice($basePrice, $memberDiscountPercent);

        $discount = $basePrice;
        $discountPercent = $memberDiscountPercent;

        $siteNameEn = (string) Config::get('site_name_en', 'Gundam Shop');

        $reviewModel = new ReviewModel();
        $itemReviews = $reviewModel->getReviewsForItem($id, 50);
        $itemReviewCount = $reviewModel->countReviewsForItem($id);

        $this->render('product/detail', [
            'item'            => $item,
            'finalPrice'      => $finalPrice,
            'discount'        => $discount,
            'discountPercent' => $discountPercent,
            'itemReviews'     => $itemReviews,
            'itemReviewCount' => $itemReviewCount,
            'title'           => ($item['name'] ?? '') . ' - ' . $siteNameEn,
            'head_extra_css'  => ['css/item.css'],
            'foot_extra_js'  => ['js/cart.js', 'js/wishlist.js'],
        ]);
    }
}

