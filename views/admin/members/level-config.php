<?php
$url = $url ?? fn($p = '') => $p;
$levels = $levels ?? [];
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">會員等級配置</h4>
        <a href="<?= $url('admin/members') ?>" class="btn btn-outline-secondary">返回會員列表</a>
    </div>

    <?php foreach ($levels as $level): ?>
        <?php
        $benefits = json_decode((string) ($level['benefits_json'] ?? ''), true);
        if (!is_array($benefits)) {
            $benefits = [];
        }
        ?>
        <form method="POST" action="<?= $url('admin/members/levels/' . (int) $level['id']) ?>" class="border rounded p-3 mb-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><?= htmlspecialchars((string) ($level['level_name'] ?? '')) ?></h5>
                <span class="text-muted">消費區間：<?= (float) ($level['min_total_spent'] ?? 0) ?> - <?= ($level['max_total_spent'] === null ? '∞' : (float) $level['max_total_spent']) ?></span>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">折扣率（%）</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control" name="discount_rate" value="<?= (float) ($level['discount_rate'] ?? 100) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">免運門檻</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="free_shipping_threshold" value="<?= (float) ($benefits['free_shipping_threshold'] ?? 0) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">權益說明</label>
                    <input type="text" class="form-control" name="description" value="<?= htmlspecialchars((string) ($benefits['description'] ?? '')) ?>">
                </div>
            </div>
            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-primary">保存配置</button>
            </div>
        </form>
    <?php endforeach; ?>
</div>
