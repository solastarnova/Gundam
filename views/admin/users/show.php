<?php
$url = $url ?? fn($p = '') => $p;
$user = $user ?? null;
$orders = $orders ?? [];
$levels = $levels ?? [];
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">用戶詳情</h4>
            <?php if ($user): ?>
                <p class="text-muted mb-0">#<?= (int) $user['id'] ?> · <?= htmlspecialchars($user['name']) ?></p>
            <?php endif; ?>
        </div>
        <a href="<?= $url('admin/users') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 返回列表
        </a>
    </div>

    <?php if (!$user): ?>
        <div class="text-center py-5 text-muted">用戶不存在或已刪除。</div>
    <?php else: ?>
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="text-muted">基本資訊</h6>
                    <div class="mt-2">
                        <div><strong>用戶名：</strong><?= htmlspecialchars($user['name']) ?></div>
                        <div><strong>電郵：</strong><?= htmlspecialchars($user['email']) ?></div>
                        <?php
                        $levelNameDisplay = (string) ($user['level_name'] ?? '無等級');
                        $isLevelLocked = array_key_exists('is_level_locked', $user) && !empty($user['is_level_locked']);
                        ?>
                        <div>
                            <strong>目前狀態：</strong>
                            等級：<?= htmlspecialchars($levelNameDisplay) ?>
                            <?php if (array_key_exists('is_level_locked', $user)): ?>
                                <?php if ($isLevelLocked): ?>
                                    <span class="text-warning">（手動鎖定）</span>
                                <?php else: ?>
                                    <span class="text-success">（依消費自動）</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div><strong>累計消費：</strong><?= number_format((float) ($user['total_spent'] ?? 0), 2) ?></div>
                    </div>
                    <form method="POST" action="<?= $url('admin/users/' . (int) $user['id'] . '/membership-level') ?>" class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <select name="membership_level" class="form-select" style="max-width: 220px;">
                            <?php foreach ($levels as $level): ?>
                                <option value="<?= htmlspecialchars((string) ($level['level_key'] ?? '')) ?>" <?= (string) ($user['membership_level'] ?? '') === (string) ($level['level_key'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($level['level_name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-outline-primary">更新等級</button>
                    </form>
                    <?php if (array_key_exists('is_level_locked', $user) && $isLevelLocked): ?>
                        <form method="POST"
                              action="<?= $url('admin/users/' . (int) $user['id'] . '/unlock-level') ?>"
                              class="mt-2"
                              onsubmit="return confirm('確定恢復依累計消費自動計算等級？');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">恢復自動計算</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="text-muted">最新訂單</h6>
                    <div class="mt-2">
                        <?php if (empty($orders)): ?>
                            <div class="text-muted">暫無訂單記錄</div>
                        <?php else: ?>
                            <div class="small text-muted">顯示最近 <?= count($orders) ?> 筆</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>訂單號</th>
                        <th>金額</th>
                        <th>狀態</th>
                        <th>下單時間</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">暫無訂單資料</td>
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
                                        檢視
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
