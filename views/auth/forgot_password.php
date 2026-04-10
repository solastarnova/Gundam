<?php
$url = $url ?? fn($p = '') => $p;
$errors = $errors ?? [];
$old = $old ?? [];
$status = $status ?? null;
$has_email_sent = $has_email_sent ?? false;
?>

<div class="container d-flex justify-content-center align-items-center my-5">
    <div class="card shadow-sm" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4">
            <h3 class="text-center mb-3"><?= htmlspecialchars(__m('auth_forgot.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="text-center text-muted mb-4">
                <?= htmlspecialchars(__m('auth_forgot.intro'), ENT_QUOTES, 'UTF-8') ?>
            </p>

            <?php if ($status): ?>
                <div class="alert alert-success" role="alert"><?= htmlspecialchars($status) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>

            <?php if (!$has_email_sent): ?>
                <form action="<?= $url('forgot') ?>" method="POST" class="mb-3">
                    <div class="mb-3">
                        <label for="inputEmail" class="form-label fw-semibold"><?= htmlspecialchars(__m('auth_forgot.email_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input
                            type="email"
                            class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                            id="inputEmail"
                            name="email"
                            placeholder="Email address"
                            value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                            required
                        >
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="auth-btn-center mb-3">
                    <button class="btn btn-dark" type="submit"><?= htmlspecialchars(__m('auth_forgot.send_code'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
                </form>
            <?php else: ?>
                <form action="<?= $url('forgot/verify') ?>" method="POST" class="mb-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= htmlspecialchars(__m('auth_forgot.email_confirm_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <div class="form-control-plaintext">
                            <?= htmlspecialchars($old['email'] ?? $_SESSION['forgot_password_email'] ?? '') ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="inputCode" class="form-label fw-semibold"><?= htmlspecialchars(__m('auth_forgot.code_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input
                            type="text"
                            class="form-control <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
                            id="inputCode"
                            name="code"
                            placeholder="6-digit code"
                            maxlength="6"
                            pattern="\d{6}"
                            autocomplete="one-time-code"
                            required
                        >
                        <?php if (isset($errors['code'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['code']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="auth-btn-center mb-3">
                    <button class="btn btn-dark" type="submit"><?= htmlspecialchars(__m('auth_forgot.verify_continue'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
                </form>
                <div class="text-center mb-3">
                    <a href="<?= $url('forgot') ?>" class="text-decoration-none"><?= htmlspecialchars(__m('auth_forgot.reenter_email'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            <?php endif; ?>

            <hr class="my-3">
            <div class="d-flex justify-content-between">
                <a href="<?= $url('login') ?>" class="text-decoration-none"><?= htmlspecialchars(__m('auth_forgot.back_login'), ENT_QUOTES, 'UTF-8') ?></a>
                <a href="<?= $url('register') ?>" class="text-decoration-none"><?= htmlspecialchars(__m('auth_forgot.create_account'), ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </div>
    </div>
</div>
