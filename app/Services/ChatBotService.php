<?php

namespace App\Services;

use App\Core\Config;
use App\Models\ProductModel;

/**
 * 提供 AI 聊天服務（DeepSeek），並以 Session 保存對話歷史。
 */
class ChatBotService
{
    private string $apiKey;

    private string $apiUrl;

    private string $model;

    private int $maxHistory;

    private int $timeout;

    private int $maxTokens;

    private float $temperature;

    private int $catalogLimit;

    /** @var list<array{role: string, content: string}> */
    private array $history = [];

    private ProductModel $productModel;

    public function __construct()
    {
        $cfg = require dirname(__DIR__, 2) . '/config/deepseek.php';
        $this->apiKey = (string) ($cfg['api_key'] ?? '');
        $this->apiUrl = (string) ($cfg['api_url'] ?? '');
        $this->model = (string) ($cfg['model'] ?? 'deepseek-chat');
        $this->maxHistory = max(1, (int) ($cfg['max_history'] ?? 10));
        $this->timeout = max(5, (int) ($cfg['timeout'] ?? 30));
        $this->maxTokens = max(100, (int) ($cfg['max_tokens'] ?? 500));
        $this->temperature = (float) ($cfg['temperature'] ?? 0.7);
        $this->catalogLimit = max(1, min(500, (int) ($cfg['catalog_limit'] ?? 200)));

        $this->productModel = new ProductModel();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['chat_history']) && is_array($_SESSION['chat_history'])) {
            $this->history = $_SESSION['chat_history'];
        }
    }

    /**
     * One chat turn: system prompt, prior history, API call, then persist messages.
     */
    public function ask(string $userMessage): string
    {
        if ($this->apiKey === '') {
            return '抱歉，AI 客服尚未配置，請稍後再試或聯絡網站管理員。';
        }

        $systemPrompt = $this->buildSystemPrompt();
        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($this->history as $item) {
            if (is_array($item) && isset($item['role'], $item['content'])) {
                $messages[] = $item;
            }
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $response = $this->callDeepSeekAPI($messages);
        $this->saveToHistory($userMessage, $response);

        return $response;
    }

    /**
     * Site, currency, shipping rules, optional product catalog, and reply constraints for the model.
     */
    private function buildSystemPrompt(): string
    {
        $siteName = (string) Config::get('site_name', '高達模型商城');
        $currency = Config::get('currency', []);
        $symbol = is_array($currency) ? (string) ($currency['symbol'] ?? 'HK$') : 'HK$';
        $code = is_array($currency)
            ? (string) ($currency['code'] ?? Config::defaultCurrencyCode())
            : Config::defaultCurrencyCode();

        $shipping = Config::get('shipping', []);
        $freeThreshold = is_array($shipping) ? (float) ($shipping['free_threshold'] ?? 500) : 500.0;
        $expressFee = is_array($shipping) ? (float) ($shipping['express_fee'] ?? 80) : 80.0;
        $standardFee = is_array($shipping) ? (float) ($shipping['standard_fee'] ?? 50) : 50.0;
        $region = (string) Config::get('default_shipping_region', '香港');

        $prompt = "你是「{$siteName}」的 AI 客服助手，請以友善、專業的**繁體中文**回答。\n\n";
        $prompt .= "網站概要：販售高達／鋼普拉等模型商品。\n";
        $prompt .= "幣別：{$code}（顯示可用 {$symbol}）。\n";
        $prompt .= "配送（{$region}）：標準運費 {$standardFee} {$code}；快速運費 {$expressFee} {$code}；訂單滿 {$freeThreshold} {$code} 免標準運費（實際以結帳頁為準）。\n\n";

        $products = $this->productModel->getCatalogForChat($this->catalogLimit);
        if ($products !== []) {
            $prompt .= "以下為目錄中最多 {$this->catalogLimit} 筆商品（供回答商品相關問題；連結格式為相對路徑）：\n\n";
            $byCat = [];
            foreach ($products as $row) {
                $cat = trim((string) ($row['category'] ?? ''));
                if ($cat === '') {
                    $cat = '未分類';
                }
                if (!isset($byCat[$cat])) {
                    $byCat[$cat] = [];
                }
                $byCat[$cat][] = $row;
            }
            foreach ($byCat as $category => $rows) {
                $prompt .= "【{$category}】\n";
                foreach ($rows as $p) {
                    $id = (int) ($p['id'] ?? 0);
                    $name = (string) ($p['name'] ?? '');
                    $price = (int) ($p['price'] ?? 0);
                    $stock = (int) ($p['stock_quantity'] ?? 0);
                    $prompt .= "- {$name}（編號 {$id}）｜價格：{$price} {$code}｜庫存約：{$stock}\n";
                    $desc = trim((string) ($p['description'] ?? ''));
                    if ($desc !== '') {
                        $snippet = mb_substr(preg_replace('/\s+/', ' ', strip_tags($desc)), 0, 120);
                        $prompt .= "  簡介：{$snippet}\n";
                    }
                    if ($id > 0) {
                        $prompt .= "  商品頁：/product/{$id}\n";
                    }
                }
                $prompt .= "\n";
            }
        } else {
            $prompt .= "目前目錄中尚無商品資料可查詢。\n\n";
        }

        $prompt .= "回答規則：\n";
        $prompt .= "- 回覆請盡量控制在 100 字內（繁體中文），先給重點答案。\n";
        $prompt .= "- 若資訊較多，先給最重要 1-2 點，避免長篇。\n";
        $prompt .= "- 運費、免運門檻請以上述為準，並提醒以結帳頁為準。\n";
        $prompt .= "- 商品資訊以上述列表為主；若不在列表中，請禮貌說明並建議使用者至網站搜尋或聯絡人工客服。\n";
        $prompt .= "- 勿回答與本商城無關的內容；遇訂單爭議、退款等請建議聯絡客服。\n";

        return $prompt;
    }

    /**
     * POST to DeepSeek Chat Completions (JSON).
     *
     * @param list<array{role: string, content: string}> $messages
     * @return string Model reply or user-facing fallback on transport/parse errors
     */
    private function callDeepSeekAPI(array $messages): string
    {
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ];

        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '') {
            error_log('DeepSeek API curl error: ' . $error);

            return '抱歉，暫時無法連線，請稍後再試。';
        }

        if ($httpCode !== 200) {
            error_log('DeepSeek API HTTP ' . $httpCode . ' — ' . (string) $response);

            return '抱歉，服務暫時無法使用，請稍後再試。';
        }

        $result = json_decode((string) $response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return trim((string) $result['choices'][0]['message']['content']);
        }

        error_log('DeepSeek API unexpected response: ' . (string) $response);

        return '抱歉，暫時無法處理您的問題，請稍後再試。';
    }

    /** 附加使用者與助手訊息，並裁切至最大歷史對話對數。 */
    private function saveToHistory(string $userMessage, string $aiResponse): void
    {
        $this->history[] = ['role' => 'user', 'content' => $userMessage];
        $this->history[] = ['role' => 'assistant', 'content' => $aiResponse];

        $maxPairs = $this->maxHistory * 2;
        if (count($this->history) > $maxPairs) {
            $this->history = array_slice($this->history, -$maxPairs);
        }

        $_SESSION['chat_history'] = $this->history;
    }

    /** 清空 Session 中保存的聊天歷史。 */
    public function clearHistory(): void
    {
        $this->history = [];
        unset($_SESSION['chat_history']);
    }
}
