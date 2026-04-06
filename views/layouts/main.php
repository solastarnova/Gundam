<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : 'Gundam Shop' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= $asset('css/style.css') ?>">
    <?php foreach ((array)($head_extra_css ?? []) as $href): ?>
    <link rel="stylesheet" href="<?= $asset($href) ?>">
    <?php endforeach; ?>
</head>
<body>
<script>
window.APP_BASE = <?= json_encode(isset($baseUrl) && $baseUrl !== '' ? rtrim($baseUrl, '/') . '/' : '/') ?>;
window.isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
window.APP_CURRENCY = <?= json_encode($currency ?? [], JSON_UNESCAPED_UNICODE) ?>;
window.formatMoney = function(amount) {
    var cfg = window.APP_CURRENCY || {};
    var code = cfg.code || '';
    var locale = cfg.locale || 'zh-HK';
    var decimals = Number.isFinite(cfg.decimals) ? cfg.decimals : 2;
    var value = Number(amount || 0);
    try {
        if (!code) throw new Error('currency code missing');
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: code,
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(value);
    } catch (e) {
        var symbol = cfg.symbol || '';
        return symbol + value.toFixed(decimals);
    }
};
</script>
<?php include __DIR__ . '/../partials/header.php'; ?>

<main class="main-content"><?= $content ?? '' ?></main>

<?php if (is_array($firebase_web_config ?? null) && (!empty($firebase_auth_enabled) || isset($_SESSION['user_id']))): ?>
<script>
window.FIREBASE_WEB_CONFIG = <?= json_encode($firebase_web_config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
<?php if (!empty($firebase_auth_enabled)): ?>
window.FIREBASE_AUTH_ENDPOINT = <?= json_encode($url('auth/firebase'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
window.FIREBASE_AUTH_INTENT = <?= json_encode((string) ($firebase_auth_intent ?? 'signin'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
window.FIREBASE_REDIRECT = <?= json_encode((string) ($firebase_redirect_param ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
window.FIREBASE_ENABLE_FACEBOOK = <?= !empty($firebase_enable_facebook) ? 'true' : 'false' ?>;
<?php endif; ?>
</script>
<?php endif; ?>
<?php if (!empty($firebase_social_linking_enabled) && isset($_SESSION['user_id']) && is_array($firebase_web_config ?? null)): ?>
<script>
window.FIREBASE_SOCIAL_LINKING = true;
window.FIREBASE_ENABLE_FACEBOOK = <?= !empty($firebase_enable_facebook) ? 'true' : 'false' ?>;
</script>
<?php endif; ?>
<?php if (isset($_SESSION['user_id'])): ?>
<script>
window.LOGOUT_URL = <?= json_encode($url('logout'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
</script>
<?php endif; ?>
<?php foreach ((array)($foot_script_srcs ?? []) as $src): ?>
<script src="<?= htmlspecialchars((string) $src, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endforeach; ?>
<?php foreach ((array)($foot_extra_js ?? []) as $src): ?>
<script src="<?= $asset($src) ?>"></script>
<?php endforeach; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
