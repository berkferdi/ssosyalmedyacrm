<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once INCLUDES_PATH . '/SocialMedia/FacebookAPI.php';

Auth::requireLogin();

$redirectUri = oauth_redirect_uri('facebook');

if (!empty($_GET['error'])) {
    $msg = $_GET['error_description'] ?? $_GET['error'] ?? 'Facebook bağlantısı reddedildi';
    Security::redirect(app_url() . '/admin/accounts.php?error=' . urlencode($msg));
}

if (!empty($_GET['code'])) {
    if (!oauth_state_validate($_GET['state'] ?? '')) {
        Security::redirect(app_url() . '/admin/accounts.php?error=' . urlencode('Güvenlik doğrulaması başarısız. Tekrar deneyin.'));
    }

    $tokenData = FacebookAPI::exchangeCode($_GET['code'], $redirectUri);

    if (!empty($tokenData['error'])) {
        $msg = $tokenData['error']['message'] ?? 'Token alınamadı';
        Security::redirect(app_url() . '/admin/accounts.php?error=' . urlencode($msg));
    }

    if (!empty($tokenData['access_token'])) {
        $longLived = FacebookAPI::getLongLivedToken($tokenData['access_token']);
        $accessToken = $longLived['access_token'] ?? $tokenData['access_token'];
        $expiresIn = $longLived['expires_in'] ?? $tokenData['expires_in'] ?? 5184000;

        $pages = FacebookAPI::getPages($accessToken);
        $connected = 0;

        foreach ($pages as $page) {
            Database::insert('social_accounts', [
                'user_id' => Auth::id(),
                'platform' => 'facebook',
                'account_name' => $page['name'],
                'account_id' => $page['id'],
                'access_token' => $page['access_token'],
                'expires_at' => date('Y-m-d H:i:s', time() + $expiresIn),
                'status' => 'active',
            ]);
            $connected++;
        }

        if ($connected === 0) {
            Security::redirect(app_url() . '/admin/accounts.php?error=' . urlencode('Bağlı Facebook sayfası bulunamadı. Sayfa yöneticisi olduğunuzdan emin olun.'));
        }
    }

    Security::redirect(app_url() . '/admin/accounts.php?connected=facebook');
}

try {
    Security::redirect(FacebookAPI::getAuthUrl($redirectUri));
} catch (Throwable $e) {
    Security::redirect(app_url() . '/admin/accounts.php?error=' . urlencode($e->getMessage()));
}
