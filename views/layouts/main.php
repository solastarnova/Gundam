<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : 'Gundam Shop' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $asset('css/style.css') ?>">
    <?php foreach ((array)($head_extra_css ?? []) as $href): ?>
    <link rel="stylesheet" href="<?= $asset($href) ?>">
    <?php endforeach; ?>
</head>
<body>
<script>
window.APP_BASE = <?= json_encode(isset($baseUrl) && $baseUrl !== '' ? rtrim($baseUrl, '/') . '/' : '/') ?>;
window.isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>
<?php include __DIR__ . '/../partials/header.php'; ?>

<main class="main-content"><?= $content ?? '' ?></main>

<?php foreach ((array)($foot_extra_js ?? []) as $src): ?>
<script src="<?= $asset($src) ?>"></script>
<?php endforeach; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
