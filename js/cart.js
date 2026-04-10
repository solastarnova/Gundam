const Cart = {
    async getCountFromServer() {
        const base = window.APP_BASE || '';
        try {
            const res = await fetch(base + 'api/cart/count');
            const data = await res.json();
            return (data && typeof data.count === 'number') ? data.count : 0;
        } catch (e) {
            return 0;
        }
    },

    async updateBadge() {
        const badge = document.getElementById('cart-count');
        if (!badge) return;
        if (window.isLoggedIn) {
            const total = await this.getCountFromServer();
            badge.textContent = total;
            badge.style.display = total > 0 ? 'block' : 'none';
        } else {
            badge.textContent = '0';
            badge.style.display = 'none';
        }
    }
};

async function addToCart(id, name, price, img, qty = 1) {
    var J = window.APP_JS_I18N || {};
    if (!window.isLoggedIn) {
        alert(J.cartLoginRequired || '');
        window.location.href = (window.APP_BASE || '') + 'login?redirect=' + encodeURIComponent(window.location.pathname || '/');
        return;
    }
    const base = window.APP_BASE || '';
    const quantity = Math.max(1, parseInt(qty, 10) || 1);
    try {
        const fd = new FormData();
        fd.append('product_id', id);
        fd.append('quantity', quantity);
        const res = await fetch(base + 'api/cart/add', { method: 'POST', body: fd });
        const data = await res.json();
        if (data && data.success) {
            await Cart.updateBadge();
        } else {
            alert((data && data.message) ? data.message : (J.cartAddFailed || ''));
        }
    } catch (e) {
        alert(J.cartAddFailedRetry || '');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    Cart.updateBadge();
});
