(function() {
    'use strict';

    const baseUrl = (window.APP_BASE || '').replace(/\/$/, '') + '/';
    const stripePublishableKey = window.STRIPE_PUBLISHABLE_KEY || '';
    const paypalClientId = window.PAYPAL_CLIENT_ID || '';
    const shippingConfig = window.SHIPPING_CONFIG || { express_fee: 80, standard_fee: 50, free_threshold: 500 };

    let cartItems = [];
    let stripe = null;
    let cardElement = null;
    let paymentIntentClientSecret = null;
    let paymentIntentId = null;
    let paypalButtons = null;

    /** 將地址物件格式化成單行字串（與後端 AddressModel::formatAddressAsOneLine 一致） */
    function formatAddressOneLine(addr) {
        if (!addr) return '';
        var parts = [
            addr.region || '',
            addr.district || '',
            addr.street || '',
            addr.village_estate || '',
            addr.building || '',
            (addr.floor && String(addr.floor).trim() !== '' ? String(addr.floor).trim() + '樓' : ''),
            addr.unit || ''
        ].filter(function(v) { return String(v).trim() !== ''; });
        return parts.join(' ');
    }

    /** 取得目前選擇的配送地址單行字串 */
    function getSelectedShippingAddress() {
        var radio = document.querySelector('input[name="checkout_address"]:checked');
        if (radio && radio.getAttribute('data-address-one-line')) {
            return radio.getAttribute('data-address-one-line').trim();
        }
        return (window.DEFAULT_SHIPPING_ADDRESS || '香港').trim();
    }

    function loadAddresses() {
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
                list.forEach(function(addr, index) {
                    var oneLine = formatAddressOneLine(addr);
                    var id = 'addr-' + (addr.id || index);
                    var isDefault = addr.is_default === 1 || addr.is_default === true;
                    var card = document.createElement('div');
                    card.className = 'col-12';
                    card.innerHTML = '<div class="form-check border rounded p-3 checkout-address-card">' +
                        '<input class="form-check-input" type="radio" name="checkout_address" id="' + id + '" value="' + (addr.id || '') + '" data-address-one-line="' + (oneLine.replace(/"/g, '&quot;')) + '"' + (index === 0 || isDefault ? ' checked' : '') + '>' +
                        '<label class="form-check-label w-100 ms-2" for="' + id + '">' +
                        (isDefault ? '<span class="badge bg-danger me-2">預設</span>' : '') +
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

    function getShippingMethod() {
        const el = document.getElementById('shipping');
        return el ? el.value : 'standard';
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
            shippingFee = parseFloat(shippingConfig.express_fee) || 80;
        } else {
            const threshold = parseFloat(shippingConfig.free_threshold) || 500;
            shippingFee = subtotal >= threshold ? 0 : (parseFloat(shippingConfig.standard_fee) || 50);
        }
        return { subtotal, shippingFee, total: subtotal + shippingFee };
    }

    function updateSummary() {
        const t = calculateTotals();
        const subEl = document.getElementById('subtotal');
        const feeEl = document.getElementById('shippingFee');
        const totalEl = document.getElementById('final-total');
        if (subEl) subEl.textContent = 'HK$' + t.subtotal.toFixed(2);
        if (feeEl) feeEl.textContent = 'HK$' + t.shippingFee.toFixed(2);
        if (totalEl) totalEl.textContent = 'HK$' + t.total.toFixed(2);
        if (typeof updateCartBadge === 'function') updateCartBadge();
    }

    function renderOrderItems() {
        var container = document.getElementById('orderItems');
        if (!container) return;
        if (!cartItems || cartItems.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3">購物車是空的，請先加入商品。</p>';
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
            html += '<div class="flex-grow-1"><div class="fw-bold">' + name + '</div><small class="text-muted">HK$' + price.toFixed(0) + '/件</small></div>';
            html += '</a>';
            html += '<div class="d-flex align-items-center quantity-control me-2">';
            html += '<button type="button" class="btn btn-sm btn-outline-secondary checkout-qty-minus" data-cart-id="' + cartId + '" aria-label="減少">−</button>';
            html += '<span class="checkout-qty-display mx-2 fw-bold" data-cart-id="' + cartId + '">' + qty + '</span>';
            html += '<button type="button" class="btn btn-sm btn-outline-secondary checkout-qty-plus" data-cart-id="' + cartId + '" aria-label="增加">+</button>';
            html += '</div>';
            html += '<span class="fw-bold text-primary me-2" style="min-width:4rem;">HK$' + subtotal + '</span>';
            html += '<button type="button" class="btn btn-sm btn-link text-danger p-0 border-0 checkout-item-remove" data-cart-id="' + cartId + '" title="移除">×</button>';
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
                if (confirm('確定要移除此商品嗎？')) removeCheckoutItem(cartId);
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
        }).catch(function() { alert('更新數量失敗'); });
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
        }).catch(function() { alert('移除失敗'); });
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
        if (!stripePublishableKey || typeof Stripe === 'undefined') return;
        try {
            stripe = Stripe(stripePublishableKey);
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
        } catch (e) { console.error('Stripe init:', e); }
    }

    function createPaymentIntent() {
        return fetch(baseUrl + 'api/payment/create-intent', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ shipping_method: getShippingMethod() })
        }).then(function(r) { return r.json(); });
    }

    function confirmOrder(payload) {
        payload.shipping_address = payload.shipping_address || getSelectedShippingAddress();
        payload.shipping_method = getShippingMethod();
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
            btn.textContent = loading ? '處理中...' : '確認訂單';
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
        if (value === 'paypal' && paypalContainer && !paypalButtons && paypalClientId && typeof paypal !== 'undefined') {
            initPayPal();
        }
    }

    function handleStripeConfirm() {
        if (!stripe || !cardElement) {
            showError('請選擇信用卡付款並填寫卡號');
            return;
        }
        if (!paymentIntentClientSecret) {
            showError('請先取得支付授權');
            return;
        }
        setConfirmLoading(true);
        showError('');
        stripe.confirmCardPayment(paymentIntentClientSecret, {
            payment_method: { card: cardElement, billing_details: { address: { country: 'HK' } } }
        }).then(function(result) {
            if (result.error) {
                setConfirmLoading(false);
                showError(result.error.message || '付款失敗');
                return;
            }
            if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
                confirmOrder({
                    payment_intent_id: result.paymentIntent.id,
                    payment_method: 'credit_card'
                }).then(function(data) {
                    setConfirmLoading(false);
                    if (data.success) {
                        alert('訂單已確認，訂單編號：' + (data.order_number || data.order_id));
                        window.location.href = baseUrl + 'account/orders';
                    } else {
                        showError(data.message || '訂單確認失敗');
                    }
                }).catch(function() {
                    setConfirmLoading(false);
                    showError('訂單確認失敗，請稍後再試');
                });
            } else {
                setConfirmLoading(false);
                showError('付款尚未完成');
            }
        }).catch(function(err) {
            setConfirmLoading(false);
            showError(err.message || '付款失敗');
        });
    }

    function handleConfirmClick() {
        var method = document.querySelector('input[name="paymentMethod"]:checked');
        var value = method ? method.value : 'stripe';
        if (value === 'paypal') return;

        if (!cartItems.length) {
            loadCart();
            setTimeout(function() {
                if (!cartItems.length) alert('購物車是空的');
            }, 500);
            return;
        }

        showError('');
        setConfirmLoading(true);
        createPaymentIntent().then(function(data) {
            if (!data.success) {
                setConfirmLoading(false);
                showError(data.message || '無法建立支付');
                return;
            }
            paymentIntentClientSecret = data.client_secret;
            paymentIntentId = data.payment_intent_id;
            handleStripeConfirm();
        }).catch(function() {
            setConfirmLoading(false);
            showError('無法建立支付，請稍後再試');
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
                        body: new URLSearchParams({ shipping_method: getShippingMethod() })
                    }).then(function(r) { return r.json(); }).then(function(res) {
                        if (!res.success) throw new Error(res.message || '建立 PayPal 訂單失敗');
                        return actions.order.create({
                            purchase_units: [{ amount: { value: res.amount, currency_code: res.currency } }]
                        });
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        return confirmOrder({
                            paypal_order_id: details.id,
                            payment_method: 'paypal'
                        });
                    }).then(function(data) {
                        if (data.success) {
                            alert('訂單已確認，訂單編號：' + (data.order_number || data.order_id));
                            window.location.href = baseUrl + 'account/orders';
                        } else {
                            alert(data.message || '訂單確認失敗');
                        }
                    }).catch(function(err) {
                        alert(err.message || 'PayPal 處理失敗');
                    });
                },
                onError: function(err) { alert('PayPal 錯誤，請稍後再試'); }
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
            if (stripePublishableKey) initStripe();
            if (paypalClientId && typeof paypal !== 'undefined') initPayPal();
            loadCart();
            loadAddresses();
            togglePaymentUI();
            bindCheckoutItemEvents();
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
