<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once INCLUDES_PATH . '/SocialMedia/FacebookAPI.php';

Auth::requireLogin();

if (!empty($_GET['code'])) {
    $tokenData = FacebookAPI::exchangeCode($_GET['code']);
    if (!empty($tokenData['access_token'])) {
        $longLived = FacebookAPI::getLongLivedToken($tokenData['access_token']);
        $accessToken = $longLived['access_token'] ?? $tokenData['access_token'];
        $expiresIn = $longLived['expires_in'] ?? $tokenData['expires_in'] ?? 5184000;

        $pages = FacebookAPI::getPages($accessToken);
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
        }
    }
    Security::redirect(APP_URL . '/admin/accounts.php?connected=facebook');
}

Security::redirect(FacebookAPI::getAuthUrl());
