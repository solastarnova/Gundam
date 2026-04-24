/**
 * Account settings: link additional OAuth providers via linkWithPopup.
 */
document.addEventListener('DOMContentLoaded', function () {
    var L = window.FIREBASE_LINK_JS || {};
    if (!window.FIREBASE_SOCIAL_LINKING) {
        return;
    }
    var cfg = window.FIREBASE_WEB_CONFIG;
    if (typeof firebase === 'undefined' || !cfg) {
        return;
    }
    try {
        if (!firebase.apps || firebase.apps.length === 0) {
            firebase.initializeApp(cfg);
        }
    } catch (e) {
        return;
    }

    function providerLinked(user, providerId) {
        if (!user || !user.providerData) {
            return false;
        }
        for (var i = 0; i < user.providerData.length; i++) {
            if (user.providerData[i].providerId === providerId) {
                return true;
            }
        }
        return false;
    }

    function setRowVisibility(providerId, badgeId, btnId) {
        var user = firebase.auth().currentUser;
        var linked = providerLinked(user, providerId);
        var badge = document.getElementById(badgeId);
        var btn = document.getElementById(btnId);
        if (!badge || !btn) {
            return;
        }
        if (linked) {
            badge.classList.remove('d-none');
            btn.classList.add('d-none');
        } else {
            badge.classList.add('d-none');
            btn.classList.remove('d-none');
        }
    }

    function refreshSocialLinkUi() {
        setRowVisibility('google.com', 'badge-google-linked', 'btn-link-google');
        setRowVisibility('github.com', 'badge-github-linked', 'btn-link-github');
        if (window.FIREBASE_ENABLE_FACEBOOK) {
            setRowVisibility('facebook.com', 'badge-facebook-linked', 'btn-link-facebook');
        }
    }

    firebase.auth().onAuthStateChanged(refreshSocialLinkUi);

    function getProviderLabel(providerId) {
        var names = L.providerNames || {};
        if (providerId === 'google.com') {
            return names.google || 'Google';
        }
        if (providerId === 'github.com') {
            return names.github || 'GitHub';
        }
        if (providerId === 'facebook.com') {
            return names.facebook || 'Facebook';
        }
        return names.oauth || '第三方';
    }

    function handleLinkError(error, providerLabel, restoreButton, defaultHtml) {
        if (restoreButton) {
            restoreButton.disabled = false;
            restoreButton.innerHTML = defaultHtml;
        }
        if (!error) {
            return;
        }
        if (error.code === 'auth/popup-closed-by-user' || error.code === 'auth/cancelled-popup-request') {
            return;
        }
        if (error.code === 'auth/popup-blocked') {
            window.alert(L.popupBlocked || '');
            return;
        }
        if (error.code === 'auth/provider-already-linked') {
            window.alert(L.alreadyLinked || '');
            refreshSocialLinkUi();
            return;
        }
        if (error.code === 'auth/credential-already-in-use') {
            window.alert((L.credentialInUse || '').replace('%s', providerLabel));
            return;
        }
        if (error.code === 'auth/email-already-in-use') {
            window.alert(L.emailInUse || '');
            return;
        }
        console.error('Link Error:', error);
        window.alert(
            (L.bindFailedPrefix || '') + (error.message || L.unknownError || '')
        );
    }

    function bindLinkButton(buttonId, createProvider, providerId) {
        var el = document.getElementById(buttonId);
        if (!el) {
            return;
        }
        var defaultHtml = el.innerHTML;
        var providerLabel = getProviderLabel(providerId);

        el.addEventListener('click', function () {
            var user = firebase.auth().currentUser;

            el.disabled = true;
            el.innerHTML =
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' +
                (L.processing || '');

            var provider = createProvider();

            if (!user) {
                firebase
                    .auth()
                    .signInWithPopup(provider)
                    .then(function () {
                        window.alert((L.bindOk || '').replace('%s', providerLabel));
                        window.location.reload();
                    })
                    .catch(function (error) {
                        handleLinkError(error, providerLabel, el, defaultHtml);
                    });
                return;
            }

            user
                .linkWithPopup(provider)
                .then(function () {
                    window.alert((L.bindOk || '').replace('%s', providerLabel));
                    window.location.reload();
                })
                .catch(function (error) {
                    handleLinkError(error, providerLabel, el, defaultHtml);
                });
        });
    }

    bindLinkButton(
        'btn-link-google',
        function () {
            return new firebase.auth.GoogleAuthProvider();
        },
        'google.com'
    );
    bindLinkButton(
        'btn-link-github',
        function () {
            return new firebase.auth.GithubAuthProvider();
        },
        'github.com'
    );
    if (window.FIREBASE_ENABLE_FACEBOOK) {
        bindLinkButton(
            'btn-link-facebook',
            function () {
                return new firebase.auth.FacebookAuthProvider();
            },
            'facebook.com'
        );
    }
});
