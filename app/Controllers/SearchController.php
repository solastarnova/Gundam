<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\Product;

class SearchController extends Controller
{
    private Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
    }

    public function index(): void
    {
        $keyword = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
        $errorMessage = '';
        $products = [];
        $totalResults = 0;
        $minSearchLen = (int) Config::get('search.min_keyword_length', 2);
        if (strlen($keyword) < $minSearchLen) {
            if ($keyword !== '') {
                $errorMessage = sprintf(
                    (string) Config::get('messages.search.keyword_too_short'),
                    $minSearchLen
                );
            }
        } else {
            $products = $this->productModel->search($keyword);
            $totalResults = count($products);
        }
        $this->render('search/index', [
            'title' => ($keyword !== ''
                ? $this->titleFormat('search_with_keyword', $keyword, $this->getSiteName())
                : $this->titleWithSite('search')),
            'keyword' => $keyword,
            'errorMessage' => $errorMessage,
            'products' => $products,
            'totalResults' => $totalResults,
            'head_extra_css' => ['css/item.css'],
        ]);
    }
}
