<!-- AI chat (DeepSeek) -->
<div id="chatbox" class="chatbox-container ai-chat-window" aria-hidden="true">
    <div class="chatbox-header ai-chat-header">
        <h5 class="mb-0"><?= htmlspecialchars(__m('chatbox.title'), ENT_QUOTES, 'UTF-8') ?></h5>
        <button type="button" class="btn-ai-close" id="chatbox-close" aria-label="<?= htmlspecialchars(__m('chatbox.close_aria'), ENT_QUOTES, 'UTF-8') ?>"><span aria-hidden="true">&times;</span></button>
    </div>
    <div class="chatbox-messages" id="chatbox-messages">
        <div class="chat-message bot-message">
            <div class="message-content">
                <?= htmlspecialchars(__m('chatbox.welcome'), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
    </div>
    <div class="chatbox-input-container">
        <input type="text" id="chatbox-input" class="chatbox-input" placeholder="<?= htmlspecialchars(__m('chatbox.placeholder'), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" maxlength="2000">
        <button type="button" id="chatbox-send" class="chatbox-send-btn" aria-label="<?= htmlspecialchars(__m('chatbox.send_aria'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-send-fill" aria-hidden="true"></i>
        </button>
    </div>
    <div class="chatbox-footer">
        <button type="button" class="btn btn-link btn-sm text-muted" id="chatbox-clear"><?= htmlspecialchars(__m('chatbox.clear'), ENT_QUOTES, 'UTF-8') ?></button>
    </div>
</div>
