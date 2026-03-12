<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$error = $error ?? '';
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录 - Gundam商城</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= $asset('css/admin.css') ?>">
</head>
<body class="admin-login">
    <?php include __DIR__ . '/../partials/header.php'; ?>
    <div class="container py-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="logo">
                            <i class="bi bi-cpu"></i>
                        </div>
                        <h1 class="auth-title">Gundam 后台管理</h1>
                        <p class="auth-subtitle">请输入您的管理员账号登录</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= $url('admin/login') ?>">
                        <div class="mb-3">
                            <label class="form-label" for="admin-username">用户名</label>
                            <input type="text"
                                   class="form-control"
                                   id="admin-username"
                                   name="username"
                                   placeholder="请输入用户名"
                                   required
                                   autofocus>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="admin-password">密码</label>
                            <input type="password"
                                   class="form-control"
                                   id="admin-password"
                                   name="password"
                                   placeholder="请输入密码"
                                   required>
                        </div>

                        <button type="submit" class="btn btn-outline-success btn-login">
                            登录
                        </button>
                    </form>

                    <div class="help-links">
                        <a href="<?= $url('') ?>">返回首页</a>
                        <span class="text-muted mx-2">|</span>
                        <a href="<?= $url('faq') ?>">帮助中心</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= $asset('js/admin.js') ?>"></script>
</body>
</html>
