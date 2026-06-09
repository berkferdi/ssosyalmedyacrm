<?php

require_once INCLUDES_PATH . '/OpenAI.php';

$processed = 0;
$openai = new OpenAI();

$pendingMedia = Database::fetchAll(
    'SELECT * FROM media_library WHERE ai_processed = 0 AND file_type = ? LIMIT 10',
    ['image']
);

foreach ($pendingMedia as $media) {
    try {
        $fullPath = UPLOAD_PATH . $media['file_path'];
        if (!file_exists($fullPath)) continue;

        $result = $openai->generateFromImage($fullPath, $media['original_name']);

        Database::update('media_library', [
            'ai_title' => $result['title'] ?? '',
            'ai_description' => $result['description'] ?? '',
            'ai_hashtags' => $result['hashtags'] ?? '',
            'ai_seo' => $result['seo_text'] ?? '',
            'ai_cta' => $result['cta'] ?? '',
            'ai_processed' => 1,
        ], 'id = ?', [$media['id']]);

        $processed++;
    } catch (Throwable $e) {
        app_log('ai_content', $e->getMessage(), $media['user_id']);
    }
}

return $processed;
