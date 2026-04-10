(function() {
    'use strict';

    const baseUrl = (window.APP_BASE || '').replace(/\/$/, '') + '/';
    const stripePublishableKey = window.STRIPE_PUBLISHABLE_KEY || '';
    const paypalClientId = window.PAYPAL_CLIENT_ID || '';
    const stripeSdkUrl = window.STRIPE_SDK_URL || 'https://js.stripe.com/v3/';
    const paypalSdkUrl = window.PAYPAL_SDK_URL || '';
    const shippingConfig = window.SHIPPING_CONFIG || { express_fee: 0, standard_fee: 0, free_threshold: 0 };
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
                        '<input class="form-check-input" type="radio" name="checkout_address" id="' + id + '" value="' + (addr.id || '') + '" data-address-one-line="' + (oneLine.replace(/"/g, '&quot;')) + '"' + (shouldCheck ? ' checked' : '') + '>' +
                        '<label class="form-check-label w-100 ms-2" for="' + id + '">' +
                        (isDefault ? '<span class="badge bg-danger me-2">' + String(CI.badgeDefault || '').replace(/</g, '&lt;') + '</span>' : '') +
                        (addr.address_label ? '<strong>' + String(addr.address_label).replace(/</g, '&lt;') + '</strong><br>' : '') +
                        '<span class="text-muted small">' + String(addr.recipient_name || '').replace(/</g, '&lt;') + '　' + String(addr.phone || '').replace(/</g, '&lt;') + '</span><br>' +
                        '<span class="small">' + String(oneLine).replace(/</g, '&lt;') + '</span>' +
                        '</label></div>';
                    containerEl.appendChild(card);
                });
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
        if (!data.recipient_name || !data.phone || !data.region || !data.district || !data.building || !data.unit) {
            alert(CI.alertRequired || '');
            return;
        }
        if (!data.village_estate && !data.street) {
            alert(CI.alertVillageOrStreet || '');
            return;
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
        } catch (err) {
            console.error('saveCheckoutAddress:', err);
            alert(err.message || CI.alertSaveAddressFailed || '');
        }
    };

    function getShippingMethod() {
        const el = document.getElementById('shipping');
        return el ? el.value : 'standard';
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
        const method = getShippingMethod();
        let shippingFee = 0;
        if (method === 'express') {
            shippingFee = parseFloat(shippingConfig.express_fee) || 0;
        } else {
            const threshold = parseFloat(shippingConfig.free_threshold) || 0;
            shippingFee = subtotal >= threshold ? 0 : (parseFloat(shippingConfig.standard_fee) || 0);
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
        if (feeEl) feeEl.textContent = formatMoney(t.shippingFee);
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
            html += '<span class="fw-bold text-primary me-2" style="min-width:4rem;">' + formatMoney(subtotal) + '</span>';
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
        return fetch(baseUrl + 'api/payment/create-intent', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                shipping_method: getShippingMethod(),
                use_wallet: isUseWalletEnabled() ? '1' : '0',
                use_points: (document.getElementById('usePoints') && document.getElementById('usePoints').checked) ? '1' : '0'
            })
        }).then(function(r) { return r.json(); });
    }

    function confirmOrder(payload) {
        payload.shipping_address = payload.shipping_address || getSelectedShippingAddress();
        payload.shipping_method = getShippingMethod();
        payload.use_wallet = isUseWalletEnabled() ? '1' : '0';
        payload.use_points = (document.getElementById('usePoints') && document.getElementById('usePoints').checked) ? '1' : '0';
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
        setConfirmLoading(true);
        showError('');
        var fd = new URLSearchParams();
        fd.append('shipping_method', getShippingMethod());
        fd.append('shipping_address', getSelectedShippingAddress());
        fd.append('use_wallet', '1');
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
        var method = document.querySelector('input[name="paymentMethod"]:checked');
        var value = method ? method.value : 'stripe';
        if (value === 'paypal') return;

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
                    return fetch(baseUrl + 'api/payment/create-paypal-order', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            shipping_method: getShippingMethod(),
                            use_wallet: isUseWalletEnabled() ? '1' : '0'
                        })
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
                        var payload = { paypal_order_id: details.id, payment_method: 'paypal' };
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
    var shippingEl = document.getElementById('shipping');
    if (shippingEl) shippingEl.addEventListener('change', updateSummary);
    var walletToggleEl = document.getElementById('useWalletBalance');
    if (walletToggleEl) walletToggleEl.addEventListener('change', updateSummary);
    var pointsToggleEl = document.getElementById('usePoints');
    if (pointsToggleEl) pointsToggleEl.addEventListener('change', updateSummary);

    var confirmBtn = document.getElementById('confirmOrderBtn');
    if (confirmBtn) confirmBtn.addEventListener('click', handleConfirmClick);

    function init() {
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

            var useNewAddressBtn = document.getElementById('useNewAddress');
            if (useNewAddressBtn) {
                useNewAddressBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (typeof window.openAddAddressModal === 'function') {
                        window.openAddAddressModal();
                    }
                });
            }
            var openEmptyBtn = document.getElementById('openAddAddressFromEmpty');
            if (openEmptyBtn) {
                openEmptyBtn.addEventListener('click', function() {
                    if (typeof window.openAddAddressModal === 'function') {
                        window.openAddAddressModal();
                    }
                });
            }
            var saveAddrBtn = document.getElementById('saveCheckoutAddressBtn');
            if (saveAddrBtn) {
                saveAddrBtn.addEventListener('click', function() {
                    if (typeof window.saveCheckoutAddress === 'function') {
                        window.saveCheckoutAddress();
                    }
                });
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
