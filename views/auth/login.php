<?php
$url = $url ?? fn($p = '') => $p;
$redirect = $redirect ?? '';
$errors = $errors ?? [];
$old = $old ?? [];
$message = $message ?? null;
?>

<div class="container d-flex justify-content-center align-items-center my-5">
    <div class="card shadow-sm" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4">
            <div class="position-relative mb-4">
                <h3 class="text-center mb-0"><?= htmlspecialchars(__m('auth_login.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                <a href="<?= $url('') ?>" class="position-absolute top-50 end-0 translate-middle-y link-secondary p-1" aria-label="<?= htmlspecialchars(__m('auth_login.back_home_aria'), ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-x-lg fs-5" aria-hidden="true"></i></a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success" role="alert"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>

            <form action="<?= $url('login') ?>" method="POST" class="d-flex flex-column gap-3">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars((string) ($redirect ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <label for="inputEmail" class="form-label fw-semibold"><?= htmlspecialchars(__m('auth_login.email_label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input
                        type="email"
                        class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                        id="inputEmail"
                        name="e-mail"
                        placeholder="Email address"
                        value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                        required
                    >
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="inputPassword" class="form-label fw-semibold"><?= htmlspecialchars(__m('auth_login.password_label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input
                        type="password"
                        class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                        id="inputPassword"
                        name="password"
                        placeholder="Password"
                        required
                    >
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>
                </div>
                <button class="btn btn-dark w-100" type="submit"><?= htmlspecialchars(__m('auth_login.submit'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>

            <div class="d-flex justify-content-between align-items-center mt-3 small">
                <a href="<?= $url('forgot') ?>" class="text-decoration-none"><?= htmlspecialchars(__m('auth_login.forgot'), ENT_QUOTES, 'UTF-8') ?></a>
                <a href="<?= $url('register') ?>" class="text-decoration-none"><?= htmlspecialchars(__m('auth_login.register_now'), ENT_QUOTES, 'UTF-8') ?></a>
            </div>

            <?php if (!empty($firebase_auth_enabled)): ?>
            <div class="social-login-minimal text-center mt-4">
                <p class="text-muted small mb-3"><?= htmlspecialchars(__m('auth_login.social_intro'), ENT_QUOTES, 'UTF-8') ?></p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <button type="button" class="btn-circular btn-google" id="google-login" title="<?= htmlspecialchars(__m('auth_login.google_login'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__m('auth_login.google_login'), ENT_QUOTES, 'UTF-8') ?>">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="" width="20" height="20">
                    </button>
                    <button type="button" class="btn-circular btn-github" id="github-login" title="<?= htmlspecialchars(__m('auth_login.github_login'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__m('auth_login.github_login'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fab fa-github fa-lg" aria-hidden="true"></i>
                    </button>
                    <?php if (!empty($firebase_enable_facebook)): ?>
                    <button type="button" class="btn-circular btn-facebook" id="facebook-login" title="<?= htmlspecialchars(__m('auth_login.facebook_login'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__m('auth_login.facebook_login'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fab fa-facebook-f fa-lg" aria-hidden="true"></i>
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn-circular btn-more" title="<?= htmlspecialchars(__m('auth_login.more_login'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__m('auth_login.more_login'), ENT_QUOTES, 'UTF-8') ?>" disabled>
                        <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
