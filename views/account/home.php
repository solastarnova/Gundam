<?php
$url = $url ?? fn($p = '') => $p;
$user_name = $user_name ?? '';
$email = $email ?? '';
$account_nav_active = 'home';
?>
<div class="container account-page my-5 pt-5">
    <div class="row account-layout">
        <?php include __DIR__ . '/../partials/account_sidebar.php'; ?>
        <div class="col-lg-9 col-md-8">
            <div class="account-main-card account-main-padding">
                <h4 class="mb-4"><?= htmlspecialchars(__m('account.home.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted"><?= htmlspecialchars(__m('account.home.label_nickname'), ENT_QUOTES, 'UTF-8') ?></label>
                        <p class="mb-0"><?= htmlspecialchars($user_name) ?></p>
                    </div>
                    <div class="col-md-6"></div>
                    <div class="col-12">
                        <label class="form-label text-muted"><?= htmlspecialchars(__m('account.home.label_email'), ENT_QUOTES, 'UTF-8') ?></label>
                        <p class="mb-0"><?= htmlspecialchars($email) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
