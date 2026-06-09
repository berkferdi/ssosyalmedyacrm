<?php

$processed = 0;

$unresolved = Database::count('error_logs', 'resolved = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)');

if ($unresolved > 10) {
    $admins = Database::fetchAll("SELECT id, email FROM users WHERE role = 'super_admin' AND status = 'active'");
    foreach ($admins as $admin) {
        create_notification($admin['id'], 'system', 'Yüksek Hata Oranı',
            "Son 1 saatte {$unresolved} çözülmemiş hata kaydı var.");
    }
    $processed = $unresolved;
}

$oldResolved = Database::delete(
    'error_logs',
    'resolved = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)'
);
$processed += $oldResolved;

$oldCronLogs = Database::delete(
    'cron_logs',
    'created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)'
);
$processed += $oldCronLogs;

return $processed;
