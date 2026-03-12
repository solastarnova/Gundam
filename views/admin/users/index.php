<?php
$url = $url ?? fn($p = '') => $p;
$users = $users ?? [];
$page = $page ?? 1;
$total = $total ?? 0;
$limit = $limit ?? 15;
$search = $search ?? '';
$totalPages = ceil($total / $limit);
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">用户管理</h4>
    </div>
    
    <!-- 搜索框 -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" action="<?= $url('admin/users') ?>" class="d-flex">
                <input type="text" 
                       name="search" 
                       class="form-control me-2" 
                       placeholder="搜索用户名或邮箱..."
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
    </div>
    
    <?php if (empty($users)): ?>
        <div class="text-center py-5">
            <i class="bi bi-people" style="font-size: 48px; color: #ccc;"></i>
            <h5 class="mt-3 text-muted">暂无用户</h5>
            <?php if (!empty($search)): ?>
                <p class="text-muted">没有找到与"<?= htmlspecialchars($search) ?>"相关的用户</p>
                <a href="<?= $url('admin/users') ?>" class="btn btn-outline-secondary mt-2">清除搜索</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>邮箱</th>
                        <th>注册时间</th>
                        <th>订单数</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>#<?= $user['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($user['name']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($user['created_at'] ?? 'now')) ?></td>
                        <td>
                            <?php
                            // 获取用户订单数
                            $stmt = $pdo ?? null;
                            $orderCount = 0;
                            if (isset($GLOBALS['pdo'])) {
                                $stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
                                $stmt->execute([$user['id']]);
                                $orderCount = $stmt->fetchColumn();
                            }
                            ?>
                            <span class="badge bg-info"><?= $orderCount ?></span>
                        </td>
                        <td>
                            <a href="<?= $url('admin/users/' . $user['id']) ?>" 
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
                    <a class="page-link" href="?page=<?= $page-1 ?><?= $search ? '&search='.urlencode($search) : '' ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page+1 ?><?= $search ? '&search='.urlencode($search) : '' ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
        <div class="text-muted text-center mt-2">
            共 <?= $total ?> 个用户，当前显示第 <?= ($page-1)*$limit+1 ?> - <?= min($page*$limit, $total) ?> 个
        </div>
    <?php endif; ?>
</div>