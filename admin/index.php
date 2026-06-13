<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$stats = get_dashboard_stats(Auth::id());
$recentPosts = Database::fetchAll(
    'SELECT sp.*, sa.platform, sa.account_name FROM scheduled_posts sp
     JOIN social_accounts sa ON sp.social_account_id = sa.id
     WHERE sp.user_id = ? ORDER BY sp.created_at DESC LIMIT 10',
    [Auth::id()]
);

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
$extraScripts = ['dashboard.js'];

include TEMPLATES_PATH . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Dashboard</h4>
    <span class="text-muted">Hoş geldiniz, <?= Security::escape(Auth::user()['first_name']) ?>!</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                    <i class="bi bi-share"></i>
                </div>
                <div>
                    <div class="text-muted small">Toplam Hesap</div>
                    <h3 class="mb-0"><?= $stats['total_accounts'] ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">Bugünkü Paylaşımlar</div>
                    <h3 class="mb-0"><?= $stats['today_posts'] ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                    <i class="bi bi-clock"></i>
                </div>
                <div>
                    <div class="text-muted small">Bekleyen</div>
                    <h3 class="mb-0"><?= $stats['pending_posts'] ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div>
                    <div class="text-muted small">Planlanmış</div>
                    <h3 class="mb-0"><?= $stats['scheduled_posts'] ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                    <i class="bi bi-eye"></i>
                </div>
                <div>
                    <div class="text-muted small">Son 30 Gün Erişim</div>
                    <h3 class="mb-0"><?= number_format($stats['total_reach']) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                    <i class="bi bi-heart"></i>
                </div>
                <div>
                    <div class="text-muted small">Beğeni</div>
                    <h3 class="mb-0"><?= number_format($stats['total_likes']) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <div class="text-muted small">Takipçi Artışı</div>
                    <h3 class="mb-0"><?= number_format($stats['follower_growth']) ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Etkileşim Grafiği (30 Gün)</h6></div>
            <div class="card-body"><canvas id="engagementChart" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Platform Dağılımı</h6></div>
            <div class="card-body"><canvas id="platformChart"></canvas></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Son Paylaşımlar</h6>
        <a href="posts.php" class="btn btn-sm btn-outline-primary">Tümünü Gör</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>Platform</th><th>İçerik</th><th>Tarih</th><th>Durum</th></tr></thead>
            <tbody>
            <?php if (empty($recentPosts)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Henüz paylaşım yok</td></tr>
            <?php else: foreach ($recentPosts as $post): ?>
            <tr>
                <td><span class="platform-badge" style="background:<?= platform_color($post['platform']) ?>">
                    <i class="bi <?= platform_icon($post['platform']) ?>"></i>
                    <?= Security::escape($post['account_name']) ?>
                </span></td>
                <td><?= Security::escape(mb_substr($post['content'], 0, 60)) ?>...</td>
                <td><?= date('d.m.Y H:i', strtotime($post['scheduled_at'])) ?></td>
                <td><?= status_badge($post['status']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
