<?php
$url = $url ?? fn($p = '') => $p;
$profile = $profile ?? null;
$passwordErrors = $passwordErrors ?? [];
$passwordSuccess = $passwordSuccess ?? null;
$emailErrors = $emailErrors ?? [];
$emailSuccess = $emailSuccess ?? null;
$phoneErrors = $phoneErrors ?? [];
$phoneSuccess = $phoneSuccess ?? null;
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
                    <a href="#coupons" class="nav-link d-flex align-items-center"><i class="bi bi-ticket-perforated me-2"></i> 優惠券</a>
                    <a href="<?= $url('account/addresses') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-geo-alt me-2"></i> 預設地址</a>
                    <a href="<?= $url('account/payment') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-credit-card me-2"></i> 付款方式</a>
                    <a href="<?= $url('account/settings') ?>" class="nav-link d-flex align-items-center active"> 帳戶設定</a>
                    <a class="nav-link d-flex text-primary" href="<?= $url('logout') ?>"> 登出</a>
                </div>
            </div>
        </div>
        <div class="col-lg-9 col-md-8">
            <div class="account-main-card account-main-padding">
                <h4 class="mb-4">帳戶設定</h4>

                <div class="mb-5">
                    <h5 class="mb-2">變更電郵</h5>
                    <p class="text-muted small mb-3">修改登入使用的電郵地址，需輸入目前密碼確認。</p>
                    <?php if ($emailSuccess): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($emailSuccess) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($emailErrors['email'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($emailErrors['email']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($emailErrors['current_password'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($emailErrors['current_password']) ?></div>
                    <?php endif; ?>
                    <form method="post" action="<?= $url('account/email') ?>" class="mb-0">
                        <div class="mb-2">
                            <label for="settings_email" class="form-label">新電郵</label>
                            <input type="email" class="form-control <?= isset($emailErrors['email']) ? 'is-invalid' : '' ?>" id="settings_email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-2">
                            <label for="email_current_password" class="form-label">目前密碼</label>
                            <input type="password" class="form-control" id="email_current_password" name="current_password" required>
                        </div>
                        <button type="submit" class="btn btn-dark">更新電郵</button>
                    </form>
                </div>

                <hr>

                <div class="mb-5">
                    <h5 class="mb-2">變更手機號碼</h5>
                    <p class="text-muted small mb-3">用於收件聯絡，可選填。</p>
                    <?php if ($phoneSuccess): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($phoneSuccess) ?></div>
                    <?php endif; ?>
                    <form method="post" action="<?= $url('account/phone') ?>" class="mb-0">
                        <div class="mb-2">
                            <label for="settings_phone" class="form-label">手機號碼</label>
                            <input type="text" class="form-control" id="settings_phone" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="例如 91234567">
                        </div>
                        <button type="submit" class="btn btn-dark">更新手機</button>
                    </form>
                </div>

                <hr>

                <div class="mb-0">
                    <h5 class="mb-2">變更密碼</h5>
                    <p class="text-muted small mb-3">管理登入密碼，確保帳戶安全。</p>
                    <?php if ($passwordSuccess): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($passwordSuccess) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($passwordErrors['general'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($passwordErrors['general']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($passwordErrors) && empty($passwordErrors['general'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($passwordErrors['current_password'] ?? $passwordErrors['new_password'] ?? $passwordErrors['confirm_password'] ?? '請檢查輸入資料') ?></div>
                    <?php endif; ?>
                    <form method="post" action="<?= $url('account/password') ?>" novalidate>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">目前密碼</label>
                            <input type="password" class="form-control <?= isset($passwordErrors['current_password']) ? 'is-invalid' : '' ?>" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">新密碼</label>
                            <input type="password" class="form-control <?= isset($passwordErrors['new_password']) ? 'is-invalid' : '' ?>" id="new_password" name="new_password" required minlength="8">
                            <div class="form-text">至少 8 個字元。</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">確認新密碼</label>
                            <input type="password" class="form-control <?= isset($passwordErrors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                        <button type="submit" class="btn btn-dark">更新密碼</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
