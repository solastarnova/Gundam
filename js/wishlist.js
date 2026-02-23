/**
 * 喜愛清單：商品詳情頁收藏按鈕用
 * 依賴 api/wishlist/check、api/wishlist/toggle，與 window.APP_BASE、window.isLoggedIn
 */
(function () {
    var base = function () { return window.APP_BASE || ''; };

    window.isInWishlistAsync = function (productId) {
        if (!productId || !window.isLoggedIn) return Promise.resolve(false);
        return fetch(base() + 'api/wishlist/check?product_id=' + encodeURIComponent(productId))
            .then(function (res) { return res.json(); })
            .then(function (data) { return !!(data && data.isFavorite); })
            .catch(function () { return false; });
    };

    window.Wishlist = {
        toggle: function (opts) {
            var id = opts && (opts.id || opts.product_id);
            if (!id) return Promise.resolve(false);
            if (!window.isLoggedIn) {
                alert('請先登入才能收藏');
                window.location.href = (base() || '/') + 'login?redirect=' + encodeURIComponent(window.location.pathname || '/');
                return Promise.resolve(false);
            }
            var fd = new FormData();
            fd.append('product_id', id);
            return fetch(base() + 'api/wishlist/toggle', { method: 'POST', body: fd })
                .then(function (res) { return res.json(); })
                .then(function (data) { return !!(data && data.isFavorite); })
                .catch(function () { return false; });
        }
    };
})();
