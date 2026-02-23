<?php
$url = $url ?? fn($p = '') => $p;
?>
<div class="container my-5 pt-5">
    <div class="row">
        <div class="col-lg-3 col-md-4">
            <div class="sidebar">
                <h5 class="px-4 mb-4 text-dark fw-bold">我的帳戶</h5>
                <div class="nav flex-column">
                    <a href="<?= $url('account') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-person me-2"></i> 個人資料</a>
                    <a href="<?= $url('account/orders') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-bag me-2"></i> 訂單記錄</a>
                    <a href="<?= $url('wishlist') ?>" class="nav-link d-flex align-items-center active"><i class="bi bi-heart me-2"></i> 喜愛清單</a>
                    <a href="#coupons" class="nav-link d-flex align-items-center"><i class="bi bi-ticket-perforated me-2"></i> 優惠券</a>
                    <a href="<?= $url('account/addresses') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-geo-alt me-2"></i> 預設地址</a>
                    <a href="#payment" class="nav-link d-flex align-items-center"><i class="bi bi-credit-card me-2"></i> 付款方式</a>
                    <a class="nav-link d-flex" href="<?= $url('account/settings') ?>"> 修改密碼</a>
                    <a class="nav-link d-flex text-primary" href="<?= $url('logout') ?>"> 登出</a>
                </div>
            </div>
        </div>
        <div class="bg-white rounded shadow-sm col-lg-9 col-md-8">
            <div class="py-3">
                <div class="mb-4">
                    <h4 class="mb-4">喜愛清單</h4>
                    <p class="page-subtitle">收藏您喜愛的商品，隨時查看與購買</p>
                </div>
                <p class="text-muted mb-3" id="wishlistCount">共 0 件商品</p>
                <div class="row g-4" id="wishlistGrid"></div>
                <div id="emptyWishlist" class="empty-wishlist">
                    <div class="empty-wishlist-text">您的喜愛清單是空的</div>
                    <p class="text-muted mb-4">將您喜愛的商品加入清單，方便以後查看與購買</p>
                    <a href="<?= $url('') ?>" class="btn btn-primary"><i class="bi bi-bag me-1"></i> 去購物</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
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
        countElement.textContent = '共 ' + wishlist.length + ' 件商品';
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
                <div class="card h-100">
                    <a href="${escapeHtml(productUrl)}" class="text-decoration-none d-block">
                        <div class="ratio ratio-1x1 bg-light">
                            <img src="${escapeHtml(imgSrc)}" class="card-img-top object-fit-cover" alt="${escapeHtml(item.name || '')}" loading="lazy" onerror="this.onerror=null;this.src='${escapeHtml(placeholderImg)}';">
                        </div>
                    </a>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title text-truncate mb-2" title="${escapeHtml(item.name || '')}">${escapeHtml(item.name || '')}</h6>
                        <p class="card-text text-danger fw-bold mb-2">HK$ ${Number(item.price || 0).toFixed(0)}</p>
                        <div class="d-flex gap-2 mt-auto">
                            <button class="btn btn-primary btn-sm flex-grow-1 btn-add-cart-wishlist" data-index="${index}" type="button">
                                <i class="bi bi-cart-plus"></i> 加入購物車
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
                alert('已加入購物車：' + item.name);
            } else {
                alert((data && data.message) ? data.message : '加入購物車失敗');
            }
        } catch (e) {
            alert('加入購物車失敗，請稍後再試');
        }
    }
    async function removeFromWishlist(productId) {
        if (!confirm('確定要移除嗎？')) return;
        const base = window.APP_BASE || '';
        try {
            const fd = new FormData();
            fd.append('product_id', productId);
            const res = await fetch(base + 'api/wishlist/remove', { method: 'POST', body: fd });
            const data = await res.json();
            if (data && data.success) await loadWishlist();
            else alert(data && data.message ? data.message : '移除失敗');
        } catch (e) {
            alert('移除失敗，請稍後再試');
        }
    }
    window.shareWishlist = function() { alert('分享功能開發中'); };
    document.addEventListener('DOMContentLoaded', function() {
        loadWishlist();
        var clearBtn = document.getElementById('clearWishlistBtn');
        if (clearBtn) clearBtn.addEventListener('click', function() {
            if (!confirm('確定要清空喜愛清單嗎？')) return;
            (async function() {
                var base = window.APP_BASE || '';
                var res = await fetch(base + 'api/wishlist/items');
                var data = await res.json();
                var items = (data && data.items) ? data.items : [];
                for (var i = 0; i < items.length; i++) {
                    var fd = new FormData();
                    fd.append('product_id', items[i].id);
                    await fetch(base + 'api/wishlist/remove', { method: 'POST', body: fd });
                }
                loadWishlist();
            })();
        });
    });
})();
</script>
