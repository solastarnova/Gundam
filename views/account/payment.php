<?php
$url = $url ?? fn($p = '') => $p;
$wallet_balance = $wallet_balance ?? 0.0;
$account_nav_active = 'payment';
?>
<div class="container account-page my-5 pt-5">
    <div class="row account-layout">
        <?php include __DIR__ . '/../partials/account_sidebar.php'; ?>
        <div class="col-lg-9 col-md-8">
            <div class="account-main-card account-main-padding">
                <h4 class="mb-4"><?= htmlspecialchars(__m('account.payment.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-0 bg-primary bg-opacity-10">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="text-muted small"><?= htmlspecialchars(__m('account.payment.wallet_balance'), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="fs-4 fw-bold text-primary">
                                            <?= htmlspecialchars($money((float) $wallet_balance), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="text-muted small mt-1">
                                            <?= htmlspecialchars(__m('account.payment.wallet_hint'), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                    <i class="bi bi-wallet2 fs-1 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <div class="text-muted small"><?= htmlspecialchars(__m('account.payment.common_methods'), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="fw-semibold"><?= htmlspecialchars(__m('account.payment.card_paypal'), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small mt-1">
                                    <?= htmlspecialchars(__m('account.payment.checkout_note'), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
