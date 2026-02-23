/**
 * 購物車邏輯（參考 Reference）：僅使用 DB（API），不讀寫 localStorage。
 * 未登入不可使用購物車，須先登入。
 */
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
    },

    showNotification(title, message) {
    }
};

async function addToCart(id, name, price, img, qty = 1) {
    if (!window.isLoggedIn) {
        alert('請先登入才能加入購物車');
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
            Cart.showNotification('已加入購物車', name);
            await Cart.updateBadge();
        } else {
            alert((data && data.message) ? data.message : '加入購物車失敗');
        }
    } catch (e) {
        alert('加入購物車失敗，請稍後再試');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    Cart.updateBadge();
});
