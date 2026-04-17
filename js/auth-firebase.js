(function () {
    var F = window.FIREBASE_AUTH_JS || {};
    var cfg = window.FIREBASE_WEB_CONFIG;
    var endpoint = window.FIREBASE_AUTH_ENDPOINT;
    var intent = window.FIREBASE_AUTH_INTENT;
    if (!cfg || !endpoint || typeof firebase === 'undefined') {
        return;
    }

    firebase.initializeApp(cfg);
    // Force browser-local auth persistence explicitly.
    // This keeps login state across tabs/browser restarts unless user logs out.
    firebase
        .auth()
        .setPersistence(firebase.auth.Auth.Persistence.LOCAL)
        .catch(function () {
            // Keep legacy behavior even if persistence API fails in rare environments.
        });

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
     * fetchSignInMethodsForEmail returns full provider ids (e.g. google.com), not short names.
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
     * GitHub account-exists: resolve via fetchSignInMethodsForEmail, popup existing OAuth, then link.
     */
    function handleGithubAccountExistsWithDifferentCredential(error) {
        var pendingCred = error.credential;
        if (!pendingCred && typeof firebase.auth.GithubAuthProvider.credentialFromError === 'function') {
            pendingCred = firebase.auth.GithubAuthProvider.credentialFromError(error);
        }
        var email = error.email || (error.customData && error.customData.email) || '';
        if (!pendingCred || !email) {
            window.alert((error && error.message) || F.credEmailMissing || '');
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
                        (F.emailBindsProvider || '').replace('%s', providerName)
                    );
                    return firebase.auth().signInWithPopup(providerToLink);
                }

                if (methods.includes('facebook.com') && !window.FIREBASE_ENABLE_FACEBOOK) {
                    window.alert(F.fbDisabled || '');
                    return Promise.reject(null);
                }
                if (methods.includes('password')) {
                    window.alert(F.passwordThenGithub || '');
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
                window.alert(F.githubMergeOk || '');
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
                    window.alert(F.githubAutoUnsupported || '');
                    return;
                }
                if (
                    linkError.code === 'auth/popup-closed-by-user' ||
                    linkError.code === 'auth/cancelled-popup-request'
                ) {
                    return;
                }
                if (linkError.code === 'auth/credential-already-in-use') {
                    window.alert(F.githubInUse || '');
                    return;
                }
                window.alert(linkError.message || F.linkFailed || '');
            });
    }

    /**
     * Google/Facebook account-exists: pick existing OAuth from fetchSignInMethodsForEmail, then link.
     */
    function handleAccountExistsWithDifferentCredential(error, AttemptedProviderClass) {
        var pendingCred =
            AttemptedProviderClass && typeof AttemptedProviderClass.credentialFromError === 'function'
                ? AttemptedProviderClass.credentialFromError(error)
                : null;
        if (!pendingCred) {
            window.alert((error && error.message) || F.loginFailed || '');
            return;
        }
        var email =
            (error.customData && error.customData.email) || error.email || '';
        if (!email) {
            window.alert(F.emailMissingLink || '');
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
                        window.alert(F.passwordThenOauth || '');
                    } else {
                        window.alert(F.signInFirst || '');
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
                window.alert(F.linkOk || '');
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
                    window.alert(F.oauthInUse || '');
                    return;
                }
                window.alert((e.message) || F.linkAccountFailed || '');
            });
    }

    /**
     * @param {Promise<firebase.auth.UserCredential>} promise
     * @param {typeof firebase.auth.GoogleAuthProvider} [attemptedProviderClass] Google/Facebook account-exists flow
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
                var msg = err.message || F.loginFailed || '';
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
                    window.alert(err.message || F.loginFailed || '');
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
