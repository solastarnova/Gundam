(function () {
    'use strict';

    if (window.MapShared) {
        return;
    }

    function getMapConfig() {
        return {
            leafletCssUrl: window.LEAFLET_CSS_URL || 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            leafletJsUrl: window.LEAFLET_JS_URL || 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            nominatimReverseUrl: window.NOMINATIM_REVERSE_URL || 'https://nominatim.openstreetmap.org/reverse',
            maptilerSdkCssUrl: window.MAPTILER_SDK_CSS || 'https://cdn.maptiler.com/maptiler-sdk-js/v4.0.1/maptiler-sdk.css',
            maptilerSdkJsUrl: window.MAPTILER_SDK_JS || 'https://cdn.maptiler.com/maptiler-sdk-js/v4.0.1/maptiler-sdk.umd.min.js',
            maptilerLeafletPluginUrl: window.MAPTILER_LEAFLET_JS || 'https://cdn.maptiler.com/leaflet-maptilersdk/v4.1.0/leaflet-maptilersdk.umd.min.js',
            maptilerGeocodingControlJsUrl: window.MAPTILER_GEOCODING_CONTROL_JS || 'https://cdn.maptiler.com/maptiler-geocoding-control/v3.0.0/leaflet.umd.js',
        };
    }

    function createAssetLoader(config, namespace) {
        var cfg = config || {};
        var ns = String(namespace || 'map-shared');
        var stylesheetAttr = 'data-' + ns + '-href';
        var scriptCache = Object.create(null);
        var leafletLoadPromise = null;
        var maptilerStackPromise = null;
        var geocodingControlPromise = null;

        function loadStylesheet(href) {
            if (!href) {
                return Promise.reject(new Error('stylesheet_href_missing'));
            }
            if (document.querySelector('link[' + stylesheetAttr + '="' + href + '"]')) {
                return Promise.resolve(true);
            }
            return new Promise(function(resolve, reject) {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                link.setAttribute(stylesheetAttr, href);
                link.onload = function() { resolve(true); };
                link.onerror = function() { reject(new Error('stylesheet_load_failed')); };
                document.head.appendChild(link);
            });
        }

        function injectScript(src, globalName) {
            if (!src) {
                return Promise.reject(new Error('script_src_missing'));
            }
            if (globalName && typeof window[globalName] !== 'undefined') {
                return Promise.resolve(window[globalName]);
            }
            if (scriptCache[src]) {
                return scriptCache[src];
            }
            scriptCache[src] = new Promise(function(resolve, reject) {
                var script = document.createElement('script');
                script.src = src;
                script.async = true;
                script.onload = function() {
                    if (globalName && typeof window[globalName] === 'undefined') {
                        reject(new Error(globalName + '_not_ready'));
                        return;
                    }
                    resolve(globalName ? window[globalName] : true);
                };
                script.onerror = function() { reject(new Error('script_load_failed')); };
                document.head.appendChild(script);
            });
            return scriptCache[src];
        }

        function ensureLeafletLoaded() {
            if (window.L && typeof window.L.map === 'function') {
                return Promise.resolve(window.L);
            }
            if (leafletLoadPromise) {
                return leafletLoadPromise;
            }
            leafletLoadPromise = Promise.all([
                loadStylesheet(cfg.leafletCssUrl),
                injectScript(cfg.leafletJsUrl, 'L'),
            ]).then(function(results) {
                return results[1];
            });
            return leafletLoadPromise;
        }

        function loadMapTilerStack() {
            if (window.L && window.L.maptiler && typeof window.L.maptiler.maptilerLayer === 'function') {
                return Promise.resolve();
            }
            if (maptilerStackPromise) {
                return maptilerStackPromise;
            }
            maptilerStackPromise = loadStylesheet(cfg.maptilerSdkCssUrl)
                .then(function() { return injectScript(cfg.maptilerSdkJsUrl); })
                .then(function() { return injectScript(cfg.maptilerLeafletPluginUrl); })
                .then(function() {
                    if (!window.L || !window.L.maptiler || typeof window.L.maptiler.maptilerLayer !== 'function') {
                        throw new Error('maptiler_leaflet_not_ready');
                    }
                });
            return maptilerStackPromise;
        }

        function loadMapTilerGeocodingControl() {
            if (window.maptilerGeocoder && typeof window.maptilerGeocoder.GeocodingControl === 'function') {
                return Promise.resolve();
            }
            if (geocodingControlPromise) {
                return geocodingControlPromise;
            }
            geocodingControlPromise = injectScript(cfg.maptilerGeocodingControlJsUrl).then(function() {
                if (!window.maptilerGeocoder || typeof window.maptilerGeocoder.GeocodingControl !== 'function') {
                    throw new Error('maptiler_geocoding_control_not_ready');
                }
            });
            return geocodingControlPromise;
        }

        return {
            loadStylesheet: loadStylesheet,
            injectScript: injectScript,
            ensureLeafletLoaded: ensureLeafletLoaded,
            loadMapTilerStack: loadMapTilerStack,
            loadMapTilerGeocodingControl: loadMapTilerGeocodingControl,
        };
    }

    function createAddressMapHelpers(options) {
        var opts = options || {};
        var mapWrapperId = String(opts.mapWrapperId || 'mapWrapper');
        var latFieldId = String(opts.latFieldId || 'lat');
        var lngFieldId = String(opts.lngFieldId || 'lng');
        var addressFieldIds = Array.isArray(opts.addressFieldIds) && opts.addressFieldIds.length
            ? opts.addressFieldIds
            : ['district', 'street', 'building', 'unit', 'villageEstate'];

        function updateMapLatLng(lat, lng) {
            var latEl = document.getElementById(latFieldId);
            var lngEl = document.getElementById(lngFieldId);
            var latNum = Number(lat);
            var lngNum = Number(lng);
            if (latEl) {
                latEl.value = Number.isFinite(latNum) ? latNum.toFixed(6) : '';
            }
            if (lngEl) {
                lngEl.value = Number.isFinite(lngNum) ? lngNum.toFixed(6) : '';
            }
        }

        function normalizeCoordinate(value) {
            var parsed = Number(value);
            return Number.isFinite(parsed) ? Number(parsed.toFixed(6)) : null;
        }

        function getAddressInputEls() {
            return addressFieldIds
                .map(function(id) { return document.getElementById(id); })
                .filter(function(el) { return !!el; });
        }

        function updateMapVisualState(isWarning) {
            var wrapper = document.getElementById(mapWrapperId);
            if (wrapper) {
                wrapper.classList.toggle('border-warning', !!isWarning);
            }
        }

        function persistLastAutofillValue(el, value) {
            if (!el) {
                return;
            }
            el.dataset.lastAutofill = String(value || '');
        }

        function applyAutofillFieldValue(el, value) {
            if (!el) {
                return;
            }
            var normalized = String(value || '');
            el.value = normalized;
            persistLastAutofillValue(el, normalized);
        }

        function shouldInvalidateCoordinates(oldVal, newVal) {
            var prev = String(oldVal || '').trim();
            var next = String(newVal || '').trim();
            if (!prev || !next || prev === next) {
                return false;
            }
            var keyPart = prev.substring(0, 8);
            if (keyPart && next.indexOf(keyPart) === -1) {
                return true;
            }
            var delta = Math.abs(next.length - prev.length);
            return prev.length > 0 && (delta / prev.length) > 0.5;
        }

        return {
            updateMapLatLng: updateMapLatLng,
            normalizeCoordinate: normalizeCoordinate,
            getAddressInputEls: getAddressInputEls,
            updateMapVisualState: updateMapVisualState,
            persistLastAutofillValue: persistLastAutofillValue,
            applyAutofillFieldValue: applyAutofillFieldValue,
            shouldInvalidateCoordinates: shouldInvalidateCoordinates,
        };
    }

    function createMapStatusManager(options) {
        var opts = options || {};
        var statusElId = String(opts.statusElId || 'mapStatusText');

        function applyTone(statusEl, tone) {
            if (!statusEl) {
                return;
            }
            statusEl.classList.remove('bg-dark', 'bg-success', 'bg-warning', 'bg-danger', 'text-dark', 'text-white');
            if (tone === 'success') {
                statusEl.classList.add('bg-success', 'text-white');
            } else if (tone === 'warning') {
                statusEl.classList.add('bg-warning', 'text-dark');
            } else if (tone === 'danger') {
                statusEl.classList.add('bg-danger', 'text-white');
            } else {
                statusEl.classList.add('bg-dark', 'text-white');
            }
        }

        function setMapStatus(text, tone) {
            var statusEl = document.getElementById(statusElId);
            if (!statusEl) {
                return;
            }
            statusEl.textContent = text || '';
            applyTone(statusEl, tone);
        }

        function shortenStatusAddress(text, maxLen) {
            var normalized = String(text || '').trim();
            var limit = Number(maxLen) || 24;
            if (normalized.length <= limit) {
                return normalized;
            }
            return normalized.slice(0, limit) + '...';
        }

        return {
            setMapStatus: setMapStatus,
            shortenStatusAddress: shortenStatusAddress,
        };
    }

    function reverseGeocodeWithFallback(options) {
        var opts = options || {};
        var lat = opts.lat;
        var lng = opts.lng;
        var token = opts.token;
        var isTokenCurrent = typeof opts.isTokenCurrent === 'function' ? opts.isTokenCurrent : function() { return true; };
        var nominatimReverseUrl = String(opts.nominatimReverseUrl || '');
        var nominatimLanguage = String(opts.nominatimLanguage || 'zh-HK');
        var onNominatimData = typeof opts.onNominatimData === 'function' ? opts.onNominatimData : function() {};

        function resolveApiUrl(url) {
            var raw = String(url || '').trim();
            if (!raw) {
                return raw;
            }
            // Absolute URL or protocol-relative URL: keep as-is.
            if (/^https?:\/\//i.test(raw) || /^\/\//.test(raw)) {
                return raw;
            }
            var appBase = String(window.APP_BASE || '').trim();
            appBase = appBase.replace(/\/+$/, '');
            if (!appBase) {
                return raw.charAt(0) === '/' ? raw : ('/' + raw);
            }
            if (raw.charAt(0) === '/') {
                return appBase + raw;
            }
            return appBase + '/' + raw;
        }

        function ensureCurrent() {
            if (!isTokenCurrent(token)) {
                throw new Error('reverse_geocode_stale');
            }
        }

        function runNominatim() {
            if (!nominatimReverseUrl) {
                return Promise.reject(new Error('nominatim_url_missing'));
            }
            var rawNominatimUrl = String(nominatimReverseUrl || '').trim();
            var resolvedNominatimUrl = resolveApiUrl(rawNominatimUrl);
            var candidates = [];

            function pushCandidate(url) {
                var u = String(url || '').trim();
                if (!u) return;
                if (candidates.indexOf(u) === -1) candidates.push(u);
            }

            pushCandidate(resolvedNominatimUrl);
            // For sub-path deployments, also try root-relative URL as fallback.
            if (rawNominatimUrl.charAt(0) === '/') {
                pushCandidate(rawNominatimUrl);
            }

            function requestOne(baseUrl) {
                var nominatimUrl = baseUrl + '?format=jsonv2&lat=' + encodeURIComponent(lat)
                    + '&lon=' + encodeURIComponent(lng)
                    + '&accept-language=' + encodeURIComponent(nominatimLanguage);
                return fetch(nominatimUrl, { headers: { Accept: 'application/json' } })
                    .then(function(r) {
                        if (!r.ok) {
                            throw new Error('nominatim_failed');
                        }
                        return r.json();
                    });
            }

            function tryAt(index) {
                if (index >= candidates.length) {
                    return Promise.reject(new Error('nominatim_failed'));
                }
                return requestOne(candidates[index]).catch(function() {
                    return tryAt(index + 1);
                });
            }

            return tryAt(0).then(function(data) {
                ensureCurrent();
                onNominatimData(data);
                return { provider: 'nominatim', data: data };
            });
        }

        return runNominatim();
    }

    function autoSelectHongKongRegion(regionEl, sourceText) {
        if (!regionEl || !sourceText) {
            return '';
        }
        var text = String(sourceText).toLowerCase();
        var targetKeyword = '';
        var islandKeywords = [
            'hong kong island', 'hk island', 'central', 'admiralty', 'wan chai',
            'causeway bay', 'north point', 'quarry bay', 'sai ying pun',
            'sheung wan', 'happy valley', '香港岛', '港岛', '港島', '中环', '中環',
            '上环', '上環', '西营盘', '西營盤', '湾仔', '銅鑼灣', '铜锣湾', '北角', '太古', '筲箕湾', '筲箕灣'
        ];
        var kowloonKeywords = [
            'kowloon', 'west kowloon', 'east kowloon', 'kowloon bay', 'kai tak',
            'tsim sha tsui', 'mong kok', 'yau ma tei', 'sham shui po', 'kwun tong',
            'wong tai sin', 'diamond hill', '九龍', '九龙', '西九龙', '西九龍', '東九龍', '东九龙',
            '九龍灣', '九龙湾', '啟德', '启德', '尖沙咀', '旺角', '油麻地', '深水埗', '觀塘', '观塘', '黃大仙', '黄大仙', '鑽石山', '钻石山'
        ];
        var territoriesKeywords = [
            'new territories', 'nt', 'tsuen wan', 'tuen mun', 'yuen long',
            'tai po', 'sha tin', 'tseung kwan o', 'sai kung', 'kwai chung',
            'kwai fong', 'tin shui wai', 'ma on shan', '新界', '將軍澳', '将军澳',
            '西貢', '西贡', '葵涌', '葵芳', '天水圍', '天水围', '馬鞍山', '马鞍山',
            '屯門', '屯门', '元朗', '荃灣', '荃湾', '大埔', '沙田'
        ];

        function includesAny(keywords) {
            return keywords.some(function(keyword) {
                return text.indexOf(keyword) !== -1;
            });
        }

        if (includesAny(islandKeywords)) {
            targetKeyword = 'island';
        } else if (includesAny(kowloonKeywords)) {
            targetKeyword = 'kowloon';
        } else if (includesAny(territoriesKeywords)) {
            targetKeyword = 'territories';
        }
        if (!targetKeyword) {
            return '';
        }

        var optionToSelect = '';
        Array.prototype.slice.call(regionEl.options || []).forEach(function(opt) {
            if (!opt || !opt.value) {
                return;
            }
            var value = String(opt.value).toLowerCase();
            if (targetKeyword === 'island' && (value.indexOf('island') !== -1 || value.indexOf('香港') !== -1 || value.indexOf('港島') !== -1 || value.indexOf('港岛') !== -1)) {
                optionToSelect = opt.value;
            } else if (targetKeyword === 'kowloon' && (value.indexOf('kowloon') !== -1 || value.indexOf('九龍') !== -1 || value.indexOf('九龙') !== -1)) {
                optionToSelect = opt.value;
            } else if (targetKeyword === 'territories' && (value.indexOf('territories') !== -1 || value.indexOf('新界') !== -1)) {
                optionToSelect = opt.value;
            }
        });
        if (optionToSelect) {
            regionEl.value = optionToSelect;
        }
        return optionToSelect;
    }

    function bindGeocodingControlPick(options) {
        var opts = options || {};
        var control = opts.control;
        var map = opts.map;
        var onPick = typeof opts.onPick === 'function' ? opts.onPick : function() {};

        function extractLngLat(payload) {
            if (!payload) {
                return null;
            }
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
            if (!ll || !Number.isFinite(ll.lat) || !Number.isFinite(ll.lng)) {
                return;
            }
            onPick(ll.lat, ll.lng, raw);
        }

        if (control && typeof control.on === 'function') {
            control.on('select', onPicked);
            control.on('pick', onPicked);
        }
        if (control && typeof control.addEventListener === 'function') {
            control.addEventListener('select', onPicked);
            control.addEventListener('pick', onPicked);
        }
        if (map && typeof map.on === 'function') {
            map.on('geocoder:select', function(ev) {
                onPicked(ev);
            });
        }
    }

    function setupLeafletBasemapWithFallback(options) {
        var opts = options || {};
        var map = opts.map;
        var L = opts.L;
        var maptilerKey = String(opts.maptilerKey || '').trim();
        var loadMapTilerStack = typeof opts.loadMapTilerStack === 'function' ? opts.loadMapTilerStack : null;
        var initGeocodingControl = typeof opts.initGeocodingControl === 'function' ? opts.initGeocodingControl : function() {};
        var setMapStatus = typeof opts.setMapStatus === 'function' ? opts.setMapStatus : function() {};
        var onMapTilerFallback = typeof opts.onMapTilerFallback === 'function' ? opts.onMapTilerFallback : function() {};
        var mapMaptilerFallbackText = String(opts.mapMaptilerFallbackText || '');
        var addMapTilerLayer = typeof opts.addMapTilerLayer === 'function'
            ? opts.addMapTilerLayer
            : function() {
                if (!L || !L.maptiler || typeof L.maptiler.maptilerLayer !== 'function') {
                    throw new Error('maptiler_leaflet_not_ready');
                }
                var layerOpts = { apiKey: maptilerKey };
                if (L.maptiler.MapStyle && L.maptiler.MapStyle.STREETS !== undefined) {
                    layerOpts.style = L.maptiler.MapStyle.STREETS;
                }
                L.maptiler.maptilerLayer(layerOpts).addTo(map);
            };
        if (!maptilerKey) {
            setMapStatus(mapMaptilerFallbackText, 'warning');
            return Promise.resolve('no_maptiler_key');
        }
        if (!loadMapTilerStack) {
            setMapStatus(mapMaptilerFallbackText, 'warning');
            initGeocodingControl(maptilerKey);
            return Promise.resolve('no_maptiler_loader');
        }

        return loadMapTilerStack()
            .then(function() {
                addMapTilerLayer();
                initGeocodingControl(maptilerKey);
                return 'maptiler';
            })
            .catch(function(err) {
                onMapTilerFallback(err);
                setMapStatus(mapMaptilerFallbackText, 'warning');
                initGeocodingControl(maptilerKey);
                return 'fallback';
            });
    }

    window.MapShared = {
        getMapConfig: getMapConfig,
        createAssetLoader: createAssetLoader,
        createAddressMapHelpers: createAddressMapHelpers,
        createMapStatusManager: createMapStatusManager,
        reverseGeocodeWithFallback: reverseGeocodeWithFallback,
        autoSelectHongKongRegion: autoSelectHongKongRegion,
        bindGeocodingControlPick: bindGeocodingControlPick,
        setupLeafletBasemapWithFallback: setupLeafletBasemapWithFallback,
    };
})();
