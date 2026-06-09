<?php
$menuItems = [
    ['url' => '/admin/index.php', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'page' => 'dashboard'],
    ['url' => '/admin/accounts.php', 'icon' => 'bi-share', 'label' => 'Sosyal Hesaplar', 'page' => 'accounts'],
    ['url' => '/admin/media.php', 'icon' => 'bi-images', 'label' => 'İçerik Kütüphanesi', 'page' => 'media'],
    ['url' => '/admin/posts.php', 'icon' => 'bi-calendar-check', 'label' => 'Paylaşımlar', 'page' => 'posts'],
    ['url' => '/admin/calendar.php', 'icon' => 'bi-calendar3', 'label' => 'İçerik Takvimi', 'page' => 'calendar'],
    ['url' => '/admin/ai.php', 'icon' => 'bi-robot', 'label' => 'Yapay Zeka', 'page' => 'ai'],
    ['url' => '/admin/auto-mode.php', 'icon' => 'bi-lightning', 'label' => 'Tam Otomatik Mod', 'page' => 'auto-mode'],
    ['url' => '/admin/wordpress.php', 'icon' => 'bi-wordpress', 'label' => 'WordPress', 'page' => 'wordpress'],
    ['url' => '/admin/bulk.php', 'icon' => 'bi-file-earmark-spreadsheet', 'label' => 'Toplu İşlemler', 'page' => 'bulk'],
    ['url' => '/admin/analytics.php', 'icon' => 'bi-graph-up', 'label' => 'Analitik', 'page' => 'analytics'],
    ['url' => '/admin/notifications.php', 'icon' => 'bi-bell', 'label' => 'Bildirimler', 'page' => 'notifications'],
];

if (Auth::hasRole(['super_admin', 'agency_manager'])) {
    $menuItems[] = ['url' => '/admin/customers.php', 'icon' => 'bi-people', 'label' => 'Müşteriler', 'page' => 'customers'];
    $menuItems[] = ['url' => '/admin/projects.php', 'icon' => 'bi-folder', 'label' => 'Projeler', 'page' => 'projects'];
    $menuItems[] = ['url' => '/admin/users.php', 'icon' => 'bi-person-gear', 'label' => 'Kullanıcılar', 'page' => 'users'];
}

if (Auth::hasRole('super_admin')) {
    $menuItems[] = ['url' => '/admin/settings.php', 'icon' => 'bi-gear', 'label' => 'Sistem Ayarları', 'page' => 'settings'];
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header p-3 border-bottom">
        <a href="<?= APP_URL ?>/admin/" class="text-decoration-none d-flex align-items-center">
            <i class="bi bi-rocket-takeoff fs-4 text-primary me-2"></i>
            <span class="fw-bold sidebar-brand"><?= APP_NAME ?></span>
        </a>
    </div>
    <nav class="sidebar-nav p-2">
        <?php foreach ($menuItems as $item): ?>
        <a href="<?= APP_URL . $item['url'] ?>"
           class="nav-link <?= ($currentPage === $item['page']) ? 'active' : '' ?>">
            <i class="bi <?= $item['icon'] ?> me-2"></i>
            <span><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
</aside>
