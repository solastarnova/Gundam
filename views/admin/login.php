<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$error = $error ?? '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars((string) ($html_lang ?? 'zh-HK'), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>後台登入 - Gundam商城</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= $asset('css/admin.css') ?>">
</head>
<body class="admin-login d-flex align-items-center min-vh-100 py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="logo">
                            <i class="bi bi-cpu"></i>
                        </div>
                        <h1 class="auth-title">Gundam 後台管理</h1>
                        <p class="auth-subtitle">請輸入您的管理員帳號登入</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= $url('admin/login') ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <div class="mb-3">
                            <label class="form-label" for="admin-username">用戶名</label>
                            <input type="text"
                                   class="form-control"
                                   id="admin-username"
                                   name="username"
                                   placeholder="請輸入用戶名"
                                   required
                                   autofocus>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="admin-password">密碼</label>
                            <input type="password"
                                   class="form-control"
                                   id="admin-password"
                                   name="password"
                                   placeholder="請輸入密碼"
                                   required>
                        </div>

                        <button type="submit" class="btn btn-outline-success btn-login">
                            登入
                        </button>
                    </form>

                    <div class="help-links">
                        <a href="<?= $url('') ?>">返回首頁</a>
                        <span class="text-muted mx-2">|</span>
                        <a href="<?= $url('faq') ?>">幫助中心</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= $asset('js/admin.js') ?>"></script>
</body>
</html>
