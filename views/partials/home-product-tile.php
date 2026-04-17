<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$product = $product ?? [];
$homeProductTileMode = $homeProductTileMode ?? 'grid';
$isSwiperTile = $homeProductTileMode === 'swiper';
?>
<?php if (!$isSwiperTile): ?>
<div class="col-6 col-md-4 col-lg-3">
<?php endif; ?>
    <div class="card h-100 product-card rounded home-product-tile">
        <a href="<?= $url('product/' . (int)($product['id'] ?? 0)) ?>" class="text-decoration-none">
            <div class="ratio ratio-1x1 home-product-tile-img-wrap">
                <img src="<?= $asset('images/' . (($product['image_path'] ?? '') !== '' ? $product['image_path'] : 'placeholder.jpg')) ?>"
                     class="card-img-top object-fit-cover"
                     alt="<?= htmlspecialchars((string)($product['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                     loading="lazy"
                     onerror="this.src='<?= $asset('images/placeholder.jpg') ?>'">
            </div>
        </a>
        <div class="card-body d-flex flex-column p-3">
            <p class="card-title fs-6 mb-2 text-truncate"><?= htmlspecialchars((string)($product['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="mt-auto">
                <div class="price-box mb-1">
                    <span class="member-price"><?= htmlspecialchars($money((float)($product['price'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if (!empty($product['original_price']) && (float)$product['original_price'] > (float)($product['price'] ?? 0)): ?>
                    <span class="original-price"><?= htmlspecialchars($money((float)$product['original_price']), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="<?= $url('product/' . (int)($product['id'] ?? 0)) ?>"
               class="btn btn-sm btn-outline-primary mt-2 w-100"><?= htmlspecialchars(__m('home.view_detail'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>
<?php if (!$isSwiperTile): ?>
</div>
<?php endif; ?>
