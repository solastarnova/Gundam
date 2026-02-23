<?php
$url = $url ?? fn($p = '') => $p;
$user_name = $user_name ?? '';
$email = $email ?? '';
?>
<div class="container my-5 pt-5">
    <div class="row">
        <div class="col-lg-3 col-md-4">
            <div class="sidebar">
                <h5 class="px-4 mb-4 text-dark fw-bold">我的帳戶</h5>
                <div class="nav flex-column">
                    <a href="<?= $url('account') ?>" class="nav-link d-flex align-items-center active"><i class="bi bi-person me-2"></i> 個人資料</a>
                    <a href="<?= $url('account/orders') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-bag me-2"></i> 訂單記錄</a>
                    <a href="<?= $url('wishlist') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-heart me-2"></i> 喜愛清單</a>
                    <a href="#coupons" class="nav-link d-flex align-items-center"><i class="bi bi-ticket-perforated me-2"></i> 優惠券</a>
                    <a href="<?= $url('account/addresses') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-geo-alt me-2"></i> 預設地址</a>
                    <a href="#payment" class="nav-link d-flex align-items-center"><i class="bi bi-credit-card me-2"></i> 付款方式</a>
                    <a class="nav-link d-flex" href="<?= $url('account/settings') ?>"> 修改密碼</a>
                    <a class="nav-link d-flex text-danger" href="<?= $url('logout') ?>"> 登出</a>
                </div>
            </div>
        </div>
        <div class="col-lg-9 col-md-8">
            <div class="bg-white rounded shadow-sm p-4 p-md-5">
                <h4 class="mb-4">個人資料</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">暱稱（網站顯示的名字）</label>
                        <p class="mb-0"><?= htmlspecialchars($user_name) ?></p>
                    </div>
                    <div class="col-md-6"></div>
                    <div class="col-12">
                        <label class="form-label text-muted">電郵</label>
                        <p class="mb-0"><?= htmlspecialchars($email) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
