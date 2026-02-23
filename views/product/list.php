<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$featuredProducts = $featuredProducts ?? $featured_products ?? [];
$categories = $categories ?? [];
$productCount = count($featuredProducts);
$prices = array_map(function ($p) { return (float) ($p['price'] ?? 0); }, $featuredProducts);
$minPrice = $prices ? min($prices) : 0;
$maxPrice = $prices ? max($prices) : 500;
if ($maxPrice <= $minPrice) {
    $maxPrice = $minPrice + 100;
}
?>
<div class="container-fluid product-list">
    <div class="row">
        <!-- 模型專區側邊欄（與 www1 一致） -->
        <div class="col-lg-3 col-md-4">
            <div class="model-sidebar">
                <h3 class="model-sidebar-title">模型專區</h3>
                <div class="product-count-info">
                    1-<?= $productCount ?> 件產品，共 <?= $productCount ?> 件
                </div>
                <div class="filter-section">
                    <h4 class="filter-title">分類</h4>
                    <ul class="category-list">
                        <li><a href="#" class="filter-category active" data-category="">全部</a></li>
                        <?php foreach ($categories as $cat): ?>
                        <li><a href="#" class="filter-category" data-category="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="filter-section">
                    <h4 class="filter-title">存貨狀態</h4>
                    <div class="filter-checkbox">
                        <input type="checkbox" id="stock-order" checked>
                        <label for="stock-order">可訂貨</label>
                    </div>
                    <div class="filter-checkbox">
                        <input type="checkbox" id="stock-available" checked>
                        <label for="stock-available">即時開賣</label>
                    </div>
                </div>
                <div class="filter-section">
                    <h4 class="filter-title">價格範圍</h4>
                    <div class="price-filter-container">
                        <div class="price-sliders">
                            <div class="slider-track"></div>
                            <div class="slider-track-active" id="active-track"></div>
                            <input type="range" class="price-slider" id="min-slider" min="<?= (int) floor($minPrice) ?>" max="<?= (int) ceil($maxPrice) ?>" step="1" value="<?= (int) floor($minPrice) ?>">
                            <input type="range" class="price-slider" id="max-slider" min="<?= (int) floor($minPrice) ?>" max="<?= (int) ceil($maxPrice) ?>" step="1" value="<?= (int) ceil($maxPrice) ?>">
                        </div>
                        <div class="price-inputs">
                            <div class="price-input-group">
                                <label>最低:</label>
                                <input type="number" id="min-price-input" min="<?= (int) floor($minPrice) ?>" max="<?= (int) ceil($maxPrice) ?>" step="1" value="<?= (int) floor($minPrice) ?>">
                            </div>
                            <span class="price-separator">-</span>
                            <div class="price-input-group">
                                <label>最高:</label>
                                <input type="number" id="max-price-input" min="<?= (int) floor($minPrice) ?>" max="<?= (int) ceil($maxPrice) ?>" step="1" value="<?= (int) ceil($maxPrice) ?>">
                            </div>
                        </div>
                        <div class="price-display">
                            <span id="current-min-price">HK$<?= (int) floor($minPrice) ?></span>
                            <span id="current-max-price">HK$<?= (int) ceil($maxPrice) ?></span>
                        </div>
                        <div class="filter-buttons">
                            <button class="btn btn-primary btn-sm" type="button" id="btn-apply-price">應用篩選</button>
                            <button class="btn btn-outline-secondary btn-sm" type="button" id="btn-reset-price">重設</button>
                        </div>
                    </div>
                </div>
                <div class="filter-section">
                    <h4 class="filter-title">品牌</h4>
                    <div class="brand-item">
                        <div class="filter-checkbox">
                            <input type="checkbox" id="brand-bandai" checked>
                            <label for="brand-bandai">Bandai</label>
                        </div>
                        <span>(<?= $productCount ?>)</span>
                    </div>
                </div>
                <div class="filter-section filter-actions">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btn-clear-filters">清除所有篩選</button>
                </div>
            </div>
        </div>

        <!-- 產品列表（與 www1 一致） -->
        <div class="col-lg-9 col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <span class="me-2">排序:</span>
                    <select class="form-select form-select-sm sort-select-width" id="sort-select">
                        <option value="default" selected>預設排序</option>
                        <option value="price-low">價格從低到高</option>
                        <option value="price-high">價格從高到低</option>
                        <option value="name">商品名稱</option>
                    </select>
                </div>
                <span class="text-muted">顯示 <span id="display-count">1-<?= $productCount ?></span> 件產品</span>
            </div>
            <div class="row d-flex justify-content-center" id="products-container">
                <?php foreach ($featuredProducts as $product): ?>
                <?php
                $original = (int) str_replace('¥', '', $product['original_price'] ?? '');
                $final = (int) str_replace('¥', '', $product['final_price'] ?? '');
                $discount = ($original > 0 && $final < $original) ? round((1 - $final / $original) * 100) : 0;
                $price = (float) ($product['price'] ?? 0);
                $stars = str_repeat('<i class="bi bi-star-fill"></i>', (int)($product['rating'] ?? 5)) . str_repeat('<i class="bi bi-star"></i>', 5 - (int)($product['rating'] ?? 5));
                $imgPath = $product['image_path'] ?? 'placeholder.jpg';
                $productLink = $url('product/' . (int)($product['id'] ?? 0));
                $imgSrc = $asset('images/' . $imgPath);
                $cat = $product['category'] ?? '其他';
                $inStock = (int)($product['stock_quantity'] ?? 0) > 0;
                ?>
                <div class="p-3 col-12 col-sm-6 col-lg-4 product-item" data-price="<?= (int) round($price) ?>" data-name="<?= htmlspecialchars($product['name'] ?? '') ?>" data-category="<?= htmlspecialchars($cat) ?>" data-stock="<?= $inStock ? '1' : '0' ?>" data-brand="Bandai">
                    <div class="card shadow p-3 mb-4 bg-body-tertiary rounded product-card position-relative">
                        <?php if ($discount > 0): ?>
                        <div class="product-discount-badge">-<?= $discount ?>%</div>
                        <?php endif; ?>
                        <div class="product-brand-wrap">
                            <span class="product-brand-badge">Bandai</span>
                        </div>
                        <a href="<?= $productLink ?>">
                            <img src="<?= $imgSrc ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name'] ?? '') ?>" loading="lazy" onerror="this.src='<?= $asset('images/placeholder.jpg') ?>'">
                        </a>
                        <div class="card-body">
                            <p class="card-text fw-bold"><?= htmlspecialchars($product['name'] ?? '') ?></p>
                            <div class="rating-stars">
                                <?= $stars ?>
                                <small class="text-muted">(<?= rand(5, 15) ?>)</small>
                            </div>
                            <div class="price-section mt-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="final-price">HK$ <?= number_format($price, 0) ?></span>
                                    <?php if ($original > $price): ?>
                                    <span class="original-price ms-2">HK$ <?= number_format($original, 0) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="stock-badge mt-3">▼ 有現貨</div>
                            <div class="d-grid mt-3">
                                <button type="button" class="btn btn-danger btn-add-to-cart"
                                    data-product-id="<?= (int)($product['id'] ?? 0) ?>"
                                    data-product-name="<?= htmlspecialchars($product['name'] ?? '') ?>"
                                    data-product-price="<?= htmlspecialchars($price) ?>"
                                    data-product-image="<?= htmlspecialchars($imgPath) ?>">
                                    <i class="bi bi-cart-plus me-2"></i>加入購物車
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var base = window.APP_BASE || '';
    var container = document.getElementById('products-container');
    var productCount = <?= (int) $productCount ?>;
    var minPrice = <?= (int) floor($minPrice) ?>, maxPrice = <?= (int) ceil($maxPrice) ?>;
    var currentMin = minPrice, currentMax = maxPrice;

    function updateDisplayCount(n) {
        var el = document.getElementById('display-count');
        if (el) el.textContent = '1-' + n;
    }
    function updateCountInfo(text) {
        var el = document.querySelector('.product-count-info');
        if (el) el.innerHTML = text;
    }

    var minSlider = document.getElementById('min-slider');
    var maxSlider = document.getElementById('max-slider');
    var minInput = document.getElementById('min-price-input');
    var maxInput = document.getElementById('max-price-input');
    var activeTrack = document.getElementById('active-track');
    var curMinEl = document.getElementById('current-min-price');
    var curMaxEl = document.getElementById('current-max-price');

    function updateTrack() {
        if (!activeTrack) return;
        var pctMin = ((currentMin - minPrice) / (maxPrice - minPrice || 1)) * 100;
        var pctMax = ((currentMax - minPrice) / (maxPrice - minPrice || 1)) * 100;
        activeTrack.style.left = pctMin + '%';
        activeTrack.style.width = (pctMax - pctMin) + '%';
    }
    function updatePriceDisplay() {
        if (curMinEl) curMinEl.textContent = 'HK$' + currentMin;
        if (curMaxEl) curMaxEl.textContent = 'HK$' + currentMax;
    }

    function getSelectedCategory() {
        var active = document.querySelector('.filter-category.active');
        return active ? (active.getAttribute('data-category') || '') : '';
    }
    function applyAllFilters() {
        var selectedCat = getSelectedCategory();
        var stockAvailable = document.getElementById('stock-available');
        var stockOrder = document.getElementById('stock-order');
        var brandBandai = document.getElementById('brand-bandai');
        var onlyInStock = stockAvailable && stockAvailable.checked;
        var showOrderable = stockOrder && stockOrder.checked;
        var showBandai = !brandBandai || brandBandai.checked;

        var items = document.querySelectorAll('.product-item');
        var visible = 0;
        items.forEach(function(el) {
            var p = parseFloat(el.getAttribute('data-price')) || 0;
            var cat = el.getAttribute('data-category') || '';
            var stock = el.getAttribute('data-stock') === '1';
            var brand = el.getAttribute('data-brand') || '';
            var priceOk = p >= currentMin && p <= currentMax;
            var catOk = selectedCat === '' || cat === selectedCat;
            var stockOk = (!onlyInStock || stock) && (showOrderable || stock);
            var brandOk = brand !== 'Bandai' || showBandai;
            var show = priceOk && catOk && stockOk && brandOk;
            el.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        updateDisplayCount(visible);
        var info = visible === productCount
            ? '1-' + productCount + ' 件產品，共 ' + productCount + ' 件'
            : '符合篩選: 共 ' + visible + ' 件商品';
        updateCountInfo(info);
    }
    function applyPriceFilter() {
        applyAllFilters();
    }
    function resetPriceFilter() {
        currentMin = minPrice;
        currentMax = maxPrice;
        if (minSlider) { minSlider.value = currentMin; minSlider.min = minPrice; minSlider.max = maxPrice; }
        if (maxSlider) { maxSlider.value = currentMax; maxSlider.min = minPrice; maxSlider.max = maxPrice; }
        if (minInput) { minInput.value = currentMin; minInput.min = minPrice; minInput.max = maxPrice; }
        if (maxInput) { maxInput.value = currentMax; maxInput.min = minPrice; maxInput.max = maxPrice; }
        updateTrack();
        updatePriceDisplay();
        applyAllFilters();
    }

    if (minSlider) minSlider.addEventListener('input', function() {
        currentMin = parseFloat(minSlider.value) || 0;
        if (currentMin > currentMax) currentMin = currentMax;
        minSlider.value = currentMin;
        if (minInput) minInput.value = currentMin;
        updateTrack();
        updatePriceDisplay();
    });
    if (maxSlider) maxSlider.addEventListener('input', function() {
        currentMax = parseFloat(maxSlider.value) || maxPrice;
        if (currentMax < currentMin) currentMax = currentMin;
        maxSlider.value = currentMax;
        if (maxInput) maxInput.value = currentMax;
        updateTrack();
        updatePriceDisplay();
    });
    if (minInput) minInput.addEventListener('change', function() {
        currentMin = parseFloat(minInput.value) || 0;
        if (minSlider) minSlider.value = currentMin;
        updateTrack();
        updatePriceDisplay();
    });
    if (maxInput) maxInput.addEventListener('change', function() {
        currentMax = parseFloat(maxInput.value) || maxPrice;
        if (maxSlider) maxSlider.value = currentMax;
        updateTrack();
        updatePriceDisplay();
    });
    var btnApply = document.getElementById('btn-apply-price');
    var btnReset = document.getElementById('btn-reset-price');
    if (btnApply) btnApply.addEventListener('click', applyPriceFilter);
    if (btnReset) btnReset.addEventListener('click', resetPriceFilter);

    var sortSelect = document.getElementById('sort-select');
    if (sortSelect) sortSelect.addEventListener('change', function() {
        var val = this.value;
        var items = Array.from(document.querySelectorAll('.product-item'));
        items = items.filter(function(el) { return el.style.display !== 'none'; });
        items.sort(function(a, b) {
            var pa = parseFloat(a.getAttribute('data-price')) || 0;
            var pb = parseFloat(b.getAttribute('data-price')) || 0;
            var na = (a.getAttribute('data-name') || '').toLowerCase();
            var nb = (b.getAttribute('data-name') || '').toLowerCase();
            if (val === 'price-low') return pa - pb;
            if (val === 'price-high') return pb - pa;
            if (val === 'name') return na.localeCompare(nb);
            return 0;
        });
        if (container) items.forEach(function(el) { container.appendChild(el); });
    });

    document.querySelectorAll('.btn-add-to-cart').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = parseInt(this.getAttribute('data-product-id'), 10) || 0;
            if (id <= 0) { alert('無法加入購物車'); return; }
            if (!window.isLoggedIn) {
                alert('請先登入才能加入購物車');
                window.location.href = base + 'login?redirect=' + encodeURIComponent(window.location.pathname || '/');
                return;
            }
            var fd = new FormData();
            fd.append('product_id', id);
            fd.append('quantity', 1);
            var self = this;
            fetch(base + 'api/cart/add', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && typeof updateCartBadge === 'function') updateCartBadge();
                    var orig = self.innerHTML;
                    self.innerHTML = '<i class="bi bi-check me-2"></i>已加入購物車';
                    self.classList.replace('btn-primary', 'btn-secondary');
                    self.disabled = true;
                    setTimeout(function() {
                        self.innerHTML = orig;
                        self.classList.replace('btn-secondary', 'btn-primary');
                        self.disabled = false;
                    }, 2000);
                })
                .catch(function() { alert('加入購物車失敗，請稍後再試'); });
        });
    });

    document.querySelectorAll('.filter-category').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.filter-category').forEach(function(l) { l.classList.remove('active'); });
            this.classList.add('active');
            applyAllFilters();
        });
    });
    var stockAvailable = document.getElementById('stock-available');
    var stockOrder = document.getElementById('stock-order');
    var brandBandai = document.getElementById('brand-bandai');
    if (stockAvailable) stockAvailable.addEventListener('change', applyAllFilters);
    if (stockOrder) stockOrder.addEventListener('change', applyAllFilters);
    if (brandBandai) brandBandai.addEventListener('change', applyAllFilters);

    var btnClearFilters = document.getElementById('btn-clear-filters');
    if (btnClearFilters) btnClearFilters.addEventListener('click', function() {
        document.querySelectorAll('.filter-category').forEach(function(l) { l.classList.remove('active'); });
        var allCat = document.querySelector('.filter-category[data-category=""]');
        if (allCat) allCat.classList.add('active');
        if (stockOrder) stockOrder.checked = true;
        if (stockAvailable) stockAvailable.checked = true;
        if (brandBandai) brandBandai.checked = true;
        resetPriceFilter();
    });

    updateTrack();
    updatePriceDisplay();
    applyAllFilters();
})();
</script>
