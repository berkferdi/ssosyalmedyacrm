<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::logout();
Security::redirect(APP_URL . '/admin/login.php');
