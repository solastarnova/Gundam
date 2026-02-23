<?php
$url = $url ?? fn($p = '') => $p;
$redirect = $redirect ?? '';
$errors = $errors ?? [];
$old = $old ?? [];
?>
<div class="container mx-auto mt-5 py-4">
    <div class="alert alert-primary text-center m-0" role="alert">登入高達賬戶</div>
    <div class="min-vh-50 bg-light">
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>
        <form action="<?= $url('login') ?>" method="POST">
            <?php if ($redirect !== ''): ?><input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>"><?php endif; ?>
            <div class="col-12 auth-form-col">
                <label for="inputEmail4" class="form-label">電郵</label>
                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="inputEmail4" name="e-mail" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="col-12 auth-form-col">
                <label for="inputPassword4" class="form-label">密碼</label>
                <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" title="密碼須至少 8 個字元" minlength="8" id="inputPassword4" name="password">
                <?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="text-center">
                <button class="btn btn-outline-success mt-2 mb-3" type="submit">登入</button>
            </div>
        </form>
        <div class="text-center mb-1">
            <a href="<?= $url('resetpw') ?>" class="text-decoration-none">忘記密碼？</a>
        </div>
        <div class="border-top border-dashed mx-auto auth-border-dashed">
            <p class="mt-2 mb-2 text-dark text-center">沒有帳號？</p>
            <a href="<?= $url('register') ?>">
                <div class="text-center">
                    <button class="btn btn-outline-success mt-2 mb-3" type="button">點擊註冊</button>
                </div>
            </a>
        </div>
    </div>
</div>
