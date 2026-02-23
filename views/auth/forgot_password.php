<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$error = $error ?? '';
?>
<div class="container mx-auto my-5 py-4">
    <div class="text-center mb-4">
        <h2 class="mt-2 mb-2 text-dark text-center">忘記密碼</h2>
        <p class="text-muted">輸入您的郵箱地址，我們將發送重設連結給您</p>
    </div>
    <form action="<?= $url('resetpw') ?>" method="POST" id="sendCodeForm">
        <div class="col-12 auth-form-col">
            <label for="recipientEmail" class="form-label fw-semibold">電子郵箱</label>
            <input type="email" class="form-control form-control-lg" id="recipientEmail" name="recipientEmail" placeholder="請輸入您的郵箱地址" required>
            <div class="form-text">請輸入您註冊時使用的郵箱地址</div>
            <input type="password" class="form-control form-control-lg mt-3" placeholder="請輸入您的新密碼" title="密碼須至少 8 個字元" minlength="8" id="inputPassword" name="password">
            <div class="row g-2 align-items-center mt-2">
                <div class="col-10">
                    <div class="input-group">
                        <input type="text" name="captcha" class="form-control" placeholder="驗證碼">
                    </div>
                </div>
                <div class="col-2">
                    <img src="<?= rtrim($url(''), '/') . '/captcha.php' ?>" class="img-fluid float-end captcha-img" id="captcha" onclick="this.src=this.src+'?d='+Math.random();" title="點擊刷新" alt="驗證碼">
                </div>
            </div>
            <div class="text-center">
                <button class="btn btn-outline-success mt-2" type="submit">重置</button>
            </div>
            <?php if ($error !== ''): ?><div class="text-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        </div>
    </form>
    <div class="border-top border-dashed mx-auto mt-2 auth-border-dashed">
        <div class="text-center">
            <a href="<?= $url('register') ?>" class="btn btn-outline-success mt-2 mb-3">點擊建立新帳戶</a>
        </div>
    </div>
</div>
