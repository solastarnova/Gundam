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

    'map_client' => [
        'leaflet_css' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        'leaflet_js' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        'nominatim_reverse_url' => 'https://nominatim.openstreetmap.org/reverse',
        'maptiler_reverse_geocode_url' => 'https://api.maptiler.com/geocoding',
        'maptiler_sdk_css' => 'https://cdn.maptiler.com/maptiler-sdk-js/v4.0.1/maptiler-sdk.css',
        'maptiler_sdk_js' => 'https://cdn.maptiler.com/maptiler-sdk-js/v4.0.1/maptiler-sdk.umd.min.js',
        'maptiler_leaflet_js' => 'https://cdn.maptiler.com/leaflet-maptilersdk/v4.1.0/leaflet-maptilersdk.umd.min.js',
        'maptiler_geocoding_control_js' => 'https://cdn.maptiler.com/maptiler-geocoding-control/v3.0.0/leaflet.umd.js',
    ],

    /**
     * Lalamove API v3（同城配送）。憑證請設於 .env：LALAMOVE_API_KEY、LALAMOVE_API_SECRET。
     * @see https://developers.lalamove.com/
     */
    'lalamove' => [
        'api_key' => isset($_ENV['LALAMOVE_API_KEY']) && is_string($_ENV['LALAMOVE_API_KEY']) ? $_ENV['LALAMOVE_API_KEY'] : '',
        'api_secret' => isset($_ENV['LALAMOVE_API_SECRET']) && is_string($_ENV['LALAMOVE_API_SECRET']) ? $_ENV['LALAMOVE_API_SECRET'] : '',
        'sandbox' => !isset($_ENV['LALAMOVE_SANDBOX']) || $_ENV['LALAMOVE_SANDBOX'] === '' || filter_var($_ENV['LALAMOVE_SANDBOX'], FILTER_VALIDATE_BOOLEAN),
        'market' => isset($_ENV['LALAMOVE_MARKET']) && is_string($_ENV['LALAMOVE_MARKET']) && $_ENV['LALAMOVE_MARKET'] !== ''
            ? $_ENV['LALAMOVE_MARKET']
            : 'HK',
        'language' => isset($_ENV['LALAMOVE_LANGUAGE']) && is_string($_ENV['LALAMOVE_LANGUAGE']) && $_ENV['LALAMOVE_LANGUAGE'] !== ''
            ? $_ENV['LALAMOVE_LANGUAGE']
            : 'zh_HK',
        'pickup_lat' => isset($_ENV['LALAMOVE_PICKUP_LAT']) && is_string($_ENV['LALAMOVE_PICKUP_LAT']) ? trim($_ENV['LALAMOVE_PICKUP_LAT']) : '',
        'pickup_lng' => isset($_ENV['LALAMOVE_PICKUP_LNG']) && is_string($_ENV['LALAMOVE_PICKUP_LNG']) ? trim($_ENV['LALAMOVE_PICKUP_LNG']) : '',
        'pickup_address' => isset($_ENV['LALAMOVE_PICKUP_ADDRESS']) && is_string($_ENV['LALAMOVE_PICKUP_ADDRESS']) ? trim($_ENV['LALAMOVE_PICKUP_ADDRESS']) : '',
        'checkout_service_type' => isset($_ENV['LALAMOVE_SERVICE_TYPE']) && is_string($_ENV['LALAMOVE_SERVICE_TYPE']) && $_ENV['LALAMOVE_SERVICE_TYPE'] !== ''
            ? $_ENV['LALAMOVE_SERVICE_TYPE']
            : 'MOTORCYCLE',
        'nominatim_user_agent' => isset($_ENV['NOMINATIM_USER_AGENT']) && is_string($_ENV['NOMINATIM_USER_AGENT']) && $_ENV['NOMINATIM_USER_AGENT'] !== ''
            ? $_ENV['NOMINATIM_USER_AGENT']
            : 'GundamShop/1.0 (lalamove-checkout)',
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
