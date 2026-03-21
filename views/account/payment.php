<?php
$url = $url ?? fn($p = '') => $p;
$wallet_balance = $wallet_balance ?? 0.0;
?>
<div class="container account-page my-5 pt-5">
    <div class="row account-layout">
        <div class="col-lg-3 col-md-4">
            <div class="sidebar account-sidebar">
                <h5 class="px-4 mb-4 text-dark fw-bold">我的帳戶</h5>
                <div class="nav flex-column">
                    <a href="<?= $url('account') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-person me-2"></i> 個人資料</a>
                    <a href="<?= $url('account/orders') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-bag me-2"></i> 訂單記錄</a>
                    <a href="<?= $url('wishlist') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-heart me-2"></i> 喜愛清單</a>
                    <span class="nav-link d-flex align-items-center text-muted user-select-none" style="pointer-events: none; cursor: default;" title="暫未開放"><i class="bi bi-ticket-perforated me-2"></i> 優惠券</span>
                    <a href="<?= $url('account/addresses') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-geo-alt me-2"></i> 預設地址</a>
                    <a href="<?= $url('account/payment') ?>" class="nav-link d-flex align-items-center active"><i class="bi bi-credit-card me-2"></i> 付款方式</a>
                    <a href="<?= $url('account/settings') ?>" class="nav-link d-flex align-items-center"> 帳戶設定</a>
                    <a class="nav-link d-flex text-primary" href="<?= $url('logout') ?>"> 登出</a>
                </div>
            </div>
        </div>
        <div class="col-lg-9 col-md-8">
            <div class="account-main-card account-main-padding">
                <h4 class="mb-4">付款方式</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-0 bg-primary bg-opacity-10">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="text-muted small">錢包餘額</div>
                                        <div class="fs-4 fw-bold text-primary">
                                            <?= htmlspecialchars($money((float) $wallet_balance), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="text-muted small mt-1">
                                            結帳時可使用錢包抵扣部分金額（若你選擇使用）。
                                        </div>
                                    </div>
                                    <i class="bi bi-wallet2 fs-1 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <div class="text-muted small">常見付款方式</div>
                                <div class="fw-semibold">信用卡 / PayPal</div>
                                <div class="text-muted small mt-1">
                                    實際以結帳頁面可選項為準。
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
