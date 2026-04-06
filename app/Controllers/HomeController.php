<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Models\UserModel;

class HomeController extends Controller
{
    private Product $productModel;
    private Review $reviewModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
        $this->reviewModel = new Review();
    }

    public function index(): void
    {
        $featuredLimit = (int) Config::get('home_featured_limit', 8);
        $reviewsLimit = (int) Config::get('home_reviews_limit', 6);
        $placeholder = (string) Config::get('placeholder_image', 'images/placeholder.jpg');

        $dbProducts = $this->productModel->getFeatured($featuredLimit);

        $memberDiscountPercent = 0.0;
        if (isset($_SESSION['user_id'])) {
            $userId = (int) $_SESSION['user_id'];
            $membershipInfo = (new UserModel())->getMembershipInfo($userId);
            $currentRule = $membershipInfo['current_rule'] ?? null;
            $memberDiscountPercent = max(0.0, min(100.0, (float) ($currentRule['discount_percent'] ?? 0)));
        }

        foreach ($dbProducts as &$product) {
            $basePrice = (float) ($product['price'] ?? 0);
            $memberPrice = $basePrice;
            if ($memberDiscountPercent > 0) {
                $memberPrice = round($basePrice * (1 - ($memberDiscountPercent / 100)), 2);
            }
            $product['original_price'] = $basePrice;
            $product['price'] = $memberPrice;
            $product['discount_percent'] = $memberDiscountPercent;
        }
        unset($product);

        $dbReviews = $this->reviewModel->getFeaturedReviews($reviewsLimit);

        $projectRoot = dirname(__DIR__, 2);
        $slideDir = $projectRoot . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'slides' . DIRECTORY_SEPARATOR;
        $images = is_dir($slideDir) ? (glob($slideDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE) ?: []) : [];
        $images = array_map(function ($p) {
            return 'images/slides/' . basename($p);
        }, $images);
        if (empty($images)) {
            $images = [$placeholder];
        }

        $this->render('home/index', [
            'title' => $this->titleWithSite('home'),
            'dbProducts' => $dbProducts,
            'dbReviews' => $dbReviews,
            'images' => $images,
            'head_extra_css' => ['css/anime_section.css'],
            'foot_extra_js' => ['js/anime_section.js'],
        ]);
    }
}

