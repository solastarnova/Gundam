<?php
$url = $url ?? fn($p = '') => $p;
$errors = $errors ?? [];
$old = $old ?? [];
$status = $status ?? null;
?>

<div class="container d-flex justify-content-center align-items-center my-5">
    <div class="card shadow-sm" style="max-width: 480px; width: 100%;">
        <div class="card-body p-4">
            <h3 class="text-center mb-3">建立新帳戶</h3>

            <?php if ($status): ?>
                <div class="alert alert-success" role="alert"><?= htmlspecialchars($status) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>

            <form action="<?= $url('register') ?>" method="POST" id="registerForm" class="mb-3">
                <div class="mb-3">
                    <label for="inputName" class="form-label fw-semibold">暱稱</label>
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

                <div class="mb-3">
                    <label for="inputEmail" class="form-label fw-semibold">電子郵箱</label>
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
                    <div class="form-text small text-muted">請先點擊「發送驗證碼」，再填寫下方驗證碼。</div>
                </div>

                <div class="mb-3">
                    <label for="inputCode" class="form-label fw-semibold">郵箱驗證碼</label>
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
                        <button type="button" class="btn btn-outline-dark" id="btnSendCode">發送驗證碼</button>
                    </div>
                    <?php if (isset($errors['verification_code'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['verification_code']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="inputPassword" class="form-label fw-semibold">密碼</label>
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

                <div class="mb-3">
                    <label for="inputPassword2" class="form-label fw-semibold">確認密碼</label>
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

                <div class="auth-btn-center mb-3">
                <button class="btn btn-dark" type="submit">註冊會員</button>
            </div>
            </form>

            <hr class="my-3">
            <div class="text-center">
                <p class="mb-2 text-muted small">已經有帳戶？</p>
                <div class="auth-btn-center">
                    <a href="<?= $url('login') ?>" class="btn btn-outline-dark">前往登入</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('registerForm');
    var btnSend = document.getElementById('btnSendCode');
    var emailInput = document.getElementById('inputEmail');
    var nameInput = document.getElementById('inputName');

    if (btnSend && form) {
        btnSend.addEventListener('click', function() {
            var email = emailInput ? emailInput.value.trim() : '';
            var name = nameInput ? nameInput.value.trim() : '';
            if (!email) {
                alert('請先輸入郵箱');
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
            document.body.appendChild(sendForm);
            sendForm.submit();
        });
    }
})();
</script>
