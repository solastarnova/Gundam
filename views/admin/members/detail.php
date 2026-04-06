<?php
$url = $url ?? fn($p = '') => $p;
$member = $member ?? [];
$orders = $orders ?? [];
$benefits = $benefits ?? [];
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">會員詳情 #<?= (int) ($member['id'] ?? 0) ?></h4>
        <a href="<?= $url('admin/members') ?>" class="btn btn-outline-secondary">返回</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="border rounded p-3 h-100">
                <h6 class="text-muted">基本資料</h6>
                <p class="mb-1"><strong>姓名：</strong><?= htmlspecialchars((string) ($member['name'] ?? '')) ?></p>
                <p class="mb-1"><strong>電郵：</strong><?= htmlspecialchars((string) ($member['email'] ?? '')) ?></p>
                <p class="mb-1"><strong>等級：</strong><?= htmlspecialchars((string) ($member['level_name'] ?? '普通會員')) ?></p>
                <p class="mb-1"><strong>累計消費：</strong><?= htmlspecialchars($money((float) ($member['total_spent'] ?? 0))) ?></p>
                <p class="mb-0"><strong>最近升級：</strong><?= htmlspecialchars((string) ($member['last_level_up_time'] ?? '-')) ?></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border rounded p-3 h-100">
                <h6 class="text-muted">等級權益</h6>
                <?php if (empty($benefits)): ?>
                    <p class="text-muted mb-0">暫無權益配置</p>
                <?php else: ?>
                    <ul class="mb-0">
                        <?php foreach ($benefits as $k => $v): ?>
                            <li>
                                <strong><?= htmlspecialchars((string) $k) ?>：</strong>
                                <?= htmlspecialchars(is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE)) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h5 class="mb-3">最近訂單</h5>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>訂單編號</th>
                    <th>金額</th>
                    <th>狀態</th>
                    <th>下單時間</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">暫無訂單</td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($order['order_number'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($money((float) ($order['total_amount'] ?? 0))) ?></td>
                        <td><?= htmlspecialchars((string) ($order['status'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($order['created_at'] ?? '')) ?></td>
                        <td><a href="<?= $url('admin/orders/' . (int) $order['id']) ?>" class="btn btn-sm btn-outline-primary">查看</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
