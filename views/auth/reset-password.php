<?php
$url = $url ?? fn($p = '') => $p;
$errors = $errors ?? [];
?>

<div class="container d-flex justify-content-center align-items-center my-5">
    <div class="card shadow-sm" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4">
            <h3 class="text-center mb-3"><?= htmlspecialchars(__m('auth_reset.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="text-center text-muted mb-4">
                <?= htmlspecialchars(__m('auth_reset.intro'), ENT_QUOTES, 'UTF-8') ?>
            </p>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>

            <form action="<?= $url('forgot/reset') ?>" method="POST">
                <div class="mb-3">
                    <label for="inputPassword" class="form-label fw-semibold"><?= htmlspecialchars(__m('auth_reset.new_password'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input
                        type="password"
                        class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                        id="inputPassword"
                        name="password"
                        placeholder="New password"
                        minlength="8"
                        required
                    >
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="inputPassword2" class="form-label fw-semibold"><?= htmlspecialchars(__m('auth_reset.confirm_password'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input
                        type="password"
                        class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                        id="inputPassword2"
                        name="password_confirm"
                        placeholder="Confirm new password"
                        minlength="8"
                        required
                    >
                    <?php if (isset($errors['password_confirm'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password_confirm']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="auth-btn-center mb-3">
                <button class="btn btn-dark" type="submit"><?= htmlspecialchars(__m('auth_reset.submit'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
            </form>

            <hr class="my-3">
            <div class="text-center">
                <a href="<?= $url('login') ?>" class="text-decoration-none small"><?= htmlspecialchars(__m('auth_reset.back_login'), ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </div>
    </div>
</div>
