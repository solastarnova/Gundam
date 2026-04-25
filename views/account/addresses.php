<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$addresses = $addresses ?? [];
$account_nav_active = 'addresses';
$mapClientConfig = (isset($mapClientConfig) && is_array($mapClientConfig)) ? $mapClientConfig : [];
$mapSharedJsPath = dirname(__DIR__, 2) . '/js/map-shared.js';
$mapSharedJsVersion = is_file($mapSharedJsPath) ? (string) filemtime($mapSharedJsPath) : (string) time();
$addressModalSharedJsPath = dirname(__DIR__, 2) . '/js/address-modal.shared.js';
$addressModalSharedJsVersion = is_file($addressModalSharedJsPath) ? (string) filemtime($addressModalSharedJsPath) : (string) time();
?>
<div class="container account-page my-5 pt-5">
    <div class="row account-layout">
        <?php include __DIR__ . '/../partials/account-sidebar.php'; ?>
        <div class="col-lg-9 col-md-8">
            <div class="account-main-card account-main-padding">
                <div class="mb-4">
                    <h4 class="mb-2"><?= htmlspecialchars(__m('account.addresses_page.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                    <p class="page-subtitle text-muted mb-0"><?= htmlspecialchars(__m('account.addresses_page.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div id="addressesList">
                    <?php if (empty($addresses)): ?>
                        <div class="border rounded p-5 text-center">
                            <p class="text-muted mb-4"><?= htmlspecialchars(__m('account.addresses_page.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                            <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addressModal" onclick="openAddModal()"><?= htmlspecialchars(__m('account.addresses_page.add_first'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    <?php else: ?>
                        <div class="row g-3 mb-4">
                            <?php foreach ($addresses as $address): ?>
                                <div class="col-12">
                                    <div class="border rounded p-3 address-card <?= $address['is_default'] ? 'border-primary' : '' ?>"
                                         <?php if (!$address['is_default']): ?> onclick="setDefault(<?= (int)$address['id'] ?>)" style="cursor: pointer;" title="<?= htmlspecialchars(__m('account.addresses_page.title_set_default'), ENT_QUOTES, 'UTF-8') ?>"
                                         <?php else: ?> title="<?= htmlspecialchars(__m('account.addresses_page.title_is_default'), ENT_QUOTES, 'UTF-8') ?>" <?php endif; ?>>
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <?php if ($address['is_default']): ?><span class="badge bg-danger"><?= htmlspecialchars(__m('account.addresses_page.badge_default'), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                                <?php if (!empty($address['address_label'])): ?><h5 class="mb-0"><?= htmlspecialchars($address['address_label']) ?></h5><?php endif; ?>
                                            </div>
                                            <div onclick="event.stopPropagation();">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-dark" onclick="event.stopPropagation(); openEditModal(<?= (int)$address['id'] ?>)"><?= htmlspecialchars(__m('account.addresses_page.edit'), ENT_QUOTES, 'UTF-8') ?></button>
                                                    <button type="button" class="btn btn-outline-danger" onclick="event.stopPropagation(); deleteAddress(<?= (int)$address['id'] ?>)"><?= htmlspecialchars(__m('account.addresses_page.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="address-details small">
                                            <p class="mb-1"><strong><?= htmlspecialchars(__m('account.addresses_page.label_recipient'), ENT_QUOTES, 'UTF-8') ?></strong><?= htmlspecialchars($address['recipient_name']) ?></p>
                                            <p class="mb-1"><strong><?= htmlspecialchars(__m('account.addresses_page.label_phone'), ENT_QUOTES, 'UTF-8') ?></strong><?= htmlspecialchars($address['phone']) ?></p>
                                            <p class="mb-1"><strong><?= htmlspecialchars(__m('account.addresses_page.label_type'), ENT_QUOTES, 'UTF-8') ?></strong><?= htmlspecialchars($address['address_type']) ?></p>
                                            <p class="mb-0"><strong><?= htmlspecialchars(__m('account.addresses_page.label_address'), ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?php
                                            $unit = $address['unit'] ?? '';
                                            $unitSuffix = __m('account.addresses_page.unit_suffix');
                                            if ($unit && ctype_digit($unit) && strpos($unit, $unitSuffix) === false) {
                                                $unit = $unit . $unitSuffix;
                                            }
                                            $floorSuffix = __m('checkout.js_floor_suffix');
                                            $addressParts = [ $address['region'], $address['district'], $address['village_estate'] ?: $address['street'], $address['building'], !empty($address['floor']) ? $address['floor'] . $floorSuffix : '', $unit ];
                                            echo htmlspecialchars(implode(' ', array_filter($addressParts)));
                                            ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mb-4"><button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addressModal" onclick="openAddModal()"><?= htmlspecialchars(__m('account.addresses_page.add_more'), ENT_QUOTES, 'UTF-8') ?></button></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel"><?= htmlspecialchars(__m('account.addresses_js.modal_add'), ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(__m('checkout.modal_close_aria'), ENT_QUOTES, 'UTF-8') ?>"></button>
            </div>
            <div class="modal-body p-0">
                <form id="addressForm">
                    <input type="hidden" id="addressId" name="id">
                    <div id="mapWrapper" class="checkout-map-wrapper">
                        <div id="checkoutAddressMap" class="checkout-address-map"></div>
                        <button type="button" id="locateMeBtn" class="btn btn-light shadow-sm checkout-map-locate-btn" aria-label="<?= htmlspecialchars(__m('checkout.map_locate_btn'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fas fa-location-arrow text-primary"></i>
                        </button>
                        <div id="mapStatusText" class="badge bg-dark bg-opacity-75 checkout-map-status-badge"></div>
                        <input type="hidden" id="lat" name="lat">
                        <input type="hidden" id="lng" name="lng">
                    </div>
                    <div class="p-4">
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
                            <label for="unit" class="form-label"><?= htmlspecialchars(__m('checkout.label_unit'), ENT_QUOTES, 'UTF-8') ?> <span class="text-muted"><?= htmlspecialchars(__m('checkout.optional_paren'), ENT_QUOTES, 'UTF-8') ?></span></label>
                            <input type="text" class="form-control" id="unit" name="unit" placeholder="N/A">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="isDefault" name="is_default" value="1">
                                <label class="form-check-label" for="isDefault"><?= htmlspecialchars(__m('checkout.label_default_address'), ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        </div>
                    </div></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(__m('checkout.btn_cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                <button type="button" class="btn btn-dark" id="saveCheckoutAddressBtn"><?= htmlspecialchars(__m('checkout.btn_save'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>
<script>
window.LEAFLET_CSS_URL = <?= json_encode((string) ($mapClientConfig['leaflet_css'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
window.LEAFLET_JS_URL = <?= json_encode((string) ($mapClientConfig['leaflet_js'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
window.NOMINATIM_REVERSE_URL = <?= json_encode((string) ($mapClientConfig['nominatim_reverse_url'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
window.MAPTILER_API_KEY = <?= json_encode((string) ($mapClientConfig['maptiler_api_key'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
window.MAPTILER_SDK_CSS = <?= json_encode((string) ($mapClientConfig['maptiler_sdk_css'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
window.MAPTILER_SDK_JS = <?= json_encode((string) ($mapClientConfig['maptiler_sdk_js'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
window.MAPTILER_LEAFLET_JS = <?= json_encode((string) ($mapClientConfig['maptiler_leaflet_js'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
window.MAPTILER_GEOCODING_CONTROL_JS = <?= json_encode((string) ($mapClientConfig['maptiler_geocoding_control_js'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
window.ADDRESS_PAGE_I18N = <?= json_encode([
    'modalAdd' => __m('account.addresses_js.modal_add'),
    'modalEdit' => __m('account.addresses_js.modal_edit'),
    'loadFailed' => __m('account.addresses_js.load_failed'),
    'loadError' => __m('account.addresses_js.load_error'),
    'alertRequired' => __m('account.addresses_js.alert_required'),
    'alertVillageOrStreet' => __m('account.addresses_js.alert_village_or_street'),
    'saveFailed' => __m('account.addresses_js.save_failed'),
    'saveError' => __m('account.addresses_js.save_error'),
    'confirmDelete' => __m('account.addresses_js.confirm_delete'),
    'deleteFailed' => __m('account.addresses_js.delete_failed'),
    'deleteError' => __m('account.addresses_js.delete_error'),
    'defaultFailed' => __m('account.addresses_js.default_failed'),
    'defaultError' => __m('account.addresses_js.default_error'),
    'residentialDefault' => __m('checkout.address_type_residential'),
    'mapHelp' => __m('checkout.map_help'),
    'mapLocating' => __m('checkout.map_locating'),
    'mapReverseGeocoding' => __m('checkout.map_reverse_geocoding'),
    'mapResolvedAddress' => __m('checkout.map_resolved_address'),
    'mapLocateUnsupported' => __m('checkout.map_locate_unsupported'),
    'mapLocateDenied' => __m('checkout.map_locate_denied'),
    'mapLocateTimeout' => __m('checkout.map_locate_timeout'),
    'mapLocateFailed' => __m('checkout.map_locate_failed'),
    'mapResolveFailed' => __m('checkout.map_resolve_failed'),
    'mapMaptilerFallback' => __m('checkout.map_maptiler_fallback'),
    'mapAddressEditedHint' => __m('checkout.map_address_edited_hint'),
    'mapAddressChangedConfirm' => __m('checkout.map_address_changed_confirm'),
    'mapRelocateRequired' => __m('checkout.js_map_relocate_required'),
    'mapRequirePinForQuote' => __m('checkout.js_map_require_pin_for_quote'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
</script>
<script src="<?= $asset('js/map-shared.js') ?>?v=<?= urlencode($mapSharedJsVersion) ?>"></script>
<script src="<?= $asset('js/address-modal.shared.js') ?>?v=<?= urlencode($addressModalSharedJsVersion) ?>"></script>
