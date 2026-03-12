<?php
$url = $url ?? fn($p = '') => $p;
$orders = $orders ?? [];
$page = $page ?? 1;
$total = $total ?? 0;
$limit = $limit ?? 15;
$status = $status ?? '';
$stats = $stats ?? [];
$totalPages = ceil($total / $limit);

$statusLabels = [
    'pending' => ['text' => '待付款', 'class' => 'badge-pending'],
    'paid' => ['text' => '已付款', 'class' => 'badge-paid'],
    'shipped' => ['text' => '已发货', 'class' => 'badge-shipped'],
    'completed' => ['text' => '已完成', 'class' => 'badge-completed'],
    'cancelled' => ['text' => '已取消', 'class' => 'badge-cancelled']
];
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">订单管理</h4>
    </div>
    
    <!-- 状态统计 -->
    <div class="row mb-4">
        <div class="col-md-2 col-6 mb-2">
            <div class="card text-center p-2 <?= $status === '' ? 'border-primary' : '' ?>">
                <a href="<?= $url('admin/orders') ?>" class="text-decoration-none">
                    <div class="small">全部</div>
                    <strong><?= array_sum($stats) ?></strong>
                </a>
            </div>
        </div>
        <?php foreach ($stats as $key => $count): ?>
            <?php if (isset($statusLabels[$key])): ?>
            <div class="col-md-2 col-6 mb-2">
                <div class="card text-center p-2 <?= $status === $key ? 'border-primary' : '' ?>">
                    <a href="?status=<?= $key ?>" class="text-decoration-none">
                        <div class="small"><?= $statusLabels[$key]['text'] ?></div>
                        <strong><?= $count ?></strong>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x" style="font-size: 48px; color: #ccc;"></i>
            <h5 class="mt-3 text-muted">暂无订单</h5>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>订单号</th>
                        <th>用户</th>
                        <th>金额</th>
                        <th>支付方式</th>
                        <th>状态</th>
                        <th>下单时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($order['user_name'] ?? '未知') ?></td>
                        <td>
                            <strong class="text-danger">HK$ <?= number_format($order['total_amount'], 2) ?></strong>
                        </td>
                        <td>
                            <?php
                            $methodMap = [
                                'credit' => '信用卡',
                                'paypal' => 'PayPal',
                                'credit_card' => '信用卡'
                            ];
                            echo $methodMap[$order['payment_method']] ?? $order['payment_method'];
                            ?>
                        </td>
                        <td>
                            <?php
                            $statusInfo = $statusLabels[$order['status']] ?? ['text' => $order['status'], 'class' => 'badge-secondary'];
                            ?>
                            <span class="badge-status <?= $statusInfo['class'] ?>">
                                <?= $statusInfo['text'] ?>
                            </span>
                        </td>
                        <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                        <td>
                            <a href="<?= $url('admin/orders/' . $order['id']) ?>" 
                               class="btn btn-sm btn-info text-white">
                                <i class="bi bi-eye"></i> 详情
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 分页 -->
        <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page-1 ?><?= $status ? '&status='.$status : '' ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $status ? '&status='.$status : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page+1 ?><?= $status ? '&status='.$status : '' ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>