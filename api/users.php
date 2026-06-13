<?php
require_once __DIR__ . '/../includes/bootstrap.php';

Auth::requireRole(['super_admin', 'agency_manager']);

if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    Security::jsonResponse(['success' => false, 'message' => 'Geçersiz CSRF token'], 403);
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $email = trim($_POST['email'] ?? '');
        if (!Security::validateEmail($email)) {
            Security::jsonResponse(['success' => false, 'message' => 'Geçersiz e-posta']);
        }
        if (Database::fetch('SELECT id FROM users WHERE email = ?', [$email])) {
            Security::jsonResponse(['success' => false, 'message' => 'E-posta zaten kayıtlı']);
        }
        if (strlen($_POST['password'] ?? '') < PASSWORD_MIN_LENGTH) {
            Security::jsonResponse(['success' => false, 'message' => 'Şifre en az ' . PASSWORD_MIN_LENGTH . ' karakter']);
        }

        $id = Database::insert('users', [
            'role' => $_POST['role'] ?? 'editor',
            'email' => $email,
            'password' => Security::hashPassword($_POST['password']),
            'first_name' => Security::sanitize($_POST['first_name'] ?? ''),
            'last_name' => Security::sanitize($_POST['last_name'] ?? ''),
            'status' => 'active',
        ]);

        Security::jsonResponse(['success' => true, 'id' => $id]);
        break;

    default:
        Security::jsonResponse(['success' => false, 'message' => 'Geçersiz işlem'], 400);
}
