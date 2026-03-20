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
            <h3 class="text-center mb-3">登入賬戶</h3>

            <?php if ($message): ?>
                <div class="alert alert-success" role="alert"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>

            <form action="<?= $url('login') ?>" method="POST" class="mb-3">
                <?php if ($redirect !== ''): ?>
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                <?php endif; ?>
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
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="inputPassword" class="form-label fw-semibold mb-0">密碼</label>
                        <a href="<?= $url('forgot') ?>" class="text-decoration-none small">忘記密碼？</a>
                    </div>
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

                <div class="auth-btn-center mb-3">
                <button class="btn btn-dark" type="submit">登入</button>
            </div>
            </form>

            <hr class="my-3">
            <div class="text-center">
                <p class="mb-2 text-muted small">還沒有帳戶？</p>
                <div class="auth-btn-center">
                    <a href="<?= $url('register') ?>" class="btn btn-outline-dark">建立新帳戶</a>
                </div>
            </div>
        </div>
    </div>
</div>
