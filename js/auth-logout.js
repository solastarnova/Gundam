(function () {
    var authStateWatcherBound = false;
    var suppressAutoLogout = false;

    function goPhpLogout() {
        var u = typeof window.LOGOUT_URL === 'string' ? window.LOGOUT_URL : '';
        window.location.href = u || '/logout';
    }

    function ensureFirebaseApp(cfg) {
        try {
            if (!firebase.apps || firebase.apps.length === 0) {
                firebase.initializeApp(cfg);
            }
            return true;
        } catch (ignore) {
            return false;
        }
    }

    function bindAuthStateWatcher() {
        var cfg = window.FIREBASE_WEB_CONFIG;
        var loginType = typeof window.APP_LOGIN_TYPE === 'string' ? window.APP_LOGIN_TYPE : '';
        if (loginType !== 'firebase') {
            return;
        }
        if (typeof firebase === 'undefined' || !cfg || authStateWatcherBound) {
            return;
        }
        if (!ensureFirebaseApp(cfg)) {
            return;
        }
        authStateWatcherBound = true;
        firebase.auth().onAuthStateChanged(function (user) {
            if (suppressAutoLogout) {
                return;
            }
            if (!user) {
                goPhpLogout();
            }
        });
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
        if (!ensureFirebaseApp(cfg)) {
            goPhpLogout();
            return false;
        }
        suppressAutoLogout = true;
        firebase
            .auth()
            .signOut()
            .then(function () {
                goPhpLogout();
            })
            .catch(function () {
                goPhpLogout();
            });
        return false;
    }

    window.handleLogout = handleLogoutClick;

    document.querySelectorAll('a[data-logout="1"]').forEach(function (a) {
        a.addEventListener('click', handleLogoutClick);
    });

    bindAuthStateWatcher();
})();
