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
                    <?= htmlspecialchars(__m('search.results_prefix'), ENT_QUOTES, 'UTF-8') ?><span class="text-primary fw-bold"><?= (int) $totalResults ?></span><?= htmlspecialchars(__m('search.results_mid'), ENT_QUOTES, 'UTF-8') ?><span class="text-danger">"<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"</span><?= htmlspecialchars(__m('search.results_suffix'), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php if ($totalResults > 0): ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card h-100 product-card">
                                <img src="<?= $asset('images/' . ($product['image_path'] ?? 'placeholder.jpg')) ?>" class="card-img-top search-card-img" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='<?= $asset('images/placeholder.jpg') ?>'">
                                <div class="card-body">
                                    <h6 class="card-title"><?= view_highlight_keyword($product['name'], $keyword) ?></h6>
                                    <div class="price-box mb-2">
                                        <span class="member-price"><?= htmlspecialchars($money((float)($product['price'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <a href="<?= $url('product/' . (int)($product['id'] ?? 0)) ?>" class="btn btn-outline-primary btn-sm"><?= htmlspecialchars(__m('search.view_detail'), ENT_QUOTES, 'UTF-8') ?></a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <i class="bi bi-search no-results-icon"></i>
                        <h4 class="mt-3"><?= htmlspecialchars(__m('search.no_results_title'), ENT_QUOTES, 'UTF-8') ?></h4>
                        <p class="text-muted"><?= htmlspecialchars(__m('search.no_results_hint'), ENT_QUOTES, 'UTF-8') ?></p>
                        <a href="<?= $url('') ?>" class="btn btn-primary mt-3"><?= htmlspecialchars(__m('search.browse_all'), ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
