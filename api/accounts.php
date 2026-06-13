<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/SocialMedia/WordPressAPI.php';

Auth::requireLogin();

if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    Security::jsonResponse(['success' => false, 'message' => 'Geçersiz CSRF token'], 403);
}

$action = $_POST['action'] ?? '';
$userId = Auth::id();

switch ($action) {
    case 'connect_wordpress':
        $siteUrl = rtrim($_POST['site_url'] ?? '', '/');
        $username = $_POST['username'] ?? '';
        $appPassword = $_POST['app_password'] ?? '';

        $test = WordPressAPI::testConnection($siteUrl, $username, $appPassword);
        if (empty($test['id']) && ($test['_http_code'] ?? 0) !== 200) {
            Security::jsonResponse(['success' => false, 'message' => 'WordPress bağlantısı başarısız']);
        }

        $id = Database::insert('social_accounts', [
            'user_id' => $userId,
            'platform' => 'wordpress',
            'account_name' => parse_url($siteUrl, PHP_URL_HOST) ?: $siteUrl,
            'account_id' => $siteUrl,
            'access_token' => $username,
            'refresh_token' => $appPassword,
            'status' => 'active',
            'metadata' => json_encode(['site_url' => $siteUrl, 'user_name' => $test['name'] ?? $username]),
        ]);

        Security::jsonResponse(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        Database::delete('social_accounts', 'id = ? AND user_id = ?', [$id, $userId]);
        Security::jsonResponse(['success' => true]);
        break;

    case 'sync':
        $id = (int) ($_POST['id'] ?? 0);
        Database::update('social_accounts', ['last_sync' => date('Y-m-d H:i:s')], 'id = ? AND user_id = ?', [$id, $userId]);
        Security::jsonResponse(['success' => true]);
        break;

    default:
        Security::jsonResponse(['success' => false, 'message' => 'Geçersiz işlem'], 400);
}
