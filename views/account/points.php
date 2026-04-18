<?php

use App\Core\Constants;
use App\Models\UserModel;

$url = $url ?? fn($p = '') => $p;
$money = $money ?? static fn(float $amount): string => number_format($amount, 2);
$membershipInfo = $membershipInfo ?? [];
$membershipRules = $membershipRules ?? ($membershipInfo['rules'] ?? []);
$pointsLogs = $pointsLogs ?? [];

$user = $membershipInfo['user'] ?? [];
$currentRule = $membershipInfo['current_rule'] ?? [];
if (!is_array($currentRule)) {
    $currentRule = [];
}
$nextRule = $membershipInfo['next_rule'] ?? null;
if (!is_array($nextRule)) {
    $nextRule = null;
}
$gapToNext = (float) ($membershipInfo['gap_to_next'] ?? 0);

$currentLevelName = $currentRule !== []
    ? UserModel::membershipLevelDisplayName($currentRule)
    : __m('account.points.default_level_name');
$currentPoints = (int) ($user['points'] ?? 0);
$redeemHkd = $currentPoints / Constants::POINTS_PER_HKD;
$totalPointsEarned = (int) ($user['total_points_earned'] ?? 0);
$totalPointsSpent = (int) ($user['total_points_spent'] ?? 0);
$totalSpent = (float) ($user['total_spent'] ?? 0);
$nextMinSpent = (float) ($membershipInfo['next_min_spent'] ?? ($nextRule['min_spent'] ?? 0));

$pointsMultiplier = (float) ($currentRule['points_multiplier'] ?? 1);
$discountPercent = (float) ($currentRule['discount_percent'] ?? 0);
$discountNone = __m('account.points.discount_none');
$discountLabelShort = $discountPercent > 0.00001
    ? sprintf(
        __m('account.points.discount_label'),
        rtrim(rtrim(number_format($discountPercent, 2, '.', ''), '0'), '.'),
        rtrim(rtrim(number_format(100 - $discountPercent, 2, '.', ''), '0'), '.')
    )
    : $discountNone;

$multStr = rtrim(rtrim(number_format($pointsMultiplier, 2, '.', ''), '0'), '.');
$benefitsLine = sprintf(__m('account.points.status_benefits_line'), $multStr, $discountLabelShort);
$availablePointsLine = sprintf(
    __m('account.points.status_available_line'),
    number_format($currentPoints),
    htmlspecialchars($money($redeemHkd), ENT_QUOTES, 'UTF-8')
);

$upgradeProgressPercent = (float) ($membershipInfo['progress_percent'] ?? 100.0);
$upgradeProgressRounded = (int) round(min(100.0, max(0.0, $upgradeProgressPercent)));

$nextRankName = is_array($nextRule) && $nextRule !== []
    ? UserModel::membershipLevelDisplayName($nextRule)
    : __m('account.points.fallback_next_level');
$promoteHint = $nextRule
    ? sprintf(
        __m('account.points.promote_hint_quotes'),
        $nextRankName,
        number_format(max(0, $gapToNext), 2)
    )
    : '';

$statsRuleLine = sprintf(
    __m('account.points.rule_block_stats_body'),
    number_format($totalPointsEarned),
    number_format($totalPointsSpent)
);

$account_nav_active = 'points';
?>
<div class="container account-page my-5 pt-5">
    <div class="row account-layout">
        <?php include __DIR__ . '/../partials/account-sidebar.php'; ?>

        <div class="col-lg-9 col-md-8">
            <div class="account-main-card account-main-padding membership-hub">
                <header class="membership-hub-header mb-4">
                    <h4 class="mb-1"><?= htmlspecialchars(__m('account.points.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                </header>

                <section class="membership-hub-section mb-4" aria-labelledby="hub-status-heading">
                    <h5 id="hub-status-heading" class="hub-section-title">
                        <i class="bi bi-card-heading hub-section-icon" aria-hidden="true"></i>
                        <?= htmlspecialchars(__m('account.points.section_status'), ENT_QUOTES, 'UTF-8') ?>
                    </h5>
                    <div class="member-status-card border rounded-3 p-3 p-md-4">
                        <dl class="row member-status-dl mb-0">
                            <dt class="col-sm-4 col-lg-3"><?= htmlspecialchars(__m('account.points.label_member_level'), ENT_QUOTES, 'UTF-8') ?></dt>
                            <dd class="col-sm-8 col-lg-9"><?= htmlspecialchars($currentLevelName, ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt class="col-sm-4 col-lg-3"><?= htmlspecialchars(__m('account.points.label_available'), ENT_QUOTES, 'UTF-8') ?></dt>
                            <dd class="col-sm-8 col-lg-9"><?= htmlspecialchars($availablePointsLine, ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt class="col-sm-4 col-lg-3"><?= htmlspecialchars(__m('account.points.label_member_benefits'), ENT_QUOTES, 'UTF-8') ?></dt>
                            <dd class="col-sm-8 col-lg-9"><?= htmlspecialchars($benefitsLine, ENT_QUOTES, 'UTF-8') ?></dd>
                        </dl>
                    </div>
                </section>

                <section class="membership-hub-section mb-4" aria-labelledby="hub-progress-heading">
                    <h5 id="hub-progress-heading" class="hub-section-title">
                        <i class="bi bi-bar-chart-steps hub-section-icon" aria-hidden="true"></i>
                        <?= htmlspecialchars(__m('account.points.section_level_progress'), ENT_QUOTES, 'UTF-8') ?>
                    </h5>
                    <div class="level-progress-card border rounded-3 p-3 p-md-4 bg-body-tertiary">
                        <?php if ($nextRule): ?>
                            <dl class="row member-status-dl mb-3 mb-md-4">
                                <dt class="col-sm-4 col-lg-3"><?= htmlspecialchars(__m('account.points.label_total_spent'), ENT_QUOTES, 'UTF-8') ?></dt>
                                <dd class="col-sm-8 col-lg-9 font-monospace"><?= htmlspecialchars($money($totalSpent), ENT_QUOTES, 'UTF-8') ?></dd>
                                <dt class="col-sm-4 col-lg-3"><?= htmlspecialchars(__m('account.points.label_upgrade_target'), ENT_QUOTES, 'UTF-8') ?></dt>
                                <dd class="col-sm-8 col-lg-9 font-monospace">
                                    HK$ <?= number_format($nextMinSpent, 2) ?>
                                    <span class="text-muted">（<?= htmlspecialchars($nextRankName, ENT_QUOTES, 'UTF-8') ?>）</span>
                                </dd>
                            </dl>
                            <div class="mb-2 small text-muted"><?= htmlspecialchars(__m('account.points.progress_bar_label'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="progress level-progress-visual flex-grow-1" style="height: 10px;" role="progressbar" aria-valuenow="<?= $upgradeProgressRounded ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= htmlspecialchars(__m('account.points.section_level_progress'), ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="progress-bar" style="width: <?= $upgradeProgressPercent ?>%;"></div>
                                </div>
                                <span class="level-progress-pct small fw-semibold text-body-secondary text-nowrap"><?= $upgradeProgressRounded ?>%</span>
                            </div>
                            <p class="mb-0 small text-body-secondary"><?= htmlspecialchars($promoteHint, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php else: ?>
                            <dl class="row member-status-dl mb-0">
                                <dt class="col-sm-4 col-lg-3"><?= htmlspecialchars(__m('account.points.label_total_spent'), ENT_QUOTES, 'UTF-8') ?></dt>
                                <dd class="col-sm-8 col-lg-9 font-monospace"><?= htmlspecialchars($money($totalSpent), ENT_QUOTES, 'UTF-8') ?></dd>
                            </dl>
                            <p class="mb-0 mt-3 small text-success"><?= htmlspecialchars(__m('account.points.max_level_message'), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="membership-hub-section mb-4" aria-labelledby="hub-rules-heading">
                    <h5 id="hub-rules-heading" class="hub-section-title">
                        <i class="bi bi-journal-text hub-section-icon" aria-hidden="true"></i>
                        <?= htmlspecialchars(__m('account.points.rules_heading'), ENT_QUOTES, 'UTF-8') ?>
                    </h5>
                    <div class="member-rules-card border rounded-3 p-3 p-md-4">
                        <dl class="member-rules-dl mb-0">
                            <dt><?= htmlspecialchars(__m('account.points.rule_block_upgrade_title'), ENT_QUOTES, 'UTF-8') ?></dt>
                            <dd><?= htmlspecialchars(__m('account.points.rule_block_upgrade_body'), ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt><?= htmlspecialchars(__m('account.points.rule_block_earn_title'), ENT_QUOTES, 'UTF-8') ?></dt>
                            <dd><?= __m('account.points.rule_block_earn_body') ?></dd>
                            <dt><?= htmlspecialchars(__m('account.points.rule_block_redeem_title'), ENT_QUOTES, 'UTF-8') ?></dt>
                            <dd><?= htmlspecialchars(sprintf(__m('account.points.rule_block_redeem_body'), number_format((int) Constants::POINTS_PER_HKD)), ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt><?= htmlspecialchars(__m('account.points.rule_block_stats_title'), ENT_QUOTES, 'UTF-8') ?></dt>
                            <dd><?= htmlspecialchars($statsRuleLine, ENT_QUOTES, 'UTF-8') ?></dd>
                        </dl>
                    </div>
                </section>

                <?php if (!empty($membershipRules)): ?>
                <section class="membership-hub-section mb-4" aria-labelledby="hub-table-heading">
                    <h5 id="hub-table-heading" class="hub-section-title">
                        <i class="bi bi-table hub-section-icon" aria-hidden="true"></i>
                        <?= htmlspecialchars(__m('account.points.table_heading'), ENT_QUOTES, 'UTF-8') ?>
                    </h5>
                    <div class="table-responsive border rounded-3 overflow-hidden">
                        <table class="table table-sm table-hover align-middle mb-0 rank-overview-table">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col"><?= htmlspecialchars(__m('account.points.th_member_level'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th scope="col" class="text-end"><?= htmlspecialchars(__m('account.points.th_upgrade_threshold'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th scope="col" class="text-end"><?= htmlspecialchars(__m('account.points.th_points_rebate'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th scope="col" class="text-end"><?= htmlspecialchars(__m('account.points.th_member_discount_col'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($membershipRules as $rule): ?>
                                    <?php
                                    $dp = (float) ($rule['discount_percent'] ?? 0);
                                    $dpTrim = rtrim(rtrim(number_format($dp, 2, '.', ''), '0'), '.');
                                    $dpShow = $dp > 0.00001
                                        ? sprintf(__m('account.points.discount_table_pct'), $dpTrim)
                                        : $discountNone;
                                    $pm = (float) ($rule['points_multiplier'] ?? 1);
                                    $minSpent = (float) ($rule['min_spent'] ?? 0);
                                    $thresholdCell = $minSpent <= 0.00001
                                        ? __m('account.points.threshold_initial')
                                        : number_format($minSpent, 2);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars(UserModel::membershipLevelDisplayName($rule), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end font-monospace small"><?= htmlspecialchars($thresholdCell, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end fw-medium"><?= rtrim(rtrim(number_format($pm, 2, '.', ''), '0'), '.') ?>x</td>
                                        <td class="text-end"><?= htmlspecialchars($dpShow, ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <?php endif; ?>

                <section class="membership-hub-section mb-0" aria-labelledby="hub-logs-heading">
                    <h5 id="hub-logs-heading" class="hub-section-title">
                        <i class="bi bi-clock-history hub-section-icon" aria-hidden="true"></i>
                        <?= htmlspecialchars(__m('account.points.logs_heading'), ENT_QUOTES, 'UTF-8') ?>
                    </h5>
                    <?php if (empty($pointsLogs)): ?>
                        <p class="text-muted mb-0"><?= htmlspecialchars(__m('account.points.logs_empty'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <div class="table-responsive border rounded-3 overflow-hidden">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
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
                </section>
            </div>
        </div>
    </div>
</div>
