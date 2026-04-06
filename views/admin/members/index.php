<?php
$url = $url ?? fn($p = '') => $p;
$members = $members ?? [];
$levels = $levels ?? [];
$search = $search ?? '';
$vipLevelId = (int) ($vip_level_id ?? 0);
$page = (int) ($page ?? 1);
$limit = (int) ($limit ?? 15);
$total = (int) ($total ?? 0);
$totalPages = (int) ceil($total / max(1, $limit));
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">會員管理</h4>
        <a href="<?= $url('admin/members/levels') ?>" class="btn btn-outline-primary">等級配置</a>
    </div>

    <form method="GET" action="<?= $url('admin/members') ?>" class="row g-2 mb-3">
        <div class="col-md-5">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="搜尋姓名或電郵">
        </div>
        <div class="col-md-4">
            <select name="vip_level_id" class="form-select">
                <option value="0">全部等級</option>
                <?php foreach ($levels as $level): ?>
                    <option value="<?= (int) $level['id'] ?>" <?= $vipLevelId === (int) $level['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $level['level_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" type="submit">查詢</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>ID</th>
                <th>會員</th>
                <th>等級</th>
                <th>累計消費</th>
                <th>折扣率</th>
                <th>升級時間</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($members)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">暫無會員資料</td>
                </tr>
            <?php else: ?>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td>#<?= (int) $member['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars((string) $member['name']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars((string) $member['email']) ?></small>
                        </td>
                        <td><?= htmlspecialchars((string) ($member['level_name'] ?? '普通會員')) ?></td>
                        <td><?= htmlspecialchars($money((float) ($member['total_spent'] ?? 0))) ?></td>
                        <td><?= (float) ($member['discount_rate'] ?? 100) ?>%</td>
                        <td><?= htmlspecialchars((string) ($member['last_level_up_time'] ?? '-')) ?></td>
                        <td>
                            <a href="<?= $url('admin/members/' . (int) $member['id']) ?>" class="btn btn-sm btn-outline-primary">詳情</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&vip_level_id=<?= $vipLevelId ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
