<?php
require_once __DIR__ . '/../includes/bootstrap.php';

Auth::requireLogin();

if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    Security::jsonResponse(['success' => false, 'message' => 'Geçersiz CSRF token'], 403);
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'mark_all_read':
        Database::update('notifications', ['is_read' => 1], 'user_id = ?', [Auth::id()]);
        Security::jsonResponse(['success' => true]);
        break;

    default:
        Security::jsonResponse(['success' => false, 'message' => 'Geçersiz işlem'], 400);
}
