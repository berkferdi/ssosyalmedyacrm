<?php
require_once __DIR__ . '/../includes/bootstrap.php';

Auth::requireLogin();

if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    Security::jsonResponse(['success' => false, 'message' => 'Geçersiz CSRF token'], 403);
}

$action = $_POST['action'] ?? '';
$userId = Auth::id();

switch ($action) {
    case 'set_theme':
        set_setting('theme', $_POST['theme'] ?? 'light', $userId);
        Security::jsonResponse(['success' => true]);
        break;

    case 'update_profile':
        Database::update('users', [
            'first_name' => Security::sanitize($_POST['first_name'] ?? ''),
            'last_name' => Security::sanitize($_POST['last_name'] ?? ''),
        ], 'id = ?', [$userId]);
        Security::jsonResponse(['success' => true]);
        break;

    case 'change_password':
        $user = Database::fetch('SELECT password FROM users WHERE id = ?', [$userId]);
        if (!Security::verifyPassword($_POST['current_password'] ?? '', $user['password'])) {
            Security::jsonResponse(['success' => false, 'message' => 'Mevcut şifre hatalı']);
        }
        if (strlen($_POST['new_password'] ?? '') < PASSWORD_MIN_LENGTH) {
            Security::jsonResponse(['success' => false, 'message' => 'Şifre en az ' . PASSWORD_MIN_LENGTH . ' karakter']);
        }
        Database::update('users', [
            'password' => Security::hashPassword($_POST['new_password']),
        ], 'id = ?', [$userId]);
        Security::jsonResponse(['success' => true]);
        break;

    case 'save_notifications':
        set_setting('email_notifications', !empty($_POST['email_notifications']) ? '1' : '0', $userId);
        set_setting('telegram_notifications', !empty($_POST['telegram_notifications']) ? '1' : '0', $userId);
        set_setting('telegram_chat_id', Security::sanitize($_POST['telegram_chat_id'] ?? ''), $userId);
        set_setting('whatsapp_notifications', !empty($_POST['whatsapp_notifications']) ? '1' : '0', $userId);
        set_setting('whatsapp_phone', Security::sanitize($_POST['whatsapp_phone'] ?? ''), $userId);
        Security::jsonResponse(['success' => true]);
        break;

    case 'save_api_keys':
        if (!Auth::hasRole('super_admin')) {
            Security::jsonResponse(['success' => false, 'message' => 'Yetkiniz yok'], 403);
        }

        $secretFields = ['openai_api_key', 'facebook_app_secret', 'linkedin_client_secret'];
        $textFields = [
            'openai_api_key', 'openai_model',
            'facebook_app_id', 'facebook_app_secret',
            'linkedin_client_id', 'linkedin_client_secret',
        ];

        $saved = [];
        foreach ($textFields as $field) {
            $value = trim($_POST[$field] ?? '');
            if ($value === '' && in_array($field, $secretFields, true)) {
                continue;
            }
            if ($value !== '') {
                set_system_setting($field, $value);
                $saved[] = $field;
            }
        }

        if (empty($saved)) {
            Security::jsonResponse(['success' => false, 'message' => 'Kaydedilecek bir değer girilmedi']);
        }

        Security::jsonResponse(['success' => true, 'message' => 'API ayarları kaydedildi', 'saved' => $saved]);
        break;

    case 'test_openai':
        if (!Auth::hasRole('super_admin')) {
            Security::jsonResponse(['success' => false, 'message' => 'Yetkiniz yok'], 403);
        }

        $newKey = trim($_POST['openai_api_key'] ?? '');
        if ($newKey !== '') {
            set_system_setting('openai_api_key', $newKey);
        }

        require_once INCLUDES_PATH . '/OpenAI.php';
        $result = (new OpenAI())->testConnection();
        Security::jsonResponse($result, $result['success'] ? 200 : 400);
        break;

    case 'get_api_keys':
        if (!Auth::hasRole('super_admin')) {
            Security::jsonResponse(['success' => false, 'message' => 'Yetkiniz yok'], 403);
        }

        $openaiKey = get_api_setting('openai_api_key');
        Security::jsonResponse([
            'success' => true,
            'settings' => [
                'openai_api_key_set' => $openaiKey !== '',
                'openai_api_key_masked' => $openaiKey ? mask_api_key($openaiKey) : '',
                'openai_model' => get_api_setting('openai_model') ?: (defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini'),
                'facebook_app_id' => get_api_setting('facebook_app_id'),
                'facebook_app_secret_set' => get_api_setting('facebook_app_secret') !== '',
                'linkedin_client_id' => get_api_setting('linkedin_client_id'),
                'linkedin_client_secret_set' => get_api_setting('linkedin_client_secret') !== '',
            ],
        ]);
        break;

    default:
        Security::jsonResponse(['success' => false, 'message' => 'Geçersiz işlem'], 400);
}
