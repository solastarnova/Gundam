<?php
/** @var callable $url */
$url = $url ?? fn (string $p = '') => $p;
$account_nav_active = (string) ($account_nav_active ?? '');
$navActive = static function (string $key) use ($account_nav_active): string {
    return $account_nav_active === $key ? ' active' : '';
};
?>
        <div class="col-lg-3 col-md-4">
            <div class="sidebar account-sidebar">
                <h5 class="px-4 mb-4 text-dark fw-bold"><?= htmlspecialchars(__m('account_sidebar.title'), ENT_QUOTES, 'UTF-8') ?></h5>
                <div class="nav flex-column">
                    <a href="<?= $url('account') ?>" class="nav-link d-flex align-items-center<?= $navActive('home') ?>"><i class="bi bi-person me-2"></i> <?= htmlspecialchars(__m('account_sidebar.nav_profile'), ENT_QUOTES, 'UTF-8') ?></a>
                    <a href="<?= $url('account/points') ?>" class="nav-link d-flex align-items-center<?= $navActive('points') ?>"><i class="bi bi-award me-2"></i> <?= htmlspecialchars(__m('account_sidebar.nav_membership'), ENT_QUOTES, 'UTF-8') ?></a>
                    <a href="<?= $url('account/orders') ?>" class="nav-link d-flex align-items-center<?= $navActive('orders') ?>"><i class="bi bi-bag me-2"></i> <?= htmlspecialchars(__m('account_sidebar.nav_orders'), ENT_QUOTES, 'UTF-8') ?></a>
                    <a href="<?= $url('wishlist') ?>" class="nav-link d-flex align-items-center<?= $navActive('wishlist') ?>"><i class="bi bi-heart me-2"></i> <?= htmlspecialchars(__m('account_sidebar.nav_wishlist'), ENT_QUOTES, 'UTF-8') ?></a>
                    <a href="<?= $url('account/addresses') ?>" class="nav-link d-flex align-items-center<?= $navActive('addresses') ?>"><i class="bi bi-geo-alt me-2"></i> <?= htmlspecialchars(__m('account_sidebar.nav_addresses'), ENT_QUOTES, 'UTF-8') ?></a>
                    <a href="<?= $url('account/payment') ?>" class="nav-link d-flex align-items-center<?= $navActive('payment') ?>"><i class="bi bi-credit-card me-2"></i> <?= htmlspecialchars(__m('account_sidebar.nav_payment'), ENT_QUOTES, 'UTF-8') ?></a>
                    <a href="<?= $url('account/settings') ?>" class="nav-link d-flex align-items-center<?= $navActive('settings') ?>"><i class="bi bi-gear me-2" aria-hidden="true"></i> <?= htmlspecialchars(__m('account_sidebar.nav_settings'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </div>
        </div>
