<?php
require_once __DIR__ . '/../includes/bootstrap.php';

Auth::requireRole(['super_admin', 'agency_manager']);

if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    Security::jsonResponse(['success' => false, 'message' => 'Geçersiz CSRF token'], 403);
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $customer = Database::fetch('SELECT agency_id FROM customers WHERE id = ?', [(int) ($_POST['customer_id'] ?? 0)]);
        if (!$customer) {
            Security::jsonResponse(['success' => false, 'message' => 'Müşteri bulunamadı']);
        }

        $id = Database::insert('projects', [
            'customer_id' => (int) $_POST['customer_id'],
            'agency_id' => $customer['agency_id'],
            'name' => Security::sanitize($_POST['name'] ?? ''),
            'description' => Security::sanitize($_POST['description'] ?? ''),
            'status' => 'active',
        ]);
        Security::jsonResponse(['success' => true, 'id' => $id]);
        break;

    default:
        Security::jsonResponse(['success' => false, 'message' => 'Geçersiz işlem'], 400);
}
