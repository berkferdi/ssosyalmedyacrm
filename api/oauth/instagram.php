<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once INCLUDES_PATH . '/SocialMedia/FacebookAPI.php';
require_once INCLUDES_PATH . '/SocialMedia/InstagramAPI.php';

Auth::requireLogin();

if (!empty($_GET['error'])) {
    $msg = $_GET['error_description'] ?? $_GET['error'] ?? 'Instagram bağlantısı reddedildi';
    Security::redirect(app_url() . '/admin/accounts.php?error=' . urlencode($msg));
}

if (!empty($_GET['code'])) {
    if (!oauth_state_validate($_GET['state'] ?? '')) {
        Security::redirect(app_url() . '/admin/accounts.php?error=' . urlencode('Güvenlik doğrulaması başarısız. Tekrar deneyin.'));
    }

    $tokenData = InstagramAPI::exchangeCode($_GET['code']);

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
            $igAccount = $page['instagram_business_account'] ?? null;
            if (empty($igAccount['id'])) {
                $igData = InstagramAPI::getBusinessAccounts($page['id'], $page['access_token']);
                $igAccount = $igData['instagram_business_account'] ?? null;
            }

            if (!empty($igAccount['id'])) {
                $username = $igAccount['username'] ?? '';
                $accountName = $username ? '@' . $username : ($page['name'] . ' (Instagram)');

                Database::insert('social_accounts', [
                    'user_id' => Auth::id(),
                    'platform' => 'instagram',
                    'account_name' => $accountName,
                    'account_id' => $igAccount['id'],
                    'access_token' => $page['access_token'],
                    'expires_at' => date('Y-m-d H:i:s', time() + $expiresIn),
                    'status' => 'active',
                    'metadata' => json_encode([
                        'page_id' => $page['id'],
                        'page_name' => $page['name'],
                        'username' => $username,
                    ]),
                ]);
                $connected++;
            }
        }

        if ($connected === 0) {
            Security::redirect(app_url() . '/admin/accounts.php?error=' . urlencode(
                'Instagram Business hesabı bulunamadı. Facebook sayfanıza Instagram Business/Creator hesabı bağlı olmalı.'
            ));
        }
    }

    Security::redirect(app_url() . '/admin/accounts.php?connected=instagram');
}

try {
    Security::redirect(InstagramAPI::getAuthUrl());
} catch (Throwable $e) {
    Security::redirect(app_url() . '/admin/accounts.php?error=' . urlencode($e->getMessage()));
}
