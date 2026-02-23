<?php $url = $url ?? fn($p = '') => $p; ?>
<div class="container py-5">
    <h1 class="mb-3">404 - 找不到頁面</h1>
    <p class="mb-4">很抱歉，您請求的頁面不存在或已被移除。</p>
    <a href="<?= $url('') ?>" class="btn btn-primary">返回首頁</a>
</div>

