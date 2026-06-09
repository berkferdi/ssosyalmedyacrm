<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once INCLUDES_PATH . '/SocialMedia/LinkedInAPI.php';

Auth::requireLogin();

if (!empty($_GET['code'])) {
    $tokenData = LinkedInAPI::exchangeCode($_GET['code']);
    if (!empty($tokenData['access_token'])) {
        $orgs = LinkedInAPI::getOrganizations($tokenData['access_token']);
        $elements = $orgs['elements'] ?? [];

        foreach ($elements as $element) {
            $orgUrn = $element['organization'] ?? '';
            $orgId = str_replace('urn:li:organization:', '', $orgUrn);

            Database::insert('social_accounts', [
                'user_id' => Auth::id(),
                'platform' => 'linkedin',
                'account_name' => 'LinkedIn Org ' . $orgId,
                'account_id' => $orgId,
                'access_token' => $tokenData['access_token'],
                'expires_at' => date('Y-m-d H:i:s', time() + ($tokenData['expires_in'] ?? 5184000)),
                'status' => 'active',
            ]);
        }
    }
    Security::redirect(APP_URL . '/admin/accounts.php?connected=linkedin');
}

Security::redirect(LinkedInAPI::getAuthUrl());
