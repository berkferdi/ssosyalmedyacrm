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

    default:
        Security::jsonResponse(['success' => false, 'message' => 'Geçersiz işlem'], 400);
}
