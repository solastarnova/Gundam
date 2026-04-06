<?php
$url = $url ?? fn($p = '') => $p;
$membershipInfo = $membershipInfo ?? [];
$pointsLogs = $pointsLogs ?? [];

$user = $membershipInfo['user'] ?? [];
$currentRule = $membershipInfo['current_rule'] ?? [];
$nextRule = $membershipInfo['next_rule'] ?? null;
$gapToNext = (float) ($membershipInfo['gap_to_next'] ?? 0);

$currentLevelName = (string) ($currentRule['level_name'] ?? '青铜');
$currentPoints = (int) ($user['points'] ?? 0);
$totalPointsEarned = (int) ($user['total_points_earned'] ?? 0);
$totalPointsSpent = (int) ($user['total_points_spent'] ?? 0);
$totalSpent = (float) ($user['total_spent'] ?? 0);
$nextMinSpent = (float) ($membershipInfo['next_min_spent'] ?? ($nextRule['min_spent'] ?? 0));
$currentMinSpent = (float) ($membershipInfo['current_min_spent'] ?? ($currentRule['min_spent'] ?? 0));

$pointsMultiplier = (float) ($currentRule['points_multiplier'] ?? 1);
$discountPercent = (float) ($currentRule['discount_percent'] ?? 0);

$upgradeProgressPercent = (float) ($membershipInfo['progress_percent'] ?? 100.0);
?>
<div class="container account-page my-5 pt-5">
    <div class="row account-layout">
        <div class="col-lg-3 col-md-4">
            <div class="sidebar account-sidebar">
                <h5 class="px-4 mb-4 text-dark fw-bold">我的帳戶</h5>
                <div class="nav flex-column">
                    <a href="<?= $url('account') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-person me-2"></i> 個人資料</a>
                    <a href="<?= $url('account/points') ?>" class="nav-link d-flex align-items-center active"><i class="bi bi-award me-2"></i> 會員中心</a>
                    <a href="<?= $url('account/orders') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-bag me-2"></i> 訂單記錄</a>
                    <a href="<?= $url('wishlist') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-heart me-2"></i> 喜愛清單</a>
                    <a href="<?= $url('account/addresses') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-geo-alt me-2"></i> 預設地址</a>
                    <a href="<?= $url('account/payment') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-credit-card me-2"></i> 付款方式</a>
                    <a href="<?= $url('account/settings') ?>" class="nav-link d-flex align-items-center">帳戶設定</a>
                    <a class="nav-link d-flex text-danger" href="<?= $url('logout') ?>">登出</a>
                </div>
            </div>
        </div>

        <div class="col-lg-9 col-md-8">
            <div class="account-main-card account-main-padding">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h4 class="mb-0">會員中心</h4>
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($currentLevelName) ?></span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">可用積分</div>
                            <div class="fs-5 fw-bold text-primary"><?= $currentPoints ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">累計獲得</div>
                            <div class="fs-6 fw-bold text-success"><?= $totalPointsEarned ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">累計使用</div>
                            <div class="fs-6 fw-bold text-danger"><?= $totalPointsSpent ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">倍率 / 折扣</div>
                            <div class="fs-6 fw-bold"><?= $pointsMultiplier ?>x / <?= $discountPercent ?>%</div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <?php if ($nextRule): ?>
                        <div class="d-flex justify-content-between small mb-2">
                            <span>累計消費進度（<?= htmlspecialchars((string) ($currentRule['level_name'] ?? '当前等级')) ?>）</span>
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
                            距離下一級 <strong class="text-dark"><?= htmlspecialchars((string) ($nextRule['level_name'] ?? '下一級')) ?></strong>
                            還差 <strong class="text-dark">HK$ <?= number_format(max(0, $gapToNext), 2) ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mb-0">您目前已是最高等級，無需再滿足升級條件。</div>
                    <?php endif; ?>

                    <div class="mt-3 text-muted small">
                        可抵扣金額：<strong class="text-dark"><?= htmlspecialchars($money($currentPoints / 1000), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>

                <h5 class="mb-3">積分記錄</h5>
                <?php if (empty($pointsLogs)): ?>
                    <p class="text-muted mb-0">暫無積分變動記錄</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>時間</th>
                                    <th>類型</th>
                                    <th>變動</th>
                                    <th>說明</th>
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
