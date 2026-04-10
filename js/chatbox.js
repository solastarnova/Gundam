/**
 * AI chat: POST /api/chat, /api/chat/clear
 */
(function () {
    function C() {
        return window.CHATBOX_JS || {};
    }
    function apiBase() {
        var raw = typeof window.APP_BASE === 'string' ? window.APP_BASE : '/';
        if (raw === '/' || raw === '') {
            return '';
        }
        return raw.replace(/\/+$/, '');
    }

    function ChatBox() {
        this.chatbox = document.getElementById('chatbox');
        this.messagesContainer = document.getElementById('chatbox-messages');
        this.input = document.getElementById('chatbox-input');
        this.sendBtn = document.getElementById('chatbox-send');
        this.closeBtn = document.getElementById('chatbox-close');
        this.clearBtn = document.getElementById('chatbox-clear');
        this.toggleBtn = document.getElementById('ai-chat-trigger');
        this.baseUrl = apiBase();
        this.isOpen = false;
        this.isLoading = false;
        this.init();
    }

    ChatBox.prototype.init = function () {
        var self = this;
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (window.navigator && typeof window.navigator.vibrate === 'function') {
                    window.navigator.vibrate(20);
                }
                var btn = this;
                btn.style.borderColor = '#dc3545';
                btn.style.color = '#dc3545';
                var label = btn.querySelector('.fab-text');
                var icon = btn.querySelector('.fa-robot');
                if (label) {
                    label.style.color = '#dc3545';
                }
                if (icon) {
                    icon.style.color = '#dc3545';
                }
                window.setTimeout(function () {
                    btn.style.borderColor = '';
                    btn.style.color = '';
                    if (label) {
                        label.style.color = '';
                    }
                    if (icon) {
                        icon.style.color = '';
                    }
                }, 200);
                self.toggle();
            });
        }
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', function () {
                self.close();
            });
        }
        if (this.sendBtn) {
            this.sendBtn.addEventListener('click', function () {
                self.sendMessage();
            });
        }
        if (this.clearBtn) {
            this.clearBtn.addEventListener('click', function () {
                self.clearHistory();
            });
        }
        if (this.input) {
            this.input.addEventListener('keypress', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });
        }
    };

    ChatBox.prototype.toggle = function () {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    };

    ChatBox.prototype.open = function () {
        if (this.chatbox) {
            this.chatbox.classList.add('show');
            this.chatbox.setAttribute('aria-hidden', 'false');
            this.isOpen = true;
            if (this.input) {
                this.input.focus();
            }
            this.scrollToBottom();
        }
    };

    ChatBox.prototype.close = function () {
        if (this.chatbox) {
            this.chatbox.classList.remove('show');
            this.chatbox.setAttribute('aria-hidden', 'true');
            this.isOpen = false;
        }
    };

    ChatBox.prototype.sendMessage = function () {
        var self = this;
        var message = this.input && this.input.value.trim();
        if (!message || this.isLoading) {
            return;
        }
        this.addMessage(message, 'user');
        this.input.value = '';
        this.input.disabled = true;
        this.sendBtn.disabled = true;
        this.isLoading = true;
        this.showLoading();

        var url = (this.baseUrl ? this.baseUrl : '') + '/api/chat';
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message }),
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                self.hideLoading();
                var data = result.data || {};
                if (data.success && data.response) {
                    self.addMessage(data.response, 'bot');
                } else {
                    self.addMessage(data.error || C().errorGeneric || '', 'bot');
                }
            })
            .catch(function () {
                self.hideLoading();
                self.addMessage(C().networkError || '', 'bot');
            })
            .finally(function () {
                if (self.input) {
                    self.input.disabled = false;
                }
                if (self.sendBtn) {
                    self.sendBtn.disabled = false;
                }
                self.isLoading = false;
                if (self.input) {
                    self.input.focus();
                }
            });
    };

    ChatBox.prototype.addMessage = function (text, type) {
        if (!this.messagesContainer) {
            return;
        }
        var messageDiv = document.createElement('div');
        messageDiv.className = 'chat-message ' + type + '-message';
        var contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.textContent = text;
        messageDiv.appendChild(contentDiv);
        this.messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
    };

    ChatBox.prototype.showLoading = function () {
        if (!this.messagesContainer) {
            return;
        }
        var loadingDiv = document.createElement('div');
        loadingDiv.className = 'chat-message loading';
        loadingDiv.id = 'chat-loading';
        var contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.innerHTML =
            '<div class="loading-dots"><span></span><span></span><span></span></div>';
        loadingDiv.appendChild(contentDiv);
        this.messagesContainer.appendChild(loadingDiv);
        this.scrollToBottom();
    };

    ChatBox.prototype.hideLoading = function () {
        var loading = document.getElementById('chat-loading');
        if (loading) {
            loading.remove();
        }
    };

    ChatBox.prototype.clearHistory = function () {
        var self = this;
        if (!confirm(C().confirmClear || '')) {
            return;
        }
        var url = (this.baseUrl ? this.baseUrl : '') + '/api/chat/clear';
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (data.success && self.messagesContainer) {
                    self.messagesContainer.innerHTML =
                        '<div class="chat-message bot-message">' +
                        '<div class="message-content">' +
                        String(C().welcomeHtml || '').replace(/</g, '&lt;') +
                        '</div>' +
                        '</div>';
                }
            })
            .catch(function () {
                window.alert(C().clearFailed || '');
            });
    };

    ChatBox.prototype.scrollToBottom = function () {
        if (this.messagesContainer) {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('chatbox')) {
            window.chatBox = new ChatBox();
        }
    });
})();
