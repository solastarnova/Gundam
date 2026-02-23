<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    /** Default display value when product has no rating on list page. */
    private const DEFAULT_DISPLAY_RATING = 5;

    private Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
    }

    public function list(): void
    {
        $limit = (int) ($this->getConfig()['product_list_limit'] ?? 50);
        $rows = $this->productModel->getFeatured($limit);
        $featuredProducts = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $price = (float) ($row['price'] ?? 0);
            $imgPath = $row['image_path'] ?? '';
            $category = trim((string) ($row['category'] ?? ''));
            $stockQty = (int) ($row['stock_quantity'] ?? 0);
            $featuredProducts[] = [
                'id' => $id,
                'name' => $row['name'] ?? '未命名商品',
                'image_path' => $imgPath,
                'price' => $price,
                'category' => $category !== '' ? $category : '其他',
                'stock_quantity' => $stockQty,
                'final_price' => '¥' . (string) (int) round($price),
                'original_price' => '¥' . (string) (int) round($price),
                'hkd_price' => 'HKD ' . number_format($price, 2, '.', ''),
                'rating' => self::DEFAULT_DISPLAY_RATING,
            ];
        }

        $categories = $this->productModel->getCategories();

        $this->render('product/list', [
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
            'title' => '模型專區 - ' . $this->getSiteName(),
            'head_extra_css' => ['css/item.css'],
        ]);
    }

    public function detail(string $id): void
    {
        $productId = (int) $id;
        $item = $this->productModel->find($productId);

        if (!$item) {
            http_response_code(404);
            $this->render('errors/404', [
                'title' => '商品不存在 - ' . $this->getSiteName(),
            ]);
            return;
        }

        $finalPrice = $item['price'];
        $discount   = $item['original_price'] ?? 0;
        $discountPercent = ($discount > $finalPrice)
            ? round((1 - $finalPrice / $discount) * 100)
            : 0;

        $siteNameEn = (string) ($this->getConfig()['site_name_en'] ?? 'Gundam Shop');
        $this->render('product/detail', [
            'item'            => $item,
            'finalPrice'      => $finalPrice,
            'discount'        => $discount,
            'discountPercent' => $discountPercent,
            'title'           => ($item['name'] ?? '') . ' - ' . $siteNameEn,
            'head_extra_css'  => ['css/item.css'],
            'foot_extra_js'  => ['js/cart.js', 'js/wishlist.js'],
        ]);
    }
}

