<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/OpenAI.php';

Auth::requireLogin();

if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    Security::jsonResponse(['success' => false, 'message' => 'Geçersiz CSRF token'], 403);
}

$action = $_POST['action'] ?? '';
$userId = Auth::id();

switch ($action) {
    case 'upload':
        if (empty($_FILES['file'])) {
            Security::jsonResponse(['success' => false, 'message' => 'Dosya bulunamadı']);
        }

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            Security::jsonResponse(['success' => false, 'message' => 'Yükleme hatası']);
        }

        if ($file['size'] > UPLOAD_MAX_SIZE) {
            Security::jsonResponse(['success' => false, 'message' => 'Dosya çok büyük (max 50MB)']);
        }

        $mime = mime_content_type($file['tmp_name']);
        $fileType = 'document';
        if (in_array($mime, ALLOWED_IMAGE_TYPES)) $fileType = 'image';
        elseif (in_array($mime, ALLOWED_VIDEO_TYPES)) $fileType = 'video';
        elseif (in_array($mime, ALLOWED_DOC_TYPES)) $fileType = 'pdf';
        else {
            Security::jsonResponse(['success' => false, 'message' => 'Desteklenmeyen dosya türü']);
        }

        $relativePath = generate_unique_filename($file['name']);
        $fullPath = UPLOAD_PATH . 'media/' . $relativePath;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            Security::jsonResponse(['success' => false, 'message' => 'Dosya kaydedilemedi']);
        }

        $id = Database::insert('media_library', [
            'user_id' => $userId,
            'filename' => basename($relativePath),
            'original_name' => $file['name'],
            'file_path' => 'media/' . $relativePath,
            'file_type' => $fileType,
            'mime_type' => $mime,
            'file_size' => $file['size'],
        ]);

        if ($fileType === 'image') {
            $openai = new OpenAI();
            $ai = $openai->generateFromImage($fullPath);
            Database::update('media_library', [
                'ai_title' => $ai['title'] ?? '',
                'ai_description' => $ai['description'] ?? '',
                'ai_hashtags' => $ai['hashtags'] ?? '',
                'ai_seo' => $ai['seo_text'] ?? '',
                'ai_cta' => $ai['cta'] ?? '',
                'ai_processed' => 1,
            ], 'id = ?', [$id]);
        }

        Security::jsonResponse(['success' => true, 'id' => $id]);
        break;

    case 'detail':
        $id = (int) ($_POST['id'] ?? 0);
        $media = Database::fetch('SELECT * FROM media_library WHERE id = ? AND user_id = ?', [$id, $userId]);
        if (!$media) {
            Security::jsonResponse(['success' => false, 'message' => 'Medya bulunamadı']);
        }
        $media['file_size_formatted'] = format_file_size($media['file_size']);
        Security::jsonResponse(['success' => true, 'media' => $media]);
        break;

    case 'create_folder':
        $name = Security::sanitize($_POST['name'] ?? '');
        if (empty($name)) {
            Security::jsonResponse(['success' => false, 'message' => 'Klasör adı gerekli']);
        }
        $id = Database::insert('media_folders', ['user_id' => $userId, 'name' => $name]);
        Security::jsonResponse(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        $media = Database::fetch('SELECT file_path FROM media_library WHERE id = ? AND user_id = ?', [$id, $userId]);
        if ($media) {
            $fullPath = UPLOAD_PATH . $media['file_path'];
            if (file_exists($fullPath)) unlink($fullPath);
            Database::delete('media_library', 'id = ?', [$id]);
        }
        Security::jsonResponse(['success' => true]);
        break;

    default:
        Security::jsonResponse(['success' => false, 'message' => 'Geçersiz işlem'], 400);
}
