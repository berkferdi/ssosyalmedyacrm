<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Geçersiz istek.';
    } else {
        $result = Auth::requestPasswordReset($_POST['email'] ?? '');
        $message = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırlama - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="card login-card">
        <div class="card-body p-5">
            <h4 class="text-center mb-4">Şifre Sıfırlama</h4>
            <?php if ($message): ?>
            <div class="alert alert-success"><?= Security::escape($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?= Security::escape($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <?= Security::csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">E-posta Adresiniz</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sıfırlama Bağlantısı Gönder</button>
            </form>
            <div class="text-center mt-3">
                <a href="login.php">Giriş sayfasına dön</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
