(function() {
    'use strict';

    const baseUrl = (window.APP_BASE || '').replace(/\/$/, '') + '/';
    const stripePublishableKey = window.STRIPE_PUBLISHABLE_KEY || '';
    const paypalClientId = window.PAYPAL_CLIENT_ID || '';
    const stripeSdkUrl = window.STRIPE_SDK_URL || 'https://js.stripe.com/v3/';
    const paypalSdkUrl = window.PAYPAL_SDK_URL || '';
    const mapCfg = window.MapShared && typeof window.MapShared.getMapConfig === 'function'
        ? window.MapShared.getMapConfig()
        : {};
    const leafletCssUrl = mapCfg.leafletCssUrl;
    const leafletJsUrl = mapCfg.leafletJsUrl;
    const nominatimReverseUrl = mapCfg.nominatimReverseUrl;
    const maptilerSdkCssUrl = mapCfg.maptilerSdkCssUrl;
    const maptilerSdkJsUrl = mapCfg.maptilerSdkJsUrl;
    const maptilerLeafletPluginUrl = mapCfg.maptilerLeafletPluginUrl;
    const maptilerGeocodingControlJsUrl = mapCfg.maptilerGeocodingControlJsUrl;
    const maptilerReverseGeocodeUrl = mapCfg.maptilerReverseGeocodeUrl;
    const mapAssetLoader = window.MapShared && typeof window.MapShared.createAssetLoader === 'function'
        ? window.MapShared.createAssetLoader({
            leafletCssUrl: leafletCssUrl,
            leafletJsUrl: leafletJsUrl,
            maptilerSdkCssUrl: maptilerSdkCssUrl,
            maptilerSdkJsUrl: maptilerSdkJsUrl,
            maptilerLeafletPluginUrl: maptilerLeafletPluginUrl,
            maptilerGeocodingControlJsUrl: maptilerGeocodingControlJsUrl
        }, 'checkout')
        : null;
    const walletBalance = parseFloat(window.WALLET_BALANCE || 0) || 0;
    const pointsBalance = parseInt(window.POINTS_BALANCE || 0, 10) || 0;
    const POINTS_PER_HKD = (function() {
        const n = Number(window.POINTS_PER_HKD);
        if (!Number.isFinite(n) || n < 1) {
            throw new Error('POINTS_PER_HKD missing or invalid (must match server Constants::POINTS_PER_HKD)');
        }
        return n;
    })();
    const CI = window.CHECKOUT_I18N || {};
    const checkoutBlocked = !!window.CHECKOUT_BLOCKED;
    const lalamoveEnabled = !!window.LALAMOVE_CHECKOUT_ENABLED;
    let lalamoveFee = null;
    /** Server-issued one-shot quote snapshot (POST as checkout_token). */
    let checkoutSnapshotToken = '';
    let lalamoveQuoteToken = 0;
    let lalamoveQuoteTimer = null;
    let lalamoveQuoteLoading = false;
    let leafletLoadPromise = null;
    let maptilerStackPromise = null;
    const externalScriptPromises = Object.create(null);
    let mapInstance = null;
    let mapMarker = null;
    let geocodingControl = null;
    let reverseGeocodeToken = 0;
    let hasMapSelection = false;
    let addressEditedAfterMapPick = false;
    let mapAutofillInProgress = false;
    const formatMoney = typeof window.formatMoney === 'function'
        ? window.formatMoney
        : function(v) {
            const cfg = window.APP_CURRENCY || {};
            const amount = Number(v || 0);
            try {
                if (cfg.code) {
                    return new Intl.NumberFormat(cfg.locale || 'zh-HK', {
                        style: 'currency',
                        currency: cfg.code,
                        minimumFractionDigits: Number.isInteger(cfg.decimals) ? cfg.decimals : 2,
                        maximumFractionDigits: Number.isInteger(cfg.decimals) ? cfg.decimals : 2
                    }).format(amount);
                }
            } catch (e) {}
            return (cfg.symbol || '') + amount.toFixed(Number.isInteger(cfg.decimals) ? cfg.decimals : 2);
        };

    let cartItems = [];
    let stripe = null;
    let cardElement = null;
    let paymentIntentClientSecret = null;
    let paymentIntentId = null;
    let orderNumberForConfirm = null;
    let paypalButtons = null;
    let addressModalManager = null;
    const PaymentLoader = {
        stripe: null,
        cache: Object.create(null),
        injectScript: function(src, globalName) {
            if (!src) {
                return Promise.reject(new Error('script_src_missing'));
            }
            if (globalName && typeof window[globalName] !== 'undefined') {
                return Promise.resolve(window[globalName]);
            }
            if (this.cache[src]) {
                return this.cache[src];
            }
            this.cache[src] = new Promise(function(resolve, reject) {
                const script = document.createElement('script');
                script.src = src;
                script.async = true;
                script.onload = function() {
                    if (globalName && typeof window[globalName] === 'undefined') {
                        reject(new Error(globalName + '_not_ready'));
                        return;
                    }
                    resolve(globalName ? window[globalName] : true);
                };
                script.onerror = function() {
                    reject(new Error('script_load_failed'));
                };
                document.head.appendChild(script);
            });
            return this.cache[src];
        },
        loadStripe: function() {
            if (this.stripe) {
                return Promise.resolve(this.stripe);
            }
            return this.injectScript(stripeSdkUrl, 'Stripe').then(function(StripeCtor) {
                if (!stripePublishableKey) {
                    throw new Error('stripe_not_configured');
                }
                const instance = StripeCtor(stripePublishableKey);
                PaymentLoader.stripe = instance;
                return instance;
            });
        },
        loadPayPal: function() {
            return this.injectScript(paypalSdkUrl, 'paypal');
        }
    };

    function loadStylesheet(href) {
        if (mapAssetLoader) {
            return mapAssetLoader.loadStylesheet(href);
        }
        if (!href) return Promise.reject(new Error('stylesheet_href_missing'));
        if (document.querySelector('link[data-checkout-href="' + href + '"]')) {
            return Promise.resolve(true);
        }
        return new Promise(function(resolve, reject) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.setAttribute('data-checkout-href', href);
            link.onload = function() { resolve(true); };
            link.onerror = function() { reject(new Error('stylesheet_load_failed')); };
            document.head.appendChild(link);
        });
    }

    var mapStatusManager = window.MapShared && typeof window.MapShared.createMapStatusManager === 'function'
        ? window.MapShared.createMapStatusManager({ statusElId: 'mapStatusText' })
        : null;
    function setMapStatus(text, tone) {
        if (mapStatusManager) {
            mapStatusManager.setMapStatus(text, tone);
            return;
        }
        var statusEl = document.getElementById('mapStatusText');
        if (!statusEl) return;
        statusEl.textContent = text || '';
        statusEl.classList.remove('bg-dark', 'bg-success', 'bg-warning', 'bg-danger', 'text-dark', 'text-white');
        if (tone === 'success') statusEl.classList.add('bg-success', 'text-white');
        else if (tone === 'warning') statusEl.classList.add('bg-warning', 'text-dark');
        else if (tone === 'danger') statusEl.classList.add('bg-danger', 'text-white');
        else statusEl.classList.add('bg-dark', 'text-white');
    }
    function shortenStatusAddress(text, maxLen) {
        if (mapStatusManager) {
            return mapStatusManager.shortenStatusAddress(text, maxLen);
        }
        var normalized = String(text || '').trim();
        var limit = Number(maxLen) || 24;
        if (!normalized) return '';
        if (normalized.length <= limit) return normalized;
        return normalized.slice(0, limit) + '...';
    }

    function ensureLeafletLoaded() {
        if (mapAssetLoader) {
            return mapAssetLoader.ensureLeafletLoaded();
        }
        if (window.L && typeof window.L.map === 'function') {
            return Promise.resolve(window.L);
        }
        if (leafletLoadPromise) return leafletLoadPromise;
        leafletLoadPromise = Promise.all([
            loadStylesheet(leafletCssUrl),
            PaymentLoader.injectScript(leafletJsUrl, 'L')
        ]).then(function(results) {
            return results[1];
        });
        return leafletLoadPromise;
    }

    function injectExternalScript(src) {
        if (mapAssetLoader) {
            return mapAssetLoader.injectScript(src);
        }
        if (!src) {
            return Promise.reject(new Error('script_src_missing'));
        }
        if (externalScriptPromises[src]) {
            return externalScriptPromises[src];
        }
        externalScriptPromises[src] = new Promise(function(resolve, reject) {
            var script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = function() {
                resolve(true);
            };
            script.onerror = function() {
                reject(new Error('script_load_failed'));
            };
            document.head.appendChild(script);
        });
        return externalScriptPromises[src];
    }

    /**
     * 載入 MapTiler 向量底圖依賴（必須在 Leaflet 已載入後執行）：
     * maptiler-sdk-js → @maptiler/leaflet-maptilersdk UMD（掛在 L.maptiler.*）
     * @see https://github.com/maptiler/leaflet-maptilersdk
     */
    function loadMapTilerStack() {
        if (mapAssetLoader) {
            return mapAssetLoader.loadMapTilerStack();
        }
        if (window.L && window.L.maptiler && typeof window.L.maptiler.maptilerLayer === 'function') {
            return Promise.resolve();
        }
        if (maptilerStackPromise) {
            return maptilerStackPromise;
        }
        maptilerStackPromise = loadStylesheet(maptilerSdkCssUrl)
            .then(function() {
                return injectExternalScript(maptilerSdkJsUrl);
            })
            .then(function() {
                return injectExternalScript(maptilerLeafletPluginUrl);
            })
            .then(function() {
                if (!window.L || !window.L.maptiler || typeof window.L.maptiler.maptilerLayer !== 'function') {
                    throw new Error('maptiler_leaflet_not_ready');
                }
            });
        return maptilerStackPromise;
    }

    function loadMapTilerGeocodingControl() {
        if (mapAssetLoader) {
            return mapAssetLoader.loadMapTilerGeocodingControl();
        }
        if (window.maptilerGeocoder && typeof window.maptilerGeocoder.GeocodingControl === 'function') {
            return Promise.resolve();
        }
        return injectExternalScript(maptilerGeocodingControlJsUrl).then(function() {
            if (!window.maptilerGeocoder || typeof window.maptilerGeocoder.GeocodingControl !== 'function') {
                throw new Error('maptiler_geocoding_control_not_ready');
            }
        });
    }

    function bindGeocodingControlPick(gc) {
        if (bindSharedGeocodingPick) {
            bindSharedGeocodingPick({
                control: gc,
                map: mapInstance,
                onPick: function(lat, lng) {
                    handleMapPick(lat, lng);
                }
            });
            return;
        }
        function pickFromCoords(lng, lat) {
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            handleMapPick(lat, lng);
        }

        function extractLngLat(payload) {
            if (!payload) return null;
            if (Array.isArray(payload.center) && payload.center.length >= 2) {
                return { lng: Number(payload.center[0]), lat: Number(payload.center[1]) };
            }
            if (payload.geometry && Array.isArray(payload.geometry.coordinates) && payload.geometry.coordinates.length >= 2) {
                return { lng: Number(payload.geometry.coordinates[0]), lat: Number(payload.geometry.coordinates[1]) };
            }
            if (payload.feature) {
                return extractLngLat(payload.feature);
            }
            if (payload.detail) {
                return extractLngLat(payload.detail);
            }
            return null;
        }

        function onPicked(raw) {
            var ll = extractLngLat(raw);
            if (!ll) return;
            pickFromCoords(ll.lng, ll.lat);
        }

        if (gc && typeof gc.on === 'function') {
            gc.on('select', onPicked);
            gc.on('pick', onPicked);
        }
        if (gc && typeof gc.addEventListener === 'function') {
            gc.addEventListener('select', onPicked);
            gc.addEventListener('pick', onPicked);
        }
        if (mapInstance && mapInstance.on) {
            mapInstance.on('geocoder:select', function(ev) {
                onPicked(ev);
            });
        }
    }

    function initGeocodingControl(L, maptilerKey) {
        if (!maptilerKey || !mapInstance || geocodingControl) return;
        loadMapTilerGeocodingControl().then(function() {
            if (!window.maptilerGeocoder || typeof window.maptilerGeocoder.GeocodingControl !== 'function') {
                return;
            }
            geocodingControl = new window.maptilerGeocoder.GeocodingControl({
                apiKey: maptilerKey,
                position: 'bottomright'
            });
            if (typeof mapInstance.addControl === 'function') {
                mapInstance.addControl(geocodingControl);
            }
            bindGeocodingControlPick(geocodingControl);
        }).catch(function(err) {
            console.error('MapTiler GeocodingControl load failed:', err);
        });
    }

    var setupSharedLeafletBasemap = window.MapShared && typeof window.MapShared.setupLeafletBasemapWithFallback === 'function'
        ? window.MapShared.setupLeafletBasemapWithFallback
        : null;
    var mapUiHelpers = window.MapShared && typeof window.MapShared.createAddressMapHelpers === 'function'
        ? window.MapShared.createAddressMapHelpers()
        : null;
    var updateMapLatLng = mapUiHelpers
        ? mapUiHelpers.updateMapLatLng
        : function(lat, lng) {
            var latEl = document.getElementById('lat');
            var lngEl = document.getElementById('lng');
            var parsedLat = Number(lat);
            var parsedLng = Number(lng);
            var latValue = Number.isFinite(parsedLat) ? parsedLat.toFixed(6) : '';
            var lngValue = Number.isFinite(parsedLng) ? parsedLng.toFixed(6) : '';
            if (latEl) latEl.value = latValue;
            if (lngEl) lngEl.value = lngValue;
        };
    var normalizeCoordinate = mapUiHelpers
        ? mapUiHelpers.normalizeCoordinate
        : function(value) {
            var parsed = Number(value);
            if (!Number.isFinite(parsed)) return null;
            return Number(parsed.toFixed(6));
        };
    var getAddressInputEls = mapUiHelpers
        ? mapUiHelpers.getAddressInputEls
        : function() {
            return ['district', 'street', 'building', 'unit', 'villageEstate'].map(function(id) {
                return document.getElementById(id);
            }).filter(function(el) {
                return !!el;
            });
        };
    var updateMapVisualState = mapUiHelpers
        ? mapUiHelpers.updateMapVisualState
        : function(isWarning) {
            var mapWrapper = document.getElementById('mapWrapper');
            if (mapWrapper) {
                mapWrapper.classList.toggle('border-warning', !!isWarning);
            }
        };
    var shouldInvalidateCoordinates = mapUiHelpers
        ? mapUiHelpers.shouldInvalidateCoordinates
        : function(oldVal, newVal) {
            var prev = String(oldVal || '').trim();
            var next = String(newVal || '').trim();
            if (!prev || !next || prev === next) return false;
            var keyPart = prev.substring(0, 8);
            if (keyPart && next.indexOf(keyPart) === -1) {
                return true;
            }
            var lengthDelta = Math.abs(next.length - prev.length);
            return prev.length > 0 && (lengthDelta / prev.length) > 0.5;
        };
    var autoSelectHongKongRegion = window.MapShared && typeof window.MapShared.autoSelectHongKongRegion === 'function'
        ? window.MapShared.autoSelectHongKongRegion
        : null;
    var bindSharedGeocodingPick = window.MapShared && typeof window.MapShared.bindGeocodingControlPick === 'function'
        ? window.MapShared.bindGeocodingControlPick
        : null;
    var reverseGeocodeWithFallback = window.MapShared && typeof window.MapShared.reverseGeocodeWithFallback === 'function'
        ? window.MapShared.reverseGeocodeWithFallback
        : null;

    function invalidateStoredCoordinates() {
        updateMapLatLng('', '');
        hasMapSelection = false;
        updateMapVisualState(true);
        setMapStatus(CI.mapRelocateRequired || '', 'warning');
    }

    function autoSelectRegionByText(sourceText) {
        var regionEl = document.getElementById('region');
        if (!regionEl || !sourceText) return;
        if (autoSelectHongKongRegion) {
            autoSelectHongKongRegion(regionEl, sourceText);
            return;
        }
        var text = String(sourceText).toLowerCase();
        var key = /hong kong island|港島|港岛|香港岛/.test(text) ? 'island' : (/kowloon|九龍|九龙/.test(text) ? 'kowloon' : (/new territories|新界/.test(text) ? 'territories' : ''));
        if (!key) return;
        Array.prototype.slice.call(regionEl.options || []).forEach(function(opt) {
            if (!opt || !opt.value) return;
            var value = String(opt.value).toLowerCase();
            if (key === 'island' && /island|香港|港島|港岛/.test(value)) regionEl.value = opt.value;
            if (key === 'kowloon' && /kowloon|九龍|九龙/.test(value)) regionEl.value = opt.value;
            if (key === 'territories' && /territories|新界/.test(value)) regionEl.value = opt.value;
        });
    }

    function flashMapUpdatedFields() {
        getAddressInputEls().forEach(function(el) {
            el.classList.remove('checkout-map-updated');
            // Force reflow to allow repeated animation when user clicks map multiple times.
            void el.offsetWidth;
            el.classList.add('checkout-map-updated');
        });
    }

    function beginMapAutofill() {
        mapAutofillInProgress = true;
    }

    function endMapAutofill() {
        mapAutofillInProgress = false;
        flashMapUpdatedFields();
        hasMapSelection = true;
        addressEditedAfterMapPick = false;
        updateMapVisualState(false);
    }

    function applyAutofillFieldValue(el, value) {
        if (mapUiHelpers) {
            mapUiHelpers.applyAutofillFieldValue(el, value);
            return;
        }
        if (!el) return;
        var normalizedValue = String(value || '');
        el.value = normalizedValue;
        el.dataset.lastAutofill = normalizedValue;
    }

    function updateAddressFieldsFromNominatim(data) {
        if (!data || !data.address) return;
        var addr = data.address || {};
        var district = addr.city_district || addr.suburb || addr.borough || addr.town || addr.city || addr.county || addr.state_district || '';
        var road = addr.road || addr.pedestrian || addr.residential || addr.footway || '';
        var houseNumber = addr.house_number || '';
        var street = [road, houseNumber].filter(function(v) {
            return String(v || '').trim() !== '';
        }).join(' ');
        var building = addr.building || addr.commercial || addr.amenity || addr.shop || '';
        var unit = '';

        var districtEl = document.getElementById('district');
        var streetEl = document.getElementById('street');
        var buildingEl = document.getElementById('building');
        var unitEl = document.getElementById('unit');
        var villageEl = document.getElementById('villageEstate');

        beginMapAutofill();
        autoSelectRegionByText([
            data.display_name || '',
            addr.city || '',
            addr.county || '',
            addr.state || '',
            district
        ].join(' '));
        applyAutofillFieldValue(districtEl, district);
        applyAutofillFieldValue(streetEl, street);
        applyAutofillFieldValue(buildingEl, building);
        applyAutofillFieldValue(unitEl, unit);
        applyAutofillFieldValue(villageEl, addr.neighbourhood || addr.quarter || '');
        endMapAutofill();
    }

    function updateAddressFieldsFromMapTiler(data) {
        if (!data || !Array.isArray(data.features) || data.features.length === 0) return false;
        var f = data.features[0] || {};
        var p = f.properties || {};
        var district = p.district || p.suburb || p.city || p.county || p.municipality || '';
        var street = [p.street || '', p.housenumber || ''].filter(function(v) {
            return String(v || '').trim() !== '';
        }).join(' ');
        var building = p.name || p.poi || '';
        var unit = '';
        var village = p.neighbourhood || '';

        var districtEl = document.getElementById('district');
        var streetEl = document.getElementById('street');
        var buildingEl = document.getElementById('building');
        var unitEl = document.getElementById('unit');
        var villageEl = document.getElementById('villageEstate');

        beginMapAutofill();
        autoSelectRegionByText([
            f.place_name_zh_hant || '',
            f.place_name || '',
            p.city || '',
            p.county || '',
            p.state || '',
            district
        ].join(' '));
        applyAutofillFieldValue(districtEl, district);
        applyAutofillFieldValue(streetEl, street);
        applyAutofillFieldValue(buildingEl, building);
        applyAutofillFieldValue(unitEl, unit);
        applyAutofillFieldValue(villageEl, village);
        endMapAutofill();
        return true;
    }

    function reverseGeocode(lat, lng) {
        var token = ++reverseGeocodeToken;
        setMapStatus(CI.mapReverseGeocoding || '');
        if (reverseGeocodeWithFallback) {
            reverseGeocodeWithFallback({
                lat: lat,
                lng: lng,
                token: token,
                isTokenCurrent: function(currentToken) { return currentToken === reverseGeocodeToken; },
                maptilerReverseGeocodeUrl: maptilerReverseGeocodeUrl,
                nominatimReverseUrl: nominatimReverseUrl,
                maptilerApiKey: String(window.MAPTILER_API_KEY || '').trim(),
                maptilerLanguage: 'zh-Hant',
                nominatimLanguage: 'zh-HK',
                onMapTilerData: function(data) {
                    var updated = updateAddressFieldsFromMapTiler(data);
                    if (!updated) {
                        return false;
                    }
                    var feature = data.features && data.features[0] ? data.features[0] : {};
                    var displayName = feature.place_name_zh_hant || feature.place_name || feature.text || '';
                    setMapStatus(displayName ? (CI.mapResolvedAddress || '') + ': ' + shortenStatusAddress(displayName, 24) : (CI.mapResolvedAddress || ''), 'success');
                    return true;
                },
                onNominatimData: function(data) {
                    updateAddressFieldsFromNominatim(data);
                    if (data && data.display_name) {
                        setMapStatus((CI.mapResolvedAddress || '') + ': ' + shortenStatusAddress(data.display_name, 24), 'success');
                    } else {
                        setMapStatus(CI.mapResolvedAddress || '', 'success');
                    }
                }
            }).catch(function() {
                if (token !== reverseGeocodeToken) return;
                setMapStatus(CI.mapResolveFailed || '', 'danger');
            });
            return;
        }
        setMapStatus(CI.mapResolveFailed || '', 'danger');
    }

    function handleMapPick(lat, lng) {
        var normalizedLat = normalizeCoordinate(lat);
        var normalizedLng = normalizeCoordinate(lng);
        if (!Number.isFinite(normalizedLat) || !Number.isFinite(normalizedLng)) return;
        if (!mapInstance || !window.L) return;
        if (!mapMarker) {
            mapMarker = window.L.marker([normalizedLat, normalizedLng]).addTo(mapInstance);
        } else {
            mapMarker.setLatLng([normalizedLat, normalizedLng]);
        }
        mapInstance.setView([normalizedLat, normalizedLng], Math.max(mapInstance.getZoom(), 16));
        updateMapLatLng(normalizedLat, normalizedLng);
        reverseGeocode(normalizedLat, normalizedLng);
    }

    function initLeafletAddressMapInternal(maptilerKey) {
        ensureLeafletLoaded().then(function(L) {
            if (!mapInstance) {
                var hkCenter = [22.3193, 114.1694];
                mapInstance = L.map('checkoutAddressMap', { minZoom: 10, maxZoom: 20 }).setView(hkCenter, 12);
                mapInstance.on('click', function(ev) {
                    handleMapPick(ev.latlng.lat, ev.latlng.lng);
                });
                if (setupSharedLeafletBasemap) {
                    setupSharedLeafletBasemap({
                        map: mapInstance,
                        L: L,
                        maptilerKey: maptilerKey,
                        loadMapTilerStack: loadMapTilerStack,
                        initGeocodingControl: function(key) { initGeocodingControl(L, key); },
                        setMapStatus: setMapStatus,
                        mapMaptilerFallbackText: CI.mapMaptilerFallback || '',
                        onMapTilerFallback: function(err) { console.error('MapTiler load failed:', err); }
                    });
                } else if (maptilerKey) {
                    loadMapTilerStack().then(function() {
                        if (L.maptiler && L.maptiler.maptilerLayer) {
                            var opts = { apiKey: maptilerKey };
                            if (L.maptiler.MapStyle && L.maptiler.MapStyle.STREETS !== undefined) {
                                opts.style = L.maptiler.MapStyle.STREETS;
                            }
                            L.maptiler.maptilerLayer(opts).addTo(mapInstance);
                        }
                        initGeocodingControl(L, maptilerKey);
                    }).catch(function(err) {
                        console.error('MapTiler load failed:', err);
                        setMapStatus(CI.mapMaptilerFallback || '', 'warning');
                        initGeocodingControl(L, maptilerKey);
                    });
                }
            }

            if (mapInstance) {
                mapInstance.invalidateSize();
            }
            setTimeout(function() {
                if (mapInstance) {
                    mapInstance.invalidateSize();
                }
            }, 200);
            setMapStatus(CI.mapHelp || '', 'default');
        }).catch(function(err) {
            console.error('Leaflet load failed:', err);
            setMapStatus(CI.mapResolveFailed || '', 'danger');
        });
    }

    function initAddressMap() {
        var mapEl = document.getElementById('checkoutAddressMap');
        if (!mapEl) return;
        initLeafletAddressMapInternal(String(window.MAPTILER_API_KEY || '').trim());
    }

    function showSystemLinking(show) {
        const loader = document.getElementById('paymentSystemLoader');
        if (!loader) return;
        loader.style.display = show ? 'block' : 'none';
    }

    // Format address object as one line (same semantics as backend)
    function formatAddressOneLine(addr) {
        if (!addr) return '';
        var floorSuffix = CI.floorSuffix || '';
        var parts = [
            addr.region || '',
            addr.district || '',
            addr.street || '',
            addr.village_estate || '',
            addr.building || '',
            (addr.floor && String(addr.floor).trim() !== '' ? String(addr.floor).trim() + floorSuffix : ''),
            addr.unit || ''
        ].filter(function(v) { return String(v).trim() !== ''; });
        return parts.join(' ');
    }

    // Selected shipping address as one line
    function getSelectedShippingAddress() {
        var radio = document.querySelector('input[name="checkout_address"]:checked');
        if (radio && radio.getAttribute('data-address-one-line')) {
            return radio.getAttribute('data-address-one-line').trim();
        }
        return String(window.DEFAULT_SHIPPING_ADDRESS || CI.defaultShipping || '').trim();
    }

    function getSelectedShippingCoordinates() {
        var radio = document.querySelector('input[name="checkout_address"]:checked');
        if (!radio) return null;
        var lat = Number(radio.getAttribute('data-lat'));
        var lng = Number(radio.getAttribute('data-lng'));
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
        return {
            lat: lat.toFixed(6),
            lng: lng.toFixed(6)
        };
    }

    /**
     * @param {string|number} [selectAfterId] Address id to select after reload (e.g. newly created)
     */
    function loadAddresses(selectAfterId) {
        var loadingEl = document.getElementById('addressLoading');
        var containerEl = document.getElementById('addressCardsContainer');
        var noMsgEl = document.getElementById('noAddressMessage');
        if (!loadingEl || !containerEl || !noMsgEl) return;
        fetch(baseUrl + 'api/address/list')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var list = (data && data.addresses) ? data.addresses : [];
                loadingEl.style.display = 'none';
                if (list.length === 0) {
                    noMsgEl.style.display = 'block';
                    containerEl.style.display = 'none';
                    return;
                }
                noMsgEl.style.display = 'none';
                containerEl.style.display = 'flex';
                containerEl.innerHTML = '';
                var targetId = selectAfterId;
                if (targetId == null || targetId === '') {
                    var i;
                    for (i = 0; i < list.length; i++) {
                        if (list[i].is_default === 1 || list[i].is_default === true) {
                            targetId = list[i].id;
                            break;
                        }
                    }
                    if (targetId == null || targetId === '') {
                        targetId = list[0] ? list[0].id : '';
                    }
                }
                list.forEach(function(addr, index) {
                    var oneLine = formatAddressOneLine(addr);
                    var id = 'addr-' + (addr.id || index);
                    var isDefault = addr.is_default === 1 || addr.is_default === true;
                    var shouldCheck = String(addr.id) === String(targetId);
                    var card = document.createElement('div');
                    card.className = 'col-12';
                    card.innerHTML = '<div class="form-check border rounded p-3 checkout-address-card">' +
                        '<input class="form-check-input" type="radio" name="checkout_address" id="' + id + '" value="' + (addr.id || '') + '" data-address-one-line="' + (oneLine.replace(/"/g, '&quot;')) + '"' +
                        (addr.lat != null ? ' data-lat="' + String(addr.lat).replace(/"/g, '&quot;') + '"' : '') +
                        (addr.lng != null ? ' data-lng="' + String(addr.lng).replace(/"/g, '&quot;') + '"' : '') +
                        (shouldCheck ? ' checked' : '') + '>' +
                        '<label class="form-check-label w-100 ms-2" for="' + id + '">' +
                        (isDefault ? '<span class="badge bg-danger me-2">' + String(CI.badgeDefault || '').replace(/</g, '&lt;') + '</span>' : '') +
                        (addr.address_label ? '<strong>' + String(addr.address_label).replace(/</g, '&lt;') + '</strong><br>' : '') +
                        '<span class="text-muted small">' + String(addr.recipient_name || '').replace(/</g, '&lt;') + '　' + String(addr.phone || '').replace(/</g, '&lt;') + '</span><br>' +
                        '<span class="small">' + String(oneLine).replace(/</g, '&lt;') + '</span>' +
                        '</label></div>';
                    containerEl.appendChild(card);
                });
                if (lalamoveEnabled && !checkoutBlocked) {
                    scheduleLalamoveQuote();
                }
            })
            .catch(function() {
                loadingEl.style.display = 'none';
                noMsgEl.style.display = 'block';
                containerEl.style.display = 'none';
            });
    }

    window.openAddAddressModal = function() {
        var modal = document.getElementById('addressModal');
        var modalLabel = document.getElementById('addressModalLabel');
        var form = document.getElementById('addressForm');
        if (!modal || !form) return;
        if (modalLabel) modalLabel.textContent = CI.modalAddAddress || '';
        form.reset();
        var aid = document.getElementById('addressId');
        if (aid) aid.value = '';
        var isDef = document.getElementById('isDefault');
        if (isDef) isDef.checked = false;
        form.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
        updateMapLatLng('', '');
        hasMapSelection = false;
        addressEditedAfterMapPick = false;
        reverseGeocodeToken += 1;
        updateMapVisualState(false);
        getAddressInputEls().forEach(function(el) {
            delete el.dataset.lastAutofill;
        });
        setMapStatus(CI.mapHelp || '', 'default');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    };

    window.saveCheckoutAddress = async function() {
        var form = document.getElementById('addressForm');
        if (!form) return;
        var formData = new FormData(form);
        var addressId = formData.get('id');
        var data = Object.fromEntries(formData.entries());
        var hasAddressText = !!(
            String(data.region || '').trim() ||
            String(data.district || '').trim() ||
            String(data.street || '').trim() ||
            String(data.village_estate || '').trim() ||
            String(data.building || '').trim()
        );
        var hasLatLng = String(data.lat || '').trim() !== '' && String(data.lng || '').trim() !== '';
        if (!data.recipient_name || !data.phone || !data.region || !data.district || !data.building) {
            alert(CI.alertRequired || '');
            return;
        }
        if (!data.village_estate && !data.street) {
            alert(CI.alertVillageOrStreet || '');
            return;
        }
        if (hasAddressText && !hasLatLng) {
            alert(CI.mapRequirePinForQuote || CI.mapHelp || '');
            setMapStatus(CI.mapHelp || '', 'warning');
            return;
        }
        if (hasMapSelection && addressEditedAfterMapPick) {
            var shouldContinue = confirm(CI.mapAddressChangedConfirm || '');
            if (!shouldContinue) {
                return;
            }
        }
        data.is_default = document.getElementById('isDefault') && document.getElementById('isDefault').checked ? 1 : 0;
        var payload = addressId ? Object.assign({}, data, { id: addressId }) : data;
        if (!addressId) {
            delete payload.id;
        }
        var path = addressId ? 'api/address/update' : 'api/address/create';
        try {
            var response = await fetch(baseUrl + path, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            var result = await response.json();
            if (!result.success) {
                throw new Error(result.message || result.error || CI.errSaveGeneric || '');
            }
            var modalEl = document.getElementById('addressModal');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var inst = bootstrap.Modal.getInstance(modalEl);
                if (inst) inst.hide();
            }
            var newId = result.address_id || addressId;
            loadAddresses(newId);
            if (lalamoveEnabled && !checkoutBlocked && payload.lat && payload.lng) {
                setTimeout(function() {
                    scheduleLalamoveQuote();
                }, 600);
            }
        } catch (err) {
            console.error('saveCheckoutAddress:', err);
            var reason = err && err.message ? String(err.message).trim() : '';
            if (reason) {
                alert((CI.alertSaveAddressFailedWithReason || CI.alertSaveAddressFailed || '') + reason);
            } else {
                alert(CI.alertSaveAddressFailed || '');
            }
        }
    };

    function getShippingMethod() {
        var el = document.getElementById('shipping');
        if (el && el.value) {
            return el.value;
        }
        return checkoutBlocked ? 'unavailable' : 'lalamove';
    }

    function setLalamoveNote(text, show) {
        var note = document.getElementById('lalamoveQuoteNote');
        if (!note) return;
        note.style.display = show ? 'block' : 'none';
        note.textContent = show ? (text || '') : '';
    }

    function scheduleLalamoveQuote() {
        if (!lalamoveEnabled || checkoutBlocked) return;
        if (!cartItems || cartItems.length === 0) {
            lalamoveFee = null;
            checkoutSnapshotToken = '';
            lalamoveQuoteLoading = false;
            setLalamoveNote('', false);
            updateSummary();
            return;
        }
        if (lalamoveQuoteTimer) clearTimeout(lalamoveQuoteTimer);
        lalamoveQuoteTimer = setTimeout(function() {
            requestLalamoveQuote();
        }, 550);
    }

    function requestLalamoveQuote() {
        if (!lalamoveEnabled || checkoutBlocked) return;
        var line = getSelectedShippingAddress();
        var coordinates = getSelectedShippingCoordinates();
        if (!line || String(line).trim().length < 8) {
            lalamoveFee = null;
            checkoutSnapshotToken = '';
            lalamoveQuoteLoading = false;
            setLalamoveNote('', false);
            updateSummary();
            return;
        }
        var token = ++lalamoveQuoteToken;
        lalamoveQuoteLoading = true;
        updateSummary();

        function submitQuote(useCoordinates, hasRetriedFallback) {
            fetch(baseUrl + 'api/shipping/lalamove-quote', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    address_line: String(line).trim(),
                    coordinates: useCoordinates ? coordinates : null
                })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (token !== lalamoveQuoteToken) return;
                if (data.success && data.shipping_fee != null) {
                    lalamoveQuoteLoading = false;
                    var f = parseFloat(data.shipping_fee);
                    lalamoveFee = Number.isFinite(f) ? f : null;
                    checkoutSnapshotToken = (data.checkout_token && String(data.checkout_token).trim())
                        ? String(data.checkout_token).trim()
                        : '';
                    setLalamoveNote(CI.lalamoveDisclaimer || '', true);
                    updateSummary();
                    return;
                }

                if (useCoordinates && coordinates && !hasRetriedFallback) {
                    submitQuote(false, true);
                    return;
                }

                lalamoveQuoteLoading = false;
                lalamoveFee = null;
                checkoutSnapshotToken = '';
                var errPart = data.message || CI.lalamoveError || '';
                var disc = CI.lalamoveDisclaimer || '';
                setLalamoveNote(errPart ? (errPart + (disc ? ' ' + disc : '')) : disc, true);
                updateSummary();
            }).catch(function() {
                if (token !== lalamoveQuoteToken) return;
                if (useCoordinates && coordinates && !hasRetriedFallback) {
                    submitQuote(false, true);
                    return;
                }
                lalamoveQuoteLoading = false;
                lalamoveFee = null;
                checkoutSnapshotToken = '';
                setLalamoveNote(CI.lalamoveError || '', true);
                updateSummary();
            });
        }

        submitQuote(!!coordinates, false);
    }

    function isUseWalletEnabled() {
        var walletEl = document.getElementById('useWalletBalance');
        return !!(walletEl && walletEl.checked);
    }

    function calculateTotals() {
        let subtotal = 0;
        const qtyKey = function(item) { return parseInt(item.qty ?? item.quantity, 10) || 1; };
        if (cartItems && cartItems.length) {
            cartItems.forEach(function(item) {
                subtotal += (parseFloat(item.price) || 0) * qtyKey(item);
            });
        }
        let shippingFee = 0;
        if (lalamoveEnabled && !checkoutBlocked) {
            if (lalamoveFee !== null && Number.isFinite(lalamoveFee)) {
                shippingFee = lalamoveFee;
            }
        }
        return { subtotal, shippingFee, total: subtotal + shippingFee };
    }

    function updateSummary() {
        const t = calculateTotals();
        const subEl = document.getElementById('subtotal');
        const feeEl = document.getElementById('shippingFee');
        const orderTotalEl = document.getElementById('orderTotal');
        const walletBalanceEl = document.getElementById('walletBalance');
        const walletUsedEl = document.getElementById('walletUsed');
        const pointsBalanceEl = document.getElementById('pointsBalance');
        const pointsUsedEl = document.getElementById('pointsUsed');
        const totalEl = document.getElementById('final-total');

        const useWallet = isUseWalletEnabled();
        const usePointsEl = document.getElementById('usePoints');
        const usePoints = !!(usePointsEl && usePointsEl.checked);

        const walletUsed = useWallet ? Math.max(0, Math.min(walletBalance, t.total)) : 0;
        const totalAfterWallet = Math.max(0, t.total - walletUsed);

        const maxPointsCanUse = Math.floor(totalAfterWallet * POINTS_PER_HKD);
        const pointsToUse = usePoints ? Math.max(0, Math.min(pointsBalance, maxPointsCanUse)) : 0;
        const pointsHkdUsed = pointsToUse / POINTS_PER_HKD;

        const payable = Math.max(0, totalAfterWallet - pointsHkdUsed);

        if (subEl) subEl.textContent = formatMoney(t.subtotal);
        if (feeEl) {
            if (!lalamoveEnabled || checkoutBlocked) {
                feeEl.textContent = '—';
            } else if (lalamoveQuoteLoading) {
                feeEl.textContent = CI.lalamovePending || '…';
            } else if (lalamoveFee !== null && Number.isFinite(lalamoveFee)) {
                feeEl.textContent = formatMoney(lalamoveFee);
            } else {
                feeEl.textContent = CI.lalamoveError || '—';
            }
        }
        if (orderTotalEl) orderTotalEl.textContent = formatMoney(t.total);
        if (walletBalanceEl) walletBalanceEl.textContent = formatMoney(walletBalance);
        if (walletUsedEl) walletUsedEl.textContent = '-' + formatMoney(walletUsed);
        if (pointsBalanceEl) pointsBalanceEl.textContent = String(pointsBalance);
        if (pointsUsedEl) pointsUsedEl.textContent = '-' + formatMoney(pointsHkdUsed);
        if (totalEl) totalEl.textContent = formatMoney(payable);

        if (typeof updateCartBadge === 'function') updateCartBadge();
        updateZeroPayUi();
    }

    function getPayableInfo() {
        var t = calculateTotals();
        var walletUsed = isUseWalletEnabled() ? Math.max(0, Math.min(walletBalance, t.total)) : 0;
        var totalAfterWallet = Math.max(0, t.total - walletUsed);
        var usePointsEl = document.getElementById('usePoints');
        var usePoints = !!(usePointsEl && usePointsEl.checked);
        var maxPointsCanUse = Math.floor(totalAfterWallet * POINTS_PER_HKD);
        var pointsUsed = usePoints ? Math.max(0, Math.min(pointsBalance, maxPointsCanUse)) : 0;
        var pointsHkdUsed = pointsUsed / POINTS_PER_HKD;
        var payable = Math.max(0, totalAfterWallet - pointsHkdUsed);
        return { total: t.total, walletUsed: walletUsed, pointsUsed: pointsUsed, pointsHkdUsed: pointsHkdUsed, payable: payable };
    }

    function isWalletOnlyCheckout() {
        var p = getPayableInfo();
        if (p.total <= 0) return false;
        if (!isUseWalletEnabled()) return false;
        if (p.payable > 0.00001) return false;
        return walletBalance + 0.00001 >= p.total;
    }

    function updateZeroPayUi() {
        var only = isWalletOnlyCheckout();
        var stripeForm = document.getElementById('stripe-payment-form');
        var paypalRadio = document.querySelector('input[name="paymentMethod"][value="paypal"]');
        var stripeRadio = document.querySelector('input[name="paymentMethod"][value="stripe"]');
        var hintEl = document.getElementById('walletZeroCheckoutHint');
        if (only) {
            if (stripeForm) stripeForm.style.display = 'none';
            if (paypalRadio) {
                paypalRadio.disabled = true;
                if (paypalRadio.checked && stripeRadio) stripeRadio.checked = true;
            }
            if (hintEl) hintEl.style.display = 'block';
        } else {
            if (paypalRadio) paypalRadio.disabled = false;
            if (hintEl) hintEl.style.display = 'none';
            togglePaymentUI();
        }
    }

    function renderOrderItems() {
        var container = document.getElementById('orderItems');
        if (!container) return;
        if (!cartItems || cartItems.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3">' + String(CI.emptyCart || '').replace(/</g, '&lt;') + '</p>';
            return;
        }
        var imgBase = baseUrl.replace(/\/$/, '') + '/';
        var productBase = baseUrl + 'product/';
        var html = '';
        cartItems.forEach(function(item) {
            var cartId = item.cart_item_id || item.id;
            var productId = item.id || '';
            var qty = parseInt(item.qty || item.quantity, 10) || 1;
            var price = parseFloat(item.price) || 0;
            var subtotal = (price * qty).toFixed(2);
            var imgPath = item.image_path ? (imgBase + 'images/' + (item.image_path || '').replace(/^\//, '')) : (imgBase + 'images/placeholder.jpg');
            var name = (item.name || '').replace(/</g, '&lt;');
            var detailUrl = productBase + productId;
            html += '<div class="d-flex align-items-center py-3 border-bottom checkout-order-row" data-cart-id="' + cartId + '">';
            html += '<a href="' + detailUrl + '" class="d-flex align-items-center text-decoration-none text-dark flex-grow-1 me-2">';
            html += '<img src="' + imgPath + '" alt="" class="rounded me-3 checkout-item-img">';
            html += '<div class="flex-grow-1"><div class="fw-bold">' + name + '</div><small class="text-muted">' + formatMoney(price) + String(CI.perUnit || '').replace(/"/g, '&quot;') + '</small></div>';
            html += '</a>';
            html += '<div class="d-flex align-items-center quantity-control me-2">';
            html += '<button type="button" class="btn btn-sm btn-outline-secondary checkout-qty-minus" data-cart-id="' + cartId + '" aria-label="' + String(CI.ariaQtyMinus || '').replace(/"/g, '&quot;') + '">−</button>';
            html += '<span class="checkout-qty-display mx-2 fw-bold" data-cart-id="' + cartId + '">' + qty + '</span>';
            html += '<button type="button" class="btn btn-sm btn-outline-secondary checkout-qty-plus" data-cart-id="' + cartId + '" aria-label="' + String(CI.ariaQtyPlus || '').replace(/"/g, '&quot;') + '">+</button>';
            html += '</div>';
            html += '<span class="fw-bold text-primary me-2 checkout-item-subtotal">' + formatMoney(subtotal) + '</span>';
            html += '<button type="button" class="btn btn-sm btn-link text-danger p-0 border-0 checkout-item-remove" data-cart-id="' + cartId + '" title="' + String(CI.removeTitle || '').replace(/"/g, '&quot;') + '">×</button>';
            html += '</div>';
        });
        container.innerHTML = html;
    }

    function bindCheckoutItemEvents() {
        var container = document.getElementById('orderItems');
        if (!container) return;

        container.addEventListener('click', function(e) {
            var target = e.target.closest('.checkout-qty-minus');
            if (target) {
                e.preventDefault();
                var cartId = target.getAttribute('data-cart-id');
                var row = target.closest('.checkout-order-row');
                var span = row ? row.querySelector('.checkout-qty-display') : null;
                var qty = span ? Math.max(1, (parseInt(span.textContent, 10) || 1) - 1) : 1;
                updateCheckoutQty(cartId, qty);
                return;
            }
            target = e.target.closest('.checkout-qty-plus');
            if (target) {
                e.preventDefault();
                var cartId = target.getAttribute('data-cart-id');
                var row = target.closest('.checkout-order-row');
                var span = row ? row.querySelector('.checkout-qty-display') : null;
                var qty = span ? (parseInt(span.textContent, 10) || 1) + 1 : 1;
                updateCheckoutQty(cartId, qty);
                return;
            }
            target = e.target.closest('.checkout-item-remove');
            if (target) {
                e.preventDefault();
                var cartId = target.getAttribute('data-cart-id');
                if (confirm(CI.confirmRemoveLine || '')) removeCheckoutItem(cartId);
                return;
            }
        });
    }

    function updateCheckoutQty(cartItemId, qty) {
        var fd = new FormData();
        fd.append('cart_item_id', cartItemId);
        fd.append('quantity', qty);
        fetch(baseUrl + 'api/cart/update', {
            method: 'POST',
            body: fd
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) loadCart();
            else if (data.message) alert(data.message);
        }).catch(function() { alert(CI.alertUpdateQtyFailed || ''); });
    }

    function removeCheckoutItem(cartItemId) {
        var fd = new FormData();
        fd.append('cart_item_id', cartItemId);
        fetch(baseUrl + 'api/cart/remove', {
            method: 'POST',
            body: fd
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) loadCart();
            else if (data.message) alert(data.message);
        }).catch(function() { alert(CI.alertRemoveFailed || ''); });
    }

    function loadCart() {
        fetch(baseUrl + 'api/cart/items')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                cartItems = (data && data.items) ? data.items : [];
                updateSummary();
                renderOrderItems();
                if (lalamoveEnabled && !checkoutBlocked) {
                    scheduleLalamoveQuote();
                }
            })
            .catch(function() { cartItems = []; updateSummary(); renderOrderItems(); });
    }

    function initStripe() {
        if (!stripePublishableKey || typeof Stripe === 'undefined') return false;
        if (stripe && cardElement) return true;
        try {
            stripe = PaymentLoader.stripe || Stripe(stripePublishableKey);
            var elements = stripe.elements();
            cardElement = elements.create('card', {
                style: { base: { fontSize: '16px' }, invalid: { color: '#9e2146' } }
            });
            var container = document.getElementById('stripe-card-element');
            if (container) {
                cardElement.mount('#stripe-card-element');
                cardElement.on('change', function(ev) {
                    var errEl = document.getElementById('stripe-card-errors');
                    if (errEl) errEl.textContent = ev.error ? ev.error.message : '';
                });
            }
            return true;
        } catch (e) {
            console.error('Stripe init:', e);
            return false;
        }
    }

    function ensureStripeReady() {
        if (!stripePublishableKey) {
            return Promise.reject(new Error('stripe_not_configured'));
        }
        if (stripe && cardElement) {
            return Promise.resolve(true);
        }
        showSystemLinking(true);
        return PaymentLoader.loadStripe().then(function(stripeInstance) {
            stripe = stripeInstance;
            if (!initStripe()) {
                throw new Error('stripe_init_failed');
            }
            return true;
        }).finally(function() {
            showSystemLinking(false);
        });
    }

    function createPaymentIntent() {
        var coords = getSelectedShippingCoordinates();
        var intentParams = new URLSearchParams({
            shipping_method: getShippingMethod(),
            shipping_address: getSelectedShippingAddress(),
            shipping_lat: coords ? coords.lat : '',
            shipping_lng: coords ? coords.lng : '',
            use_wallet: isUseWalletEnabled() ? '1' : '0',
            use_points: (document.getElementById('usePoints') && document.getElementById('usePoints').checked) ? '1' : '0'
        });
        if (checkoutSnapshotToken) {
            intentParams.set('checkout_token', checkoutSnapshotToken);
        }
        return fetch(baseUrl + 'api/payment/create-intent', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: intentParams
        }).then(function(r) { return r.json(); });
    }

    function confirmOrder(payload) {
        var coords = getSelectedShippingCoordinates();
        payload.shipping_address = payload.shipping_address || getSelectedShippingAddress();
        payload.shipping_method = getShippingMethod();
        payload.shipping_lat = coords ? coords.lat : '';
        payload.shipping_lng = coords ? coords.lng : '';
        payload.use_wallet = isUseWalletEnabled() ? '1' : '0';
        payload.use_points = (document.getElementById('usePoints') && document.getElementById('usePoints').checked) ? '1' : '0';
        if (checkoutSnapshotToken) {
            payload.checkout_token = checkoutSnapshotToken;
        }
        return fetch(baseUrl + 'api/payment/confirm', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(payload)
        }).then(function(r) { return r.json(); });
    }

    function setConfirmLoading(loading) {
        var btn = document.getElementById('confirmOrderBtn');
        if (btn) {
            btn.disabled = loading;
            btn.textContent = loading ? (CI.processing || '') : (CI.confirmOrder || '');
        }
    }

    function showError(msg) {
        var errEl = document.getElementById('stripe-card-errors');
        if (errEl) errEl.textContent = msg;
        else alert(msg);
    }

    function togglePaymentUI() {
        var method = document.querySelector('input[name="paymentMethod"]:checked');
        var value = method ? method.value : 'stripe';
        var stripeForm = document.getElementById('stripe-payment-form');
        var paypalContainer = document.getElementById('paypal-button-container');
        var confirmOrderBtn = document.getElementById('confirmOrderBtn');
        if (stripeForm) stripeForm.style.display = value === 'stripe' ? 'block' : 'none';
        if (paypalContainer) paypalContainer.style.display = value === 'paypal' ? 'block' : 'none';
        if (confirmOrderBtn) confirmOrderBtn.style.display = value === 'paypal' ? 'none' : 'block';
        if (value === 'stripe') {
            ensureStripeReady().catch(function(err) {
                console.error('Stripe SDK load:', err);
                showError(CI.errStripeModule || '');
            });
        }
        if (value === 'paypal' && paypalContainer && !paypalButtons && paypalClientId) {
            showSystemLinking(true);
            PaymentLoader.loadPayPal().then(function() {
                initPayPal();
            }).catch(function(err) {
                console.error('PayPal SDK load:', err);
                alert(CI.alertPaypalLoadFailed || '');
            }).finally(function() {
                showSystemLinking(false);
            });
        }
    }

    function handleStripeConfirm() {
        if (!stripe || !cardElement) {
            showError(CI.errSelectCard || '');
            return;
        }
        if (!paymentIntentClientSecret) {
            showError(CI.errNeedPaymentAuth || '');
            return;
        }
        setConfirmLoading(true);
        showError('');
        stripe.confirmCardPayment(paymentIntentClientSecret, {
            payment_method: { card: cardElement, billing_details: { address: { country: 'HK' } } }
        }).then(function(result) {
            if (result.error) {
                setConfirmLoading(false);
                showError(result.error.message || CI.errPaymentFailed || '');
                return;
            }
            if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
                var payload = {
                    payment_intent_id: result.paymentIntent.id,
                    payment_method: 'credit_card'
                };
                if (orderNumberForConfirm) payload.order_number = orderNumberForConfirm;
                confirmOrder(payload).then(function(data) {
                    setConfirmLoading(false);
                    if (data.success) {
                        alert((CI.orderConfirmedPrefix || '') + (data.order_number || data.order_id));
                        window.location.href = baseUrl + 'account/orders';
                    } else {
                        showError(data.message || CI.errOrderConfirm || '');
                    }
                }).catch(function() {
                    setConfirmLoading(false);
                    showError(CI.errOrderConfirmRetry || '');
                });
            } else {
                setConfirmLoading(false);
                showError(CI.errPaymentIncomplete || '');
            }
        }).catch(function(err) {
            setConfirmLoading(false);
            showError(err.message || CI.errPaymentFailed || '');
        });
    }

    function handleWalletOnlyCheckout() {
        if (checkoutBlocked) {
            alert(CI.deliveryUnavailable || '');
            return;
        }
        setConfirmLoading(true);
        showError('');
        var fd = new URLSearchParams();
        var coords = getSelectedShippingCoordinates();
        fd.append('shipping_method', getShippingMethod());
        fd.append('shipping_address', getSelectedShippingAddress());
        fd.append('shipping_lat', coords ? coords.lat : '');
        fd.append('shipping_lng', coords ? coords.lng : '');
        fd.append('use_wallet', '1');
        if (checkoutSnapshotToken) {
            fd.append('checkout_token', checkoutSnapshotToken);
        }
        fetch(baseUrl + 'api/payment/wallet-checkout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: fd
        }).then(function(r) { return r.json(); }).then(function(data) {
            setConfirmLoading(false);
            if (data.success) {
                alert((CI.orderConfirmedPrefix || '') + (data.order_number || data.order_id));
                window.location.href = baseUrl + 'account/orders';
            } else {
                showError(data.message || CI.errCheckout || '');
            }
        }).catch(function() {
            setConfirmLoading(false);
            showError(CI.errCheckoutRetry || '');
        });
    }

    function handleConfirmClick() {
        if (checkoutBlocked) {
            alert(CI.deliveryUnavailable || '');
            return;
        }
        var method = document.querySelector('input[name="paymentMethod"]:checked');
        var value = method ? method.value : 'stripe';
        if (value === 'paypal') return;

        if (lalamoveEnabled) {
            var addrLine = getSelectedShippingAddress();
            if (addrLine && String(addrLine).trim().length >= 8) {
                if (lalamoveQuoteLoading || lalamoveFee === null || !Number.isFinite(lalamoveFee)) {
                    alert(CI.lalamoveWaiting || CI.lalamovePending || '');
                    return;
                }
            }
        }

        if (!cartItems.length) {
            loadCart();
            setTimeout(function() {
                if (!cartItems.length) alert(CI.alertCartEmpty || '');
            }, 500);
            return;
        }

        if (isWalletOnlyCheckout()) {
            handleWalletOnlyCheckout();
            return;
        }

        showError('');
        setConfirmLoading(true);
        ensureStripeReady().then(function() {
            return createPaymentIntent();
        }).then(function(data) {
            if (!data.success) {
                setConfirmLoading(false);
                showError(data.message || CI.errCreatePayment || '');
                return;
            }
            paymentIntentClientSecret = data.client_secret;
            paymentIntentId = data.payment_intent_id;
            orderNumberForConfirm = data.order_number || null;
            handleStripeConfirm();
        }).catch(function(err) {
            setConfirmLoading(false);
            if (err && err.message === 'stripe_not_configured') {
                showError(CI.errStripeNotConfigured || '');
                return;
            }
            showError(CI.errCreatePaymentRetry || '');
        });
    }

    function initPayPal() {
        if (checkoutBlocked) {
            return;
        }
        if (!paypalClientId || typeof paypal === 'undefined') {
            console.warn('PayPal SDK not available');
            return;
        }
        var container = document.getElementById('paypal-button-container');
        if (!container) return;
        if (paypalButtons) return;
        container.style.display = 'block';
        try {
            paypalButtons = paypal.Buttons({
                style: { layout: 'vertical', color: 'blue', shape: 'rect', label: 'paypal' },
                createOrder: function(data, actions) {
                    var coords = getSelectedShippingCoordinates();
                    var ppParams = new URLSearchParams({
                        shipping_method: getShippingMethod(),
                        shipping_address: getSelectedShippingAddress(),
                        shipping_lat: coords ? coords.lat : '',
                        shipping_lng: coords ? coords.lng : '',
                        use_wallet: isUseWalletEnabled() ? '1' : '0',
                        use_points: (document.getElementById('usePoints') && document.getElementById('usePoints').checked) ? '1' : '0'
                    });
                    if (checkoutSnapshotToken) {
                        ppParams.set('checkout_token', checkoutSnapshotToken);
                    }
                    return fetch(baseUrl + 'api/payment/create-paypal-order', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: ppParams
                    }).then(function(r) { return r.json(); }).then(function(res) {
                        if (!res.success) throw new Error(res.message || CI.errPaypalCreateOrder || '');
                        orderNumberForConfirm = res.order_number || null;
                        return actions.order.create({
                            purchase_units: [{ amount: { value: res.amount, currency_code: res.currency } }]
                        });
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        var purchaseUnit = (details && details.purchase_units && details.purchase_units[0]) ? details.purchase_units[0] : null;
                        var capture = (purchaseUnit && purchaseUnit.payments && purchaseUnit.payments.captures && purchaseUnit.payments.captures[0])
                            ? purchaseUnit.payments.captures[0]
                            : null;
                        var amountNode = (capture && capture.amount) ? capture.amount : (purchaseUnit && purchaseUnit.amount ? purchaseUnit.amount : null);
                        var payload = {
                            paypal_order_id: details.id,
                            payment_method: 'paypal',
                            paypal_capture_status: capture && capture.status ? String(capture.status) : '',
                            paypal_amount: amountNode && amountNode.value != null ? String(amountNode.value) : '',
                            paypal_currency: amountNode && amountNode.currency_code ? String(amountNode.currency_code) : '',
                            paypal_capture_id: capture && capture.id ? String(capture.id) : ''
                        };
                        if (orderNumberForConfirm) payload.order_number = orderNumberForConfirm;
                        return confirmOrder(payload);
                    }).then(function(data) {
                        if (data.success) {
                            alert((CI.orderConfirmedPrefix || '') + (data.order_number || data.order_id));
                            window.location.href = baseUrl + 'account/orders';
                        } else {
                            alert(data.message || CI.errOrderConfirm || '');
                        }
                    }).catch(function(err) {
                        alert(err.message || CI.errPaypalProcess || '');
                    });
                },
                onError: function(err) { alert(CI.alertPaypalError || ''); }
            });
            var renderPromise = paypalButtons.render('#paypal-button-container');
            if (renderPromise && typeof renderPromise.then === 'function') {
                renderPromise.then(function() { togglePaymentUI(); }).catch(function(err) {
                    console.error('PayPal render error:', err);
                    togglePaymentUI();
                });
            } else {
                togglePaymentUI();
            }
        } catch (e) {
            console.error('PayPal init:', e);
            container.style.display = 'none';
            paypalButtons = null;
            togglePaymentUI();
        }
    }

    document.querySelectorAll('input[name="paymentMethod"]').forEach(function(radio) {
        radio.addEventListener('change', togglePaymentUI);
    });
    var walletToggleEl = document.getElementById('useWalletBalance');
    if (walletToggleEl) walletToggleEl.addEventListener('change', updateSummary);
    var pointsToggleEl = document.getElementById('usePoints');
    if (pointsToggleEl) pointsToggleEl.addEventListener('change', updateSummary);

    var confirmBtn = document.getElementById('confirmOrderBtn');
    if (confirmBtn) confirmBtn.addEventListener('click', handleConfirmClick);

    function init() {
        function initSharedAddressModal() {
            if (typeof window.createAddressModalManager !== 'function') {
                return false;
            }
            addressModalManager = window.createAddressModalManager({
                baseUrl: window.APP_BASE || '/',
                requireMapPin: true,
                i18n: {
                    modalAdd: CI.modalAddAddress || '',
                    modalEdit: CI.modalEditAddress || '',
                    loadFailed: CI.alertLoadAddressFailed || '',
                    loadError: CI.alertLoadAddressFailed || '',
                    alertRequired: CI.alertRequired || '',
                    alertVillageOrStreet: CI.alertVillageOrStreet || '',
                    saveFailed: CI.alertSaveAddressFailed || '',
                    saveError: CI.alertSaveAddressFailed || '',
                    confirmDelete: CI.alertDeleteAddressConfirm || '',
                    deleteFailed: CI.alertDeleteAddressFailed || '',
                    deleteError: CI.alertDeleteAddressFailed || '',
                    defaultFailed: CI.alertSetDefaultAddressFailed || '',
                    defaultError: CI.alertSetDefaultAddressFailed || '',
                    residentialDefault: CI.addressTypeResidential || '',
                    mapHelp: CI.mapHelp || '',
                    mapLocating: CI.mapLocating || '',
                    mapReverseGeocoding: CI.mapReverseGeocoding || '',
                    mapResolvedAddress: CI.mapResolvedAddress || '',
                    mapLocateUnsupported: CI.mapLocateUnsupported || '',
                    mapLocateDenied: CI.mapLocateDenied || '',
                    mapLocateTimeout: CI.mapLocateTimeout || '',
                    mapLocateFailed: CI.mapLocateFailed || '',
                    mapResolveFailed: CI.mapResolveFailed || '',
                    mapMaptilerFallback: CI.mapMaptilerFallback || '',
                    mapAddressEditedHint: CI.mapAddressEditedHint || '',
                    mapAddressChangedConfirm: CI.mapAddressChangedConfirm || '',
                    mapRelocateRequired: CI.mapRelocateRequired || '',
                    mapRequirePinForQuote: CI.mapRequirePinForQuote || ''
                },
                onSaveSuccess: function(result, ctx) {
                    var newId = (result && result.address_id) || (ctx && ctx.addressId) || null;
                    loadAddresses(newId);
                    if (lalamoveEnabled && !checkoutBlocked && ctx && ctx.payload && ctx.payload.lat && ctx.payload.lng) {
                        setTimeout(function() { scheduleLalamoveQuote(); }, 600);
                    }
                }
            });
            window.openAddAddressModal = addressModalManager.openAddModal;
            window.saveCheckoutAddress = addressModalManager.saveAddress;
            addressModalManager.initBindings();
            return true;
        }

        function waitForDOM() {
            var orderItemsContainer = document.getElementById('orderItems');
            var stripeCardElement = document.getElementById('stripe-card-element');
            if (!orderItemsContainer || !stripeCardElement) {
                setTimeout(waitForDOM, 50);
                return;
            }
            loadCart();
            loadAddresses();
            togglePaymentUI();
            bindCheckoutItemEvents();

            var addrContainer = document.getElementById('addressCardsContainer');
            if (addrContainer) {
                addrContainer.addEventListener('change', function(e) {
                    var t = e.target;
                    if (t && t.name === 'checkout_address') {
                        scheduleLalamoveQuote();
                    }
                });
            }

            var useNewAddressBtn = document.getElementById('useNewAddress');
            if (useNewAddressBtn) {
                useNewAddressBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (typeof window.openAddAddressModal === 'function') {
                        window.openAddAddressModal();
                    }
                });
            }
            var usingSharedAddressModal = initSharedAddressModal();
            if (!usingSharedAddressModal) {
                var saveAddrBtn = document.getElementById('saveCheckoutAddressBtn');
                if (saveAddrBtn) {
                    saveAddrBtn.addEventListener('click', function() {
                        if (typeof window.saveCheckoutAddress === 'function') {
                            window.saveCheckoutAddress();
                        }
                    });
                }
                var modalEl = document.getElementById('addressModal');
                if (modalEl) {
                    modalEl.addEventListener('shown.bs.modal', function() {
                        initAddressMap();
                    });
                }
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                waitForDOM();
            });
        } else {
            waitForDOM();
        }
    }
    init();
})();
