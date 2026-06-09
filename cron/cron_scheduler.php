<?php
/**
 * SocialPilot AI - Cron Scheduler
 * Run every minute: * * * * * php /path/to/cron/cron_scheduler.php
 */

require_once __DIR__ . '/../includes/bootstrap.php';

if (php_sapi_name() !== 'cli') {
    $key = $_GET['key'] ?? '';
    if ($key !== CRON_SECRET_KEY) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$tasks = [
    'publish_posts' => __DIR__ . '/tasks/publish_posts.php',
    'refresh_tokens' => __DIR__ . '/tasks/refresh_tokens.php',
    'fetch_stats' => __DIR__ . '/tasks/fetch_stats.php',
    'ai_content' => __DIR__ . '/tasks/ai_content.php',
    'check_errors' => __DIR__ . '/tasks/check_errors.php',
];

foreach ($tasks as $name => $file) {
    $start = microtime(true);
    $status = 'success';
    $message = '';
    $processed = 0;

    try {
        if (file_exists($file)) {
            $processed = (int) include $file;
        }
    } catch (Throwable $e) {
        $status = 'failed';
        $message = $e->getMessage();
        app_log('cron_' . $name, $e->getMessage());
    }

    $elapsed = round(microtime(true) - $start, 3);

    Database::insert('cron_logs', [
        'task_name' => $name,
        'status' => $status,
        'message' => $message ?: "Processed {$processed} items",
        'items_processed' => $processed,
        'execution_time' => $elapsed,
    ]);
}

if (php_sapi_name() !== 'cli') {
    echo json_encode(['success' => true, 'tasks' => count($tasks)]);
}
