<?php
require_once __DIR__ . '/../includes/bootstrap.php';

Auth::requireLogin();

if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    Security::jsonResponse(['success' => false, 'message' => 'Geçersiz CSRF token'], 403);
}

$action = $_POST['action'] ?? '';
$userId = Auth::id();

switch ($action) {
    case 'platform_breakdown':
        $data = Database::fetchAll(
            'SELECT sa.platform, COUNT(*) as cnt FROM social_accounts sa
             WHERE sa.user_id = ? AND sa.status = ? GROUP BY sa.platform',
            [$userId, 'active']
        );
        Security::jsonResponse([
            'success' => true,
            'labels' => array_map(fn($d) => ucfirst($d['platform']), $data),
            'data' => array_map(fn($d) => (int) $d['cnt'], $data),
        ]);
        break;

    case 'daily_engagement':
        $days = (int) ($_POST['days'] ?? 30);
        $data = Database::fetchAll(
            'SELECT a.metric_date, SUM(a.reach) as reach, SUM(a.likes) as likes
             FROM analytics a JOIN social_accounts sa ON a.social_account_id = sa.id
             WHERE sa.user_id = ? AND a.metric_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY a.metric_date ORDER BY a.metric_date',
            [$userId, $days]
        );
        Security::jsonResponse([
            'success' => true,
            'labels' => array_map(fn($d) => date('d.m', strtotime($d['metric_date'])), $data),
            'reach' => array_map(fn($d) => (int) $d['reach'], $data),
            'likes' => array_map(fn($d) => (int) $d['likes'], $data),
        ]);
        break;

    case 'report':
        $period = $_POST['period'] ?? 'daily';
        $interval = match ($period) {
            'weekly' => 7,
            'monthly' => 30,
            default => 1,
        };

        $totals = Database::fetch(
            'SELECT COALESCE(SUM(a.likes),0) as likes, COALESCE(SUM(a.comments),0) as comments,
                    COALESCE(SUM(a.reach),0) as reach, COALESCE(SUM(a.impressions),0) as impressions
             FROM analytics a JOIN social_accounts sa ON a.social_account_id = sa.id
             WHERE sa.user_id = ? AND a.metric_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)',
            [$userId, $interval * 7]
        );

        $daily = Database::fetchAll(
            'SELECT a.metric_date, SUM(a.likes) as likes, SUM(a.reach) as reach, SUM(a.impressions) as impressions
             FROM analytics a JOIN social_accounts sa ON a.social_account_id = sa.id
             WHERE sa.user_id = ? AND a.metric_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY a.metric_date ORDER BY a.metric_date',
            [$userId, $interval * 7]
        );

        $platforms = Database::fetchAll(
            'SELECT sa.platform as name, SUM(a.likes) as likes, SUM(a.reach) as reach
             FROM analytics a JOIN social_accounts sa ON a.social_account_id = sa.id
             WHERE sa.user_id = ? AND a.metric_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY sa.platform',
            [$userId, $interval * 7]
        );

        Security::jsonResponse([
            'success' => true,
            'totals' => [
                'likes' => (int) ($totals['likes'] ?? 0),
                'comments' => (int) ($totals['comments'] ?? 0),
                'reach' => (int) ($totals['reach'] ?? 0),
                'impressions' => (int) ($totals['impressions'] ?? 0),
            ],
            'labels' => array_map(fn($d) => date('d.m', strtotime($d['metric_date'])), $daily),
            'likes' => array_map(fn($d) => (int) $d['likes'], $daily),
            'reach' => array_map(fn($d) => (int) $d['reach'], $daily),
            'impressions' => array_map(fn($d) => (int) $d['impressions'], $daily),
            'platforms' => $platforms,
        ]);
        break;

    default:
        Security::jsonResponse(['success' => false, 'message' => 'Geçersiz işlem'], 400);
}
