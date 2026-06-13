<?php

require_once INCLUDES_PATH . '/SocialMedia/FacebookAPI.php';
require_once INCLUDES_PATH . '/SocialMedia/InstagramAPI.php';
require_once INCLUDES_PATH . '/SocialMedia/LinkedInAPI.php';
require_once INCLUDES_PATH . '/Notifications/Email.php';
require_once INCLUDES_PATH . '/Notifications/Telegram.php';
require_once INCLUDES_PATH . '/Notifications/WhatsApp.php';

$processed = 0;

$posts = Database::fetchAll(
    "SELECT sp.*, sa.platform, sa.account_id, sa.access_token, sa.account_name,
            ml.file_path as media_path
     FROM scheduled_posts sp
     JOIN social_accounts sa ON sp.social_account_id = sa.id
     LEFT JOIN media_library ml ON sp.media_id = ml.id
     WHERE sp.status IN ('pending', 'retry')
       AND sp.scheduled_at <= NOW()
       AND sp.retry_count < 3
     ORDER BY sp.scheduled_at ASC
     LIMIT 20"
);

foreach ($posts as $post) {
    try {
        $result = publishToPlatform($post);

        if (!empty($result['success'])) {
            Database::update('scheduled_posts', [
                'status' => 'published',
                'published_at' => date('Y-m-d H:i:s'),
                'platform_post_id' => $result['post_id'] ?? null,
            ], 'id = ?', [$post['id']]);

            create_notification($post['user_id'], 'post_published', 'Paylaşım Yapıldı',
                "{$post['account_name']} hesabında paylaşım yayınlandı.");

            notifyUser($post['user_id'], 'Paylaşım Yapıldı',
                "{$post['account_name']} hesabında içerik yayınlandı.");

            if ($post['auto_generated']) {
                Database::query(
                    'UPDATE auto_campaigns SET published_posts = published_posts + 1
                     WHERE user_id = ? AND status = ?',
                    [$post['user_id'], 'active']
                );
            }
        } else {
            throw new Exception($result['error'] ?? 'Bilinmeyen hata');
        }
    } catch (Throwable $e) {
        $newStatus = ($post['retry_count'] + 1 >= 3) ? 'failed' : 'retry';
        Database::update('scheduled_posts', [
            'status' => $newStatus,
            'retry_count' => $post['retry_count'] + 1,
            'error_message' => $e->getMessage(),
        ], 'id = ?', [$post['id']]);

        if ($newStatus === 'failed') {
            create_notification($post['user_id'], 'post_failed', 'Paylaşım Hatası',
                "{$post['account_name']}: {$e->getMessage()}");
            notifyUser($post['user_id'], 'Paylaşım Hatası', $e->getMessage());
        }

        app_log('publish', $e->getMessage(), $post['user_id'], ['post_id' => $post['id']]);
    }

    $processed++;
}

return $processed;

function publishToPlatform(array $post): array
{
    $message = $post['content'];
    if ($post['hashtags']) {
        $message .= "\n\n" . $post['hashtags'];
    }

    $imageUrl = null;
    if ($post['media_path']) {
        $imageUrl = app_url() . '/uploads/' . $post['media_path'];
    }

    return match ($post['platform']) {
        'facebook' => (function () use ($post, $message, $imageUrl) {
            $result = FacebookAPI::publishPost($post['account_id'], $post['access_token'], $message, $imageUrl);
            return ['success' => !empty($result['id']), 'post_id' => $result['id'] ?? null, 'error' => $result['error']['message'] ?? null];
        })(),
        'instagram' => (function () use ($post, $message, $imageUrl) {
            if (!$imageUrl) return ['success' => false, 'error' => 'Instagram için görsel gerekli'];
            $result = InstagramAPI::publishPost($post['account_id'], $post['access_token'], $imageUrl, $message);
            return ['success' => !empty($result['id']), 'post_id' => $result['id'] ?? null, 'error' => $result['error']['message'] ?? ($result['error'] ?? null)];
        })(),
        'linkedin' => (function () use ($post, $message) {
            $result = LinkedInAPI::publishPost($post['account_id'], $post['access_token'], $message);
            return ['success' => !empty($result['id']), 'post_id' => $result['id'] ?? null];
        })(),
        default => ['success' => false, 'error' => 'Desteklenmeyen platform'],
    };
}

function notifyUser(int $userId, string $title, string $message): void
{
    if (get_setting('email_notifications', '1', $userId) === '1') {
        EmailNotification::notifyUser($userId, 'info', $title, $message);
    }
    if (get_setting('telegram_notifications', '0', $userId) === '1') {
        TelegramNotification::notifyUser($userId, $title, $message);
    }
    if (get_setting('whatsapp_notifications', '0', $userId) === '1') {
        WhatsAppNotification::notifyUser($userId, $title, $message);
    }
}
