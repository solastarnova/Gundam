<?php
$asset = $asset ?? fn($p) => $p;
$imgPath = $asset('images/' . (trim($item['image_path'] ?? '') ?: 'placeholder.jpg'));
?>

<script>
// 在佈局中 footer 也會補 APP_BASE，這裡確保 isLoggedIn 存在
window.isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>

<div class="container1">
    <!-- 左邊：商品圖片 -->
    <div class="product-image">
        <img src="<?= $imgPath ?>"
             alt="<?= htmlspecialchars($item['name']) ?>"
             onerror="this.onerror=null; this.src='<?= $asset('images/placeholder.jpg') ?>';">
    </div>

    <!-- 右邊：商品資訊 -->
    <div class="product-info">
        <h1><?= htmlspecialchars($item['name']) ?></h1>

        <p class="review">
            多人評價「很有質感」
            <span><?= number_format($item['review_count'] ?? rand(800, 1500)) ?> 人加購</span>
        </p>

        <!-- 折扣標籤 -->
        <?php if ($discountPercent > 0): ?>
        <div class="discount-badge">
            限時折扣 <?= $discountPercent ?>% OFF
        </div>
        <?php endif; ?>

        <div class="price-section">
            <div class="price">
                <span class="final-price">HK$ <?= number_format($finalPrice) ?></span>
                <?php if ($discount > $finalPrice): ?>
                    <span class="original-price">HK$ <?= number_format($discount) ?></span>
                <?php endif; ?>
            </div>
            <div class="hkd-price">
                <?= $item['category'] ?? 'RG' ?> 系列 | 庫存：<?= $item['stock_quantity'] ?? 50 ?> 件
            </div>
        </div>

        <!-- 數量選擇 -->
        <div class="quantity mt-4">
            <label class="fw-bold">數量</label>
            <div class="d-flex align-items-center">
                <button class="qty-btn" onclick="changeQty(-1)">-</button>
                <input type="number" id="qty" value="1" min="1" max="<?= $item['stock_quantity'] ?? 99 ?>" readonly>
                <button class="qty-btn" onclick="changeQty(1)">+</button>
                <span class="stock ms-3 <?= ($item['stock_quantity'] ?? 0) > 0 ? 'text-success' : 'text-danger' ?>">
                    <i class="bi bi-<?= ($item['stock_quantity'] ?? 0) > 0 ? 'check-circle' : 'x-circle' ?>"></i>
                    <?= ($item['stock_quantity'] ?? 0) > 0 ? '現貨供應' : '缺貨中' ?>
                </span>
            </div>
        </div>

        <!-- 按鈕區域 -->
        <div class="actions mt-4">
            <button type="button" class="add-to-cart" id="product-add-to-cart-btn"
                    data-product-id="<?= (int)$item['id'] ?>"
                    data-product-name="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>"
                    data-product-price="<?= (float)$finalPrice ?>"
                    data-product-img="<?= htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8') ?>"
                    <?= ($item['stock_quantity'] ?? 0) <= 0 ? 'disabled' : '' ?>>
                <i class="bi bi-cart-plus"></i> 加入購物車
            </button>
            <button type="button" class="btn btn-outline-danger btn-lg product-favorite-btn ms-2"
                    id="product-favorite-btn"
                    data-product-id="<?= (int)$item['id'] ?>"
                    data-product-name="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>"
                    data-product-price="<?= (float)$finalPrice ?>"
                    data-product-img="<?= htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8') ?>"
                    aria-label="加入收藏"
                    title="加入收藏">
                <i class="bi bi-heart" id="favorite-icon"></i>
                <span id="favorite-text">收藏</span>
            </button>
        </div>

        <!-- 商品描述 -->
        <div class="product-description mt-4 p-3 bg-light rounded">
            <h5 class="fw-bold"><i class="bi bi-info-circle"></i> 商品描述</h5>
            <p class="mb-0"><?= nl2br(htmlspecialchars($item['description'] ?? '暫無描述')) ?></p>
        </div>
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
    // 加入購物車按鈕
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

    // 收藏按鈕
    var favBtn = document.getElementById('product-favorite-btn');
    if (!favBtn) return;
    var productId = parseInt(favBtn.dataset.productId, 10) || 0;
    var name = favBtn.dataset.productName || '';
    var price = parseFloat(favBtn.dataset.productPrice, 10) || 0;
    var img = favBtn.dataset.productImg || '';

    function setFavoriteState(isFavorite) {
        var icon = document.getElementById('favorite-icon');
        var text = document.getElementById('favorite-text');
        if (!icon || !text) return;
        if (isFavorite) {
            icon.className = 'bi bi-heart-fill';
            text.textContent = '已收藏';
            favBtn.classList.add('active');
            favBtn.setAttribute('aria-label', '取消收藏');
        } else {
            icon.className = 'bi bi-heart';
            text.textContent = '收藏';
            favBtn.classList.remove('active');
            favBtn.setAttribute('aria-label', '加入收藏');
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

