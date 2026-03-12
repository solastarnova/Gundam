<?php
$url = $url ?? fn($p = '') => $p;
$user = $user ?? null;
$orders = $orders ?? [];
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">用户详情</h4>
            <?php if ($user): ?>
                <p class="text-muted mb-0">#<?= (int) $user['id'] ?> · <?= htmlspecialchars($user['name']) ?></p>
            <?php endif; ?>
        </div>
        <a href="<?= $url('admin/users') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 返回列表
        </a>
    </div>

    <?php if (!$user): ?>
        <div class="text-center py-5 text-muted">用户不存在或已删除。</div>
    <?php else: ?>
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="text-muted">基本信息</h6>
                    <div class="mt-2">
                        <div><strong>用户名：</strong><?= htmlspecialchars($user['name']) ?></div>
                        <div><strong>邮箱：</strong><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="text-muted">最新订单</h6>
                    <div class="mt-2">
                        <?php if (empty($orders)): ?>
                            <div class="text-muted">暂无订单记录</div>
                        <?php else: ?>
                            <div class="small text-muted">显示最近 <?= count($orders) ?> 笔</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>订单号</th>
                        <th>金额</th>
                        <th>状态</th>
                        <th>下单时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">暂无订单数据</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($order['order_number'] ?? $order['id']) ?></td>
                                <td><?= htmlspecialchars($order['total_amount'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($order['status'] ?? 'pending') ?></td>
                                <td><?= htmlspecialchars($order['created_at'] ?? '') ?></td>
                                <td>
                                    <a href="<?= $url('admin/orders/' . $order['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        查看
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
