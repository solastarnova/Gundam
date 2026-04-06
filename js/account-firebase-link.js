/**
 * 帳戶設定：已登入 Firebase 的用戶以 linkWithPopup 綁定其他社群提供者。
 */
document.addEventListener('DOMContentLoaded', function () {
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

    function bindLinkButton(buttonId, createProvider, providerLabel) {
        var el = document.getElementById(buttonId);
        if (!el) {
            return;
        }
        var defaultHtml = el.innerHTML;

        el.addEventListener('click', function () {
            var user = firebase.auth().currentUser;
            if (!user) {
                window.alert('請先登入！請從登入頁使用 Google／GitHub／Facebook 成功登入後，再回到此頁綁定。');
                return;
            }

            el.disabled = true;
            el.innerHTML =
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 處理中...';

            var provider = createProvider();
            user
                .linkWithPopup(provider)
                .then(function () {
                    window.alert(providerLabel + ' 帳號綁定成功！');
                    window.location.reload();
                })
                .catch(function (error) {
                    el.disabled = false;
                    el.innerHTML = defaultHtml;

                    if (!error) {
                        return;
                    }
                    if (error.code === 'auth/popup-closed-by-user' || error.code === 'auth/cancelled-popup-request') {
                        return;
                    }
                    if (error.code === 'auth/popup-blocked') {
                        window.alert('請允許瀏覽器的彈出視窗，以便完成授權。');
                        return;
                    }
                    if (error.code === 'auth/provider-already-linked') {
                        window.alert('此登入方式已綁定。');
                        refreshSocialLinkUi();
                        return;
                    }
                    if (error.code === 'auth/credential-already-in-use') {
                        window.alert(
                            '錯誤：此 ' +
                                providerLabel +
                                ' 帳號已被其他商城用戶綁定，請換一個帳號嘗試。'
                        );
                        return;
                    }
                    if (error.code === 'auth/email-already-in-use') {
                        window.alert('此電郵已用於其他帳號，請使用帳號連結流程處理。');
                        return;
                    }
                    console.error('Link Error:', error);
                    window.alert('綁定失敗：' + (error.message || '未知錯誤'));
                });
        });
    }

    bindLinkButton(
        'btn-link-google',
        function () {
            return new firebase.auth.GoogleAuthProvider();
        },
        'Google'
    );
    bindLinkButton(
        'btn-link-github',
        function () {
            return new firebase.auth.GithubAuthProvider();
        },
        'GitHub'
    );
    if (window.FIREBASE_ENABLE_FACEBOOK) {
        bindLinkButton(
            'btn-link-facebook',
            function () {
                return new firebase.auth.FacebookAuthProvider();
            },
            'Facebook'
        );
    }
});
