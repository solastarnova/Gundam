<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$isLoggedIn = $isLoggedIn ?? $is_logged_in ?? false;
?>
<div class="container mt-5 pt-5">
    <h2 class="mb-4">購物車</h2>

    <?php if (!$isLoggedIn): ?>
        <div class="alert alert-info">
            <p class="mb-3">請先登入以使用購物車與結帳。</p>
            <a href="<?= $url('login') ?>?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? $url('cart')) ?>" class="btn btn-primary">前往登入</a>
            <a href="<?= $url('products') ?>" class="btn btn-outline-secondary me-3">繼續購物</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>商品</th>
                        <th>單價</th>
                        <th>數量</th>
                        <th>小計</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cart-items">
                    <tr><td colspan="5" class="text-center text-muted py-4">載入中...</td></tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">總計：</td>
                        <td colspan="2" class="text-primary fs-4 fw-bold" id="cart-total">HK$0</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="text-end mt-3 d-none" id="cart-actions">
            <a href="<?= $url('products') ?>" class="btn btn-outline-secondary me-3">繼續購物</a>
            <a href="<?= $url('checkout') ?>" class="btn btn-success">結帳</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($isLoggedIn): ?>
<script>
(function() {
    var base = window.APP_BASE || '';
    var tbody = document.getElementById('cart-items');
    var totalEl = document.getElementById('cart-total');
    var actionsEl = document.getElementById('cart-actions');

    function loadCart() {
        if (!tbody) return;
        fetch(base + 'api/cart/items')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var items = (data && data.items) ? data.items : [];
                if (items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5"><div class="empty-cart"><i class="bi bi-cart-x empty-cart-icon"></i><h5 class="mt-3 text-muted">購物車是空的</h5><a href="' + (base + 'products') + '" class="btn btn-primary mt-3">去逛逛</a></div></td></tr>';
                    if (totalEl) totalEl.textContent = 'HK$0';
                    if (actionsEl) actionsEl.classList.add('d-none');
                } else {
                    var html = '';
                    var total = 0;
                    var imgBase = base.replace(/\/$/, '') + '/';
                    items.forEach(function(item, index) {
                        var sub = (parseFloat(item.price) || 0) * (parseInt(item.qty, 10) || 1);
                        total += sub;
                        var img = (item.image_path ? (imgBase + 'images/' + item.image_path) : (imgBase + 'images/placeholder.jpg'));
                        var cartId = (item.cart_item_id || item.id || index);
                        html += '<tr>';
                        html += '<td><div class="d-flex align-items-center"><img src="' + img + '" class="cart-thumbnail me-3" alt=""><div><div class="fw-bold">' + (item.name || '').replace(/</g, '&lt;') + '</div><small class="text-muted">編號: ' + (item.id || 'N/A') + '</small></div></div></td>';
                        html += '<td class="fw-bold">HK$' + (parseFloat(item.price) || 0).toFixed(0) + '</td>';
                        html += '<td><div class="quantity-control"><button class="btn btn-sm btn-outline-secondary cart-qty-minus" type="button" data-cart-id="' + cartId + '"><i class="bi bi-dash"></i></button><span class="cart-qty-display fw-bold">' + (item.qty || 1) + '</span><button class="btn btn-sm btn-outline-secondary cart-qty-plus" type="button" data-cart-id="' + cartId + '"><i class="bi bi-plus"></i></button></div></td>';
                        html += '<td class="fw-bold text-primary">HK$' + sub.toFixed(0) + '</td>';
                        html += '<td><button class="btn btn-outline-danger btn-sm cart-remove" type="button" data-cart-id="' + cartId + '"><i class="bi bi-trash"></i> 移除</button></td>';
                        html += '</tr>';
                    });
                    tbody.innerHTML = html;
                    if (totalEl) totalEl.textContent = 'HK$' + total.toFixed(0);
                    if (actionsEl) actionsEl.classList.remove('d-none');
                    bindCartEvents(items);
                }
                if (typeof updateCartBadge === 'function') updateCartBadge();
            })
            .catch(function() {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">無法載入購物車，請稍後再試。</td></tr>';
            });
    }

    function bindCartEvents(items) {
        var rowByCartId = {};
        tbody.querySelectorAll('tr').forEach(function(tr, i) {
            var btn = tr.querySelector('.cart-qty-minus, .cart-qty-plus');
            if (btn && items[i]) rowByCartId[items[i].cart_item_id || items[i].id || i] = { tr: tr, item: items[i] };
        });
        tbody.querySelectorAll('.cart-qty-minus').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var cartId = this.getAttribute('data-cart-id');
                var span = this.closest('tr').querySelector('.cart-qty-display');
                var qty = Math.max(1, (parseInt(span.textContent, 10) || 1) - 1);
                updateQty(cartId, qty);
            });
        });
        tbody.querySelectorAll('.cart-qty-plus').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var span = this.closest('tr').querySelector('.cart-qty-display');
                var qty = (parseInt(span.textContent, 10) || 1) + 1;
                var cartId = this.getAttribute('data-cart-id');
                updateQty(cartId, qty);
            });
        });
        tbody.querySelectorAll('.cart-remove').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var cartId = this.getAttribute('data-cart-id');
                var fd = new FormData();
                fd.append('cart_item_id', cartId);
                fetch(base + 'api/cart/remove', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) { if (data && data.success) loadCart(); });
            });
        });
    }

    function updateQty(cartId, qty) {
        var fd = new FormData();
        fd.append('cart_item_id', cartId);
        fd.append('quantity', qty);
        fetch(base + 'api/cart/update', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) { if (data && data.success) loadCart(); });
    }

    loadCart();
})();
</script>
<?php endif; ?>
