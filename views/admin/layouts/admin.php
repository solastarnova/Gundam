<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$admin = $admin ?? ['username' => 'Admin'];
$title = $title ?? '后台管理';
$success = $success ?? null;
$error = $error ?? null;

// 当前页面高亮判断
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Gundam后台</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- 后台自定义样式 -->
    <link rel="stylesheet" href="<?= $asset('css/admin.css') ?>">
</head>
<body class="admin-layout">
    <!-- 顶部导航 -->
    <nav class="navbar-top">
        <a href="<?= $url('admin/dashboard') ?>" class="navbar-brand">
            <i class="bi bi-cpu"></i>
            <span>Gundam 后台</span>
        </a>
        
        <div class="navbar-user">
            <span>
                <i class="bi bi-person-circle me-1"></i>
                <?= htmlspecialchars($admin['username']) ?>
            </span>
            <a href="<?= $url('admin/logout') ?>" class="btn-logout">
                <i class="bi bi-box-arrow-right me-1"></i>登出
            </a>
        </div>
    </nav>
    
    <!-- 侧边栏 -->
    <aside class="sidebar">
        <div class="nav flex-column">
            <a href="<?= $url('admin/dashboard') ?>" 
               class="nav-link <?= strpos($currentUri, '/admin/dashboard') !== false ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                仪表盘
            </a>
            <a href="<?= $url('admin/products') ?>" 
               class="nav-link <?= strpos($currentUri, '/admin/products') !== false ? 'active' : '' ?>">
                <i class="bi bi-box"></i>
                商品管理
            </a>
            <a href="<?= $url('admin/orders') ?>" 
               class="nav-link <?= strpos($currentUri, '/admin/orders') !== false ? 'active' : '' ?>">
                <i class="bi bi-cart"></i>
                订单管理
            </a>
            <a href="<?= $url('admin/users') ?>" 
               class="nav-link <?= strpos($currentUri, '/admin/users') !== false ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                用户管理
            </a>
        </div>
    </aside>
    
    <!-- 主内容区 -->
    <main class="main-content">
        <!-- 提示消息 -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- 页面内容 -->
        <?= $content ?? '' ?>
    </main>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- 后台通用JS -->
    <script src="<?= $asset('js/admin.js') ?>"></script>
</body>
</html>