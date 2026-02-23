<?php

namespace App\Controllers;

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
        if (strlen($keyword) < 2) {
            if ($keyword !== '') {
                $errorMessage = '請輸入至少 2 個字元進行搜尋';
            }
        } else {
            $products = $this->productModel->search($keyword);
            $totalResults = count($products);
        }
        $this->render('search/index', [
            'title' => ($keyword !== '' ? '搜索：' . $keyword . ' - ' : '搜索 - ') . $this->getSiteName(),
            'keyword' => $keyword,
            'errorMessage' => $errorMessage,
            'products' => $products,
            'totalResults' => $totalResults,
            'head_extra_css' => ['css/item.css'],
        ]);
    }
}
