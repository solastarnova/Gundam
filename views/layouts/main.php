<!DOCTYPE html>
<html lang="<?= htmlspecialchars((string) ($html_lang ?? 'zh-HK'), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : 'Gundam Shop' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= $asset('css/style.css') ?>">
    <?php foreach ((array)($head_extra_css ?? []) as $href): ?>
    <?php
        $hrefStr = (string) $href;
        $cssHref = preg_match('#^https?://#i', $hrefStr) === 1 ? $hrefStr : $asset($hrefStr);
    ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssHref, ENT_QUOTES, 'UTF-8') ?>">
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
window.APP_JS_I18N = <?= json_encode([
    'cartLoginRequired' => __m('cart.js_login_required'),
    'cartAddFailed' => __m('cart.js_add_failed_generic'),
    'cartAddFailedRetry' => __m('cart.js_add_failed_retry'),
    'wishlistLoginRequired' => __m('wishlist.js_login_required'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
window.CHATBOX_JS = <?= json_encode([
    'errorGeneric' => __m('chatbox.js_error_generic'),
    'networkError' => __m('chatbox.js_network_error'),
    'confirmClear' => __m('chatbox.js_confirm_clear'),
    'clearFailed' => __m('chatbox.js_clear_failed'),
    'welcomeHtml' => __m('chatbox.welcome'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
</script>
<?php include __DIR__ . '/../partials/header.php'; ?>

<main class="main-content">
    <div class="gundam-fab-container">
        <div class="gundam-fab-glow" aria-hidden="true"></div>
        <button type="button" class="gundam-fab-main" id="ai-chat-trigger" aria-label="<?= htmlspecialchars(__m('main.fab_open_aria'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars(__m('main.fab_title'), ENT_QUOTES, 'UTF-8') ?>">
            <div class="fab-scanner" aria-hidden="true"></div>
            <i class="fas fa-robot" aria-hidden="true"></i>
            <span class="fab-text">AI SYSTEM</span>
        </button>
    </div>
    <?= $content ?? '' ?>
</main>
<?php include __DIR__ . '/../partials/chatbox.php'; ?>

<?php if (is_array($firebase_web_config ?? null) && (!empty($firebase_auth_enabled) || isset($_SESSION['user_id']))): ?>
<script>
window.FIREBASE_WEB_CONFIG = <?= json_encode($firebase_web_config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
<?php if (!empty($firebase_auth_enabled)): ?>
window.FIREBASE_AUTH_ENDPOINT = <?= json_encode($url('auth/firebase'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
window.FIREBASE_AUTH_INTENT = <?= json_encode((string) ($firebase_auth_intent ?? 'signin'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
window.FIREBASE_REDIRECT = <?= json_encode((string) ($firebase_redirect_param ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
window.FIREBASE_ENABLE_FACEBOOK = <?= !empty($firebase_enable_facebook) ? 'true' : 'false' ?>;
window.FIREBASE_AUTH_JS = <?= json_encode([
    'credEmailMissing' => __m('firebase_auth_js.cred_email_missing'),
    'emailBindsProvider' => __m('firebase_auth_js.email_binds_provider'),
    'fbDisabled' => __m('firebase_auth_js.fb_disabled'),
    'passwordThenGithub' => __m('firebase_auth_js.password_then_github'),
    'githubMergeOk' => __m('firebase_auth_js.github_merge_ok'),
    'githubAutoUnsupported' => __m('firebase_auth_js.github_auto_unsupported'),
    'githubInUse' => __m('firebase_auth_js.github_in_use'),
    'linkFailed' => __m('firebase_auth_js.link_failed'),
    'loginFailed' => __m('firebase_auth_js.login_failed'),
    'emailMissingLink' => __m('firebase_auth_js.email_missing_link'),
    'passwordThenOauth' => __m('firebase_auth_js.password_then_oauth'),
    'signInFirst' => __m('firebase_auth_js.sign_in_first'),
    'linkOk' => __m('firebase_auth_js.link_ok'),
    'oauthInUse' => __m('firebase_auth_js.oauth_in_use'),
    'linkAccountFailed' => __m('firebase_auth_js.link_account_failed'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
<?php endif; ?>
</script>
<?php endif; ?>
<?php if (!empty($firebase_social_linking_enabled) && isset($_SESSION['user_id']) && is_array($firebase_web_config ?? null)): ?>
<script>
window.FIREBASE_SOCIAL_LINKING = true;
window.FIREBASE_ENABLE_FACEBOOK = <?= !empty($firebase_enable_facebook) ? 'true' : 'false' ?>;
window.FIREBASE_LINK_JS = <?= json_encode([
    'needLogin' => __m('firebase_link_js.need_login'),
    'processing' => __m('firebase_link_js.processing'),
    'bindOk' => __m('firebase_link_js.bind_ok'),
    'popupBlocked' => __m('firebase_link_js.popup_blocked'),
    'alreadyLinked' => __m('firebase_link_js.already_linked'),
    'credentialInUse' => __m('firebase_link_js.credential_in_use'),
    'emailInUse' => __m('firebase_link_js.email_in_use'),
    'bindFailedPrefix' => __m('firebase_link_js.bind_failed_prefix'),
    'unknownError' => __m('firebase_link_js.unknown_error'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
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
<script src="<?= $asset('js/chatbox.js') ?>"></script>
<?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
