(function() {
    'use strict';

    function createAddressModalManager(options) {
        var opts = options || {};
        var baseUrl = String(opts.baseUrl || (window.APP_BASE || '')).replace(/\/$/, '') + '/';
        var I = opts.i18n || {};
        var requireMapPin = opts.requireMapPin !== false;

        var mapCfg = window.MapShared && typeof window.MapShared.getMapConfig === 'function'
            ? window.MapShared.getMapConfig()
            : {};
        var leafletCssUrl = mapCfg.leafletCssUrl;
        var leafletJsUrl = mapCfg.leafletJsUrl;
        var nominatimReverseUrl = mapCfg.nominatimReverseUrl;
        var maptilerSdkCssUrl = mapCfg.maptilerSdkCssUrl;
        var maptilerSdkJsUrl = mapCfg.maptilerSdkJsUrl;
        var maptilerLeafletPluginUrl = mapCfg.maptilerLeafletPluginUrl;
        var maptilerGeocodingControlJsUrl = mapCfg.maptilerGeocodingControlJsUrl;
        var maptilerReverseGeocodeUrl = mapCfg.maptilerReverseGeocodeUrl;
        var mapAssetLoader = window.MapShared && typeof window.MapShared.createAssetLoader === 'function'
            ? window.MapShared.createAssetLoader({
                leafletCssUrl: leafletCssUrl,
                leafletJsUrl: leafletJsUrl,
                maptilerSdkCssUrl: maptilerSdkCssUrl,
                maptilerSdkJsUrl: maptilerSdkJsUrl,
                maptilerLeafletPluginUrl: maptilerLeafletPluginUrl,
                maptilerGeocodingControlJsUrl: maptilerGeocodingControlJsUrl
            }, 'address-shared')
            : null;

        var leafletLoadPromise = null;
        var maptilerStackPromise = null;
        var externalScriptPromises = Object.create(null);
        var mapInstance = null;
        var mapMarker = null;
        var geocodingControl = null;
        var reverseGeocodeToken = 0;
        var hasMapSelection = false;
        var addressEditedAfterMapPick = false;
        var mapAutofillInProgress = false;
        var pendingMapPoint = null;

        var textFieldsToTrim = ['address_label', 'address_type', 'recipient_name', 'phone', 'region', 'district', 'village_estate', 'street', 'building', 'floor', 'unit', 'lat', 'lng'];

        function apiUrl(path) {
            return baseUrl + String(path || '').replace(/^\/+/, '');
        }
        function loadStylesheet(href) {
            if (mapAssetLoader) return mapAssetLoader.loadStylesheet(href);
            if (!href) return Promise.reject(new Error('stylesheet_href_missing'));
            if (document.querySelector('link[data-address-shared-href="' + href + '"]')) return Promise.resolve(true);
            return new Promise(function(resolve, reject) {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                link.setAttribute('data-address-shared-href', href);
                link.onload = function() { resolve(true); };
                link.onerror = function() { reject(new Error('stylesheet_load_failed')); };
                document.head.appendChild(link);
            });
        }
        function injectExternalScript(src, globalName) {
            if (mapAssetLoader) return mapAssetLoader.injectScript(src, globalName);
            if (!src) return Promise.reject(new Error('script_src_missing'));
            if (globalName && typeof window[globalName] !== 'undefined') return Promise.resolve(window[globalName]);
            if (externalScriptPromises[src]) return externalScriptPromises[src];
            externalScriptPromises[src] = new Promise(function(resolve, reject) {
                var script = document.createElement('script');
                script.src = src;
                script.async = true;
                script.onload = function() { resolve(globalName ? window[globalName] : true); };
                script.onerror = function() { reject(new Error('script_load_failed')); };
                document.head.appendChild(script);
            });
            return externalScriptPromises[src];
        }
        function ensureLeafletLoaded() {
            if (mapAssetLoader) return mapAssetLoader.ensureLeafletLoaded();
            if (window.L && typeof window.L.map === 'function') return Promise.resolve(window.L);
            if (leafletLoadPromise) return leafletLoadPromise;
            leafletLoadPromise = Promise.all([loadStylesheet(leafletCssUrl), injectExternalScript(leafletJsUrl, 'L')]).then(function(results) { return results[1]; });
            return leafletLoadPromise;
        }
        function loadMapTilerStack() {
            if (mapAssetLoader) return mapAssetLoader.loadMapTilerStack();
            if (window.L && window.L.maptiler && typeof window.L.maptiler.maptilerLayer === 'function') return Promise.resolve();
            if (maptilerStackPromise) return maptilerStackPromise;
            maptilerStackPromise = loadStylesheet(maptilerSdkCssUrl).then(function() { return injectExternalScript(maptilerSdkJsUrl); }).then(function() { return injectExternalScript(maptilerLeafletPluginUrl); });
            return maptilerStackPromise;
        }
        function loadMapTilerGeocodingControl() {
            if (mapAssetLoader) return mapAssetLoader.loadMapTilerGeocodingControl();
            if (window.maptilerGeocoder && typeof window.maptilerGeocoder.GeocodingControl === 'function') return Promise.resolve();
            return injectExternalScript(maptilerGeocodingControlJsUrl).then(function() {
                if (!window.maptilerGeocoder || typeof window.maptilerGeocoder.GeocodingControl !== 'function') throw new Error('maptiler_geocoding_control_not_ready');
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
            var s = String(text || '').trim();
            var n = Number(maxLen) || 24;
            return s.length <= n ? s : (s.slice(0, n) + '...');
        }
        var mapUiHelpers = window.MapShared && typeof window.MapShared.createAddressMapHelpers === 'function'
            ? window.MapShared.createAddressMapHelpers()
            : null;
        var autoSelectHongKongRegion = window.MapShared && typeof window.MapShared.autoSelectHongKongRegion === 'function'
            ? window.MapShared.autoSelectHongKongRegion
            : null;
        var bindSharedGeocodingPick = window.MapShared && typeof window.MapShared.bindGeocodingControlPick === 'function'
            ? window.MapShared.bindGeocodingControlPick
            : null;
        var setupSharedLeafletBasemap = window.MapShared && typeof window.MapShared.setupLeafletBasemapWithFallback === 'function'
            ? window.MapShared.setupLeafletBasemapWithFallback
            : null;
        var reverseGeocodeWithFallback = window.MapShared && typeof window.MapShared.reverseGeocodeWithFallback === 'function'
            ? window.MapShared.reverseGeocodeWithFallback
            : null;
        var updateMapLatLng = mapUiHelpers
            ? mapUiHelpers.updateMapLatLng
            : function(lat, lng) {
                var latEl = document.getElementById('lat');
                var lngEl = document.getElementById('lng');
                var latNum = Number(lat);
                var lngNum = Number(lng);
                if (latEl) latEl.value = Number.isFinite(latNum) ? latNum.toFixed(6) : '';
                if (lngEl) lngEl.value = Number.isFinite(lngNum) ? lngNum.toFixed(6) : '';
            };
        var normalizeCoordinate = mapUiHelpers
            ? mapUiHelpers.normalizeCoordinate
            : function(value) {
                var parsed = Number(value);
                return Number.isFinite(parsed) ? Number(parsed.toFixed(6)) : null;
            };
        var getAddressInputEls = mapUiHelpers
            ? mapUiHelpers.getAddressInputEls
            : function() {
                return ['district', 'street', 'building', 'unit', 'villageEstate'].map(function(id) { return document.getElementById(id); }).filter(Boolean);
            };
        var updateMapVisualState = mapUiHelpers
            ? mapUiHelpers.updateMapVisualState
            : function(isWarning) {
                var wrapper = document.getElementById('mapWrapper');
                if (wrapper) wrapper.classList.toggle('border-warning', !!isWarning);
            };
        var applyAutofillFieldValue = mapUiHelpers
            ? mapUiHelpers.applyAutofillFieldValue
            : function(el, value) {
                if (!el) return;
                var normalized = String(value || '');
                el.value = normalized;
                el.dataset.lastAutofill = normalized;
            };
        function autoSelectRegionByText(text) {
            var regionEl = document.getElementById('region');
            if (!regionEl || !text) return;
            if (autoSelectHongKongRegion) {
                autoSelectHongKongRegion(regionEl, text);
                return;
            }
            var t = String(text).toLowerCase();
            var key = /hong kong island|港島|港岛|香港岛/.test(t) ? 'island' : (/kowloon|九龍|九龙/.test(t) ? 'kowloon' : (/new territories|新界/.test(t) ? 'territories' : ''));
            if (!key) return;
            Array.prototype.slice.call(regionEl.options || []).forEach(function(opt) {
                if (!opt || !opt.value) return;
                var v = String(opt.value).toLowerCase();
                if (key === 'island' && /island|香港|港島|港岛/.test(v)) regionEl.value = opt.value;
                if (key === 'kowloon' && /kowloon|九龍|九龙/.test(v)) regionEl.value = opt.value;
                if (key === 'territories' && /territories|新界/.test(v)) regionEl.value = opt.value;
            });
        }
        var shouldInvalidateCoordinates = mapUiHelpers
            ? mapUiHelpers.shouldInvalidateCoordinates
            : function(oldVal, newVal) {
                var prev = String(oldVal || '').trim();
                var next = String(newVal || '').trim();
                if (!prev || !next || prev === next) return false;
                var keyPart = prev.substring(0, 8);
                if (keyPart && next.indexOf(keyPart) === -1) return true;
                var delta = Math.abs(next.length - prev.length);
                return prev.length > 0 && (delta / prev.length) > 0.5;
            };
        function invalidateStoredCoordinates() {
            updateMapLatLng('', '');
            hasMapSelection = false;
            updateMapVisualState(true);
            setMapStatus(I.mapRelocateRequired || '', 'warning');
        }
        function beginMapAutofill() { mapAutofillInProgress = true; }
        function endMapAutofill() {
            mapAutofillInProgress = false;
            hasMapSelection = true;
            addressEditedAfterMapPick = false;
            updateMapVisualState(false);
        }
        function updateAddressFieldsFromNominatim(data) {
            if (!data || !data.address) return;
            var addr = data.address || {};
            var district = addr.city_district || addr.suburb || addr.borough || addr.town || addr.city || addr.county || '';
            var road = addr.road || addr.pedestrian || addr.residential || '';
            var houseNumber = addr.house_number || '';
            var street = [road, houseNumber].filter(function(v) { return String(v || '').trim() !== ''; }).join(' ');
            var building = addr.building || addr.commercial || addr.amenity || addr.shop || '';
            beginMapAutofill();
            autoSelectRegionByText([data.display_name || '', district].join(' '));
            applyAutofillFieldValue(document.getElementById('district'), district);
            applyAutofillFieldValue(document.getElementById('street'), street);
            applyAutofillFieldValue(document.getElementById('building'), building);
            applyAutofillFieldValue(document.getElementById('unit'), '');
            applyAutofillFieldValue(document.getElementById('villageEstate'), addr.neighbourhood || addr.quarter || '');
            endMapAutofill();
        }
        function updateAddressFieldsFromMapTiler(data) {
            if (!data || !Array.isArray(data.features) || data.features.length === 0) return false;
            var f = data.features[0] || {};
            var p = f.properties || {};
            var district = p.district || p.suburb || p.city || p.county || '';
            var street = [p.street || '', p.housenumber || ''].filter(function(v) { return String(v || '').trim() !== ''; }).join(' ');
            var building = p.name || p.poi || '';
            beginMapAutofill();
            autoSelectRegionByText([f.place_name_zh_hant || '', f.place_name || '', district].join(' '));
            applyAutofillFieldValue(document.getElementById('district'), district);
            applyAutofillFieldValue(document.getElementById('street'), street);
            applyAutofillFieldValue(document.getElementById('building'), building);
            applyAutofillFieldValue(document.getElementById('unit'), '');
            applyAutofillFieldValue(document.getElementById('villageEstate'), p.neighbourhood || '');
            endMapAutofill();
            return true;
        }
        function reverseGeocode(lat, lng) {
            var token = ++reverseGeocodeToken;
            setMapStatus(I.mapReverseGeocoding || '', 'default');
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
                        if (!updateAddressFieldsFromMapTiler(data)) {
                            return false;
                        }
                        var feature = data.features && data.features[0] ? data.features[0] : {};
                        var displayName = feature.place_name_zh_hant || feature.place_name || feature.text || '';
                        setMapStatus(displayName ? (I.mapResolvedAddress || '') + ': ' + shortenStatusAddress(displayName, 24) : (I.mapResolvedAddress || ''), 'success');
                        return true;
                    },
                    onNominatimData: function(data) {
                        updateAddressFieldsFromNominatim(data);
                        var name = data && data.display_name ? String(data.display_name) : '';
                        setMapStatus(name ? (I.mapResolvedAddress || '') + ': ' + shortenStatusAddress(name, 24) : (I.mapResolvedAddress || ''), 'success');
                    }
                }).catch(function() {
                    if (token !== reverseGeocodeToken) return;
                    setMapStatus(I.mapResolveFailed || '', 'danger');
                });
                return;
            }
            fetch(nominatimReverseUrl + '?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng) + '&accept-language=zh-HK').then(function(r) {
                if (!r.ok) throw new Error('nominatim_failed');
                return r.json();
            }).then(function(data) {
                if (token !== reverseGeocodeToken) return;
                updateAddressFieldsFromNominatim(data);
                var name = data && data.display_name ? String(data.display_name) : '';
                setMapStatus(name ? (I.mapResolvedAddress || '') + ': ' + shortenStatusAddress(name, 24) : (I.mapResolvedAddress || ''), 'success');
            }).catch(function() {
                if (token !== reverseGeocodeToken) return;
                setMapStatus(I.mapResolveFailed || '', 'danger');
            });
        }
        function handleMapPick(lat, lng) {
            var nLat = normalizeCoordinate(lat);
            var nLng = normalizeCoordinate(lng);
            if (!Number.isFinite(nLat) || !Number.isFinite(nLng)) return;
            if (!mapInstance || !window.L) return;
            if (!mapMarker) mapMarker = window.L.marker([nLat, nLng]).addTo(mapInstance);
            else mapMarker.setLatLng([nLat, nLng]);
            mapInstance.setView([nLat, nLng], Math.max(mapInstance.getZoom(), 16));
            updateMapLatLng(nLat, nLng);
            reverseGeocode(nLat, nLng);
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
            function onPicked(raw) {
                var payload = raw || {};
                var center = Array.isArray(payload.center) ? payload.center : (payload.feature && payload.feature.center);
                if (Array.isArray(center) && center.length >= 2) handleMapPick(Number(center[1]), Number(center[0]));
            }
            if (gc && typeof gc.on === 'function') {
                gc.on('select', onPicked);
                gc.on('pick', onPicked);
            }
            if (gc && typeof gc.addEventListener === 'function') {
                gc.addEventListener('select', onPicked);
                gc.addEventListener('pick', onPicked);
            }
        }
        function initGeocodingControl(maptilerKey) {
            if (!maptilerKey || !mapInstance || geocodingControl) return;
            loadMapTilerGeocodingControl().then(function() {
                geocodingControl = new window.maptilerGeocoder.GeocodingControl({ apiKey: maptilerKey, position: 'bottomright' });
                if (typeof mapInstance.addControl === 'function') mapInstance.addControl(geocodingControl);
                bindGeocodingControlPick(geocodingControl);
            }).catch(function(err) { console.error('MapTiler GeocodingControl load failed:', err); });
        }
        function initLeafletAddressMapInternal(maptilerKey) {
            ensureLeafletLoaded().then(function(L) {
                if (!mapInstance) {
                    mapInstance = L.map('checkoutAddressMap', { minZoom: 10, maxZoom: 20 }).setView([22.3193, 114.1694], 12);
                    mapInstance.on('click', function(ev) { handleMapPick(ev.latlng.lat, ev.latlng.lng); });
                    if (setupSharedLeafletBasemap) {
                        setupSharedLeafletBasemap({
                            map: mapInstance,
                            L: L,
                            maptilerKey: maptilerKey,
                            loadMapTilerStack: loadMapTilerStack,
                            initGeocodingControl: initGeocodingControl,
                            setMapStatus: setMapStatus,
                            mapMaptilerFallbackText: I.mapMaptilerFallback || ''
                        });
                    } else if (maptilerKey) {
                        loadMapTilerStack().then(function() {
                            L.maptiler.maptilerLayer({ apiKey: maptilerKey, style: L.maptiler.MapStyle.STREETS }).addTo(mapInstance);
                            initGeocodingControl(maptilerKey);
                        }).catch(function() {
                            setMapStatus(I.mapMaptilerFallback || '', 'warning');
                            initGeocodingControl(maptilerKey);
                        });
                    }
                }
                mapInstance.invalidateSize();
                setTimeout(function() { if (mapInstance) mapInstance.invalidateSize(); }, 200);
                if (pendingMapPoint && Number.isFinite(pendingMapPoint.lat) && Number.isFinite(pendingMapPoint.lng)) {
                    handleMapPick(pendingMapPoint.lat, pendingMapPoint.lng);
                    pendingMapPoint = null;
                }
                setMapStatus(I.mapHelp || '', 'default');
            }).catch(function() { setMapStatus(I.mapResolveFailed || '', 'danger'); });
        }
        function initAddressMap() {
            var mapEl = document.getElementById('checkoutAddressMap');
            if (!mapEl) return;
            initLeafletAddressMapInternal(String(window.MAPTILER_API_KEY || '').trim());
        }
        function collectAddressFormData(form) {
            var formData = new FormData(form);
            var data = Object.fromEntries(formData.entries());
            textFieldsToTrim.forEach(function(key) { if (typeof data[key] === 'string') data[key] = data[key].trim(); });
            data.is_default = document.getElementById('isDefault') && document.getElementById('isDefault').checked ? 1 : 0;
            return { addressId: String(formData.get('id') || '').trim(), data: data };
        }

        function openAddModal() {
            var modal = document.getElementById('addressModal');
            var modalLabel = document.getElementById('addressModalLabel');
            var form = document.getElementById('addressForm');
            if (!modal || !modalLabel || !form) return;
            modalLabel.textContent = I.modalAdd || '';
            form.reset();
            var idEl = document.getElementById('addressId');
            if (idEl) idEl.value = '';
            updateMapLatLng('', '');
            hasMapSelection = false;
            addressEditedAfterMapPick = false;
            reverseGeocodeToken += 1;
            pendingMapPoint = null;
            setMapStatus(I.mapHelp || '', 'default');
            updateMapVisualState(false);
            getAddressInputEls().forEach(function(el) { delete el.dataset.lastAutofill; });
            form.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        }

        async function openEditModal(addressId) {
            try {
                var response = await fetch(apiUrl('api/address/get') + '?id=' + encodeURIComponent(addressId));
                var data = await response.json();
                if (!data.success || !data.address) {
                    alert(I.loadFailed || '');
                    return;
                }
                var address = data.address;
                var modal = document.getElementById('addressModal');
                var modalLabel = document.getElementById('addressModalLabel');
                var form = document.getElementById('addressForm');
                if (!modal || !modalLabel || !form) return;
                modalLabel.textContent = I.modalEdit || '';
                document.getElementById('addressId').value = address.id || '';
                document.getElementById('addressLabel').value = address.address_label || '';
                document.getElementById('addressType').value = address.address_type || I.residentialDefault || '';
                document.getElementById('recipientName').value = address.recipient_name || '';
                document.getElementById('phone').value = address.phone || '';
                document.getElementById('region').value = address.region || '';
                document.getElementById('district').value = address.district || '';
                document.getElementById('villageEstate').value = address.village_estate || '';
                document.getElementById('street').value = address.street || '';
                document.getElementById('building').value = address.building || '';
                document.getElementById('floor').value = address.floor || '';
                document.getElementById('unit').value = address.unit || '';
                document.getElementById('lat').value = address.lat || '';
                document.getElementById('lng').value = address.lng || '';
                var isDefault = document.getElementById('isDefault');
                if (isDefault) isDefault.checked = address.is_default == 1;
                hasMapSelection = String(address.lat || '').trim() !== '' && String(address.lng || '').trim() !== '';
                addressEditedAfterMapPick = false;
                pendingMapPoint = hasMapSelection ? { lat: Number(address.lat), lng: Number(address.lng) } : null;
                setMapStatus(I.mapHelp || '', 'default');
                form.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
                var bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            } catch (error) {
                console.error('Error loading address:', error);
                alert(I.loadError || '');
            }
        }

        async function saveAddress() {
            var form = document.getElementById('addressForm');
            if (!form) return;
            var payloadData = collectAddressFormData(form);
            var addressId = payloadData.addressId;
            var data = payloadData.data;
            var hasAddressText = !!(String(data.region || '').trim() || String(data.district || '').trim() || String(data.street || '').trim() || String(data.village_estate || '').trim() || String(data.building || '').trim());
            var hasLatLng = String(data.lat || '').trim() !== '' && String(data.lng || '').trim() !== '';
            if (!data.recipient_name || !data.phone || !data.region || !data.district || !data.building) {
                alert(I.alertRequired || '');
                return;
            }
            if (!data.village_estate && !data.street) {
                alert(I.alertVillageOrStreet || '');
                return;
            }
            if (requireMapPin && hasAddressText && !hasLatLng) {
                alert(I.mapRequirePinForQuote || I.mapHelp || '');
                setMapStatus(I.mapHelp || '', 'warning');
                return;
            }
            if (hasMapSelection && addressEditedAfterMapPick) {
                if (!confirm(I.mapAddressChangedConfirm || '')) return;
            }
            try {
                var path = addressId ? 'api/address/update' : 'api/address/create';
                var payload = addressId ? Object.assign({}, data, { id: addressId }) : data;
                if (!addressId) delete payload.id;
                var response = await fetch(apiUrl(path), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                var result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || result.error || I.saveFailed || '');
                }
                var modalEl = document.getElementById('addressModal');
                if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                if (typeof opts.onSaveSuccess === 'function') opts.onSaveSuccess(result, { addressId: addressId, payload: payload });
                else window.location.reload();
            } catch (error) {
                console.error('Error saving address:', error);
                var reason = (error && error.message) ? String(error.message) : (I.saveError || '');
                if (typeof opts.onSaveError === 'function') opts.onSaveError(reason);
                else alert(reason);
            }
        }

        async function deleteAddress(addressId) {
            if (!confirm(I.confirmDelete || '')) return;
            try {
                var response = await fetch(apiUrl('api/address/delete'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: addressId })
                });
                var result = await response.json();
                if (result.success) {
                    if (typeof opts.onDeleteSuccess === 'function') opts.onDeleteSuccess(result);
                    else window.location.reload();
                } else {
                    alert(result.error || I.deleteFailed || '');
                }
            } catch (error) {
                console.error('Error deleting address:', error);
                alert(I.deleteError || '');
            }
        }

        async function setDefault(addressId) {
            try {
                var response = await fetch(apiUrl('api/address/set-default'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: addressId })
                });
                var result = await response.json();
                if (result.success) {
                    if (typeof opts.onSetDefaultSuccess === 'function') opts.onSetDefaultSuccess(result);
                    else window.location.reload();
                } else {
                    alert(result.error || I.defaultFailed || '');
                }
            } catch (error) {
                console.error('Error setting default address:', error);
                alert(I.defaultError || '');
            }
        }

        function initBindings() {
            var modalEl = document.getElementById('addressModal');
            if (modalEl) {
                modalEl.addEventListener('shown.bs.modal', initAddressMap);
            }

            var saveBtn = document.getElementById('saveCheckoutAddressBtn');
            if (saveBtn) saveBtn.addEventListener('click', saveAddress);

            var locateBtn = document.getElementById('locateMeBtn');
            if (locateBtn) {
                locateBtn.addEventListener('click', function() {
                    if (!navigator.geolocation) {
                        setMapStatus(I.mapLocateUnsupported || '', 'warning');
                        return;
                    }
                    setMapStatus(I.mapLocating || '', 'default');
                    navigator.geolocation.getCurrentPosition(function(position) {
                        handleMapPick(position.coords.latitude, position.coords.longitude);
                    }, function(error) {
                        if (error && error.code === error.PERMISSION_DENIED) return setMapStatus(I.mapLocateDenied || '', 'warning');
                        if (error && error.code === error.TIMEOUT) return setMapStatus(I.mapLocateTimeout || I.mapLocateFailed || '', 'danger');
                        setMapStatus(I.mapLocateFailed || '', 'danger');
                    }, { enableHighAccuracy: true, timeout: 12000 });
                });
            }
            getAddressInputEls().forEach(function(el) {
                el.addEventListener('input', function() {
                    if (mapAutofillInProgress || !hasMapSelection) return;
                    addressEditedAfterMapPick = true;
                    var previousAutofillValue = el.dataset.lastAutofill || '';
                    var currentValue = el.value || '';
                    if (shouldInvalidateCoordinates(previousAutofillValue, currentValue)) {
                        invalidateStoredCoordinates();
                        return;
                    }
                    setMapStatus(I.mapAddressEditedHint || '', 'warning');
                });
            });
            var regionEl = document.getElementById('region');
            if (regionEl) {
                regionEl.addEventListener('change', function() {
                    if (mapAutofillInProgress || !hasMapSelection) return;
                    addressEditedAfterMapPick = true;
                    invalidateStoredCoordinates();
                });
            }
        }

        return {
            initBindings: initBindings,
            openAddModal: openAddModal,
            openEditModal: openEditModal,
            saveAddress: saveAddress,
            deleteAddress: deleteAddress,
            setDefault: setDefault
        };
    }

    window.createAddressModalManager = createAddressModalManager;
})();

