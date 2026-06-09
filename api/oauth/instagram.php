<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once INCLUDES_PATH . '/SocialMedia/FacebookAPI.php';
require_once INCLUDES_PATH . '/SocialMedia/InstagramAPI.php';

Auth::requireLogin();

if (!empty($_GET['code'])) {
    $tokenData = FacebookAPI::exchangeCode($_GET['code']);
    if (!empty($tokenData['access_token'])) {
        $longLived = FacebookAPI::getLongLivedToken($tokenData['access_token']);
        $accessToken = $longLived['access_token'] ?? $tokenData['access_token'];
        $pages = FacebookAPI::getPages($accessToken);

        foreach ($pages as $page) {
            $igData = InstagramAPI::getBusinessAccounts($page['id'], $page['access_token']);
            if (!empty($igData['instagram_business_account']['id'])) {
                Database::insert('social_accounts', [
                    'user_id' => Auth::id(),
                    'platform' => 'instagram',
                    'account_name' => $page['name'] . ' (Instagram)',
                    'account_id' => $igData['instagram_business_account']['id'],
                    'access_token' => $page['access_token'],
                    'status' => 'active',
                    'metadata' => json_encode(['page_id' => $page['id']]),
                ]);
            }
        }
    }
    Security::redirect(APP_URL . '/admin/accounts.php?connected=instagram');
}

Security::redirect(InstagramAPI::getAuthUrl());
