<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/OpenAI.php';

Auth::requireLogin();

if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? $_GET[CSRF_TOKEN_NAME] ?? '')) {
    Security::jsonResponse(['success' => false, 'message' => 'Geçersiz CSRF token'], 403);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = Auth::id();

switch ($action) {
    case 'create':
        $accountId = (int) ($_POST['social_account_id'] ?? 0);
        $account = Database::fetch('SELECT id FROM social_accounts WHERE id = ? AND user_id = ?', [$accountId, $userId]);
        if (!$account) {
            Security::jsonResponse(['success' => false, 'message' => 'Geçersiz hesap']);
        }

        $scheduledAt = ($_POST['schedule_date'] ?? '') . ' ' . ($_POST['schedule_time'] ?? '09:00') . ':00';

        $id = Database::insert('scheduled_posts', [
            'user_id' => $userId,
            'social_account_id' => $accountId,
            'title' => Security::sanitize($_POST['title'] ?? ''),
            'content' => $_POST['content'] ?? '',
            'hashtags' => Security::sanitize($_POST['hashtags'] ?? ''),
            'scheduled_at' => $scheduledAt,
            'status' => 'pending',
        ]);

        Security::jsonResponse(['success' => true, 'id' => $id]);
        break;

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        Database::delete('scheduled_posts', 'id = ? AND user_id = ? AND status = ?', [$id, $userId, 'pending']);
        Security::jsonResponse(['success' => true]);
        break;

    case 'calendar_events':
        $year = (int) ($_POST['year'] ?? date('Y'));
        $month = (int) ($_POST['month'] ?? date('m'));
        $events = Database::fetchAll(
            'SELECT id, title, content, scheduled_at, status FROM scheduled_posts
             WHERE user_id = ? AND YEAR(scheduled_at) = ? AND MONTH(scheduled_at) = ?
             ORDER BY scheduled_at',
            [$userId, $year, $month]
        );
        Security::jsonResponse(['success' => true, 'events' => $events]);
        break;

    case 'reschedule':
        $postId = (int) ($_POST['post_id'] ?? 0);
        $newDate = $_POST['new_date'] ?? '';
        $post = Database::fetch('SELECT scheduled_at FROM scheduled_posts WHERE id = ? AND user_id = ?', [$postId, $userId]);
        if (!$post) {
            Security::jsonResponse(['success' => false, 'message' => 'Paylaşım bulunamadı']);
        }
        $time = date('H:i:s', strtotime($post['scheduled_at']));
        Database::update('scheduled_posts', [
            'scheduled_at' => $newDate . ' ' . $time,
        ], 'id = ?', [$postId]);
        Security::jsonResponse(['success' => true]);
        break;

    case 'create_campaign':
        $name = Security::sanitize($_POST['name'] ?? '');
        $accountIds = $_POST['account_ids'] ?? [];
        $morningTime = $_POST['morning_time'] ?? '09:00';
        $eveningTime = $_POST['evening_time'] ?? '18:00';
        $startDate = $_POST['start_date'] ?? date('Y-m-d');

        if (empty($accountIds) || empty($name)) {
            Security::jsonResponse(['success' => false, 'message' => 'Eksik bilgi']);
        }

        $campaignId = Database::insert('auto_campaigns', [
            'user_id' => $userId,
            'name' => $name,
            'social_account_ids' => json_encode($accountIds),
            'morning_time' => $morningTime . ':00',
            'evening_time' => $eveningTime . ':00',
            'start_date' => $startDate,
            'status' => 'active',
        ]);

        $openai = new OpenAI();
        $currentDate = $startDate;
        $slot = 0;
        $totalPosts = 0;

        if (!empty($_FILES['images'])) {
            $files = $_FILES['images'];
            $count = is_array($files['name']) ? count($files['name']) : 1;

            for ($i = 0; $i < $count; $i++) {
                $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                $fileType = is_array($files['type']) ? $files['type'][$i] : $files['type'];

                if ($fileSize > UPLOAD_MAX_SIZE) continue;

                $relativePath = generate_unique_filename($fileName);
                $fullPath = UPLOAD_PATH . 'media/' . $relativePath;
                $dir = dirname($fullPath);
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                if (!move_uploaded_file($tmpName, $fullPath)) continue;

                $ai = $openai->generateFromImage($fullPath);
                $mediaId = Database::insert('media_library', [
                    'user_id' => $userId,
                    'filename' => basename($relativePath),
                    'original_name' => $fileName,
                    'file_path' => 'media/' . $relativePath,
                    'file_type' => 'image',
                    'mime_type' => $fileType,
                    'file_size' => $fileSize,
                    'ai_title' => $ai['title'] ?? '',
                    'ai_description' => $ai['description'] ?? '',
                    'ai_hashtags' => $ai['hashtags'] ?? '',
                    'ai_seo' => $ai['seo_text'] ?? '',
                    'ai_cta' => $ai['cta'] ?? '',
                    'ai_processed' => 1,
                ]);

                $time = ($slot % 2 === 0) ? $morningTime : $eveningTime;
                if ($slot % 2 === 1) {
                    $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
                }

                $content = ($ai['description'] ?? '') . "\n\n" . ($ai['hashtags'] ?? '');
                $accountId = $accountIds[$slot % count($accountIds)];

                Database::insert('scheduled_posts', [
                    'user_id' => $userId,
                    'social_account_id' => (int) $accountId,
                    'media_id' => $mediaId,
                    'title' => $ai['title'] ?? '',
                    'content' => $content,
                    'hashtags' => $ai['hashtags'] ?? '',
                    'scheduled_at' => $currentDate . ' ' . $time . ':00',
                    'status' => 'pending',
                    'auto_generated' => 1,
                ]);

                $slot++;
                $totalPosts++;
            }
        }

        Database::update('auto_campaigns', ['total_posts' => $totalPosts], 'id = ?', [$campaignId]);
        Security::jsonResponse(['success' => true, 'campaign_id' => $campaignId, 'total_posts' => $totalPosts]);
        break;

    default:
        Security::jsonResponse(['success' => false, 'message' => 'Geçersiz işlem'], 400);
}
