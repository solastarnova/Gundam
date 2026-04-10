<?php

use App\Core\Constants;

$url = $url ?? fn($p = '') => $p;
$membershipInfo = $membershipInfo ?? [];
$membershipRules = $membershipRules ?? ($membershipInfo['rules'] ?? []);
$pointsLogs = $pointsLogs ?? [];

$user = $membershipInfo['user'] ?? [];
$currentRule = $membershipInfo['current_rule'] ?? [];
$nextRule = $membershipInfo['next_rule'] ?? null;
$gapToNext = (float) ($membershipInfo['gap_to_next'] ?? 0);

$currentLevelName = (string) ($currentRule['level_name'] ?? __m('account.points.default_level_name'));
$currentPoints = (int) ($user['points'] ?? 0);
$totalPointsEarned = (int) ($user['total_points_earned'] ?? 0);
$totalPointsSpent = (int) ($user['total_points_spent'] ?? 0);
$totalSpent = (float) ($user['total_spent'] ?? 0);
$nextMinSpent = (float) ($membershipInfo['next_min_spent'] ?? ($nextRule['min_spent'] ?? 0));
$currentMinSpent = (float) ($membershipInfo['current_min_spent'] ?? ($currentRule['min_spent'] ?? 0));

$pointsMultiplier = (float) ($currentRule['points_multiplier'] ?? 1);
$discountPercent = (float) ($currentRule['discount_percent'] ?? 0);
$discountNone = __m('account.points.discount_none');
$discountLabel = $discountPercent > 0.00001
    ? sprintf(
        __m('account.points.discount_label'),
        rtrim(rtrim(number_format($discountPercent, 2, '.', ''), '0'), '.'),
        rtrim(rtrim(number_format(100 - $discountPercent, 2, '.', ''), '0'), '.')
    )
    : $discountNone;

$upgradeProgressPercent = (float) ($membershipInfo['progress_percent'] ?? 100.0);
$account_nav_active = 'points';
?>
<div class="container account-page my-5 pt-5">
    <div class="row account-layout">
        <?php include __DIR__ . '/../partials/account_sidebar.php'; ?>

        <div class="col-lg-9 col-md-8">
            <div class="account-main-card account-main-padding">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h4 class="mb-0"><?= htmlspecialchars(__m('account.points.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($currentLevelName) ?></span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small"><?= htmlspecialchars(__m('account.points.label_available'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="fs-5 fw-bold text-primary"><?= $currentPoints ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small"><?= htmlspecialchars(__m('account.points.label_earned'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="fs-6 fw-bold text-success"><?= $totalPointsEarned ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small"><?= htmlspecialchars(__m('account.points.label_spent'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="fs-6 fw-bold text-danger"><?= $totalPointsSpent ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small"><?= htmlspecialchars(__m('account.points.label_multiplier'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="fs-6 fw-bold"><?= rtrim(rtrim(number_format($pointsMultiplier, 2, '.', ''), '0'), '.') ?>x</div>
                            <div class="text-muted small mt-2"><?= htmlspecialchars(__m('account.points.label_member_discount'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="fs-6 fw-bold"><?= htmlspecialchars($discountLabel, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border mb-4 small">
                    <div class="fw-semibold text-dark mb-2"><?= htmlspecialchars(__m('account.points.rules_heading'), ENT_QUOTES, 'UTF-8') ?></div>
                    <ul class="mb-0 ps-3">
                        <li><?= __m('account.points.rule_li_1') ?></li>
                        <li><?= __m('account.points.rule_li_2') ?></li>
                        <li><?= sprintf(__m('account.points.rule_li_3'), (int) Constants::POINTS_PER_HKD) ?></li>
                        <li><?= htmlspecialchars(__m('account.points.rule_li_4'), ENT_QUOTES, 'UTF-8') ?></li>
                    </ul>
                </div>

                <?php if (!empty($membershipRules)): ?>
                <div class="mb-4">
                    <h6 class="mb-2"><?= htmlspecialchars(__m('account.points.table_heading'), ENT_QUOTES, 'UTF-8') ?></h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?= htmlspecialchars(__m('account.points.th_level'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(__m('account.points.th_threshold'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(__m('account.points.th_multiplier'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th class="text-end"><?= htmlspecialchars(__m('account.points.th_discount'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($membershipRules as $rule): ?>
                                    <?php
                                    $dp = (float) ($rule['discount_percent'] ?? 0);
                                    $dpShow = $dp > 0.00001
                                        ? rtrim(rtrim(number_format($dp, 2, '.', ''), '0'), '.') . '%'
                                        : $discountNone;
                                    $pm = (float) ($rule['points_multiplier'] ?? 1);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($rule['level_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end"><?= number_format((float) ($rule['min_spent'] ?? 0), 2) ?></td>
                                        <td class="text-end"><?= rtrim(rtrim(number_format($pm, 2, '.', ''), '0'), '.') ?>x</td>
                                        <td class="text-end"><?= htmlspecialchars($dpShow, ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mb-4">
                    <?php if ($nextRule): ?>
                        <div class="d-flex justify-content-between small mb-2">
                            <span><?= htmlspecialchars(sprintf(__m('account.points.progress_label'), (string) ($currentRule['level_name'] ?? __m('account.points.fallback_current_level'))), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong>HK$ <?= number_format($totalSpent, 2) ?> / <?= number_format($nextMinSpent, 2) ?></strong>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div
                                class="progress-bar bg-primary"
                                role="progressbar"
                                style="width: <?= $upgradeProgressPercent ?>%;"
                                aria-valuenow="<?= $upgradeProgressPercent ?>"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>
                        <div class="mt-2 small text-muted">
                            <?= htmlspecialchars(__m('account.points.next_level_prefix'), ENT_QUOTES, 'UTF-8') ?> <strong class="text-dark"><?= htmlspecialchars((string) ($nextRule['level_name'] ?? __m('account.points.fallback_next_level')), ENT_QUOTES, 'UTF-8') ?></strong>
                            <?= htmlspecialchars(__m('account.points.gap_prefix'), ENT_QUOTES, 'UTF-8') ?> <strong class="text-dark">HK$ <?= number_format(max(0, $gapToNext), 2) ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mb-0"><?= htmlspecialchars(__m('account.points.max_level_message'), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <div class="mt-3 text-muted small">
                        <?= htmlspecialchars(sprintf(__m('account.points.redeemable_intro'), (int) Constants::POINTS_PER_HKD), ENT_QUOTES, 'UTF-8') ?><strong class="text-dark"><?= htmlspecialchars($money($currentPoints / Constants::POINTS_PER_HKD), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>

                <h5 class="mb-3"><?= htmlspecialchars(__m('account.points.logs_heading'), ENT_QUOTES, 'UTF-8') ?></h5>
                <?php if (empty($pointsLogs)): ?>
                    <p class="text-muted mb-0"><?= htmlspecialchars(__m('account.points.logs_empty'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th><?= htmlspecialchars(__m('account.points.th_time'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(__m('account.points.th_type'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(__m('account.points.th_change'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(__m('account.points.th_note'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pointsLogs as $log): ?>
                                    <?php $change = (int) ($log['points_change'] ?? 0); ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?></td>
                                        <td>
                                            <span class="badge <?= $change >= 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?>">
                                                <?= htmlspecialchars((string) ($log['change_type'] ?? '')) ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold <?= $change >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $change >= 0 ? '+' : '' ?><?= $change ?>
                                        </td>
                                        <td><?= htmlspecialchars((string) ($log['description'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
