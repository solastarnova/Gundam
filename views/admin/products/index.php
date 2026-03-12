<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$products = $products ?? [];
$page = $page ?? 1;
$total = $total ?? 0;
$limit = $limit ?? 15;
$search = $search ?? '';
$totalPages = ceil($total / $limit);
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">商品管理</h4>
        <a href="<?= $url('admin/products/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>新增商品
        </a>
    </div>
    
    <!-- 搜索框 -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" action="<?= $url('admin/products') ?>" class="d-flex">
                <input type="text" 
                       name="search" 
                       class="form-control me-2" 
                       placeholder="搜索商品名称或分类..."
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
    </div>
    
    <!-- 商品列表 -->
    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="bi bi-box" style="font-size: 48px; color: #ccc;"></i>
            <h5 class="mt-3 text-muted">暂无商品</h5>
            <?php if (!empty($search)): ?>
                <p class="text-muted">没有找到与"<?= htmlspecialchars($search) ?>"相关的商品</p>
                <a href="<?= $url('admin/products') ?>" class="btn btn-outline-secondary mt-2">清除搜索</a>
            <?php else: ?>
                <a href="<?= $url('admin/products/create') ?>" class="btn btn-primary mt-2">立即添加</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 80px;">图片</th>
                        <th>商品名称</th>
                        <th>分类</th>
                        <th>价格</th>
                        <th>库存</th>
                        <th style="width: 150px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td>#<?= $product['id'] ?></td>
                        <td>
                            <img src="<?= $asset('images/' . ($product['image_path'] ?? 'placeholder.jpg')) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($product['name']) ?></strong>
                            <br>
                            <small class="text-muted"><?= htmlspecialchars(substr($product['description'] ?? '', 0, 30)) ?>...</small>
                        </td>
                        <td>
                            <span class="badge bg-info text-white"><?= htmlspecialchars($product['category'] ?? '未分类') ?></span>
                        </td>
                        <td>
                            <strong class="text-danger">HK$ <?= number_format($product['price']) ?></strong>
                        </td>
                        <td>
                            <?php if ($product['stock_quantity'] > 10): ?>
                                <span class="badge bg-success"><?= $product['stock_quantity'] ?></span>
                            <?php elseif ($product['stock_quantity'] > 0): ?>
                                <span class="badge bg-warning"><?= $product['stock_quantity'] ?></span>
                            <?php else: ?>
                                <span class="badge bg-danger">缺货</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= $url('admin/products/edit/' . $product['id']) ?>" 
                               class="btn btn-sm btn-warning" title="编辑">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="<?= $url('admin/products/delete/' . $product['id']) ?>" 
                               class="btn btn-sm btn-danger" 
                               title="删除"
                               onclick="return confirm('确定要删除该商品吗？\n<?= htmlspecialchars($product['name']) ?>')">
                                <i class="bi bi-trash"></i>
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
                    <?php if ($i == $page): ?>
                        <li class="page-item active"><span class="page-link"><?= $i ?></span></li>
                    <?php else: ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endif; ?>
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
            共 <?= $total ?> 件商品，当前显示第 <?= ($page-1)*$limit+1 ?> - <?= min($page*$limit, $total) ?> 件
        </div>
    <?php endif; ?>
</div>