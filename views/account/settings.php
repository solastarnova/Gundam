<?php
$url = $url ?? fn($p = '') => $p;
$profile = $profile ?? null;
$passwordErrors = $passwordErrors ?? [];
$passwordSuccess = $passwordSuccess ?? null;
$phoneErrors = $phoneErrors ?? [];
$phoneSuccess = $phoneSuccess ?? null;
$wallet_balance = $wallet_balance ?? 0.0;
$isPasswordSetupMode = !empty($isPasswordSetupMode);
$firebase_social_linking_enabled = !empty($firebase_social_linking_enabled);
$firebase_enable_facebook = !empty($firebase_enable_facebook);
$account_nav_active = 'settings';
?>
<div class="container account-page my-5 pt-5">
    <div class="row account-layout">
        <?php include __DIR__ . '/../partials/account-sidebar.php'; ?>
        <div class="col-lg-9 col-md-8">
            <div class="account-main-card account-main-padding">
                <h4 class="mb-4"><?= htmlspecialchars(__m('account.settings.title'), ENT_QUOTES, 'UTF-8') ?></h4>

                <div class="mb-5">
                    <h5 class="mb-2"><?= htmlspecialchars(__m($isPasswordSetupMode ? 'account.settings.password_setup_heading' : 'account.settings.password_heading'), ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="text-muted small mb-3"><?= htmlspecialchars(__m($isPasswordSetupMode ? 'account.settings.password_setup_intro' : 'account.settings.password_intro'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($passwordSuccess): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($passwordSuccess) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($passwordErrors['general'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($passwordErrors['general']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($passwordErrors) && empty($passwordErrors['general'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($passwordErrors['current_password'] ?? $passwordErrors['new_password'] ?? $passwordErrors['confirm_password'] ?? __m('account.settings.check_input')) ?></div>
                    <?php endif; ?>
                    <form method="post" action="<?= $url('account/password') ?>" novalidate>
                        <?php if (!$isPasswordSetupMode): ?>
                        <div class="mb-3">
                            <label for="current_password" class="form-label"><?= htmlspecialchars(__m('account.settings.current_password'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="password" class="form-control <?= isset($passwordErrors['current_password']) ? 'is-invalid' : '' ?>" id="current_password" name="current_password" required>
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="new_password" class="form-label"><?= htmlspecialchars(__m('account.settings.new_password'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="password" class="form-control <?= isset($passwordErrors['new_password']) ? 'is-invalid' : '' ?>" id="new_password" name="new_password" required minlength="8">
                            <div class="form-text"><?= htmlspecialchars(__m('account.settings.password_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label"><?= htmlspecialchars(__m('account.settings.confirm_password'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="password" class="form-control <?= isset($passwordErrors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                        <button type="submit" class="btn btn-dark"><?= htmlspecialchars(__m($isPasswordSetupMode ? 'account.settings.set_password' : 'account.settings.update_password'), ENT_QUOTES, 'UTF-8') ?></button>
                    </form>
                </div>

                <hr>

                <div class="mb-5">
                    <h5 class="mb-2"><?= htmlspecialchars(__m('account.settings.phone_heading'), ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="text-muted small mb-3"><?= htmlspecialchars(__m('account.settings.phone_intro'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($phoneSuccess): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($phoneSuccess) ?></div>
                    <?php endif; ?>
                    <form method="post" action="<?= $url('account/phone') ?>" class="mb-0">
                        <div class="mb-2">
                            <label for="settings_phone" class="form-label"><?= htmlspecialchars(__m('account.settings.phone_label'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" class="form-control" id="settings_phone" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="<?= htmlspecialchars(__m('account.settings.phone_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <button type="submit" class="btn btn-dark"><?= htmlspecialchars(__m('account.settings.update_phone'), ENT_QUOTES, 'UTF-8') ?></button>
                    </form>
                </div>

                <?php if ($firebase_social_linking_enabled): ?>
                <hr>
                <div class="mb-0">
                    <div class="card border" id="social-link-card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-link-45deg me-2" aria-hidden="true"></i><?= htmlspecialchars(__m('account.settings.social_heading'), ENT_QUOTES, 'UTF-8') ?></h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                <?= htmlspecialchars(sprintf(__m('account.settings.social_intro'), $firebase_enable_facebook ? __m('account.settings.social_intro_fb') : ''), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <?php if (trim((string) ($profile['firebase_uid'] ?? '')) === ''): ?>
                                <div class="alert alert-light border small mb-3">
                                    <?= sprintf(__m('account.settings.firebase_email_only_hint'), htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex flex-column gap-3">
                                <div id="status-google" class="d-flex align-items-center justify-content-between p-2 border rounded">
                                    <div>
                                        <i class="bi bi-google text-danger me-2" aria-hidden="true"></i>
                                        <strong>Google</strong>
                                    </div>
                                    <div id="google-action-area">
                                        <span id="badge-google-linked" class="badge bg-success d-none"><?= htmlspecialchars(__m('account.settings.linked_badge'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <button type="button" id="btn-link-google" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(__m('account.settings.link_account'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </div>
                                </div>
                                <div id="status-github" class="d-flex align-items-center justify-content-between p-2 border rounded">
                                    <div>
                                        <i class="bi bi-github me-2" aria-hidden="true"></i>
                                        <strong>GitHub</strong>
                                    </div>
                                    <div id="github-action-area">
                                        <span id="badge-github-linked" class="badge bg-success d-none"><?= htmlspecialchars(__m('account.settings.linked_badge'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <button type="button" id="btn-link-github" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(__m('account.settings.link_account'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </div>
                                </div>
                                <?php if ($firebase_enable_facebook): ?>
                                <div id="status-facebook" class="d-flex align-items-center justify-content-between p-2 border rounded">
                                    <div>
                                        <i class="bi bi-facebook text-primary me-2" aria-hidden="true"></i>
                                        <strong>Facebook</strong>
                                    </div>
                                    <div id="facebook-action-area">
                                        <span id="badge-facebook-linked" class="badge bg-success d-none"><?= htmlspecialchars(__m('account.settings.linked_badge'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <button type="button" id="btn-link-facebook" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(__m('account.settings.link_account'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
