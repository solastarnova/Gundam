<?php
$url = $url ?? fn($p = '') => $p;
$account_nav_active = 'wishlist';
?>
<div class="container account-page my-5 pt-5">
    <div class="row account-layout">
        <?php include __DIR__ . '/../partials/account-sidebar.php'; ?>
        <div class="col-lg-9 col-md-8">
            <div class="account-main-card account-main-padding">
                <div class="mb-4">
                    <h4 class="mb-4"><?= htmlspecialchars(__m('wishlist.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                    <p class="page-subtitle"><?= htmlspecialchars(__m('wishlist.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <p class="text-muted mb-3" id="wishlistCount"><?= htmlspecialchars(__m('wishlist.count_initial'), ENT_QUOTES, 'UTF-8') ?></p>
                <div class="row g-4" id="wishlistGrid"></div>
                <div id="emptyWishlist" class="empty-wishlist">
                    <div class="empty-wishlist-text"><?= htmlspecialchars(__m('wishlist.empty_title'), ENT_QUOTES, 'UTF-8') ?></div>
                    <p class="text-muted mb-4"><?= htmlspecialchars(__m('wishlist.empty_hint'), ENT_QUOTES, 'UTF-8') ?></p>
                    <a href="<?= $url('') ?>" class="btn btn-primary"><i class="bi bi-bag me-1"></i> <?= htmlspecialchars(__m('wishlist.go_shopping'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.VIEW_WISHLIST = <?= json_encode([
    'countTpl' => __m('wishlist.js_count_tpl'),
    'addToCart' => __mu('add_to_cart'),
    'addedPrefix' => __m('wishlist.js_added_prefix'),
    'addFailed' => __m('wishlist.js_add_failed'),
    'addFailedRetry' => __m('wishlist.js_add_failed_retry'),
    'confirmRemove' => __m('wishlist.js_confirm_remove'),
    'removeFailed' => __m('wishlist.js_remove_failed'),
    'removeFailedRetry' => __m('wishlist.js_remove_failed_retry'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
(function() {
    const v = window.VIEW_WISHLIST || {};
    let wishlist = [];
    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }
    async function loadWishlist() {
        const base = window.APP_BASE || '';
        const container = document.getElementById('wishlistGrid');
        const emptyWishlist = document.getElementById('emptyWishlist');
        const countElement = document.getElementById('wishlistCount');
        try {
            const res = await fetch(base + 'api/wishlist/items');
            const data = await res.json();
            wishlist = (data && data.items) ? data.items : [];
        } catch (e) {
            wishlist = [];
        }
        countElement.textContent = (v.countTpl || '').replace(/\{\{n\}\}/g, String(wishlist.length));
        if (wishlist.length === 0) {
            container.style.display = 'none';
            emptyWishlist.style.display = 'block';
            return;
        }
        container.style.display = '';
        emptyWishlist.style.display = 'none';
        container.innerHTML = '';
        const baseUrl = (base.replace(/\/$/, '') || '') + '/';
        const placeholderImg = baseUrl + 'images/placeholder.jpg';
        wishlist.forEach((item, index) => {
            const col = document.createElement('div');
            col.className = 'col-12 col-md-6 col-lg-4';
            let imgPath = (item.img && item.img.trim()) || (item.image_path && item.image_path.trim()) || '';
            if (!imgPath || imgPath === 'placeholder.jpg') imgPath = 'images/placeholder.jpg';
            else if (!imgPath.startsWith('http') && !imgPath.startsWith('images/')) imgPath = 'images/' + imgPath;
            const imgSrc = imgPath.startsWith('http') ? imgPath : (baseUrl + imgPath.replace(/^\/+/, ''));
            const productUrl = baseUrl + 'product/' + (item.id || '');
            col.innerHTML = `
                <div class="card h-100 product-card">
                    <a href="${escapeHtml(productUrl)}" class="text-decoration-none d-block">
                        <div class="ratio ratio-1x1 bg-light">
                            <img src="${escapeHtml(imgSrc)}" class="card-img-top object-fit-cover" alt="${escapeHtml(item.name || '')}" loading="lazy" onerror="this.onerror=null;this.src='${escapeHtml(placeholderImg)}';">
                        </div>
                    </a>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title text-truncate mb-2" title="${escapeHtml(item.name || '')}">${escapeHtml(item.name || '')}</h6>
                        <div class="price-box mb-2"><span class="member-price">${(typeof window.formatMoney === 'function' ? window.formatMoney(Number(item.price || 0)) : Number(item.price || 0).toFixed(2))}</span></div>
                        <div class="d-flex gap-2 mt-auto">
                            <button class="btn btn-primary btn-sm flex-grow-1 btn-add-cart-wishlist" data-index="${index}" type="button">
                                <i class="bi bi-cart-plus"></i> ${escapeHtml(v.addToCart || '')}
                            </button>
                            <button class="btn btn-outline-danger btn-sm btn-remove-wishlist" data-id="${item.id}" type="button">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(col);
        });
        container.querySelectorAll('.btn-add-cart-wishlist').forEach(btn => {
            btn.addEventListener('click', function() { addToCartFromWishlist(parseInt(this.getAttribute('data-index'), 10)); });
        });
        container.querySelectorAll('.btn-remove-wishlist').forEach(btn => {
            btn.addEventListener('click', function() { removeFromWishlist(parseInt(this.getAttribute('data-id'), 10)); });
        });
    }
    async function addToCartFromWishlist(index) {
        const item = wishlist[index];
        const base = window.APP_BASE || '';
        try {
            const fd = new FormData();
            fd.append('product_id', item.id);
            fd.append('quantity', 1);
            const res = await fetch(base + 'api/cart/add', { method: 'POST', body: fd });
            const data = await res.json();
            if (data && data.success) {
                if (typeof updateCartBadge === 'function') await updateCartBadge();
                alert((v.addedPrefix || '') + item.name);
            } else {
                alert((data && data.message) ? data.message : (v.addFailed || ''));
            }
        } catch (e) {
            alert(v.addFailedRetry || '');
        }
    }
    async function removeFromWishlist(productId) {
        if (!confirm(v.confirmRemove || '')) return;
        const base = window.APP_BASE || '';
        try {
            const fd = new FormData();
            fd.append('product_id', productId);
            const res = await fetch(base + 'api/wishlist/remove', { method: 'POST', body: fd });
            const data = await res.json();
            if (data && data.success) await loadWishlist();
            else alert(data && data.message ? data.message : (v.removeFailed || ''));
        } catch (e) {
            alert(v.removeFailedRetry || '');
        }
    }
    document.addEventListener('DOMContentLoaded', loadWishlist);
})();
</script>
