<?php

/**
 * DeepSeek API（AI 客服）
 * 金鑰請設於 .env，勿提交。
 */
return [
    'api_key' => trim((string) (getenv('DEEPSEEK_API_KEY') ?: '')),
    'api_url' => trim((string) (getenv('DEEPSEEK_API_URL') ?: 'https://api.deepseek.com/chat/completions')),
    'model' => trim((string) (getenv('DEEPSEEK_MODEL') ?: 'deepseek-chat')),
    'max_history' => (int) (getenv('DEEPSEEK_MAX_HISTORY') ?: 10),
    'timeout' => (int) (getenv('DEEPSEEK_TIMEOUT') ?: 60),
    'max_tokens' => (int) (getenv('DEEPSEEK_MAX_TOKENS') ?: 800),
    'temperature' => (float) (getenv('DEEPSEEK_TEMPERATURE') ?: 0.7),
    'catalog_limit' => (int) (getenv('DEEPSEEK_CATALOG_LIMIT') ?: 150),
];
