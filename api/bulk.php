<?php
require_once __DIR__ . '/../includes/bootstrap.php';

Auth::requireLogin();

if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    Security::jsonResponse(['success' => false, 'message' => 'Geçersiz CSRF token'], 403);
}

$action = $_POST['action'] ?? '';
$userId = Auth::id();

switch ($action) {
    case 'import':
        if (empty($_FILES['file'])) {
            Security::jsonResponse(['success' => false, 'message' => 'Dosya bulunamadı']);
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileType = in_array($ext, ['xlsx', 'xls']) ? 'xlsx' : 'csv';

        $importId = Database::insert('bulk_imports', [
            'user_id' => $userId,
            'filename' => $file['name'],
            'file_type' => $fileType,
            'status' => 'processing',
        ]);

        $processed = 0;
        $total = 0;

        if ($fileType === 'csv' && ($handle = fopen($file['tmp_name'], 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $total++;
                if (count($row) < 3) continue;

                $accountId = (int) ($row[0] ?? 0);
                $content = $row[1] ?? '';
                $scheduledAt = $row[2] ?? '';

                if ($accountId && $content && $scheduledAt) {
                    Database::insert('scheduled_posts', [
                        'user_id' => $userId,
                        'social_account_id' => $accountId,
                        'content' => $content,
                        'hashtags' => $row[3] ?? '',
                        'scheduled_at' => $scheduledAt,
                        'status' => 'pending',
                    ]);
                    $processed++;
                }
            }
            fclose($handle);
        }

        Database::update('bulk_imports', [
            'total_rows' => $total,
            'processed_rows' => $processed,
            'status' => 'completed',
        ], 'id = ?', [$importId]);

        Security::jsonResponse(['success' => true, 'processed' => $processed, 'total' => $total]);
        break;

    case 'bulk_schedule':
        $startDate = $_POST['start_date'] ?? '';
        $postsPerDay = (int) ($_POST['posts_per_day'] ?? 2);
        $times = array_map('trim', explode(',', $_POST['times'] ?? '09:00,18:00'));

        $pendingMedia = Database::fetchAll(
            'SELECT ml.* FROM media_library ml
             LEFT JOIN scheduled_posts sp ON ml.id = sp.media_id
             WHERE ml.user_id = ? AND sp.id IS NULL AND ml.ai_processed = 1
             ORDER BY ml.created_at LIMIT 100',
            [$userId]
        );

        $accounts = Database::fetchAll(
            'SELECT id FROM social_accounts WHERE user_id = ? AND status = ?',
            [$userId, 'active']
        );

        if (empty($accounts) || empty($pendingMedia)) {
            Security::jsonResponse(['success' => false, 'message' => 'Planlanacak medya veya hesap yok']);
        }

        $currentDate = $startDate;
        $scheduled = 0;

        foreach ($pendingMedia as $i => $media) {
            $timeIndex = $i % $postsPerDay;
            if ($timeIndex === 0 && $i > 0) {
                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            }
            $time = $times[$timeIndex % count($times)] ?? '09:00';
            $account = $accounts[$i % count($accounts)];

            $content = ($media['ai_description'] ?? '') . "\n\n" . ($media['ai_hashtags'] ?? '');
            Database::insert('scheduled_posts', [
                'user_id' => $userId,
                'social_account_id' => $account['id'],
                'media_id' => $media['id'],
                'title' => $media['ai_title'] ?? '',
                'content' => $content,
                'hashtags' => $media['ai_hashtags'] ?? '',
                'scheduled_at' => $currentDate . ' ' . $time . ':00',
                'status' => 'pending',
                'auto_generated' => 1,
            ]);
            $scheduled++;
        }

        Security::jsonResponse(['success' => true, 'message' => "{$scheduled} paylaşım planlandı"]);
        break;

    default:
        Security::jsonResponse(['success' => false, 'message' => 'Geçersiz işlem'], 400);
}
