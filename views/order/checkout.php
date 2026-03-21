<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$stripePublishableKey = $stripePublishableKey ?? '';
$paypalClientId = $paypalClientId ?? '';
$shippingConfig = $shippingConfig ?? ['express_fee' => 0, 'standard_fee' => 0, 'free_threshold' => 0];
$defaultShippingAddress = $defaultShippingAddress ?? '香港';
$walletBalance = isset($walletBalance) ? (float) $walletBalance : 0.0;
$money = $money ?? fn(float $amount) => number_format($amount, 2);
$checkoutJsPath = dirname(__DIR__, 2) . '/js/checkout.js';
$checkoutJsVersion = is_file($checkoutJsPath) ? (string) filemtime($checkoutJsPath) : (string) time();
$isLoggedIn = !empty($isLoggedIn);
?>
<div class="container mt-5 pt-5">
    <h2 class="mb-4">結帳付款</h2>
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>收貨地址</span>
                    <div class="d-flex gap-2">
                        <?php if ($isLoggedIn): ?>
                        <a href="#" id="useNewAddress" class="btn btn-sm btn-outline-primary">新增地址</a>
                        <?php endif; ?>
                        <a href="<?= $url('account/addresses') ?>" class="btn btn-sm btn-outline-secondary">管理地址</a>
                    </div>
                </div>
                <div class="card-body">
                    <div id="addressSelectionArea">
                        <div id="addressLoading" class="text-center py-3">
                            <p class="text-muted mb-0">載入地址中...</p>
                        </div>
                        <div id="addressCardsContainer" class="row g-2" style="display: none;">
                        </div>
                        <div id="noAddressMessage" style="display: none;" class="text-center py-3">
                            <p class="text-muted mb-2">尚未儲存任何收件地址</p>
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <?php if ($isLoggedIn): ?>
                                <button type="button" class="btn btn-primary btn-sm" id="openAddAddressFromEmpty">新增地址</button>
                                <?php endif; ?>
                                <a href="<?= $url('account/addresses') ?>" class="btn btn-outline-primary btn-sm">前往地址管理</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">訂單訊息</div>
                <div class="card-body">
                    <div id="orderItems">
                        <p class="text-muted text-center py-3">載入中...</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">付款詳情</div>
                <div class="card-body">
                    <div class="mb-2">
                        <label for="shipping" class="form-label small">送貨方式</label>
                        <select class="form-select form-select-sm" id="shipping">
                            <option value="standard">標準配送 (2-3 工作天)</option>
                            <option value="express">快速配送 (1 工作天)</option>
                        </select>
                    </div>
                    <p class="mb-1">商品總額：<span id="subtotal" class="float-end"><?= htmlspecialchars($money(0.0), ENT_QUOTES, 'UTF-8') ?></span></p>
                    <p class="mb-1">運費：<span id="shippingFee" class="float-end"><?= htmlspecialchars($money(0.0), ENT_QUOTES, 'UTF-8') ?></span></p>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="useWalletBalance" <?= $walletBalance > 0 ? 'checked' : '' ?> <?= $walletBalance > 0 ? '' : 'disabled' ?>>
                        <label class="form-check-label small" for="useWalletBalance">
                            使用錢包餘額抵扣
                        </label>
                    </div>
                    <p class="mb-1">錢包餘額：<span id="walletBalance" class="float-end"><?= htmlspecialchars($money($walletBalance), ENT_QUOTES, 'UTF-8') ?></span></p>
                    <p class="mb-1 text-success">錢包抵扣：<span id="walletUsed" class="float-end">-<?= htmlspecialchars($money(0.0), ENT_QUOTES, 'UTF-8') ?></span></p>
                    <hr>
                    <p class="mb-1">訂單總額：<span id="orderTotal" class="float-end"><?= htmlspecialchars($money(0.0), ENT_QUOTES, 'UTF-8') ?></span></p>
                    <p class="fs-5 fw-bold">實際需支付：<span id="final-total" class="float-end text-danger"><?= htmlspecialchars($money(0.0), ENT_QUOTES, 'UTF-8') ?></span></p>
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="paymentStripe" value="stripe" <?= $stripePublishableKey ? 'checked' : '' ?> <?= $stripePublishableKey ? '' : 'disabled' ?>>
                            <label class="form-check-label" for="paymentStripe"><strong>信用卡 / 扣帳卡</strong></label>
                            <?php if (!$stripePublishableKey): ?><small class="text-muted">（未配置）</small><?php endif; ?>
                        </div>
                        <div id="stripe-payment-form" class="ms-4 <?= $stripePublishableKey ? '' : 'stripe-form-hidden' ?>">
                            <div id="stripe-card-element" class="p-2 border rounded mb-2"></div>
                            <div id="stripe-card-errors" role="alert" class="text-danger small mb-2"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="paymentPaypal" value="paypal" <?= $paypalClientId ? '' : 'disabled' ?>>
                            <label class="form-check-label" for="paymentPaypal"><strong>PayPal</strong></label>
                            <?php if (!$paypalClientId): ?><small class="text-muted">（未配置）</small><?php endif; ?>
                        </div>
                        <div id="paypal-button-container" class="ms-4 mt-2" style="display: none;"></div>
                    </div>

                    <p id="walletZeroCheckoutHint" class="small text-success mb-2" style="display: none;">
                        應付金額為 0，將以錢包全額支付；請按下方「確認訂單」，無需輸入卡號或 PayPal。
                    </p>

                    <button type="button" class="btn btn-primary btn-lg w-100 mt-3" id="confirmOrderBtn">確認訂單</button>
                    <a href="<?= $url('cart') ?>" class="btn btn-outline-secondary w-100 mt-2">返回購物車</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isLoggedIn): ?>
<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">新增地址</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addressForm">
                    <input type="hidden" id="addressId" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="addressLabel" class="form-label">地址標籤 <span class="text-muted">(可選)</span></label>
                            <input type="text" class="form-control" id="addressLabel" name="address_label" placeholder="例如：住宅、公司">
                        </div>
                        <div class="col-md-6">
                            <label for="addressType" class="form-label">地址類型</label>
                            <select class="form-select" id="addressType" name="address_type" required>
                                <option value="住宅">住宅</option>
                                <option value="商業">商業</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="recipientName" class="form-label">收件人姓名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="recipientName" name="recipient_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">聯絡電話 <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="col-md-4">
                            <label for="region" class="form-label">地區 <span class="text-danger">*</span></label>
                            <select class="form-select" id="region" name="region" required>
                                <option value="">請選擇</option>
                                <option value="香港島">香港島</option>
                                <option value="九龍">九龍</option>
                                <option value="新界">新界</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label for="district" class="form-label">區域 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="district" name="district" required placeholder="例如：中環、尖沙咀">
                        </div>
                        <div class="col-12">
                            <label class="form-label">地址詳細 <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-md-6"><input type="text" class="form-control" id="villageEstate" name="village_estate" placeholder="屋邨/屋苑名稱（選填）"></div>
                                <div class="col-md-6"><input type="text" class="form-control" id="street" name="street" placeholder="街道（含號碼，選填）"></div>
                            </div>
                            <small class="text-muted">屋邨/屋苑名稱和街道至少填寫一項</small>
                        </div>
                        <div class="col-md-6">
                            <label for="building" class="form-label">大廈/樓宇名稱 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="building" name="building" required>
                        </div>
                        <div class="col-md-3">
                            <label for="floor" class="form-label">樓層 <span class="text-muted">(可選)</span></label>
                            <input type="text" class="form-control" id="floor" name="floor">
                        </div>
                        <div class="col-md-3">
                            <label for="unit" class="form-label">單位號碼 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="unit" name="unit" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="isDefault" name="is_default" value="1">
                                <label class="form-check-label" for="isDefault">設為預設地址</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-dark" id="saveCheckoutAddressBtn">儲存</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://js.stripe.com/v3/"></script>
<?php if ($paypalClientId): ?>
<?php
$paypalCurrency = strtoupper((string) (($currency['code'] ?? '')));
$paypalCurrency = $paypalCurrency !== '' ? $paypalCurrency : 'USD';
$paypalLocale = 'zh_HK';
$paypalSdkUrl = sprintf(
    'https://www.paypal.com/sdk/js?client-id=%s&currency=%s&locale=%s',
    htmlspecialchars($paypalClientId, ENT_QUOTES, 'UTF-8'),
    $paypalCurrency,
    $paypalLocale
);
?>
<script src="<?= $paypalSdkUrl ?>"></script>
<?php endif; ?>
<script>
window.APP_BASE = '<?= rtrim($url(), '/') ?>/';
window.STRIPE_PUBLISHABLE_KEY = <?= json_encode($stripePublishableKey, JSON_UNESCAPED_UNICODE) ?>;
window.PAYPAL_CLIENT_ID = <?= json_encode($paypalClientId, JSON_UNESCAPED_UNICODE) ?>;
window.SHIPPING_CONFIG = <?= json_encode($shippingConfig, JSON_UNESCAPED_UNICODE) ?>;
window.DEFAULT_SHIPPING_ADDRESS = <?= json_encode($defaultShippingAddress, JSON_UNESCAPED_UNICODE) ?>;
window.WALLET_BALANCE = <?= json_encode($walletBalance, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= $asset('js/checkout.js') ?>?v=<?= urlencode($checkoutJsVersion) ?>"></script>
