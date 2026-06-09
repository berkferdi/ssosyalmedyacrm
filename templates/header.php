<?php
$currentPage = $currentPage ?? '';
$theme = get_setting('theme', 'light', Auth::id());
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="<?= Security::escape($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= page_title($pageTitle ?? 'Dashboard') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet">
    <?php if ($theme === 'dark'): ?>
    <link href="<?= APP_URL ?>/assets/css/dark-theme.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body>
<div class="wrapper d-flex">
    <?php include TEMPLATES_PATH . '/sidebar.php'; ?>
    <div class="main-content flex-grow-1">
        <nav class="navbar navbar-expand-lg border-bottom px-4 py-2">
            <button class="btn btn-link d-lg-none" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div class="ms-auto d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary" id="themeToggle" title="Tema Değiştir">
                    <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>"></i>
                </button>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                            <?= strtoupper(substr(Auth::user()['first_name'], 0, 1)) ?>
                        </div>
                        <span class="d-none d-md-inline"><?= Security::escape(Auth::user()['first_name'] . ' ' . Auth::user()['last_name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/settings.php"><i class="bi bi-gear me-2"></i>Ayarlar</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Çıkış</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="content-area p-4">
