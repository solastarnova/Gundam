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
$firebase_social_linking_enabled = !empty($firebase_social_linking_enabled);
$firebase_enable_facebook = !empty($firebase_enable_facebook);
?>
<div class="container account-page my-5 pt-5">
    <div class="row account-layout">
        <div class="col-lg-3 col-md-4">
            <div class="sidebar account-sidebar">
                <h5 class="px-4 mb-4 text-dark fw-bold">我的帳戶</h5>
                <div class="nav flex-column">
                    <a href="<?= $url('account') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-person me-2"></i> 個人資料</a>
                    <a href="<?= $url('account/points') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-award me-2"></i> 會員中心</a>
                    <a href="<?= $url('account/orders') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-bag me-2"></i> 訂單記錄</a>
                    <a href="<?= $url('wishlist') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-heart me-2"></i> 喜愛清單</a>
                    <span class="nav-link d-flex align-items-center text-muted user-select-none" style="pointer-events: none; cursor: default;" title="暫未開放"><i class="bi bi-ticket-perforated me-2"></i> 優惠券</span>
                    <a href="<?= $url('account/addresses') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-geo-alt me-2"></i> 預設地址</a>
                    <a href="<?= $url('account/payment') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-credit-card me-2"></i> 付款方式</a>
                    <a href="<?= $url('account/settings') ?>" class="nav-link d-flex align-items-center active"> 帳戶設定</a>
                    <a class="nav-link d-flex text-danger" href="<?= $url('logout') ?>" data-logout="1"> 登出</a>
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

                <?php if ($firebase_social_linking_enabled): ?>
                <hr>
                <div class="mb-5">
                    <div class="card mt-4 border" id="social-link-card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-link-45deg me-2" aria-hidden="true"></i>社群帳號綁定</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                綁定後，您可以使用 Google<?= $firebase_enable_facebook ? '、Facebook' : '' ?> 或 GitHub 登入同一個 Gundam 商城帳號。
                            </p>
                            <?php if (trim((string) ($profile['firebase_uid'] ?? '')) === ''): ?>
                                <div class="alert alert-light border small mb-3">
                                    您目前可能僅以電郵密碼登入。請先到<a href="<?= $url('login') ?>">登入頁</a>使用社群登入並完成帳號連結，讓瀏覽器保留 Firebase 工作階段後，再回到此頁綁定其他平台。
                                </div>
                            <?php endif; ?>
                            <p class="text-muted small mb-3">
                                須在瀏覽器內已登入 Firebase（曾以社群成功登入本網站）。下方狀態由 Firebase 即時判斷；若顯示有誤請重新以社群登入一次。
                            </p>
                            <div class="d-flex flex-column gap-3">
                                <div id="status-google" class="d-flex align-items-center justify-content-between p-2 border rounded">
                                    <div>
                                        <i class="bi bi-google text-danger me-2" aria-hidden="true"></i>
                                        <strong>Google</strong>
                                    </div>
                                    <div id="google-action-area">
                                        <span id="badge-google-linked" class="badge bg-success d-none">已連結</span>
                                        <button type="button" id="btn-link-google" class="btn btn-sm btn-outline-primary">連結帳號</button>
                                    </div>
                                </div>
                                <div id="status-github" class="d-flex align-items-center justify-content-between p-2 border rounded">
                                    <div>
                                        <i class="bi bi-github me-2" aria-hidden="true"></i>
                                        <strong>GitHub</strong>
                                    </div>
                                    <div id="github-action-area">
                                        <span id="badge-github-linked" class="badge bg-success d-none">已連結</span>
                                        <button type="button" id="btn-link-github" class="btn btn-sm btn-outline-primary">連結帳號</button>
                                    </div>
                                </div>
                                <?php if ($firebase_enable_facebook): ?>
                                <div id="status-facebook" class="d-flex align-items-center justify-content-between p-2 border rounded">
                                    <div>
                                        <i class="bi bi-facebook text-primary me-2" aria-hidden="true"></i>
                                        <strong>Facebook</strong>
                                    </div>
                                    <div id="facebook-action-area">
                                        <span id="badge-facebook-linked" class="badge bg-success d-none">已連結</span>
                                        <button type="button" id="btn-link-facebook" class="btn btn-sm btn-outline-primary">連結帳號</button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

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
