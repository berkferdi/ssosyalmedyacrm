<?php
require_once __DIR__ . '/../includes/bootstrap.php';

Auth::requireRole(['super_admin', 'agency_manager']);

if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    Security::jsonResponse(['success' => false, 'message' => 'Geçersiz CSRF token'], 403);
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $agency = Database::fetch('SELECT id FROM agencies LIMIT 1');
        $id = Database::insert('customers', [
            'agency_id' => $agency['id'] ?? 1,
            'company_name' => Security::sanitize($_POST['company_name'] ?? ''),
            'contact_name' => Security::sanitize($_POST['contact_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => Security::sanitize($_POST['phone'] ?? ''),
            'status' => 'active',
        ]);
        Security::jsonResponse(['success' => true, 'id' => $id]);
        break;

    default:
        Security::jsonResponse(['success' => false, 'message' => 'Geçersiz işlem'], 400);
}
