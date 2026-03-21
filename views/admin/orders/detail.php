<?php
$url = $url ?? fn($p = '') => $p;
$order = $order ?? [];
$items = $items ?? [];

$statusLabels = [
    'pending' => ['text' => '待付款', 'class' => 'badge-pending'],
    'paid' => ['text' => '已付款', 'class' => 'badge-paid'],
    'shipped' => ['text' => '已發貨', 'class' => 'badge-shipped'],
    'completed' => ['text' => '已完成', 'class' => 'badge-completed'],
    'cancelled' => ['text' => '已取消', 'class' => 'badge-cancelled']
];
$statusInfo = $statusLabels[$order['status']] ?? ['text' => $order['status'], 'class' => 'badge-secondary'];

$methodMap = [
    'credit' => '信用卡',
    'paypal' => 'PayPal',
    'credit_card' => '信用卡'
];
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">訂單詳情 #<?= htmlspecialchars($order['order_number']) ?></h4>
        <a href="<?= $url('admin/orders') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>返回列表
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">訂單商品</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($items)): ?>
                        <p class="text-muted text-center">暫無商品資訊</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>商品</th>
                                        <th>單價</th>
                                        <th>數量</th>
                                        <th>小計</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= htmlspecialchars($money((float) $item['price']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td><?= htmlspecialchars($money((float) $item['price'] * (int) $item['quantity']), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">合計：</th>
                                        <th class="text-danger"><?= htmlspecialchars($money((float) $order['total_amount']), ENT_QUOTES, 'UTF-8') ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">訂單資訊</h6>
                </div>
                <div class="card-body">
                    <p>
                        <strong>訂單狀態：</strong><br>
                        <span class="badge-status <?= $statusInfo['class'] ?>"><?= $statusInfo['text'] ?></span>
                    </p>
                    <p><strong>下單時間：</strong><br><?= date('Y-m-d H:i:s', strtotime($order['created_at'])) ?></p>
                    <p><strong>支付方式：</strong><br><?= $methodMap[$order['payment_method']] ?? $order['payment_method'] ?></p>
                    <p><strong>配送地址：</strong><br><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">用戶資訊</h6>
                </div>
                <div class="card-body">
                    <p><strong>姓名：</strong><br><?= htmlspecialchars($order['user_name'] ?? '未知') ?></p>
                    <p><strong>電郵：</strong><br><?= htmlspecialchars($order['email'] ?? '未知') ?></p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">更新狀態</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= $url('admin/orders/' . $order['id'] . '/status') ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <select name="status" class="form-select mb-3">
                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>待付款</option>
                            <option value="paid" <?= $order['status'] == 'paid' ? 'selected' : '' ?>>已付款</option>
                            <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>已發貨</option>
                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>已完成</option>
                            <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>已取消</option>
                        </select>
                        <button type="submit" class="btn btn-primary w-100">
                            更新狀態
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>