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
    case 'create':
        $accountId = (int) ($_POST['social_account_id'] ?? 0);
        $account = Database::fetch('SELECT * FROM social_accounts WHERE id = ? AND user_id = ? AND platform = ?', [$accountId, $userId, 'wordpress']);
        if (!$account) {
            Security::jsonResponse(['success' => false, 'message' => 'WordPress hesabı bulunamadı']);
        }

        $meta = json_decode($account['metadata'] ?? '{}', true);
        $status = $_POST['status'] ?? 'draft';

        $id = Database::insert('wordpress_posts', [
            'user_id' => $userId,
            'social_account_id' => $accountId,
            'title' => Security::sanitize($_POST['title'] ?? ''),
            'content' => $_POST['content'] ?? '',
            'seo_description' => Security::sanitize($_POST['seo_description'] ?? ''),
            'status' => $status,
        ]);

        if ($status === 'publish') {
            $result = WordPressAPI::createPost(
                $meta['site_url'] ?? $account['account_id'],
                $account['access_token'],
                $account['refresh_token'],
                $_POST['title'],
                $_POST['content'],
                'publish'
            );
            if (!empty($result['id'])) {
                Database::update('wordpress_posts', [
                    'wp_post_id' => $result['id'],
                    'published_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$id]);
            }
        }

        Security::jsonResponse(['success' => true, 'id' => $id]);
        break;

    case 'publish':
        $id = (int) ($_POST['id'] ?? 0);
        $post = Database::fetch(
            'SELECT wp.*, sa.access_token, sa.refresh_token, sa.account_id, sa.metadata
             FROM wordpress_posts wp JOIN social_accounts sa ON wp.social_account_id = sa.id
             WHERE wp.id = ? AND wp.user_id = ?',
            [$id, $userId]
        );
        if (!$post) {
            Security::jsonResponse(['success' => false, 'message' => 'Yazı bulunamadı']);
        }

        $meta = json_decode($post['metadata'] ?? '{}', true);
        $result = WordPressAPI::createPost(
            $meta['site_url'] ?? $post['account_id'],
            $post['access_token'],
            $post['refresh_token'],
            $post['title'],
            $post['content'],
            'publish'
        );

        if (!empty($result['id'])) {
            Database::update('wordpress_posts', [
                'status' => 'publish',
                'wp_post_id' => $result['id'],
                'published_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
            Security::jsonResponse(['success' => true]);
        }

        Security::jsonResponse(['success' => false, 'message' => 'Yayınlama başarısız']);
        break;

    default:
        Security::jsonResponse(['success' => false, 'message' => 'Geçersiz işlem'], 400);
}
