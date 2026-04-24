<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\ProductModel;
use App\Models\UserModel;

/** 渲染商店首頁內容與區塊資料。 */
class HomeController extends Controller
{
    private ProductModel $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new ProductModel();
    }

    /**
     * 為已登入會員補齊原價、折後價與折扣百分比欄位。
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function withMemberPricing(array $rows): array
    {
        $userModel = new UserModel();
        $memberDiscountPercent = isset($_SESSION['user_id'])
            ? $userModel->getMemberDiscountPercentForUser((int) $_SESSION['user_id'])
            : 0.0;

        foreach ($rows as &$product) {
            $basePrice = (float) ($product['price'] ?? 0);
            $memberPrice = UserModel::getDiscountedPrice($basePrice, $memberDiscountPercent);
            $product['original_price'] = $basePrice;
            $product['price'] = $memberPrice;
            $product['discount_percent'] = $memberDiscountPercent;
        }
        unset($product);

        return $rows;
    }

    /** 渲染首頁推薦區與動漫商品區。 */
    public function index(): void
    {
        $placeholder = Config::defaultPlaceholderImage();
        $homeCfg = Config::get('home', []);
        $homeCfg = is_array($homeCfg) ? $homeCfg : [];

        $newLimit = (int) ($homeCfg['new_arrivals_limit'] ?? Config::get('home_featured_limit', 8));
        $recLimit = (int) ($homeCfg['recommended_limit'] ?? Config::get('home_featured_limit', 8));
        $newLimit = max(1, $newLimit);
        $recLimit = max(1, $recLimit);

        $newArrivals = $this->withMemberPricing($this->productModel->getNewArrivals($newLimit));
        $recommendedProducts = $this->withMemberPricing($this->productModel->getRecommendedHome($recLimit));

        $categoryBoxes = $homeCfg['category_boxes'] ?? [];
        $categoryBoxes = is_array($categoryBoxes) ? array_values(array_filter($categoryBoxes, 'is_array')) : [];

        $projectRoot = dirname(__DIR__, 2);
        $slideDir = $projectRoot . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'slides' . DIRECTORY_SEPARATOR;
        $images = is_dir($slideDir) ? (glob($slideDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE) ?: []) : [];
        $images = array_map(function ($p) {
            return 'images/slides/' . basename($p);
        }, $images);
        if ($images === []) {
            $images = [$placeholder];
        }

        $news = $homeCfg['news'] ?? [];
        $news = is_array($news) ? array_values(array_filter($news, 'is_array')) : [];

        $this->render('home/index', [
            'title' => $this->titleWithSite('home'),
            'newArrivals' => $newArrivals,
            'recommendedProducts' => $recommendedProducts,
            'homeCategoryBoxes' => $categoryBoxes,
            'homeNews' => $news,
            'images' => $images,
            'head_extra_css' => [
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
            ],
            'foot_script_srcs' => [
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
            ],
            'foot_extra_js' => ['js/home-swiper.js'],
        ]);
    }
}
