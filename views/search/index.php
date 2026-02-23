<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$keyword = $keyword ?? '';
$errorMessage = $errorMessage ?? $error_message ?? '';
$products = $products ?? [];
$totalResults = (int) ($totalResults ?? $total_results ?? 0);

function view_highlight_keyword($text, $keyword) {
    if (empty($keyword)) return htmlspecialchars($text);
    $pattern = '/(' . preg_quote($keyword, '/') . ')/iu';
    return preg_replace($pattern, '<span class="search-highlight">$1</span>', htmlspecialchars($text));
}
?>
<div class="container mt-5 pt-5">
    <div class="row">
        <div class="col-12">
            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-warning"><?= htmlspecialchars($errorMessage) ?></div>
            <?php elseif ($keyword !== ''): ?>
                <div class="search-stats">
                    找到 <span class="text-primary fw-bold"><?= $totalResults ?></span> 個與 "<span class="text-danger"><?= htmlspecialchars($keyword) ?></span>" 相關的商品
                </div>
                <?php if ($totalResults > 0): ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card h-100">
                                <img src="<?= $asset('images/' . ($product['image_path'] ?? 'placeholder.jpg')) ?>" class="card-img-top search-card-img" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='<?= $asset('images/placeholder.jpg') ?>'">
                                <div class="card-body">
                                    <h6 class="card-title"><?= view_highlight_keyword($product['name'], $keyword) ?></h6>
                                    <p class="card-text text-primary fw-bold">HK$ <?= number_format((float)($product['price'] ?? 0), 2) ?></p>
                                    <a href="<?= $url('product/' . (int)($product['id'] ?? 0)) ?>" class="btn btn-outline-primary btn-sm">查看詳情</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <i class="bi bi-search no-results-icon"></i>
                        <h4 class="mt-3">沒有找到相關商品</h4>
                        <p class="text-muted">嘗試使用其他關鍵詞或查看我們的熱門商品</p>
                        <a href="<?= $url('') ?>" class="btn btn-primary mt-3">瀏覽所有商品</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
