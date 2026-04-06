(function () {
    var cfg = window.FIREBASE_WEB_CONFIG;
    var endpoint = window.FIREBASE_AUTH_ENDPOINT;
    var intent = window.FIREBASE_AUTH_INTENT;
    if (!cfg || !endpoint || typeof firebase === 'undefined') {
        return;
    }

    firebase.initializeApp(cfg);

    function postIdToken(idToken) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = endpoint;
        var t = document.createElement('input');
        t.name = 'id_token';
        t.type = 'hidden';
        t.value = idToken;
        form.appendChild(t);
        var i = document.createElement('input');
        i.name = 'intent';
        i.type = 'hidden';
        i.value = intent || 'signin';
        form.appendChild(i);
        var r = document.createElement('input');
        r.name = 'redirect';
        r.type = 'hidden';
        r.value = typeof window.FIREBASE_REDIRECT === 'string' ? window.FIREBASE_REDIRECT : '';
        form.appendChild(r);
        document.body.appendChild(form);
        form.submit();
    }

    function newProviderForMethodId(methodId) {
        if (methodId === 'google.com') {
            return new firebase.auth.GoogleAuthProvider();
        }
        if (methodId === 'github.com') {
            return new firebase.auth.GithubAuthProvider();
        }
        if (methodId === 'facebook.com') {
            return new firebase.auth.FacebookAuthProvider();
        }
        return null;
    }

    /**
     * Firebase fetchSignInMethodsForEmail 回傳的是完整登入方式 id，例如 "google.com"、"github.com"
     * （勿與 "google" 等短字串比對，否則條件永遠不成立）。
     * @param {string[]} methods
     */
    function pickExistingOAuthProvider(methods) {
        var m = Array.isArray(methods) ? methods : [];
        if (!m.length) {
            return null;
        }
        var order = ['google.com', 'github.com', 'facebook.com'];
        for (var o = 0; o < order.length; o++) {
            var mid = order[o];
            if (!m.includes(mid)) {
                continue;
            }
            if (mid === 'facebook.com' && !window.FIREBASE_ENABLE_FACEBOOK) {
                continue;
            }
            return newProviderForMethodId(mid);
        }
        return null;
    }

    /**
     * GitHub popup 觸發 account-exists：暫存 pendingCred，先 fetchSignInMethodsForEmail 再彈出對應 OAuth（Google > Facebook），最後 link。
     * 全程 signInWithPopup，不使用 redirect。
     */
    function handleGithubAccountExistsWithDifferentCredential(error) {
        var pendingCred = error.credential;
        if (!pendingCred && typeof firebase.auth.GithubAuthProvider.credentialFromError === 'function') {
            pendingCred = firebase.auth.GithubAuthProvider.credentialFromError(error);
        }
        var email = error.email || (error.customData && error.customData.email) || '';
        if (!pendingCred || !email) {
            window.alert((error && error.message) || '無法取得憑證或電郵，無法連結帳號。');
            return;
        }

        firebase
            .auth()
            .fetchSignInMethodsForEmail(email)
            .then(function (methods) {
                methods = Array.isArray(methods) ? methods : [];
                var providerToLink = null;
                var providerName = '';

                if (methods.includes('google.com')) {
                    providerToLink = new firebase.auth.GoogleAuthProvider();
                    providerToLink.setCustomParameters({ login_hint: email });
                    providerName = 'Google';
                } else if (methods.includes('facebook.com') && window.FIREBASE_ENABLE_FACEBOOK) {
                    providerToLink = new firebase.auth.FacebookAuthProvider();
                    providerName = 'Facebook';
                }

                if (providerToLink) {
                    window.alert(
                        '此 Email 已綁定 ' +
                            providerName +
                            '，請在接下來的視窗登入以連結 GitHub 帳號。'
                    );
                    return firebase.auth().signInWithPopup(providerToLink);
                }

                if (methods.includes('facebook.com') && !window.FIREBASE_ENABLE_FACEBOOK) {
                    window.alert(
                        '此電郵已綁定 Facebook，請於本站啟用 Facebook 登入（環境設定）後再試。'
                    );
                    return Promise.reject(null);
                }
                if (methods.includes('password')) {
                    window.alert(
                        '此電郵已使用密碼註冊，請先以電郵密碼登入，之後再在 Firebase／帳號設定中連結 GitHub。'
                    );
                    return Promise.reject(null);
                }

                var unsupported = new Error('PROVIDER_NOT_SUPPORTED');
                unsupported.code = 'PROVIDER_NOT_SUPPORTED';
                return Promise.reject(unsupported);
            })
            .then(function (result) {
                if (!result || !result.user) {
                    return null;
                }
                return result.user.linkWithCredential(pendingCred);
            })
            .then(function (linkResult) {
                if (!linkResult || !linkResult.user) {
                    return null;
                }
                window.alert('連結成功！現在你的 GitHub 帳號已合併。');
                return linkResult.user.getIdToken(true);
            })
            .then(function (token) {
                if (token) {
                    postIdToken(token);
                }
            })
            .catch(function (linkError) {
                if (linkError === null) {
                    return;
                }
                if (!linkError) {
                    return;
                }
                if (
                    linkError.code === 'PROVIDER_NOT_SUPPORTED' ||
                    linkError.message === 'PROVIDER_NOT_SUPPORTED'
                ) {
                    window.alert(
                        '此電郵的登入方式無法透過彈窗自動連結 GitHub，請改用已註冊方式登入或聯絡管理員。'
                    );
                    return;
                }
                if (
                    linkError.code === 'auth/popup-closed-by-user' ||
                    linkError.code === 'auth/cancelled-popup-request'
                ) {
                    return;
                }
                if (linkError.code === 'auth/credential-already-in-use') {
                    window.alert('這個 GitHub 帳號已經被另一個獨立的 Firebase 帳號綁定了。');
                    return;
                }
                window.alert(linkError.message || '連結失敗');
            });
    }

    /**
     * Google／Facebook popup：依 fetchSignInMethodsForEmail 選既有 OAuth，再 link。
     * @param {firebase.auth.AuthError} error
     * @param {typeof firebase.auth.GoogleAuthProvider} AttemptedProviderClass
     */
    function handleAccountExistsWithDifferentCredential(error, AttemptedProviderClass) {
        var pendingCred =
            AttemptedProviderClass && typeof AttemptedProviderClass.credentialFromError === 'function'
                ? AttemptedProviderClass.credentialFromError(error)
                : null;
        if (!pendingCred) {
            window.alert((error && error.message) || '登入失敗');
            return;
        }
        var email =
            (error.customData && error.customData.email) || error.email || '';
        if (!email) {
            window.alert('無法取得電郵，無法自動連結帳號。');
            return;
        }

        firebase
            .auth()
            .fetchSignInMethodsForEmail(email)
            .then(function (methods) {
                var m = Array.isArray(methods) ? methods : [];
                var provider = pickExistingOAuthProvider(m);
                if (!provider) {
                    if (m.includes('password')) {
                        window.alert(
                            '此電郵已使用密碼註冊，請先以電郵密碼登入，之後再在 Firebase／帳號設定中連結第三方。'
                        );
                    } else {
                        window.alert('請先使用已註冊的登入方式登入，再連結此第三方帳號。');
                    }
                    return null;
                }
                return firebase.auth().signInWithPopup(provider).then(function (result) {
                    return result.user.linkWithCredential(pendingCred);
                });
            })
            .then(function (userCred) {
                if (!userCred || !userCred.user) {
                    return null;
                }
                window.alert('帳號連結成功！以後可用此方式登入。');
                return userCred.user.getIdToken(true);
            })
            .then(function (token) {
                if (token) {
                    postIdToken(token);
                }
            })
            .catch(function (e) {
                if (!e) {
                    return;
                }
                if (e.code === 'auth/popup-closed-by-user' || e.code === 'auth/cancelled-popup-request') {
                    return;
                }
                if (e.code === 'auth/credential-already-in-use') {
                    window.alert('此第三方帳號已綁定其他使用者。');
                    return;
                }
                window.alert((e.message) || '連結帳號失敗');
            });
    }

    /**
     * @param {Promise<firebase.auth.UserCredential>} promise
     * @param {typeof firebase.auth.GoogleAuthProvider} [attemptedProviderClass] Google／Facebook account-exists 時使用
     */
    function runPopup(promise, attemptedProviderClass) {
        promise
            .then(function (cred) {
                return cred.user.getIdToken();
            })
            .then(postIdToken)
            .catch(function (err) {
                if (!err) {
                    return;
                }
                if (err.code === 'auth/popup-closed-by-user' || err.code === 'auth/cancelled-popup-request') {
                    return;
                }
                if (
                    err.code === 'auth/account-exists-with-different-credential' &&
                    attemptedProviderClass
                ) {
                    handleAccountExistsWithDifferentCredential(err, attemptedProviderClass);
                    return;
                }
                var msg = err.message || '登入失敗';
                window.alert(msg);
            });
    }

    var gBtn = document.getElementById('google-login');
    if (gBtn) {
        gBtn.addEventListener('click', function () {
            runPopup(
                firebase.auth().signInWithPopup(new firebase.auth.GoogleAuthProvider()),
                firebase.auth.GoogleAuthProvider
            );
        });
    }

    var ghBtn = document.getElementById('github-login');
    if (ghBtn) {
        ghBtn.addEventListener('click', function () {
            firebase
                .auth()
                .signInWithPopup(new firebase.auth.GithubAuthProvider())
                .then(function (cred) {
                    return cred.user.getIdToken();
                })
                .then(postIdToken)
                .catch(function (err) {
                    if (!err) {
                        return;
                    }
                    if (err.code === 'auth/popup-closed-by-user' || err.code === 'auth/cancelled-popup-request') {
                        return;
                    }

                    if (err.code === 'auth/account-exists-with-different-credential') {
                        handleGithubAccountExistsWithDifferentCredential(err);
                        return;
                    }
                    window.alert(err.message || '登入失敗');
                });
        });
    }

    var fBtn = document.getElementById('facebook-login');
    if (fBtn && window.FIREBASE_ENABLE_FACEBOOK) {
        fBtn.addEventListener('click', function () {
            runPopup(
                firebase.auth().signInWithPopup(new firebase.auth.FacebookAuthProvider()),
                firebase.auth.FacebookAuthProvider
            );
        });
    }
})();
