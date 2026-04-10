<?php
$url = $url ?? fn($p = '') => $p;
$redirect = $redirect ?? '';
$errors = $errors ?? [];
$old = $old ?? [];
$status = $status ?? null;
?>

<div class="container d-flex justify-content-center align-items-center my-5">
    <div class="card shadow-sm" style="max-width: 480px; width: 100%;">
        <div class="card-body p-4">
            <div class="position-relative mb-4">
                <h3 class="text-center mb-0"><?= htmlspecialchars(__m('auth_register.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                <a href="<?= $url('') ?>" class="position-absolute top-50 end-0 translate-middle-y link-secondary p-1" aria-label="<?= htmlspecialchars(__m('auth_register.back_home_aria'), ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-x-lg fs-5" aria-hidden="true"></i></a>
            </div>

            <?php if ($status): ?>
                <div class="alert alert-success" role="alert"><?= htmlspecialchars($status) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>

            <form action="<?= $url('register') ?>" method="POST" id="registerForm" class="d-flex flex-column gap-3">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars((string) ($old['redirect'] ?? $redirect ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <label for="inputName" class="form-label fw-semibold"><?= htmlspecialchars(__m('auth_register.name_label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input
                        type="text"
                        class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                        id="inputName"
                        name="name"
                        placeholder="Name"
                        value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                        required
                    >
                    <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="inputEmail" class="form-label fw-semibold"><?= htmlspecialchars(__m('auth_register.email_label'), ENT_QUOTES, 'UTF-8') ?></label>
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
                    <label for="inputCode" class="form-label fw-semibold"><?= htmlspecialchars(__m('auth_register.code_label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <div class="input-group">
                        <input
                            type="text"
                            class="form-control <?= isset($errors['verification_code']) ? 'is-invalid' : '' ?>"
                            id="inputCode"
                            name="verification_code"
                            placeholder="6-digit code"
                            maxlength="6"
                            pattern="\d{6}"
                            autocomplete="one-time-code"
                        >
                        <button type="button" class="btn btn-outline-dark" id="btnSendCode"><?= htmlspecialchars(__m('auth_register.send_code'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                    <?php if (isset($errors['verification_code'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['verification_code']) ?></div>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="inputPassword" class="form-label fw-semibold"><?= htmlspecialchars(__m('auth_register.password_label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input
                        type="password"
                        class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                        id="inputPassword"
                        name="password"
                        placeholder="Password"
                        minlength="8"
                        required
                    >
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="inputPassword2" class="form-label fw-semibold"><?= htmlspecialchars(__m('auth_register.password2_label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input
                        type="password"
                        class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                        id="inputPassword2"
                        name="password_confirm"
                        placeholder="Confirm password"
                        minlength="8"
                        required
                    >
                    <?php if (isset($errors['password_confirm'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password_confirm']) ?></div>
                    <?php endif; ?>
                </div>

                <button class="btn btn-dark w-100" type="submit"><?= htmlspecialchars(__m('auth_register.submit'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>

            <div class="d-flex justify-content-between align-items-center mt-3 small">
                <span class="text-muted"><?= htmlspecialchars(__m('auth_register.has_account'), ENT_QUOTES, 'UTF-8') ?></span>
                <a href="<?= $url('login') ?>?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/') ?>" class="text-decoration-none"><?= htmlspecialchars(__m('auth_register.go_login'), ENT_QUOTES, 'UTF-8') ?></a>
            </div>

            <?php if (!empty($firebase_auth_enabled)): ?>
            <div class="social-login-minimal text-center mt-4">
                <p class="text-muted small mb-3"><?= htmlspecialchars(__m('auth_register.social_intro'), ENT_QUOTES, 'UTF-8') ?></p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <button type="button" class="btn-circular btn-google" id="google-login" title="<?= htmlspecialchars(__m('auth_register.google_title'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__m('auth_register.google_title'), ENT_QUOTES, 'UTF-8') ?>">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="" width="20" height="20">
                    </button>
                    <button type="button" class="btn-circular btn-github" id="github-login" title="<?= htmlspecialchars(__m('auth_register.github_title'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__m('auth_register.github_title'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fab fa-github fa-lg" aria-hidden="true"></i>
                    </button>
                    <?php if (!empty($firebase_enable_facebook)): ?>
                    <button type="button" class="btn-circular btn-facebook" id="facebook-login" title="<?= htmlspecialchars(__m('auth_register.facebook_title'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__m('auth_register.facebook_title'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fab fa-facebook-f fa-lg" aria-hidden="true"></i>
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn-circular btn-more" title="<?= htmlspecialchars(__m('auth_register.more_title'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__m('auth_register.more_title'), ENT_QUOTES, 'UTF-8') ?>" disabled>
                        <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
window.REGISTER_PAGE = <?= json_encode(['emailRequired' => __m('account.register_js.email_required')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
(function() {
    var form = document.getElementById('registerForm');
    var btnSend = document.getElementById('btnSendCode');
    var emailInput = document.getElementById('inputEmail');
    var nameInput = document.getElementById('inputName');
    var R = window.REGISTER_PAGE || {};

    if (btnSend && form) {
        btnSend.addEventListener('click', function() {
            var email = emailInput ? emailInput.value.trim() : '';
            var name = nameInput ? nameInput.value.trim() : '';
            if (!email) {
                alert(R.emailRequired || '');
                return;
            }
            var sendForm = document.createElement('form');
            sendForm.method = 'POST';
            sendForm.action = '<?= $url('register/send-code') ?>';
            var e = document.createElement('input');
            e.name = 'e-mail';
            e.value = email;
            e.type = 'hidden';
            sendForm.appendChild(e);
            var n = document.createElement('input');
            n.name = 'name';
            n.value = name;
            n.type = 'hidden';
            sendForm.appendChild(n);
            var redirectInput = form.querySelector('input[name="redirect"]');
            if (redirectInput) {
                var r = document.createElement('input');
                r.name = 'redirect';
                r.value = redirectInput.value || '';
                r.type = 'hidden';
                sendForm.appendChild(r);
            }
            document.body.appendChild(sendForm);
            sendForm.submit();
        });
    }
})();
</script>
