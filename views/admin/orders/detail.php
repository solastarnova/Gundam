<?php
$url = $url ?? fn($p = '') => $p;
$order = $order ?? [];
$items = $items ?? [];

$statusLabels = [
    'pending' => ['text' => '待付款', 'class' => 'badge-pending'],
    'paid' => ['text' => '已付款', 'class' => 'badge-paid'],
    'shipped' => ['text' => '已发货', 'class' => 'badge-shipped'],
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
        <h4 class="mb-0">订单详情 #<?= htmlspecialchars($order['order_number']) ?></h4>
        <a href="<?= $url('admin/orders') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>返回列表
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <!-- 订单商品 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">订单商品</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($items)): ?>
                        <p class="text-muted text-center">暂无商品信息</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>商品</th>
                                        <th>单价</th>
                                        <th>数量</th>
                                        <th>小计</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td>HK$ <?= number_format($item['price'], 2) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>HK$ <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">合计：</th>
                                        <th class="text-danger">HK$ <?= number_format($order['total_amount'], 2) ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- 订单信息 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">订单信息</h6>
                </div>
                <div class="card-body">
                    <p>
                        <strong>订单状态：</strong><br>
                        <span class="badge-status <?= $statusInfo['class'] ?>"><?= $statusInfo['text'] ?></span>
                    </p>
                    <p><strong>下单时间：</strong><br><?= date('Y-m-d H:i:s', strtotime($order['created_at'])) ?></p>
                    <p><strong>支付方式：</strong><br><?= $methodMap[$order['payment_method']] ?? $order['payment_method'] ?></p>
                    <p><strong>配送地址：</strong><br><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                </div>
            </div>
            
            <!-- 用户信息 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">用户信息</h6>
                </div>
                <div class="card-body">
                    <p><strong>姓名：</strong><br><?= htmlspecialchars($order['user_name'] ?? '未知') ?></p>
                    <p><strong>邮箱：</strong><br><?= htmlspecialchars($order['email'] ?? '未知') ?></p>
                </div>
            </div>
            
            <!-- 更新状态 -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">更新状态</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= $url('admin/orders/' . $order['id'] . '/status') ?>">
                        <select name="status" class="form-select mb-3">
                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>待付款</option>
                            <option value="paid" <?= $order['status'] == 'paid' ? 'selected' : '' ?>>已付款</option>
                            <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>已发货</option>
                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>已完成</option>
                            <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>已取消</option>
                        </select>
                        <button type="submit" class="btn btn-primary w-100">
                            更新状态
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>