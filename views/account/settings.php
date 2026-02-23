<?php
$url = $url ?? fn($p = '') => $p;
$passwordErrors = $passwordErrors ?? [];
$passwordSuccess = $passwordSuccess ?? null;
?>
<div class="container my-5 pt-5">
    <div class="row">
        <div class="col-lg-3 col-md-4">
            <div class="sidebar">
                <h5 class="px-4 mb-4 text-dark fw-bold">我的帳戶</h5>
                <div class="nav flex-column">
                    <a href="<?= $url('account') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-person me-2"></i> 個人資料</a>
                    <a href="<?= $url('account/orders') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-bag me-2"></i> 訂單記錄</a>
                    <a href="<?= $url('wishlist') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-heart me-2"></i> 喜愛清單</a>
                    <a href="#coupons" class="nav-link d-flex align-items-center"><i class="bi bi-ticket-perforated me-2"></i> 優惠券</a>
                    <a href="<?= $url('account/addresses') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-geo-alt me-2"></i> 預設地址</a>
                    <a href="#payment" class="nav-link d-flex align-items-center"><i class="bi bi-credit-card me-2"></i> 付款方式</a>
                    <a href="<?= $url('account/settings') ?>" class="nav-link d-flex align-items-center active"> 修改密碼</a>
                    <a class="nav-link d-flex text-primary" href="<?= $url('logout') ?>"> 登出</a>
                </div>
            </div>
        </div>
        <div class="bg-white rounded shadow-sm col-lg-9 col-md-8">
            <div class="py-4 px-4">
                <div class="mb-4">
                    <h4 class="mb-2">變更密碼</h4>
                    <p class="page-subtitle text-muted mb-0">管理登入密碼，確保帳戶安全。</p>
                </div>

                <?php if (!empty($passwordSuccess)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($passwordSuccess) ?></div>
                <?php endif; ?>

                <?php if (!empty($passwordErrors['general'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($passwordErrors['general']) ?></div>
                <?php elseif (!empty($passwordErrors)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($passwordErrors['current_password'] ?? $passwordErrors['new_password'] ?? $passwordErrors['confirm_password'] ?? '請檢查輸入資料') ?></div>
                <?php endif; ?>

                <form method="post" action="<?= $url('account/password') ?>" novalidate>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">目前密碼</label>
                        <input type="password" class="form-control <?= isset($passwordErrors['current_password']) ? 'is-invalid' : '' ?>" id="current_password" name="current_password" required>
                        <?php if (isset($passwordErrors['current_password'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($passwordErrors['current_password']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">新密碼</label>
                        <input type="password" class="form-control <?= isset($passwordErrors['new_password']) ? 'is-invalid' : '' ?>" id="new_password" name="new_password" required minlength="8">
                        <div class="form-text">至少 8 個字元，建議混合字母與數字。</div>
                        <?php if (isset($passwordErrors['new_password'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($passwordErrors['new_password']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">確認新密碼</label>
                        <input type="password" class="form-control <?= isset($passwordErrors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password" required minlength="8">
                        <?php if (isset($passwordErrors['confirm_password'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($passwordErrors['confirm_password']) ?></div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-dark">更新密碼</button>
                </form>
            </div>
        </div>
    </div>
</div>
