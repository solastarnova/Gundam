<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$stripePublishableKey = $stripePublishableKey ?? '';
$paypalClientId = $paypalClientId ?? '';
$shippingConfig = $shippingConfig ?? ['express_fee' => 80, 'standard_fee' => 50, 'free_threshold' => 500];
$defaultShippingAddress = $defaultShippingAddress ?? '香港';
?>
<div class="container mt-5 pt-5">
    <h2 class="mb-4">結帳付款</h2>
    <div class="row">
        <div class="col-lg-8">
            <!-- 收貨地址（參考 Reference，保持現有 card 風格） -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>收貨地址</span>
                    <a href="<?= $url('account/addresses') ?>" class="btn btn-sm btn-outline-secondary">管理地址</a>
                </div>
                <div class="card-body">
                    <div id="addressSelectionArea">
                        <div id="addressLoading" class="text-center py-3">
                            <p class="text-muted mb-0">載入地址中...</p>
                        </div>
                        <div id="addressCardsContainer" class="row g-2" style="display: none;">
                            <!-- 地址卡片由 JS 動態產生 -->
                        </div>
                        <div id="noAddressMessage" style="display: none;" class="text-center py-3">
                            <p class="text-muted mb-2">尚未儲存任何收件地址</p>
                            <a href="<?= $url('account/addresses') ?>" class="btn btn-outline-primary btn-sm">前往新增地址</a>
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
                    <p class="mb-1">商品總額：<span id="subtotal" class="float-end">HK$0.00</span></p>
                    <p class="mb-1">運費：<span id="shippingFee" class="float-end">HK$0.00</span></p>
                    <hr>
                    <p class="fs-5 fw-bold">應付總額：<span id="final-total" class="float-end text-danger">HK$0.00</span></p>
                    <!-- 付款方式 -->
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

                    <button type="button" class="btn btn-primary btn-lg w-100 mt-3" id="confirmOrderBtn">確認訂單</button>
                    <a href="<?= $url('cart') ?>" class="btn btn-outline-secondary w-100 mt-2">返回購物車</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<?php if ($paypalClientId): ?>
<?php
$paypalCurrency = 'HKD';
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
</script>
<script src="<?= $asset('js/checkout.js') ?>"></script>
