<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Geçersiz istek.';
    } else {
        $result = Auth::resetPassword($_POST['token'] ?? '', $_POST['password'] ?? '');
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Şifre - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="card login-card">
        <div class="card-body p-5">
            <h4 class="text-center mb-4">Yeni Şifre Belirle</h4>
            <?php if ($success): ?>
            <div class="alert alert-success"><?= Security::escape($success) ?></div>
            <a href="login.php" class="btn btn-primary w-100">Giriş Yap</a>
            <?php else: ?>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?= Security::escape($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <?= Security::csrfField() ?>
                <input type="hidden" name="token" value="<?= Security::escape($token) ?>">
                <div class="mb-3">
                    <label class="form-label">Yeni Şifre</label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Şifre Tekrar</label>
                    <input type="password" name="password_confirm" class="form-control" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Şifreyi Güncelle</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
