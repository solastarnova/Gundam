<?php

use App\Core\Constants;

$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$stripePublishableKey = $stripePublishableKey ?? '';
$paypalClientId = $paypalClientId ?? '';
$shippingConfig = $shippingConfig ?? ['express_fee' => 0, 'standard_fee' => 0, 'free_threshold' => 0];
$defaultShippingAddress = $defaultShippingAddress ?? __m('checkout.default_shipping_region');
$walletBalance = isset($walletBalance) ? (float) $walletBalance : 0.0;
$pointsBalance = isset($pointsBalance) ? (int) $pointsBalance : 0;
$money = $money ?? fn(float $amount) => number_format($amount, 2);
$checkoutJsPath = dirname(__DIR__, 2) . '/js/checkout.js';
$checkoutJsVersion = is_file($checkoutJsPath) ? (string) filemtime($checkoutJsPath) : (string) time();
$isLoggedIn = !empty($isLoggedIn);

$checkoutI18n = [
    'defaultShipping' => __m('checkout.default_shipping_region'),
    'floorSuffix' => __m('checkout.js_floor_suffix'),
    'badgeDefault' => __m('checkout.js_badge_default'),
    'perUnit' => __m('checkout.js_per_unit'),
    'ariaQtyMinus' => __m('checkout.js_aria_qty_minus'),
    'ariaQtyPlus' => __m('checkout.js_aria_qty_plus'),
    'removeTitle' => __m('checkout.js_remove_title'),
    'alertRequired' => __m('checkout.js_alert_required'),
    'alertVillageOrStreet' => __m('checkout.js_alert_village_or_street'),
    'errSaveGeneric' => __m('checkout.js_err_save_generic'),
    'alertSaveAddressFailed' => __m('checkout.js_alert_save_address_failed'),
    'emptyCart' => __m('checkout.js_empty_cart'),
    'confirmRemoveLine' => __m('checkout.js_confirm_remove_line'),
    'alertUpdateQtyFailed' => __m('checkout.js_alert_update_qty_failed'),
    'alertRemoveFailed' => __m('checkout.js_alert_remove_failed'),
    'confirmOrder' => __m('checkout.btn_confirm_order'),
    'processing' => __m('checkout.js_btn_processing'),
    'errStripeModule' => __m('checkout.js_err_stripe_module'),
    'alertPaypalLoadFailed' => __m('checkout.js_alert_paypal_load_failed'),
    'errSelectCard' => __m('checkout.js_err_select_card'),
    'errNeedPaymentAuth' => __m('checkout.js_err_need_payment_auth'),
    'errPaymentFailed' => __m('checkout.js_err_payment_failed'),
    'orderConfirmedPrefix' => __m('checkout.js_order_confirmed_prefix'),
    'errOrderConfirm' => __m('checkout.js_err_order_confirm'),
    'errOrderConfirmRetry' => __m('checkout.js_err_order_confirm_retry'),
    'errPaymentIncomplete' => __m('checkout.js_err_payment_incomplete'),
    'errCheckout' => __m('checkout.js_err_checkout'),
    'errCheckoutRetry' => __m('checkout.js_err_checkout_retry'),
    'alertCartEmpty' => __m('checkout.js_alert_cart_empty'),
    'errCreatePayment' => __m('checkout.js_err_create_payment'),
    'errStripeNotConfigured' => __m('checkout.js_err_stripe_not_configured'),
    'errCreatePaymentRetry' => __m('checkout.js_err_create_payment_retry'),
    'errPaypalCreateOrder' => __m('checkout.js_err_paypal_create_order'),
    'errPaypalProcess' => __m('checkout.js_err_paypal_process'),
    'alertPaypalError' => __m('checkout.js_alert_paypal_error'),
    'modalAddAddress' => __m('checkout.modal_add_address'),
];
?>
<div class="container mt-5 pt-5">
    <h2 class="mb-4"><?= htmlspecialchars(__m('checkout.title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><?= htmlspecialchars(__m('checkout.shipping_address_header'), ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="d-flex gap-2">
                        <?php if ($isLoggedIn): ?>
                        <a href="#" id="useNewAddress" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(__m('checkout.btn_add_address'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                        <a href="<?= $url('account/addresses') ?>" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(__m('checkout.btn_manage_addresses'), ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                </div>
                <div class="card-body">
                    <div id="addressSelectionArea">
                        <div id="addressLoading" class="text-center py-3">
                            <p class="text-muted mb-0"><?= htmlspecialchars(__m('checkout.address_loading'), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div id="addressCardsContainer" class="row g-2" style="display: none;">
                        </div>
                        <div id="noAddressMessage" style="display: none;" class="text-center py-3">
                            <p class="text-muted mb-2"><?= htmlspecialchars(__m('checkout.no_saved_address'), ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <?php if ($isLoggedIn): ?>
                                <button type="button" class="btn btn-primary btn-sm" id="openAddAddressFromEmpty"><?= htmlspecialchars(__m('checkout.btn_add_address'), ENT_QUOTES, 'UTF-8') ?></button>
                                <?php endif; ?>
                                <a href="<?= $url('account/addresses') ?>" class="btn btn-outline-primary btn-sm"><?= htmlspecialchars(__m('checkout.btn_go_addresses'), ENT_QUOTES, 'UTF-8') ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header"><?= htmlspecialchars(__m('checkout.order_items_header'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="card-body">
                    <div id="orderItems">
                        <p class="text-muted text-center py-3"><?= htmlspecialchars(__m('checkout.loading'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><?= htmlspecialchars(__m('checkout.payment_details_header'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="card-body">
                    <div class="mb-2">
                        <label for="shipping" class="form-label small"><?= htmlspecialchars(__m('checkout.shipping_method_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select form-select-sm" id="shipping">
                            <option value="standard"><?= htmlspecialchars(__m('checkout.shipping_standard'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="express"><?= htmlspecialchars(__m('checkout.shipping_express'), ENT_QUOTES, 'UTF-8') ?></option>
                        </select>
                    </div>
                    <p class="mb-1"><?= htmlspecialchars(__m('checkout.label_subtotal'), ENT_QUOTES, 'UTF-8') ?><span id="subtotal" class="float-end"><?= htmlspecialchars($money(0.0), ENT_QUOTES, 'UTF-8') ?></span></p>
                    <p class="mb-1"><?= htmlspecialchars(__m('checkout.label_shipping_fee'), ENT_QUOTES, 'UTF-8') ?><span id="shippingFee" class="float-end"><?= htmlspecialchars($money(0.0), ENT_QUOTES, 'UTF-8') ?></span></p>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="useWalletBalance" <?= $walletBalance > 0 ? 'checked' : '' ?> <?= $walletBalance > 0 ? '' : 'disabled' ?>>
                        <label class="form-check-label small" for="useWalletBalance">
                            <?= htmlspecialchars(__m('checkout.use_wallet_label'), ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    </div>
                    <p class="mb-1"><?= htmlspecialchars(__m('checkout.label_wallet_balance'), ENT_QUOTES, 'UTF-8') ?><span id="walletBalance" class="float-end"><?= htmlspecialchars($money($walletBalance), ENT_QUOTES, 'UTF-8') ?></span></p>
                    <p class="mb-1 text-success"><?= htmlspecialchars(__m('checkout.label_wallet_deduction'), ENT_QUOTES, 'UTF-8') ?><span id="walletUsed" class="float-end">-<?= htmlspecialchars($money(0.0), ENT_QUOTES, 'UTF-8') ?></span></p>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="usePoints" <?= $pointsBalance > 0 ? '' : 'disabled' ?>>
                        <label class="form-check-label small" for="usePoints"><?= htmlspecialchars(__m('checkout.use_points_label', (int) Constants::POINTS_PER_HKD), ENT_QUOTES, 'UTF-8') ?></label>
                    </div>
                    <p class="mb-1"><?= htmlspecialchars(__m('checkout.label_points_available'), ENT_QUOTES, 'UTF-8') ?><span id="pointsBalance" class="float-end"><?= (int) $pointsBalance ?></span></p>
                    <p class="mb-1 text-success"><?= htmlspecialchars(__m('checkout.label_points_deduction'), ENT_QUOTES, 'UTF-8') ?><span id="pointsUsed" class="float-end">-<?= htmlspecialchars($money(0.0), ENT_QUOTES, 'UTF-8') ?></span></p>
                    <hr>
                    <p class="mb-1"><?= htmlspecialchars(__m('checkout.label_order_total'), ENT_QUOTES, 'UTF-8') ?><span id="orderTotal" class="float-end"><?= htmlspecialchars($money(0.0), ENT_QUOTES, 'UTF-8') ?></span></p>
                    <p class="fs-5 fw-bold"><?= htmlspecialchars(__m('checkout.label_payable'), ENT_QUOTES, 'UTF-8') ?><span id="final-total" class="float-end text-danger"><?= htmlspecialchars($money(0.0), ENT_QUOTES, 'UTF-8') ?></span></p>
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="paymentStripe" value="stripe" <?= $stripePublishableKey ? 'checked' : '' ?> <?= $stripePublishableKey ? '' : 'disabled' ?>>
                            <label class="form-check-label" for="paymentStripe"><strong><?= htmlspecialchars(__m('checkout.payment_stripe'), ENT_QUOTES, 'UTF-8') ?></strong></label>
                            <?php if (!$stripePublishableKey): ?><small class="text-muted"><?= htmlspecialchars(__m('checkout.payment_not_configured'), ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                        </div>
                        <div id="stripe-payment-form" class="ms-4 <?= $stripePublishableKey ? '' : 'stripe-form-hidden' ?>">
                            <div id="stripe-card-element" class="p-2 border rounded mb-2"></div>
                            <div id="stripe-card-errors" role="alert" class="text-danger small mb-2"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="paymentPaypal" value="paypal" <?= $paypalClientId ? '' : 'disabled' ?>>
                            <label class="form-check-label" for="paymentPaypal"><strong><?= htmlspecialchars(__m('checkout.payment_paypal'), ENT_QUOTES, 'UTF-8') ?></strong></label>
                            <?php if (!$paypalClientId): ?><small class="text-muted"><?= htmlspecialchars(__m('checkout.payment_not_configured'), ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                        </div>
                        <div id="paypal-button-container" class="ms-4 mt-2" style="display: none;"></div>
                    </div>

                    <p id="walletZeroCheckoutHint" class="small text-success mb-2" style="display: none;">
                        <?= htmlspecialchars(__m('checkout.wallet_zero_hint'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <div id="paymentSystemLoader" class="small text-primary fw-semibold mb-2" style="display: none;">
                        <?= htmlspecialchars(__m('checkout.payment_system_linking'), ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <button type="button" class="btn btn-primary btn-lg w-100 mt-3" id="confirmOrderBtn"><?= htmlspecialchars(__m('checkout.btn_confirm_order'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a href="<?= $url('cart') ?>" class="btn btn-outline-secondary w-100 mt-2"><?= htmlspecialchars(__m('checkout.btn_back_cart'), ENT_QUOTES, 'UTF-8') ?></a>
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
                <h5 class="modal-title" id="addressModalLabel"><?= htmlspecialchars(__m('checkout.modal_add_address'), ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(__m('checkout.modal_close_aria'), ENT_QUOTES, 'UTF-8') ?>"></button>
            </div>
            <div class="modal-body">
                <form id="addressForm">
                    <input type="hidden" id="addressId" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="addressLabel" class="form-label"><?= htmlspecialchars(__m('checkout.label_address_tag'), ENT_QUOTES, 'UTF-8') ?> <span class="text-muted"><?= htmlspecialchars(__m('checkout.optional_paren'), ENT_QUOTES, 'UTF-8') ?></span></label>
                            <input type="text" class="form-control" id="addressLabel" name="address_label" placeholder="<?= htmlspecialchars(__m('checkout.address_tag_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="addressType" class="form-label"><?= htmlspecialchars(__m('checkout.label_address_type'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select class="form-select" id="addressType" name="address_type" required>
                                <option value="<?= htmlspecialchars(__m('checkout.address_type_residential'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__m('checkout.address_type_residential'), ENT_QUOTES, 'UTF-8') ?></option>
                                <option value="<?= htmlspecialchars(__m('checkout.address_type_commercial'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__m('checkout.address_type_commercial'), ENT_QUOTES, 'UTF-8') ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="recipientName" class="form-label"><?= htmlspecialchars(__m('checkout.label_recipient'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="recipientName" name="recipient_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label"><?= htmlspecialchars(__m('checkout.label_contact_phone'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="col-md-4">
                            <label for="region" class="form-label"><?= htmlspecialchars(__m('checkout.label_region'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="region" name="region" required>
                                <option value=""><?= htmlspecialchars(__m('checkout.region_select'), ENT_QUOTES, 'UTF-8') ?></option>
                                <option value="<?= htmlspecialchars(__m('checkout.region_hk_island'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__m('checkout.region_hk_island'), ENT_QUOTES, 'UTF-8') ?></option>
                                <option value="<?= htmlspecialchars(__m('checkout.region_kowloon'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__m('checkout.region_kowloon'), ENT_QUOTES, 'UTF-8') ?></option>
                                <option value="<?= htmlspecialchars(__m('checkout.region_new_territories'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__m('checkout.region_new_territories'), ENT_QUOTES, 'UTF-8') ?></option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label for="district" class="form-label"><?= htmlspecialchars(__m('checkout.label_district'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="district" name="district" required placeholder="<?= htmlspecialchars(__m('checkout.district_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= htmlspecialchars(__m('checkout.label_address_detail'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-md-6"><input type="text" class="form-control" id="villageEstate" name="village_estate" placeholder="<?= htmlspecialchars(__m('checkout.village_placeholder'), ENT_QUOTES, 'UTF-8') ?>"></div>
                                <div class="col-md-6"><input type="text" class="form-control" id="street" name="street" placeholder="<?= htmlspecialchars(__m('checkout.street_placeholder'), ENT_QUOTES, 'UTF-8') ?>"></div>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars(__m('checkout.hint_village_or_street'), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <div class="col-md-6">
                            <label for="building" class="form-label"><?= htmlspecialchars(__m('checkout.label_building'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="building" name="building" required>
                        </div>
                        <div class="col-md-3">
                            <label for="floor" class="form-label"><?= htmlspecialchars(__m('checkout.label_floor'), ENT_QUOTES, 'UTF-8') ?> <span class="text-muted"><?= htmlspecialchars(__m('checkout.optional_paren'), ENT_QUOTES, 'UTF-8') ?></span></label>
                            <input type="text" class="form-control" id="floor" name="floor">
                        </div>
                        <div class="col-md-3">
                            <label for="unit" class="form-label"><?= htmlspecialchars(__m('checkout.label_unit'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="unit" name="unit" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="isDefault" name="is_default" value="1">
                                <label class="form-check-label" for="isDefault"><?= htmlspecialchars(__m('checkout.label_default_address'), ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(__m('checkout.btn_cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                <button type="button" class="btn btn-dark" id="saveCheckoutAddressBtn"><?= htmlspecialchars(__m('checkout.btn_save'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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
<script>
window.APP_BASE = '<?= rtrim($url(), '/') ?>/';
window.STRIPE_PUBLISHABLE_KEY = <?= json_encode($stripePublishableKey, JSON_UNESCAPED_UNICODE) ?>;
window.STRIPE_SDK_URL = 'https://js.stripe.com/v3/';
window.PAYPAL_CLIENT_ID = <?= json_encode($paypalClientId, JSON_UNESCAPED_UNICODE) ?>;
window.PAYPAL_SDK_URL = <?= json_encode($paypalClientId ? $paypalSdkUrl : '', JSON_UNESCAPED_UNICODE) ?>;
window.SHIPPING_CONFIG = <?= json_encode($shippingConfig, JSON_UNESCAPED_UNICODE) ?>;
window.DEFAULT_SHIPPING_ADDRESS = <?= json_encode($defaultShippingAddress, JSON_UNESCAPED_UNICODE) ?>;
window.WALLET_BALANCE = <?= json_encode($walletBalance, JSON_UNESCAPED_UNICODE) ?>;
window.POINTS_BALANCE = <?= json_encode($pointsBalance, JSON_UNESCAPED_UNICODE) ?>;
window.POINTS_PER_HKD = <?= (int) Constants::POINTS_PER_HKD ?>;
window.CHECKOUT_I18N = <?= json_encode($checkoutI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
</script>
<script src="<?= $asset('js/checkout.js') ?>?v=<?= urlencode($checkoutJsVersion) ?>"></script>
