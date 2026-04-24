<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Services\ChatBotService;

/** 處理聊天機器人請求與會話 API。 */
class ChatController extends Controller
{
    private ChatBotService $chatBot;

    public function __construct()
    {
        parent::__construct();
        $this->chatBot = new ChatBotService();
    }

    public function chat(): void
    {
        $this->setupJsonApi();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json(['success' => false, 'error' => (string) Config::get('messages.chat.method_not_allowed')], 405);

            return;
        }

        $input = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $message = trim((string) ($input['message'] ?? ''));
        if ($message === '') {
            $this->json(['success' => false, 'error' => (string) Config::get('messages.chat.message_empty')], 400);

            return;
        }

        try {
            $response = $this->chatBot->ask($message);
            $this->json([
                'success' => true,
                'response' => $response,
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('Chat error: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => (string) Config::get('messages.chat.server_error')], 500);
        }
    }

    public function clearHistory(): void
    {
        $this->setupJsonApi();

        try {
            $this->chatBot->clearHistory();
            $this->json(['success' => true, 'message' => (string) Config::get('messages.chat.cleared')]);
        } catch (\Throwable $e) {
            error_log('Chat clearHistory: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => (string) Config::get('messages.chat.clear_failed')], 500);
        }
    }
}
