<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$product = $product ?? null;
$title = $title ?? '商品表單';
$isEdit = !is_null($product);
$currencyCode = strtoupper((string) (($currency['code'] ?? '')));
$currencyCode = $currencyCode !== '' ? $currencyCode : 'N/A';
$listedInput = '';
if ($isEdit && !empty($product['listed_at'])) {
    $ts = strtotime((string) $product['listed_at']);
    if ($ts !== false) {
        $listedInput = date('Y-m-d\TH:i', $ts);
    }
}
if ($listedInput === '') {
    $listedInput = date('Y-m-d\TH:i');
}
$isRecommended = $isEdit && !empty($product['is_recommended']);
$recommendedSort = $isEdit ? (int) ($product['recommended_sort'] ?? 0) : 0;
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><?= $title ?></h4>
        <a href="<?= $url('admin/products') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>返回列表
        </a>
    </div>
    
    <form method="POST" action="<?= $url('admin/products/save') ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= $product['id'] ?>">
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">基本資訊</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">商品名稱 <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="name" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($product['name'] ?? '') ?>"
                                   required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">價格（<?= htmlspecialchars($currencyCode, ENT_QUOTES, 'UTF-8') ?>）<span class="text-danger">*</span></label>
                                <input type="number" 
                                       name="price" 
                                       class="form-control" 
                                       step="0.01" 
                                       min="0"
                                       value="<?= $product['price'] ?? '' ?>"
                                       required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">庫存數量</label>
                                <input type="number" 
                                       name="stock" 
                                       class="form-control" 
                                       min="0"
                                       value="<?= $product['stock_quantity'] ?? 0 ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">分類</label>
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

                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">首頁展示</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">上架時間</label>
                            <input type="datetime-local"
                                   name="listed_at"
                                   class="form-control"
                                   value="<?= htmlspecialchars($listedInput, ENT_QUOTES, 'UTF-8') ?>">
                            <small class="text-muted">首頁「最新商品」依此時間由新到舊排序。</small>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox"
                                   name="is_recommended"
                                   value="1"
                                   class="form-check-input"
                                   id="is_recommended"
                                   <?= $isRecommended ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_recommended">設為首頁推薦商品</label>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">推薦排序</label>
                            <input type="number"
                                   name="recommended_sort"
                                   class="form-control"
                                   min="0"
                                   max="999999"
                                   value="<?= (int) $recommendedSort ?>">
                            <small class="text-muted">數字越小越靠前；僅在勾選推薦時生效。</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">商品圖片</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($isEdit && !empty($product['image_path'])): ?>
                            <div class="mb-3 text-center">
                                <img src="<?= $asset('images/' . $product['image_path']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     style="max-width: 100%; max-height: 200px; border-radius: 5px;">
                                <p class="text-muted small mt-2">目前圖片</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">上傳新圖片</label>
                            <input type="file" 
                                   name="image" 
                                   class="form-control" 
                                   accept="image/jpeg,image/png,image/gif">
                            <small class="text-muted">
                                支援 JPG、PNG、GIF 格式，不超過 2MB
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