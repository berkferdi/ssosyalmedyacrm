<?php

$configFile = __DIR__ . '/config.php';

if (!file_exists($configFile)) {
    if (php_sapi_name() !== 'cli' && !str_contains($_SERVER['REQUEST_URI'] ?? '', '/install/')) {
        header('Location: /install/');
        exit;
    }
    return;
}

require_once $configFile;

date_default_timezone_set(APP_TIMEZONE);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/functions.php';

Auth::init();

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

set_exception_handler(function (Throwable $e) {
    app_log('exception', $e->getMessage(), Auth::id(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    if (APP_DEBUG) {
        echo '<pre>' . Security::escape($e->getMessage()) . '</pre>';
    } else {
        http_response_code(500);
        echo 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
    }
});
