<?php

return [
    'base_url' => '',
    'site_name' => '高達模型商城',
    'order_number_prefix' => 'ORD',
    'site_name_en' => 'Gundam Shop',
    'cart_max_quantity' => 99,
    'min_password_length' => 8,
    'default_shipping_region' => '香港',
    'product_list_limit' => 50,
    'home_featured_limit' => 8,
    'home_reviews_limit' => 6,
    'placeholder_image' => 'images/placeholder.jpg',
    'currency' => [
        'code' => 'HKD',
        'locale' => 'zh-HK',
        'symbol' => 'HK$',
        'decimals' => 2,
    ],
    // 驗證碼設定
    'verification_code' => [
        'length' => 6,
        'ttl_seconds' => 600,
        'ttl_minutes' => 10,
    ],

    // 運費設定
    'shipping' => [
        'express_fee' => 80,
        'standard_fee' => 50,
        'free_threshold' => 500,
    ],

    // 訂單狀態
    'order_status' => [
        'allowed' => ['pending', 'paid', 'shipped', 'completed', 'cancelled'],
        'default' => 'pending',
        'transitions' => [
            'pending' => ['paid', 'cancelled'],
            'paid' => ['shipped', 'completed', 'cancelled'],
            'shipped' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ],
    ],

    // 上傳設定
    'upload' => [
        'max_size_bytes' => 2 * 1024 * 1024, // 2MB
        'images_path' => 'images', // 相對專案根目錄
    ],

    // 後台設定
    'admin' => [
        'list_per_page' => 15,
        'dashboard_recent_orders' => 5,
        'low_stock_threshold' => 10,
        'low_stock_limit' => 5,
        'user_detail_recent_orders' => 10, // 用戶詳情頁近期訂單筆數
    ],
];
