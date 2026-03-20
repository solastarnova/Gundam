<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$product = $product ?? null;
$title = $title ?? '商品表单';
$isEdit = !is_null($product);
$currencyCode = strtoupper((string) (($currency['code'] ?? '')));
$currencyCode = $currencyCode !== '' ? $currencyCode : 'N/A';
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><?= $title ?></h4>
        <a href="<?= $url('admin/products') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>返回列表
        </a>
    </div>
    
    <form method="POST" action="<?= $url('admin/products/save') ?>" enctype="multipart/form-data">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= $product['id'] ?>">
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">基本信息</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">商品名称 <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="name" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($product['name'] ?? '') ?>"
                                   required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">价格 (<?= htmlspecialchars($currencyCode, ENT_QUOTES, 'UTF-8') ?>) <span class="text-danger">*</span></label>
                                <input type="number" 
                                       name="price" 
                                       class="form-control" 
                                       step="0.01" 
                                       min="0"
                                       value="<?= $product['price'] ?? '' ?>"
                                       required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">库存数量</label>
                                <input type="number" 
                                       name="stock" 
                                       class="form-control" 
                                       min="0"
                                       value="<?= $product['stock_quantity'] ?? 0 ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">分类</label>
                            <input type="text" 
                                   name="category" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($product['category'] ?? '') ?>"
                                   placeholder="例如：RG、MG、PG">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">商品描述</label>
                            <textarea name="description" 
                                      class="form-control" 
                                      rows="5"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">商品图片</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($isEdit && !empty($product['image_path'])): ?>
                            <div class="mb-3 text-center">
                                <img src="<?= $asset('images/' . $product['image_path']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     style="max-width: 100%; max-height: 200px; border-radius: 5px;">
                                <p class="text-muted small mt-2">当前图片</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">上传新图片</label>
                            <input type="file" 
                                   name="image" 
                                   class="form-control" 
                                   accept="image/jpeg,image/png,image/gif">
                            <small class="text-muted">
                                支持 JPG、PNG、GIF 格式，不超过2MB
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-check-circle me-2"></i><?= $isEdit ? '更新商品' : '發佈商品' ?>
                        </button>
                        <a href="<?= $url('admin/products') ?>" class="btn btn-outline-secondary w-100">
                            取消
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>