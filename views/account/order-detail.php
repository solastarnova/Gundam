<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$order = $order ?? null;
$orderItems = $orderItems ?? [];
$canReviewItems = $canReviewItems ?? [];
$reviewSuccess = $reviewSuccess ?? null;
$reviewError = $reviewError ?? null;

$statusLabels = [
    'pending' => ['label' => __m('account.order_detail.status_pending_payment'), 'class' => 'warning'],
    'paid' => ['label' => __m('account.order_detail.status_paid'), 'class' => 'info'],
    'shipped' => ['label' => __m('account.order_detail.status_shipped'), 'class' => 'primary'],
    'completed' => ['label' => __m('account.order_detail.status_completed'), 'class' => 'success'],
    'cancelled' => ['label' => __m('account.order_detail.status_cancelled'), 'class' => 'secondary'],
];
$paymentLabels = [
    'credit' => __m('account.order_detail.pay_credit'),
    'paypal' => __m('account.order_detail.pay_paypal'),
    'credit_card' => __m('account.order_detail.pay_credit'),
];
?>
<div class="container my-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-4">
                <h2 class="mb-1"><?= htmlspecialchars(__m('account.order_detail.title'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-muted"><?= htmlspecialchars(__m('account.order_detail.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <?php if (!empty($reviewSuccess)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($reviewSuccess) ?></div>
            <?php endif; ?>
            <?php if (!empty($reviewError)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($reviewError) ?></div>
            <?php endif; ?>

            <?php if ($order): ?>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><?= htmlspecialchars(__m('account.order_detail.card_items'), ENT_QUOTES, 'UTF-8') ?></h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($orderItems)): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($orderItems as $item): ?>
                                            <?php
                                            $itemId = (int)($item['item_id'] ?? 0);
                                            $detailUrl = $itemId ? $url('product/' . $itemId) : '#';
                                            $canReview = !empty($canReviewItems[$itemId]);
                                            ?>
                                            <div class="list-group-item">
                                                <div class="d-flex align-items-center gap-3">
                                                    <?php
                                                    $rawImg = trim((string) ($item['item_image_path'] ?? ''));
                                                    if ($rawImg !== '' && str_starts_with($rawImg, 'images/')) {
                                                        $rawImg = substr($rawImg, 7);
                                                    }
                                                    $thumbFile = $rawImg !== '' ? $rawImg : 'placeholder.jpg';
                                                    $thumbSrc = $asset('images/' . $thumbFile);
                                                    ?>
                                                    <a href="<?= $detailUrl ?>" class="text-decoration-none">
                                                        <img src="<?= htmlspecialchars($thumbSrc, ENT_QUOTES, 'UTF-8') ?>" alt="" class="rounded" style="width: 64px; height: 64px; object-fit: cover;" onerror="this.onerror=null;this.src='<?= htmlspecialchars($asset('images/placeholder.jpg'), ENT_QUOTES, 'UTF-8') ?>';">
                                                    </a>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            <a href="<?= $detailUrl ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($item['product_name'] ?? '') ?></a>
                                                        </h6>
                                                        <small class="text-muted"><?= htmlspecialchars(__m('account.order_detail.qty_label'), ENT_QUOTES, 'UTF-8') ?><?= (int)($item['quantity'] ?? 0) ?></small>
                                                        <p class="mb-0 mt-1 text-danger fw-bold">
                                                            <?= htmlspecialchars($money((float)($item['price'] ?? 0)), ENT_QUOTES, 'UTF-8') ?> × <?= (int)($item['quantity'] ?? 0) ?> = <?= htmlspecialchars($money((float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <?php if ($canReview): ?>
                                                <div class="mt-3 pt-3 border-top">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#review-form-<?= $itemId ?>" aria-expanded="false"><?= htmlspecialchars(__m('account.order_detail.write_review'), ENT_QUOTES, 'UTF-8') ?></button>
                                                    <div class="collapse mt-2" id="review-form-<?= $itemId ?>">
                                                        <form method="post" action="<?= $url('account/review') ?>">
                                                            <input type="hidden" name="item_id" value="<?= $itemId ?>">
                                                            <input type="hidden" name="order_id" value="<?= (int)($order['id'] ?? 0) ?>">
                                                            <div class="mb-2">
                                                                <label class="form-label small"><?= htmlspecialchars(__m('account.order_detail.review_title'), ENT_QUOTES, 'UTF-8') ?></label>
                                                                <input type="text" class="form-control form-control-sm" name="review_title" placeholder="<?= htmlspecialchars(__m('account.order_detail.review_title_ph'), ENT_QUOTES, 'UTF-8') ?>" maxlength="255">
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="form-label small"><?= htmlspecialchars(__m('account.order_detail.review_body'), ENT_QUOTES, 'UTF-8') ?></label>
                                                                <textarea class="form-control form-control-sm" name="review_content" rows="2" placeholder="<?= htmlspecialchars(__m('account.order_detail.review_body_ph'), ENT_QUOTES, 'UTF-8') ?>" required></textarea>
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="form-label small"><?= htmlspecialchars(__m('account.order_detail.review_rating'), ENT_QUOTES, 'UTF-8') ?></label>
                                                                <select class="form-select form-select-sm" name="review_rating" required>
                                                                    <?php for ($s = 5; $s >= 1; $s--): ?>
                                                                    <option value="<?= $s ?>"><?= htmlspecialchars(sprintf(__m('account.order_detail.star_option'), $s), ENT_QUOTES, 'UTF-8') ?></option>
                                                                    <?php endfor; ?>
                                                                </select>
                                                            </div>
                                                            <button type="submit" class="btn btn-primary btn-sm"><?= htmlspecialchars(__m('account.order_detail.submit_review'), ENT_QUOTES, 'UTF-8') ?></button>
                                                        </form>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3 mb-0"><?= htmlspecialchars(__m('account.order_detail.no_items'), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><?= htmlspecialchars(__m('account.order_detail.card_info'), ENT_QUOTES, 'UTF-8') ?></h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $status = $order['status'] ?? 'pending';
                                $statusInfo = $statusLabels[$status] ?? $statusLabels['pending'];
                                ?>
                                <p class="mb-2"><span class="badge bg-<?= $statusInfo['class'] ?>"><?= htmlspecialchars($statusInfo['label'], ENT_QUOTES, 'UTF-8') ?></span></p>
                                <p class="mb-2"><strong><?= htmlspecialchars(__m('account.order_detail.label_order_no'), ENT_QUOTES, 'UTF-8') ?></strong><?= htmlspecialchars($order['order_number'] ?? '') ?></p>
                                <p class="mb-2"><strong><?= htmlspecialchars(__m('account.order_detail.label_created'), ENT_QUOTES, 'UTF-8') ?></strong><?= date('Y-m-d H:i', strtotime($order['created_at'] ?? 'now')) ?></p>
                                <?php if (!empty($order['payment_method'])): ?>
                                    <p class="mb-2"><strong><?= htmlspecialchars(__m('account.order_detail.label_payment_method'), ENT_QUOTES, 'UTF-8') ?></strong><?= htmlspecialchars((string) ($paymentLabels[$order['payment_method']] ?? $order['payment_method']), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                                <?php if (!empty($order['shipping_address'])): ?>
                                    <p class="mb-3"><strong><?= htmlspecialchars(__m('account.order_detail.label_shipping'), ENT_QUOTES, 'UTF-8') ?></strong><br><span class="text-muted small" style="white-space: pre-line;"><?= htmlspecialchars($order['shipping_address']) ?></span></p>
                                <?php endif; ?>
                                <hr>
                                <p class="mb-0 d-flex justify-content-between"><strong><?= htmlspecialchars(__m('account.order_detail.order_total'), ENT_QUOTES, 'UTF-8') ?></strong><strong class="text-danger"><?= htmlspecialchars($money((float)($order['total_amount'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></strong></p>
                                <hr>
                                <a href="<?= $url('account/orders') ?>" class="btn btn-outline-secondary w-100"><?= htmlspecialchars(__m('account.order_detail.back_list'), ENT_QUOTES, 'UTF-8') ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <p class="text-muted mb-3"><?= htmlspecialchars(__m('account.order_detail.not_found'), ENT_QUOTES, 'UTF-8') ?></p>
                        <a href="<?= $url('account/orders') ?>" class="btn btn-primary"><?= htmlspecialchars(__m('account.order_detail.back_list'), ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
