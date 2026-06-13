<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (Auth::check()) {
    Security::redirect(APP_URL . '/admin/');
}

$error = '';
$show2fa = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Geçersiz istek.';
    } elseif (!empty($_POST['totp_code'])) {
        $result = Auth::verify2FA($_POST['totp_code']);
        if ($result['success']) {
            Security::redirect(APP_URL . '/admin/');
        }
        $error = $result['message'];
        $show2fa = true;
    } else {
        $result = Auth::login($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($result['success'] && !empty($result['requires_2fa'])) {
            $show2fa = true;
        } elseif ($result['success']) {
            Security::redirect(APP_URL . '/admin/');
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
    <title>Giriş - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="card login-card">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <i class="bi bi-rocket-takeoff text-primary" style="font-size: 3rem;"></i>
                <h3 class="mt-2"><?= APP_NAME ?></h3>
                <p class="text-muted">Sosyal Medya Yönetim Paneli</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger"><?= Security::escape($error) ?></div>
            <?php endif; ?>

            <?php if ($show2fa): ?>
            <form method="POST">
                <?= Security::csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">2FA Doğrulama Kodu</label>
                    <input type="text" name="totp_code" class="form-control form-control-lg text-center"
                           maxlength="6" pattern="[0-9]{6}" autofocus required>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-lg">Doğrula</button>
            </form>
            <?php else: ?>
            <form method="POST">
                <?= Security::csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">E-posta</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Şifre</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">Giriş Yap</button>
                <div class="text-center">
                    <a href="forgot-password.php" class="text-muted small">Şifremi Unuttum</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
