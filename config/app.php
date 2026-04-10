<?php

return [
    /**
     * Message bundle: languages/{locale}/common.json, languages/{locale}/api/*.json, mail|membership.json, view/*.json.
     * Override with APP_LOCALE in .env when additional locales exist.
     */
    'locale' => isset($_ENV['APP_LOCALE']) && is_string($_ENV['APP_LOCALE']) && $_ENV['APP_LOCALE'] !== ''
        ? $_ENV['APP_LOCALE']
        : 'zh_HK',

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

    'search' => [
        'min_keyword_length' => 2,
    ],

    'currency' => [
        'code' => 'HKD',
        'locale' => 'zh-HK',
        'symbol' => 'HK$',
        'decimals' => 2,
    ],
    'verification_code' => [
        'length' => 6,
        'ttl_seconds' => 600,
        'ttl_minutes' => 10,
    ],

    'shipping' => [
        'express_fee' => 80,
        'standard_fee' => 50,
        'free_threshold' => 500,
    ],

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

    'upload' => [
        'max_size_bytes' => 2 * 1024 * 1024,
        'images_path' => 'images',
    ],

    'admin' => [
        'list_per_page' => 15,
        'dashboard_recent_orders' => 5,
        'low_stock_threshold' => 10,
        'low_stock_limit' => 5,
        'user_detail_recent_orders' => 10,
    ],

    /**
     * Firebase ID Token 內 firebase.identities 的 provider id（鍵名）。
     * 若 token 含任一鍵，後端將視為 email_verified（略過 OAuth 常見的 false）。
     */
    'firebase' => [
        'trust_email_verified_identity_providers' => [
            'facebook.com',
            'google.com',
            'github.com',
        ],
    ],

    'home' => require __DIR__ . '/home.php',
];
