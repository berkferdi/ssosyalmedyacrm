<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/OpenAI.php';

Auth::requireLogin();

if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    Security::jsonResponse(['success' => false, 'message' => 'Geçersiz CSRF token'], 403);
}

$action = $_POST['action'] ?? '';
$openai = new OpenAI();

switch ($action) {
    case 'generate_text':
        $description = $_POST['description'] ?? '';
        $context = $_POST['context'] ?? null;
        if (empty($description)) {
            Security::jsonResponse(['success' => false, 'message' => 'Açıklama gerekli']);
        }
        $result = $openai->generateContent($description, $context);
        if (!empty($result['fallback']) && empty(get_api_setting('openai_api_key'))) {
            $result['message'] = 'OpenAI API anahtarı tanımlı değil. Ayarlar sayfasından ekleyin.';
        }
        Security::jsonResponse($result);
        break;

    case 'generate':
        $mediaId = (int) ($_POST['media_id'] ?? 0);
        $media = Database::fetch('SELECT * FROM media_library WHERE id = ? AND user_id = ?', [$mediaId, Auth::id()]);
        if (!$media) {
            Security::jsonResponse(['success' => false, 'message' => 'Medya bulunamadı']);
        }

        $fullPath = UPLOAD_PATH . $media['file_path'];
        $result = $openai->generateFromImage($fullPath, $media['original_name']);

        Database::update('media_library', [
            'ai_title' => $result['title'] ?? '',
            'ai_description' => $result['description'] ?? '',
            'ai_hashtags' => $result['hashtags'] ?? '',
            'ai_seo' => $result['seo_text'] ?? '',
            'ai_cta' => $result['cta'] ?? '',
            'ai_processed' => 1,
        ], 'id = ?', [$mediaId]);

        Security::jsonResponse($result);
        break;

    default:
        Security::jsonResponse(['success' => false, 'message' => 'Geçersiz işlem'], 400);
}
