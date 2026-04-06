<?php
$url = $url ?? fn($p = '') => $p;
$users = $users ?? [];
$page = $page ?? 1;
$total = $total ?? 0;
$limit = $limit ?? 15;
$search = $search ?? '';
$levels = $levels ?? [];
$membership_level = (string) ($membership_level ?? '');
$totalPages = ceil($total / $limit);
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">用戶管理</h4>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-8">
            <form method="GET" action="<?= $url('admin/users') ?>" class="d-flex flex-wrap gap-2">
                <input type="text" 
                       name="search" 
                       class="form-control"
                       placeholder="搜尋用戶名或電郵..."
                       value="<?= htmlspecialchars($search) ?>"
                       style="max-width: 260px;">
                <select name="membership_level" class="form-select" style="max-width: 220px;">
                    <option value="">全部會員等級</option>
                    <?php foreach ($levels as $level): ?>
                        <option value="<?= htmlspecialchars((string) ($level['level_key'] ?? '')) ?>" <?= (string) ($level['level_key'] ?? '') === $membership_level ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($level['level_name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
    </div>
    
    <?php if (empty($users)): ?>
        <div class="text-center py-5">
            <i class="bi bi-people" style="font-size: 48px; color: #ccc;"></i>
            <h5 class="mt-3 text-muted">暫無用戶</h5>
            <?php if (!empty($search)): ?>
                <p class="text-muted">沒有找到與 "<?= htmlspecialchars($search) ?>" 相關的用戶</p>
                <a href="<?= $url('admin/users') ?>" class="btn btn-outline-secondary mt-2">清除搜尋</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用戶名</th>
                        <th>電郵</th>
                        <th>狀態</th>
                        <th>會員等級</th>
                        <th>累計消費</th>
                        <th>註冊時間</th>
                        <th>訂單數</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <?php $status = $user['status'] ?? 'active'; ?>
                    <tr>
                        <td>#<?= $user['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($user['name']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <?php if ($status === 'active'): ?>
                                <span class="badge bg-success">啟用</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">禁用</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="<?= $url('admin/users/' . $user['id'] . '/vip-level') ?>" class="d-flex flex-row flex-nowrap gap-1 align-items-center mb-0" style="white-space: nowrap;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <select name="membership_level" class="form-select form-select-sm" style="width: 120px; min-width: 120px;">
                                    <?php foreach ($levels as $level): ?>
                                        <option value="<?= htmlspecialchars((string) ($level['level_key'] ?? '')) ?>" <?= (string) ($user['membership_level'] ?? '') === (string) ($level['level_key'] ?? '') ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) ($level['level_name'] ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary">保存</button>
                            </form>
                        </td>
                        <td><?= number_format((float) ($user['total_spent'] ?? 0), 2) ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($user['created_at'] ?? 'now')) ?></td>
                        <td>
                            <span class="badge bg-info"><?= (int) ($user['order_count'] ?? 0) ?></span>
                        </td>
                        <td>
                            <form method="POST" action="<?= $url('admin/users/' . $user['id'] . '/toggle-status') ?>" class="d-inline me-1">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-sm <?= $status === 'active' ? 'btn-warning' : 'btn-success' ?>" title="<?= $status === 'active' ? '禁用' : '啟用' ?>">
                                    <?= $status === 'active' ? '禁用' : '啟用' ?>
                                </button>
                            </form>
                            <a href="<?= $url('admin/users/' . $user['id']) ?>" 
                               class="btn btn-sm btn-info text-white">
                                <i class="bi bi-eye"></i> 詳情
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page-1 ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $membership_level !== '' ? '&membership_level=' . urlencode($membership_level) : '' ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $membership_level !== '' ? '&membership_level=' . urlencode($membership_level) : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page+1 ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $membership_level !== '' ? '&membership_level=' . urlencode($membership_level) : '' ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
        <div class="text-muted text-center mt-2">
            共 <?= $total ?> 個用戶，當前顯示第 <?= ($page-1)*$limit+1 ?> - <?= min($page*$limit, $total) ?> 個
        </div>
    <?php endif; ?>
</div>