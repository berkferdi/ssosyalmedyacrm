<?php
session_start();

$step = (int) ($_GET['step'] ?? 1);
$error = '';
$success = '';

if (file_exists(__DIR__ . '/../includes/config.php') && $step < 5) {
    header('Location: ../admin/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            $_SESSION['install'] = [
                'db_host' => trim($_POST['db_host'] ?? 'localhost'),
                'db_port' => trim($_POST['db_port'] ?? '3306'),
                'db_name' => trim($_POST['db_name'] ?? ''),
                'db_user' => trim($_POST['db_user'] ?? ''),
                'db_pass' => $_POST['db_pass'] ?? '',
            ];

            try {
                $dsn = "mysql:host={$_SESSION['install']['db_host']};port={$_SESSION['install']['db_port']};charset=utf8mb4";
                $pdo = new PDO($dsn, $_SESSION['install']['db_user'], $_SESSION['install']['db_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$_SESSION['install']['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                header('Location: ?step=2');
                exit;
            } catch (PDOException $e) {
                $error = 'Veritabanı bağlantısı başarısız: ' . $e->getMessage();
            }
            break;

        case 2:
            try {
                $install = $_SESSION['install'];
                $dsn = "mysql:host={$install['db_host']};port={$install['db_port']};dbname={$install['db_name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $install['db_user'], $install['db_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);

                $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
                $pdo->exec($schema);
                header('Location: ?step=3');
                exit;
            } catch (PDOException $e) {
                $error = 'Tablo oluşturma hatası: ' . $e->getMessage();
            }
            break;

        case 3:
            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminPass = $_POST['admin_password'] ?? '';
            $adminName = trim($_POST['admin_name'] ?? 'Admin');

            if (strlen($adminPass) < 8) {
                $error = 'Şifre en az 8 karakter olmalı.';
                break;
            }

            try {
                $install = $_SESSION['install'];
                $dsn = "mysql:host={$install['db_host']};port={$install['db_port']};dbname={$install['db_name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $install['db_user'], $install['db_pass']);

                $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
                $parts = explode(' ', $adminName, 2);

                $stmt = $pdo->prepare('INSERT INTO users (role, email, password, first_name, last_name, status) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute(['super_admin', $adminEmail, $hash, $parts[0], $parts[1] ?? '', 'active']);

                $pdo->prepare('INSERT INTO agencies (name, slug, status) VALUES (?, ?, ?)')->execute(['Varsayılan Ajans', 'default', 'active']);

                $_SESSION['install']['admin_email'] = $adminEmail;
                header('Location: ?step=4');
                exit;
            } catch (PDOException $e) {
                $error = 'Admin oluşturma hatası: ' . $e->getMessage();
            }
            break;

        case 4:
            $appUrl = rtrim(trim($_POST['app_url'] ?? ''), '/');
            $secretKey = bin2hex(random_bytes(32));
            $cronKey = bin2hex(random_bytes(16));
            $install = $_SESSION['install'];

            $config = file_get_contents(__DIR__ . '/../includes/config.sample.php');
            $config = str_replace("'http://localhost'", "'{$appUrl}'", $config);
            $config = str_replace("'localhost'", "'{$install['db_host']}'", $config);
            $config = str_replace("'3306'", "'{$install['db_port']}'", $config);
            $config = str_replace("'socialpilot_ai'", "'{$install['db_name']}'", $config);
            $config = str_replace("'root'", "'{$install['db_user']}'", $config);
            $config = str_replace("define('DB_PASS', '');", "define('DB_PASS', '" . addslashes($install['db_pass']) . "');", $config);
            $config = str_replace('CHANGE_THIS_TO_RANDOM_64_CHAR_STRING', $secretKey, $config);
            $config = str_replace('CHANGE_THIS_CRON_SECRET', $cronKey, $config);

            if (!empty($_POST['openai_key'])) {
                $config = str_replace("define('OPENAI_API_KEY', '');", "define('OPENAI_API_KEY', '" . addslashes($_POST['openai_key']) . "');", $config);
            }

            file_put_contents(__DIR__ . '/../includes/config.php', $config);
            unset($_SESSION['install']);
            header('Location: ?step=5');
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurulum - SocialPilot AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea, #764ba2); min-height: 100vh; }
        .install-card { max-width: 600px; margin: 40px auto; border-radius: 16px; }
        .step-indicator { display: flex; justify-content: center; gap: 8px; margin-bottom: 24px; }
        .step-dot { width: 12px; height: 12px; border-radius: 50%; background: #dee2e6; }
        .step-dot.active { background: #0d6efd; }
        .step-dot.done { background: #198754; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card install-card shadow-lg">
        <div class="card-header bg-primary text-white text-center py-4">
            <h3 class="mb-0"><i class="bi bi-rocket-takeoff me-2"></i>SocialPilot AI Kurulum</h3>
            <small>Adım <?= $step ?> / 5</small>
        </div>
        <div class="card-body p-4">
            <div class="step-indicator">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="step-dot <?= $i < $step ? 'done' : ($i === $step ? 'active' : '') ?>"></div>
                <?php endfor; ?>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
            <h5 class="mb-3">Veritabanı Bağlantısı</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Sunucu</label>
                    <input type="text" name="db_host" class="form-control" value="localhost" required>
                </div>
                <div class="row">
                    <div class="col-4 mb-3">
                        <label class="form-label">Port</label>
                        <input type="text" name="db_port" class="form-control" value="3306" required>
                    </div>
                    <div class="col-8 mb-3">
                        <label class="form-label">Veritabanı Adı</label>
                        <input type="text" name="db_name" class="form-control" value="socialpilot_ai" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kullanıcı Adı</label>
                    <input type="text" name="db_user" class="form-control" value="root" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Şifre</label>
                    <input type="password" name="db_pass" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary w-100">Devam Et <i class="bi bi-arrow-right"></i></button>
            </form>

            <?php elseif ($step === 2): ?>
            <h5 class="mb-3">Veritabanı Tabloları</h5>
            <p class="text-muted">Veritabanı tabloları oluşturulacak. Devam etmek için butona tıklayın.</p>
            <form method="POST">
                <button type="submit" class="btn btn-primary w-100">Tabloları Oluştur <i class="bi bi-database"></i></button>
            </form>

            <?php elseif ($step === 3): ?>
            <h5 class="mb-3">Yönetici Hesabı</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Ad Soyad</label>
                    <input type="text" name="admin_name" class="form-control" value="Süper Admin" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">E-posta</label>
                    <input type="email" name="admin_email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Şifre (min. 8 karakter)</label>
                    <input type="password" name="admin_password" class="form-control" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Hesap Oluştur <i class="bi bi-person-plus"></i></button>
            </form>

            <?php elseif ($step === 4): ?>
            <h5 class="mb-3">Uygulama Ayarları</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Uygulama URL</label>
                    <input type="url" name="app_url" class="form-control"
                           value="<?= htmlspecialchars((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">OpenAI API Key (opsiyonel)</label>
                    <input type="text" name="openai_key" class="form-control" placeholder="sk-...">
                </div>
                <button type="submit" class="btn btn-primary w-100">Kurulumu Tamamla <i class="bi bi-check-lg"></i></button>
            </form>

            <?php elseif ($step === 5): ?>
            <div class="text-center py-4">
                <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                <h4 class="mt-3">Kurulum Tamamlandı!</h4>
                <p class="text-muted">SocialPilot AI başarıyla kuruldu. Giriş yapabilirsiniz.</p>
                <a href="../admin/login.php" class="btn btn-primary btn-lg mt-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Giriş Yap
                </a>
                <div class="alert alert-warning mt-4 text-start">
                    <strong>Güvenlik:</strong> Kurulum tamamlandıktan sonra <code>/install/</code> klasörünü silmenizi öneririz.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
