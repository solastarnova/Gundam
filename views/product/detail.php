<?php
$asset = $asset ?? fn($p) => $p;
$imgPath = $asset('images/' . (trim($item['image_path'] ?? '') ?: 'placeholder.jpg'));
$itemReviews = $itemReviews ?? [];
$itemReviewCount = (int) ($itemReviewCount ?? 0);
?>

<script>
window.isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
window.VIEW_PRODUCT_DETAIL = <?= json_encode([
    'favoriteFavorited' => __m('product_detail.favorite_favorited'),
    'favoriteText' => __m('product_detail.favorite_text'),
    'favoriteAriaRemove' => __m('product_detail.favorite_aria_remove'),
    'favoriteAriaAdd' => __m('product_detail.favorite_aria_add'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
</script>

<div class="container1">
    <div class="product-image">
        <img src="<?= $imgPath ?>"
             alt="<?= htmlspecialchars($item['name']) ?>"
             onerror="this.onerror=null; this.src='<?= $asset('images/placeholder.jpg') ?>';">
    </div>

    <div class="product-info">
        <h1><?= htmlspecialchars($item['name']) ?></h1>

        <p class="review">
            <?php if ($itemReviewCount > 0): ?>
                <?= htmlspecialchars(__m('product_detail.buyer_reviews_count', (int) $itemReviewCount), ENT_QUOTES, 'UTF-8') ?>
            <?php else: ?>
                <?= htmlspecialchars(__m('product_detail.no_reviews'), ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
        </p>

        <?php if ($discountPercent > 0): ?>
        <div class="discount-badge">
            <?= htmlspecialchars(__m('product_detail.member_discount', rtrim(rtrim(number_format($discountPercent, 2, '.', ''), '0'), '.') . '%'), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <div class="price-section">
            <div class="price-box">
                <span class="member-price"><?= htmlspecialchars($money((float) $finalPrice), ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($discount > $finalPrice): ?>
                    <span class="original-price"><?= htmlspecialchars($money((float) $discount), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <?php if ($discountPercent > 0): ?>
                <div class="small text-success mt-1"><?= htmlspecialchars(__m('product_detail.applied_member_price'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <div class="hkd-price">
                <?= htmlspecialchars(__m('product_detail.series_stock', (string) ($item['category'] ?? 'RG'), (int) ($item['stock_quantity'] ?? 50)), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <div class="quantity mt-4">
            <label class="fw-bold"><?= htmlspecialchars(__m('product_detail.qty_label'), ENT_QUOTES, 'UTF-8') ?></label>
            <div class="d-flex align-items-center">
                <button class="qty-btn" onclick="changeQty(-1)">-</button>
                <input type="number" id="qty" value="1" min="1" max="<?= $item['stock_quantity'] ?? 99 ?>" readonly>
                <button class="qty-btn" onclick="changeQty(1)">+</button>
                <span class="stock ms-3 <?= ($item['stock_quantity'] ?? 0) > 0 ? 'text-success' : 'text-danger' ?>">
                    <i class="bi bi-<?= ($item['stock_quantity'] ?? 0) > 0 ? 'check-circle' : 'x-circle' ?>"></i>
                    <?= ($item['stock_quantity'] ?? 0) > 0 ? htmlspecialchars(__m('product_detail.in_stock'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__m('product_detail.out_of_stock'), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>

        <div class="actions mt-4">
            <button type="button" class="add-to-cart" id="product-add-to-cart-btn"
                    data-product-id="<?= (int)$item['id'] ?>"
                    data-product-name="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>"
                    data-product-price="<?= (float)$finalPrice ?>"
                    data-product-img="<?= htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8') ?>"
                    <?= ($item['stock_quantity'] ?? 0) <= 0 ? 'disabled' : '' ?>>
                <i class="bi bi-cart-plus"></i> <?= htmlspecialchars(__mu('add_to_cart'), ENT_QUOTES, 'UTF-8') ?>
            </button>
            <button type="button" class="btn btn-outline-danger btn-lg product-favorite-btn ms-2"
                    id="product-favorite-btn"
                    data-product-id="<?= (int)$item['id'] ?>"
                    data-product-name="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>"
                    data-product-price="<?= (float)$finalPrice ?>"
                    data-product-img="<?= htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8') ?>"
                    aria-label="<?= htmlspecialchars(__m('product_detail.favorite_aria_add'), ENT_QUOTES, 'UTF-8') ?>"
                    title="<?= htmlspecialchars(__m('product_detail.favorite_title_add'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-heart" id="favorite-icon"></i>
                <span id="favorite-text"><?= htmlspecialchars(__m('product_detail.favorite_text'), ENT_QUOTES, 'UTF-8') ?></span>
            </button>
        </div>

        <div class="product-description mt-4 p-3 bg-light rounded">
            <h5 class="fw-bold"><i class="bi bi-info-circle"></i> <?= htmlspecialchars(__m('product_detail.product_desc'), ENT_QUOTES, 'UTF-8') ?></h5>
            <p class="mb-0"><?= nl2br(htmlspecialchars($item['description'] ?? __m('product_detail.no_description'), ENT_QUOTES, 'UTF-8')) ?></p>
        </div>

        <?php if (!empty($itemReviews)): ?>
        <div class="product-reviews mt-4 p-3 border rounded">
            <h5 class="fw-bold mb-3"><i class="bi bi-chat-square-text"></i> <?= htmlspecialchars(__m('product_detail.buyer_reviews_heading'), ENT_QUOTES, 'UTF-8') ?></h5>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($itemReviews as $rev): ?>
                <div class="border-bottom pb-3 mb-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <strong class="small"><?= htmlspecialchars($rev['user_name'] ?? __m('product_detail.user_default'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="text-muted small"><?= !empty($rev['review_date']) ? date('Y-m-d', strtotime((string) $rev['review_date'])) : '' ?></span>
                        <span class="badge bg-primary bg-opacity-10 text-primary"><?= (int) ($rev['review_rating'] ?? 0) ?> / 5</span>
                    </div>
                    <div class="mb-1">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="text-warning"><?= $i <= (int)($rev['review_rating'] ?? 0) ? '★' : '☆' ?></span>
                        <?php endfor; ?>
                    </div>
                    <?php if (!empty($rev['review_title'])): ?>
                        <div class="fw-semibold small mb-1"><?= htmlspecialchars((string) $rev['review_title']) ?></div>
                    <?php endif; ?>
                    <p class="mb-0 small text-body-secondary"><?= nl2br(htmlspecialchars((string) ($rev['review_content'] ?? ''))) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function changeQty(delta) {
    var el = document.getElementById('qty');
    if (!el) return;
    var min = parseInt(el.getAttribute('min'), 10) || 1;
    var max = parseInt(el.getAttribute('max'), 10) || 999;
    var val = parseInt(el.value, 10) || min;
    val = val + delta;
    val = Math.max(min, Math.min(max, val));
    el.value = val;
}

document.addEventListener('DOMContentLoaded', function() {
    var addBtn = document.getElementById('product-add-to-cart-btn');
    if (addBtn && typeof window.addToCart === 'function') {
        addBtn.addEventListener('click', function() {
            var id = parseInt(this.dataset.productId, 10) || 0;
            var name = this.dataset.productName || '';
            var price = parseFloat(this.dataset.productPrice, 10) || 0;
            var img = this.dataset.productImg || '';
            var qtyEl = document.getElementById('qty');
            var qty = (qtyEl && qtyEl.value) ? parseInt(qtyEl.value, 10) : 1;
            if (id <= 0) return;
            window.addToCart(id, name, price, img, qty);
        });
    }

    var favBtn = document.getElementById('product-favorite-btn');
    if (!favBtn) return;
    var productId = parseInt(favBtn.dataset.productId, 10) || 0;
    var name = favBtn.dataset.productName || '';
    var price = parseFloat(favBtn.dataset.productPrice, 10) || 0;
    var img = favBtn.dataset.productImg || '';

    function setFavoriteState(isFavorite) {
        var v = window.VIEW_PRODUCT_DETAIL || {};
        var icon = document.getElementById('favorite-icon');
        var text = document.getElementById('favorite-text');
        if (!icon || !text) return;
        if (isFavorite) {
            icon.className = 'bi bi-heart-fill';
            text.textContent = v.favoriteFavorited || '';
            favBtn.classList.add('active');
            favBtn.setAttribute('aria-label', v.favoriteAriaRemove || '');
        } else {
            icon.className = 'bi bi-heart';
            text.textContent = v.favoriteText || '';
            favBtn.classList.remove('active');
            favBtn.setAttribute('aria-label', v.favoriteAriaAdd || '');
        }
    }

    if (window.isLoggedIn && typeof window.isInWishlistAsync === 'function') {
        window.isInWishlistAsync(productId).then(setFavoriteState);
    }

    favBtn.addEventListener('click', async function() {
        if (typeof window.Wishlist === 'undefined' || !window.Wishlist.toggle) return;
        var added = await window.Wishlist.toggle({ id: productId, name: name, price: price, img: img });
        setFavoriteState(added);
    });
});
</script>

