<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$order = $order ?? null;
$orderItems = $orderItems ?? [];
?>
<div class="container my-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-4">
                <h2 class="mb-1">訂單詳情</h2>
                <p class="text-muted">查看訂單的詳細資訊與商品列表</p>
            </div>

            <?php if ($order): ?>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">訂單商品</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($orderItems)): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($orderItems as $item): ?>
                                            <?php $itemId = (int)($item['item_id'] ?? 0); $detailUrl = $itemId ? $url('product/' . $itemId) : '#'; ?>
                                            <div class="list-group-item d-flex align-items-center gap-3">
                                                <a href="<?= $detailUrl ?>" class="text-decoration-none">
                                                    <img src="<?= $asset('images/placeholder.jpg') ?>" alt="" class="rounded" style="width: 64px; height: 64px; object-fit: cover;">
                                                </a>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <a href="<?= $detailUrl ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($item['product_name'] ?? '') ?></a>
                                                    </h6>
                                                    <small class="text-muted">數量：<?= (int)($item['quantity'] ?? 0) ?></small>
                                                    <p class="mb-0 mt-1 text-danger fw-bold">
                                                        HK$<?= number_format((float)($item['price'] ?? 0), 2) ?> × <?= (int)($item['quantity'] ?? 0) ?> = HK$<?= number_format((float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 0), 2) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3 mb-0">沒有訂單商品資料</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">訂單資訊</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $statusLabels = [
                                    'pending' => ['label' => '待付款', 'class' => 'warning'],
                                    'paid' => ['label' => '已付款', 'class' => 'info'],
                                    'shipped' => ['label' => '已發貨', 'class' => 'primary'],
                                    'completed' => ['label' => '已完成', 'class' => 'success'],
                                    'cancelled' => ['label' => '已取消', 'class' => 'secondary'],
                                ];
                                $status = $order['status'] ?? 'pending';
                                $statusInfo = $statusLabels[$status] ?? $statusLabels['pending'];
                                $paymentLabels = ['credit' => '信用卡/扣帳卡', 'paypal' => 'PayPal', 'credit_card' => '信用卡/扣帳卡'];
                                ?>
                                <p class="mb-2"><span class="badge bg-<?= $statusInfo['class'] ?>"><?= $statusInfo['label'] ?></span></p>
                                <p class="mb-2"><strong>訂單編號：</strong><?= htmlspecialchars($order['order_number'] ?? '') ?></p>
                                <p class="mb-2"><strong>下單時間：</strong><?= date('Y-m-d H:i', strtotime($order['created_at'] ?? 'now')) ?></p>
                                <?php if (!empty($order['payment_method'])): ?>
                                    <p class="mb-2"><strong>付款方式：</strong><?= htmlspecialchars($paymentLabels[$order['payment_method']] ?? $order['payment_method']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($order['shipping_address'])): ?>
                                    <p class="mb-3"><strong>配送地址：</strong><br><span class="text-muted small" style="white-space: pre-line;"><?= htmlspecialchars($order['shipping_address']) ?></span></p>
                                <?php endif; ?>
                                <hr>
                                <p class="mb-0 d-flex justify-content-between"><strong>訂單總額</strong><strong class="text-danger">HK$<?= number_format((float)($order['total_amount'] ?? 0), 2) ?></strong></p>
                                <hr>
                                <a href="<?= $url('account/orders') ?>" class="btn btn-outline-secondary w-100">返回訂單列表</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <p class="text-muted mb-3">找不到此訂單</p>
                        <a href="<?= $url('account/orders') ?>" class="btn btn-primary">返回訂單列表</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
