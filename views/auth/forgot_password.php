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
            <h3 class="text-center mb-3">忘記密碼</h3>
            <p class="text-center text-muted mb-4">
                請輸入您註冊時使用的電子郵箱，我們會將驗證碼寄送至該信箱。
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
                        <label for="inputEmail" class="form-label fw-semibold">電子郵箱</label>
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
                    <button class="btn btn-dark" type="submit">發送驗證碼</button>
                </div>
                </form>
            <?php else: ?>
                <form action="<?= $url('forgot/verify') ?>" method="POST" class="mb-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">電子郵箱</label>
                        <div class="form-control-plaintext">
                            <?= htmlspecialchars($old['email'] ?? $_SESSION['forgot_password_email'] ?? '') ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="inputCode" class="form-label fw-semibold">驗證碼</label>
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
                    <button class="btn btn-dark" type="submit">驗證並繼續</button>
                </div>
                </form>
                <div class="text-center mb-3">
                    <a href="<?= $url('forgot') ?>" class="text-decoration-none">重新輸入電郵地址</a>
                </div>
            <?php endif; ?>

            <hr class="my-3">
            <div class="d-flex justify-content-between">
                <a href="<?= $url('login') ?>" class="text-decoration-none">返回登入</a>
                <a href="<?= $url('register') ?>" class="text-decoration-none">建立新帳戶</a>
            </div>
        </div>
    </div>
</div>
