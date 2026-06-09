<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(__DIR__ . '/includes/config.php')) {
    header('Location: /install/');
    exit;
}

header('Location: /admin/');
