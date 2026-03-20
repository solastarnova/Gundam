<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$admin = $admin ?? ['username' => 'Admin'];
$title = $title ?? '後台管理';
$success = $success ?? null;
$error = $error ?? null;

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Gundam後台</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $asset('css/admin.css') ?>">
</head>
<body class="admin-layout">
    <nav class="navbar-top">
        <a href="<?= $url('admin/dashboard') ?>" class="navbar-brand">
            <i class="bi bi-cpu"></i>
            <span>Gundam 後台</span>
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
    
    <aside class="sidebar">
        <div class="nav flex-column">
            <a href="<?= $url('admin/dashboard') ?>" 
               class="nav-link <?= strpos($currentUri, '/admin/dashboard') !== false ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                儀表板
            </a>
            <a href="<?= $url('admin/products') ?>" 
               class="nav-link <?= strpos($currentUri, '/admin/products') !== false ? 'active' : '' ?>">
                <i class="bi bi-box"></i>
                商品管理
            </a>
            <a href="<?= $url('admin/orders') ?>" 
               class="nav-link <?= strpos($currentUri, '/admin/orders') !== false ? 'active' : '' ?>">
                <i class="bi bi-cart"></i>
                訂單管理
            </a>
            <a href="<?= $url('admin/users') ?>" 
               class="nav-link <?= strpos($currentUri, '/admin/users') !== false ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                用戶管理
            </a>
        </div>
    </aside>
    
    <main class="main-content">
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
        
        <?= $content ?? '' ?>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $asset('js/admin.js') ?>"></script>
</body>
</html>