<?php

require_once INCLUDES_PATH . '/SocialMedia/FacebookAPI.php';

$processed = 0;

$accounts = Database::fetchAll(
    "SELECT * FROM social_accounts
     WHERE status = 'active'
       AND expires_at IS NOT NULL
       AND expires_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)
       AND platform IN ('facebook', 'instagram')"
);

foreach ($accounts as $account) {
    try {
        if ($account['platform'] === 'facebook' || $account['platform'] === 'instagram') {
            $result = FacebookAPI::getLongLivedToken($account['access_token']);
            if (!empty($result['access_token'])) {
                Database::update('social_accounts', [
                    'access_token' => $result['access_token'],
                    'expires_at' => date('Y-m-d H:i:s', time() + ($result['expires_in'] ?? 5184000)),
                    'last_sync' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$account['id']]);
                $processed++;
            }
        }
    } catch (Throwable $e) {
        Database::update('social_accounts', [
            'status' => 'expired',
        ], 'id = ?', [$account['id']]);

        create_notification($account['user_id'], 'account_disconnected',
            'Hesap Bağlantısı Koptu',
            "{$account['account_name']} hesabının token süresi doldu.");

        app_log('token_refresh', $e->getMessage(), $account['user_id']);
    }
}

return $processed;
