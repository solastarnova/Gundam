<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$dbProducts = $dbProducts ?? $db_products ?? [];
$dbReviews = $dbReviews ?? $db_reviews ?? [];
?>
<div class="container mt-5">
    <div id="dynamicCarousel" class="carousel slide" data-bs-ride="carousel">
        <?php if (count($images) > 1): ?>
        <div class="carousel-indicators">
            <?php foreach ($images as $index => $image): ?>
            <button type="button"
                    data-bs-target="#dynamicCarousel"
                    data-bs-slide-to="<?= $index ?>"
                    class="<?= $index === 0 ? 'active' : '' ?> bg-secondary rounded-circle carousel-indicators-dot"
                    aria-label="輪播圖 <?= $index + 1 ?>">
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="carousel-inner">
            <?php foreach ($images as $index => $image): ?>
            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                <div class="ratio ratio-16x9">
                    <div class="d-flex align-items-center justify-content-center">
                        <img src="<?= $asset($image) ?>"
                             class="img-fluid object-fit-contain mw-100 mh-100 p-3 p-md-4 p-lg-5"
                             alt="高達模型宣傳圖 <?= $index + 1 ?>"
                             loading="lazy"
                             onerror="this.onerror=null;this.src='<?= $asset('images/placeholder.jpg') ?>';">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($images) > 1): ?>
        <button class="carousel-control-prev d-none d-md-flex position-absolute top-50 start-0 translate-middle-y ms-4"
                type="button"
                data-bs-target="#dynamicCarousel"
                data-bs-slide="prev">
            <span class="carousel-control-prev-icon bg-dark bg-opacity-75 rounded-circle p-3"
                  aria-hidden="true"></span>
            <span class="visually-hidden">上一張</span>
        </button>
        <button class="carousel-control-next d-none d-md-flex position-absolute top-50 end-0 translate-middle-y me-4"
                type="button"
                data-bs-target="#dynamicCarousel"
                data-bs-slide="next">
            <span class="carousel-control-next-icon bg-dark bg-opacity-75 rounded-circle p-3"
                  aria-hidden="true"></span>
            <span class="visually-hidden">下一張</span>
        </button>
        <?php endif; ?>
    </div>
</div>

<main class="container py-4 position-relative">
    <h3 class="mt-2 mb-2 text-dark text-center">推薦產品</h3>

    <button class="carousel-control-prev position-absolute top-50 start-0 translate-middle-y d-none d-md-flex carousel-control-products-prev"
            type="button"
            data-bs-target="#productsCarousel"
            data-bs-slide="prev">
        <span class="carousel-control-prev-icon bg-dark rounded-circle p-3"></span>
        <span class="visually-hidden">上一組</span>
    </button>

    <button class="carousel-control-next position-absolute top-50 end-0 translate-middle-y d-none d-md-flex carousel-control-products-next"
            type="button"
            data-bs-target="#productsCarousel"
            data-bs-slide="next">
        <span class="carousel-control-next-icon bg-dark rounded-circle p-3"></span>
        <span class="visually-hidden">下一組</span>
    </button>

    <div id="productsCarousel" class="carousel slide" data-bs-ride="false" data-bs-interval="false">
        <div class="carousel-inner">
            <?php
            $displayLimit  = min(8, count($dbProducts));
            $chunkSize     = 4;
            $productChunks = array_chunk(array_slice($dbProducts, 0, $displayLimit), $chunkSize);

            foreach ($productChunks as $chunkIndex => $chunk):
            ?>
            <div class="carousel-item <?= $chunkIndex === 0 ? 'active' : '' ?>">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                    <?php foreach ($chunk as $product): ?>
                    <div class="col">
                        <div class="card h-100 product-card shadow-sm border-0">
                            <a href="<?= $url('product/' . (int)$product['id']) ?>" class="text-decoration-none">
                                <img src="<?= $asset('images/' . ($product['image_path'] ?: 'placeholder.jpg')) ?>"
                                     class="card-img-top card-img-cover"
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     loading="lazy"
                                     onerror="this.src='<?= $asset('images/placeholder.jpg') ?>'">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title mb-2 fs-6"><?= htmlspecialchars($product['name']) ?></h5>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="final-price fw-bold text-danger">HK$ <?= number_format($product['price']) ?></span>
                                        <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                            <small class="original-price text-muted text-decoration-line-through">HK$ <?= number_format($product['original_price']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a href="<?= $url('product/' . (int)$product['id']) ?>"
                                   class="btn btn-sm btn-outline-primary mt-3 w-100">
                                    查看詳情
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<div class="container mt-1">
    <div class="alert alert-primary mb-1 alert-reviews">
        產品評價 <span class="badge bg-primary ms-2">真實買家評價</span>
    </div>

    <div class="d-flex flex-nowrap overflow-x-auto pb-4">
        <div class="d-flex flex-nowrap overflow-auto gap-3 p-3">
            <?php if (!empty($dbReviews)): ?>
                <?php foreach ($dbReviews as $review): ?>
                <div class="card con review-card">
                    <div class="review-info">
                        <a href="<?= $url('product/' . (int)$review['item_id']) ?>" target="_blank">
                            <span class="product-name"><?= htmlspecialchars($review['product_name']) ?></span>
                        </a>
                        <img src="<?= $asset('images/' . ($review['product_image'] ?: 'placeholder.jpg')) ?>"
                             class="card-img-top"
                             alt="<?= htmlspecialchars($review['product_name']) ?>"
                             onerror="this.src='<?= $asset('images/placeholder.jpg') ?>';">
                    </div>
                    <div class="card-body review-card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-person-circle me-1"></i>
                            <small class="text-muted"><?= htmlspecialchars($review['user_name'] ?? '匿名用戶') ?></small>
                            <small class="text-muted ms-2"><?= date('Y-m-d', strtotime($review['review_date'])) ?></small>
                        </div>
                        <div class="starts mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= $review['review_rating'] ? 'filled' : '' ?>">★</span>
                            <?php endfor; ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary ms-2"><?= $review['review_rating'] ?>.0</span>
                        </div>
                        <h5 class="intro-title fw-bold"><?= htmlspecialchars($review['review_title'] ?? '用戶評價') ?></h5>
                        <p class="intro_content"><?= htmlspecialchars($review['review_content'] ?? '') ?></p>
                        <div class="more">閱讀更多</div>
                        <div class="mt-2">
                            <span class="badge bg-info bg-opacity-10 text-info">
                                <i class="bi bi-patch-check"></i> 已驗證購買
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="w-100 text-center py-5">
                    <div class="empty-state">
                        <i class="bi bi-chat-square-text empty-state-icon"></i>
                        <h5 class="mt-3 text-muted">暫無買家評價</h5>
                        <p class="text-muted small">成為第一個留下評價的買家吧！</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 模型專區（與 www1 一致：左文右輪播） -->
<section class="container-fluid anime-section" id="anime-section">
    <div class="container">
        <div class="row align-items-center mb-5">
            <div class="col-md-6 col-lg-5">
                <div class="mb-4">
                    <h3 class="display-4 fw-bold anime-section-title mb-3">模型專區</h3>
                    <p class="lead mb-4 anime-section-subtitle">精選人氣模型</p>
                    <a href="<?= $url('products') ?>" class="btn btn-outline-light btn-lg border-2 px-4 py-2 fw-bold">
                        <i class="bi bi-arrow-right me-2"></i>瀏覽更多
                    </a>
                </div>
            </div>
            <div class="col-md-6 col-lg-7 position-relative">
                <button class="carousel-control-prev position-absolute top-50 start-0 translate-middle-y d-none d-md-flex"
                        type="button"
                        data-bs-target="#animeProductsCarousel"
                        data-bs-slide="prev">
                    <span class="carousel-control-prev-icon bg-dark bg-opacity-75 rounded-circle p-2"></span>
                    <span class="visually-hidden">上一組</span>
                </button>
                <div id="animeProductsCarousel" class="carousel slide" data-bs-ride="false" data-bs-interval="false">
                    <div class="carousel-inner">
                        <?php
                        $displayLimit = min(8, count($dbProducts));
                        $chunkSize = 4;
                        $animeChunks = array_chunk(array_slice($dbProducts, 0, $displayLimit), $chunkSize);
                        foreach ($animeChunks as $acIndex => $acChunk):
                        ?>
                        <div class="carousel-item anime-carousel-item <?= $acIndex === 0 ? 'active' : '' ?>">
                            <div class="row row-cols-2 row-cols-md-2 row-cols-lg-4 g-3">
                                <?php foreach ($acChunk as $product): ?>
                                <div class="col">
                                    <div class="card h-100 product-card shadow-sm border-0">
                                        <a href="<?= $url('product/' . (int)$product['id']) ?>" class="text-decoration-none">
                                            <div class="ratio ratio-1x1">
                                                <img src="<?= $asset('images/' . ($product['image_path'] ?: 'placeholder.jpg')) ?>"
                                                     class="card-img-top object-fit-cover"
                                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                                     loading="lazy"
                                                     onerror="this.src='<?= $asset('images/placeholder.jpg') ?>'">
                                            </div>
                                        </a>
                                        <div class="card-body d-flex flex-column p-3">
                                            <h6 class="card-title mb-2 text-truncate"><?= htmlspecialchars($product['name']) ?></h6>
                                            <div class="mt-auto">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="final-price fw-bold text-danger">HK$ <?= number_format($product['price']) ?></span>
                                                    <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                                        <small class="original-price text-muted text-decoration-line-through">HK$ <?= number_format($product['original_price']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <a href="<?= $url('product/' . (int)$product['id']) ?>"
                                               class="btn btn-sm btn-outline-primary mt-2 w-100">查看詳情</a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="carousel-control-next position-absolute top-50 end-0 translate-middle-y d-none d-md-flex" type="button" data-bs-target="#animeProductsCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon bg-dark bg-opacity-75 rounded-circle p-2"></span>
                    <span class="visually-hidden">下一組</span>
                </button>
            </div>
        </div>
    </div>
</section>

