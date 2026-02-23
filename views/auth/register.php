<?php
$url = $url ?? fn($p = '') => $p;
$errors = $errors ?? [];
$old = $old ?? [];
?>
<div class="container mx-auto mt-5 py-4">
    <div class="alert alert-primary text-center" role="alert">註冊成為高達的基本會員</div>
    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>
    <form action="<?= $url('register') ?>" method="POST">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6">
                <label for="inputName" class="form-label">暱稱</label>
                <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="inputName" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>">
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="col-12 col-md-6">
                <label for="inputEmail" class="form-label">郵箱</label>
                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="inputEmail" name="e-mail" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,3}$">
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="col-12 col-md-6">
                <label for="inputPassword" class="form-label">密碼</label>
                <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="inputPassword" name="password" title="密碼須至少 8 個字元" minlength="8">
                <?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="col-12 col-md-6">
                <label for="inputPassword2" class="form-label">確認密碼</label>
                <input type="password" class="form-control" id="inputPassword2" name="password_confirm" title="密碼須至少 8 個字元" minlength="8">
            </div>
            <div class="text-center">
                <button class="btn btn-outline-success mt-2 mb-3" type="submit">註冊會員</button>
            </div>
        </div>
    </form>
    <div class="border-top border-dashed mx-auto mt-2 auth-border-dashed">
        <p class="mt-2 mb-2 text-dark text-center">已有帳號？</p>
        <div class="text-center">
            <a href="<?= $url('login') ?>" class="btn btn-outline-success mt-2 mb-3">點擊登入</a>
        </div>
    </div>
</div>
