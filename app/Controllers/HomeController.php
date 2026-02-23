<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\Review;

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
        $config = $this->getConfig();
        $featuredLimit = (int) ($config['home_featured_limit'] ?? 8);
        $reviewsLimit = (int) ($config['home_reviews_limit'] ?? 6);
        $placeholder = (string) ($config['placeholder_image'] ?? 'images/placeholder.jpg');

        $dbProducts = $this->productModel->getFeatured($featuredLimit);
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
            'title' => $this->getSiteName(),
            'dbProducts' => $dbProducts,
            'dbReviews' => $dbReviews,
            'images' => $images,
            'head_extra_css' => ['css/anime_section.css'],
            'foot_extra_js' => ['js/anime_section.js'],
        ]);
    }
}

