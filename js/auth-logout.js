(function () {
    function goPhpLogout() {
        var u = typeof window.LOGOUT_URL === 'string' ? window.LOGOUT_URL : '';
        window.location.href = u || '/logout';
    }

    function handleLogoutClick(e) {
        if (e && e.preventDefault) {
            e.preventDefault();
        }
        var cfg = window.FIREBASE_WEB_CONFIG;
        if (typeof firebase === 'undefined' || !cfg) {
            goPhpLogout();
            return false;
        }
        try {
            if (!firebase.apps || firebase.apps.length === 0) {
                firebase.initializeApp(cfg);
            }
        } catch (ignore) {
            goPhpLogout();
            return false;
        }
        firebase
            .auth()
            .signOut()
            .then(goPhpLogout)
            .catch(goPhpLogout);
        return false;
    }

    window.handleLogout = handleLogoutClick;

    document.querySelectorAll('a[data-logout="1"]').forEach(function (a) {
        a.addEventListener('click', handleLogoutClick);
    });
})();
