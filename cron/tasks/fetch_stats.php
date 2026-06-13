<?php

require_once INCLUDES_PATH . '/SocialMedia/FacebookAPI.php';
require_once INCLUDES_PATH . '/SocialMedia/InstagramAPI.php';
require_once INCLUDES_PATH . '/SocialMedia/LinkedInAPI.php';

$processed = 0;
$today = date('Y-m-d');

$accounts = Database::fetchAll(
    "SELECT * FROM social_accounts WHERE status = 'active'"
);

foreach ($accounts as $account) {
    try {
        $metrics = fetchPlatformMetrics($account);

        if (!empty($metrics)) {
            $existing = Database::fetch(
                'SELECT id FROM analytics WHERE social_account_id = ? AND metric_date = ?',
                [$account['id'], $today]
            );

            $data = [
                'likes' => $metrics['likes'] ?? 0,
                'comments' => $metrics['comments'] ?? 0,
                'reach' => $metrics['reach'] ?? 0,
                'impressions' => $metrics['impressions'] ?? 0,
                'clicks' => $metrics['clicks'] ?? 0,
                'engagement' => $metrics['engagement'] ?? 0,
                'followers' => $metrics['followers'] ?? 0,
            ];

            if ($existing) {
                Database::update('analytics', $data, 'id = ?', [$existing['id']]);
            } else {
                Database::insert('analytics', array_merge([
                    'social_account_id' => $account['id'],
                    'platform' => $account['platform'],
                    'metric_date' => $today,
                ], $data));
            }

            Database::update('social_accounts', [
                'last_sync' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$account['id']]);

            $processed++;
        }
    } catch (Throwable $e) {
        app_log('fetch_stats', $e->getMessage(), $account['user_id']);
    }
}

return $processed;

function fetchPlatformMetrics(array $account): array
{
    return match ($account['platform']) {
        'facebook' => (function () use ($account) {
            $insights = FacebookAPI::getInsights($account['account_id'], $account['access_token']);
            return parseInsights($insights);
        })(),
        'instagram' => (function () use ($account) {
            $insights = InstagramAPI::getInsights($account['account_id'], $account['access_token']);
            return parseInsights($insights);
        })(),
        default => [],
    };
}

function parseInsights(array $insights): array
{
    $metrics = ['likes' => 0, 'reach' => 0, 'impressions' => 0, 'engagement' => 0];
    foreach ($insights['data'] ?? [] as $item) {
        $name = $item['name'] ?? '';
        $value = $item['values'][0]['value'] ?? 0;
        if (str_contains($name, 'impression')) $metrics['impressions'] = $value;
        if (str_contains($name, 'reach') || str_contains($name, 'engaged')) $metrics['reach'] = $value;
        if (str_contains($name, 'engagement')) $metrics['engagement'] = $value;
    }
    return $metrics;
}
