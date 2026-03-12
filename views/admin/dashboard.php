<?php
$url = $url ?? fn($p = '') => $p;
$stats = $stats ?? [];
$recentOrders = $stats['recent_orders'] ?? [];
$lowStockProducts = $stats['low_stock_products'] ?? [];
?>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-info">
                <h3><?= number_format($stats['total_orders'] ?? 0) ?></h3>
                <p>总订单数</p>
            </div>
            <div class="stat-icon blue">
                <i class="bi bi-cart-check"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-info">
                <h3><?= number_format($stats['total_users'] ?? 0) ?></h3>
                <p>注册用户</p>
            </div>
            <div class="stat-icon green">
                <i class="bi bi-people"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-info">
                <h3><?= number_format($stats['total_products'] ?? 0) ?></h3>
                <p>商品总数</p>
            </div>
            <div class="stat-icon purple">
                <i class="bi bi-box"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-info">
                <h3><?= count($lowStockProducts) ?></h3>
                <p>低库存商品</p>
            </div>
            <div class="stat-icon orange">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 最近订单 -->
    <div class="col-md-6 mb-4">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">最近订单</h5>
                <a href="<?= $url('admin/orders') ?>" class="btn btn-sm btn-outline-primary">查看全部</a>
            </div>
            
            <?php if (empty($recentOrders)): ?>
                <p class="text-muted text-center py-3">暂无订单</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>订单号</th>
                                <th>用户</th>
                                <th>金额</th>
                                <th>状态</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>
                                    <a href="<?= $url('admin/orders/' . $order['id']) ?>">
                                        <?= htmlspecialchars($order['order_number']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($order['user_name'] ?? '未知') ?></td>
                                <td>HK$ <?= number_format($order['total_amount'], 2) ?></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'badge-pending',
                                        'paid' => 'badge-paid',
                                        'shipped' => 'badge-shipped',
                                        'completed' => 'badge-completed',
                                        'cancelled' => 'badge-cancelled'
                                    ];
                                    $statusText = [
                                        'pending' => '待付款',
                                        'paid' => '已付款',
                                        'shipped' => '已发货',
                                        'completed' => '已完成',
                                        'cancelled' => '已取消'
                                    ];
                                    $class = $statusClass[$order['status']] ?? 'badge-secondary';
                                    $text = $statusText[$order['status']] ?? $order['status'];
                                    ?>
                                    <span class="badge-status <?= $class ?>"><?= $text ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 低库存商品 -->
    <div class="col-md-6 mb-4">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">低库存提醒</h5>
                <a href="<?= $url('admin/products') ?>" class="btn btn-sm btn-outline-primary">管理商品</a>
            </div>
            
            <?php if (empty($lowStockProducts)): ?>
                <p class="text-muted text-center py-3">所有商品库存充足</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>商品名称</th>
                                <th>分类</th>
                                <th>当前库存</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['category'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-danger"><?= $product['stock_quantity'] ?></span>
                                </td>
                                <td>
                                    <a href="<?= $url('admin/products/edit/' . $product['id']) ?>" 
                                       class="btn btn-sm btn-warning">补货</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>