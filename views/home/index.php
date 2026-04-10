<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$images = $images ?? [];
$newArrivals = $newArrivals ?? [];
$recommendedProducts = $recommendedProducts ?? [];
$homeCategoryBoxes = $homeCategoryBoxes ?? [];
$homeNews = $homeNews ?? [];
?>
<nav class="home-secondary-nav border-bottom bg-white" aria-label="<?= htmlspecialchars(__m('home.secondary_nav_aria'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="container">
        <ul class="nav home-secondary-nav-list flex-nowrap overflow-x-auto py-2 mb-0 small">
            <li class="nav-item"><a class="nav-link px-2 py-1" href="<?= $url('') ?>#home-hero"><?= htmlspecialchars(__m('home.nav_home'), ENT_QUOTES, 'UTF-8') ?></a></li>
            <li class="nav-item"><a class="nav-link px-2 py-1" href="<?= $url('products') ?>"><?= htmlspecialchars(__m('home.nav_products'), ENT_QUOTES, 'UTF-8') ?></a></li>
            <li class="nav-item"><a class="nav-link px-2 py-1" href="<?= $url('') ?>#home-categories"><?= htmlspecialchars(__m('home.nav_categories'), ENT_QUOTES, 'UTF-8') ?></a></li>
            <li class="nav-item"><a class="nav-link px-2 py-1" href="<?= $url('') ?>#home-new-arrivals"><?= htmlspecialchars(__m('home.nav_new_arrivals'), ENT_QUOTES, 'UTF-8') ?></a></li>
            <li class="nav-item"><a class="nav-link px-2 py-1" href="<?= $url('') ?>#home-featured"><?= htmlspecialchars(__m('home.nav_featured'), ENT_QUOTES, 'UTF-8') ?></a></li>
            <li class="nav-item"><a class="nav-link px-2 py-1" href="<?= $url('') ?>#home-news"><?= htmlspecialchars(__m('home.nav_news'), ENT_QUOTES, 'UTF-8') ?></a></li>
        </ul>
    </div>
</nav>

<section id="home-hero">
    <div class="container mt-5">
        <?php $heroSlideCount = count($images); ?>
        <div class="swiper home-hero-swiper overflow-hidden rounded-2 position-relative">
            <div class="swiper-wrapper">
                <?php foreach ($images as $index => $image): ?>
                <?php
                $heroImgUrl = $asset($image);
                $heroCssUrl = addcslashes($heroImgUrl, "'\\");
                $heroBgStyle = "--bg-img: url('" . $heroCssUrl . "');";
                ?>
                <div class="swiper-slide home-hero-swiper-slide"
                     style="<?= htmlspecialchars($heroBgStyle, ENT_COMPAT, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars($heroImgUrl, ENT_QUOTES, 'UTF-8') ?>"
                         class="home-hero-swiper-img"
                         alt="<?= htmlspecialchars(__m('home.promo_alt', $index + 1), ENT_QUOTES, 'UTF-8') ?>"
                         <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
                         onerror="this.onerror=null;this.src='<?= htmlspecialchars($asset('images/placeholder.jpg'), ENT_QUOTES, 'UTF-8') ?>';">
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($heroSlideCount > 1): ?>
            <div class="swiper-pagination home-hero-swiper-pagination"></div>
            <div class="swiper-button-prev home-hero-swiper-nav" aria-label="<?= htmlspecialchars(__m('home.prev_slide'), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="swiper-button-next home-hero-swiper-nav" aria-label="<?= htmlspecialchars(__m('home.next_slide'), ENT_QUOTES, 'UTF-8') ?>"></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="home-usp" class="home-usp-bar py-4 border-bottom">
    <div class="container">
        <div class="row g-4 text-center text-md-start">
            <div class="col-6 col-md-3">
                <div class="home-usp-item">
                    <i class="bi bi-patch-check-fill home-usp-icon text-primary" aria-hidden="true"></i>
                    <h2 class="home-usp-title h6 mb-1"><?= htmlspecialchars(__m('home.usp_authentic_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="home-usp-text small text-muted mb-0"><?= htmlspecialchars(__m('home.usp_authentic_text'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="home-usp-item">
                    <i class="bi bi-truck-front-fill home-usp-icon text-primary" aria-hidden="true"></i>
                    <h2 class="home-usp-title h6 mb-1"><?= htmlspecialchars(__m('home.usp_shipping_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="home-usp-text small text-muted mb-0"><?= htmlspecialchars(__m('home.usp_shipping_text'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="home-usp-item">
                    <i class="bi bi-shield-lock-fill home-usp-icon text-primary" aria-hidden="true"></i>
                    <h2 class="home-usp-title h6 mb-1"><?= htmlspecialchars(__m('home.usp_payment_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="home-usp-text small text-muted mb-0"><?= htmlspecialchars(__m('home.usp_payment_text'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="home-usp-item">
                    <i class="bi bi-headset home-usp-icon text-primary" aria-hidden="true"></i>
                    <h2 class="home-usp-title h6 mb-1"><?= htmlspecialchars(__m('home.usp_support_title'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="home-usp-text small text-muted mb-0"><?= htmlspecialchars(__m('home.usp_support_text'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="home-categories" class="home-category-strip py-5">
    <div class="container">
        <h2 class="home-section-title h4 mb-4"><?= htmlspecialchars(__m('home.categories_title'), ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if ($homeCategoryBoxes !== []): ?>
        <div class="category-container">
            <?php foreach ($homeCategoryBoxes as $box): ?>
            <?php
            $cat = trim((string)($box['category'] ?? ''));
            $tag = trim((string)($box['series_tag'] ?? ''));
            $title = trim((string)($box['series_title'] ?? ''));
            $imgRel = trim((string)($box['image'] ?? ''));
            if ($cat === '' || $title === '') {
                continue;
            }
            $bgUrl = $imgRel !== '' ? $asset($imgRel) : '';
            $boxHref = $url('products') . '?category=' . rawurlencode($cat);
            $aria = $title . ($tag !== '' ? ' — ' . $tag : '');
            $style = '';
            if ($bgUrl !== '') {
                $cssUrl = addcslashes($bgUrl, "'\\");
                $style = "--bg-img: url('" . $cssUrl . "');";
            }
            ?>
            <a href="<?= htmlspecialchars($boxHref, ENT_QUOTES, 'UTF-8') ?>"
               class="category-box<?= $bgUrl === '' ? ' category-box--no-image' : '' ?>"
               <?= $style !== '' ? ' style="' . htmlspecialchars($style, ENT_COMPAT, 'UTF-8') . '"' : '' ?>
               aria-label="<?= htmlspecialchars(__m('home.category_box_aria', $aria), ENT_QUOTES, 'UTF-8') ?>">
                <div class="category-overlay" aria-hidden="true"></div>
                <div class="category-content">
                    <?php if ($tag !== ''): ?>
                    <span class="series-tag"><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <h3 class="series-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted mb-0"><?= htmlspecialchars(__m('home.categories_empty'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
</section>

<section id="home-new-arrivals" class="py-5 bg-body-tertiary">
    <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-4">
            <h2 class="home-section-title h4 mb-0"><?= htmlspecialchars(__m('home.new_arrivals_title'), ENT_QUOTES, 'UTF-8') ?></h2>
            <a href="<?= $url('products') ?>" class="btn btn-sm btn-primary"><?= htmlspecialchars(__m('home.section_shop_more'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
        <?php if ($newArrivals !== []): ?>
        <?php $homeProductTileMode = 'swiper'; ?>
        <div class="swiper new-arrival-swiper home-product-swiper position-relative px-xl-5">
            <div class="swiper-wrapper">
                <?php foreach ($newArrivals as $product): ?>
                <div class="swiper-slide h-auto">
                    <?php include __DIR__ . '/../partials/home_product_tile.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-button-next" aria-label="<?= htmlspecialchars(__m('home.swiper_next'), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="swiper-button-prev" aria-label="<?= htmlspecialchars(__m('home.swiper_prev'), ENT_QUOTES, 'UTF-8') ?>"></div>
        </div>
        <?php else: ?>
        <div class="text-center py-5 text-muted"><?= htmlspecialchars(__m('home.products_empty'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
</section>

<section id="home-featured" class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-4">
            <h2 class="home-section-title h4 mb-0"><?= htmlspecialchars(__m('home.featured_title'), ENT_QUOTES, 'UTF-8') ?></h2>
            <a href="<?= $url('products') ?>" class="btn btn-sm btn-primary"><?= htmlspecialchars(__m('home.section_shop_more'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
        <?php if ($recommendedProducts !== []): ?>
        <?php $homeProductTileMode = 'swiper'; ?>
        <div class="swiper featured-swiper home-product-swiper position-relative px-xl-5">
            <div class="swiper-wrapper">
                <?php foreach ($recommendedProducts as $product): ?>
                <div class="swiper-slide h-auto">
                    <?php include __DIR__ . '/../partials/home_product_tile.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-button-next" aria-label="<?= htmlspecialchars(__m('home.swiper_next'), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="swiper-button-prev" aria-label="<?= htmlspecialchars(__m('home.swiper_prev'), ENT_QUOTES, 'UTF-8') ?>"></div>
        </div>
        <?php else: ?>
        <div class="text-center py-5 text-muted"><?= htmlspecialchars(__m('home.products_empty'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
</section>

<section id="home-news" class="py-5 bg-body-tertiary border-top">
    <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-4">
            <h2 class="home-section-title h4 mb-0"><?= htmlspecialchars(__m('home.news_title'), ENT_QUOTES, 'UTF-8') ?></h2>
            <span class="small text-muted"><?= htmlspecialchars(__m('home.news_subtitle'), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php if ($homeNews !== []): ?>
        <div class="row g-4">
            <?php foreach ($homeNews as $article): ?>
            <?php
            $title = trim((string)($article['title'] ?? ''));
            $excerpt = trim((string)($article['excerpt'] ?? ''));
            $path = trim((string)($article['path'] ?? 'products'));
            if (!preg_match('#^[a-zA-Z0-9][a-zA-Z0-9/_-]*$#', $path)) {
                $path = 'products';
            }
            $published = trim((string)($article['published'] ?? ''));
            if ($title === '') {
                continue;
            }
            ?>
            <div class="col-md-4">
                <article class="card h-100 border-0 shadow-sm home-news-card position-relative">
                    <div class="card-body d-flex flex-column">
                        <?php if ($published !== ''): ?>
                        <time class="small text-muted mb-2" datetime="<?= htmlspecialchars($published, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($published, ENT_QUOTES, 'UTF-8') ?></time>
                        <?php endif; ?>
                        <h3 class="h5 card-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
                        <?php if ($excerpt !== ''): ?>
                        <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <a href="<?= $url($path) ?>" class="stretched-link fw-semibold small mt-auto"><?= htmlspecialchars(__m('home.news_read_more'), ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted mb-0"><?= htmlspecialchars(__m('home.news_empty'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
</section>
