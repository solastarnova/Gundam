<?php
$url = $url ?? fn($p = '') => $p;
$errors = $errors ?? [];
?>

<div class="container d-flex justify-content-center align-items-center my-5">
    <div class="card shadow-sm" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4">
            <h3 class="text-center mb-3">設定新密碼</h3>
            <p class="text-center text-muted mb-4">
                請輸入您的新密碼並再次確認。
            </p>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>

            <form action="<?= $url('forgot/reset') ?>" method="POST">
                <div class="mb-3">
                    <label for="inputPassword" class="form-label fw-semibold">新密碼</label>
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
                    <label for="inputPassword2" class="form-label fw-semibold">確認新密碼</label>
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
                <button class="btn btn-dark" type="submit">重設密碼</button>
            </div>
            </form>

            <hr class="my-3">
            <div class="text-center">
                <a href="<?= $url('login') ?>" class="text-decoration-none small">返回登入</a>
            </div>
        </div>
    </div>
</div>
