<?php

/**
 * Homepage content: limits and editorial “latest news” items (SEO / authority column).
 * Paths are route paths passed to $url(), e.g. "products", "about".
 */
return [
    'new_arrivals_limit' => 8,
    'recommended_limit' => 8,

    /**
     * Homepage series strip (8:3-style cards). image = path under web root for $asset(), e.g. images/categories/mg-head.jpg
     * category = query ?category= value (must match items.category in DB for filter).
     */
    'category_boxes' => [
        ['category' => 'MG', 'series_tag' => '1/100', 'series_title' => 'MG', 'image' => 'images/categories/MG_freedom_2.0.png'],
        ['category' => 'RG', 'series_tag' => '1/144', 'series_title' => 'RG', 'image' => 'images/categories/RG_Cow_Tower.png'],
        ['category' => 'HG', 'series_tag' => '1/144', 'series_title' => 'HG', 'image' => 'images/categories/HG_feng_ling.png'],
        ['category' => 'PG', 'series_tag' => '1/60', 'series_title' => 'PG', 'image' => 'images/categories/PG_unicorn-shine.png'],
    ],

    'news' => [
        [
            'title' => 'RG 與 MG 怎樣揀？比例與玩法一文看懂',
            'excerpt' => '由零件數、可動到展示空間，幫你快速配對第一款主力比例。',
            'path' => 'products',
            'published' => '2026-04-08',
        ],
        [
            'title' => '水口與無縫：新手組裝常見問題整理',
            'excerpt' => '從剪鉗角度到補色時機，減少返工、提升完成度。',
            'path' => 'products',
            'published' => '2026-04-05',
        ],
        [
            'title' => '正版通路與售後：為何要在授權商城入手',
            'excerpt' => '原廠包裝、保固與客服流程，建立長期收藏信心。',
            'path' => 'about',
            'published' => '2026-04-01',
        ],
    ],
];
