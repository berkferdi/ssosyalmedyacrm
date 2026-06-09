<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/SocialMedia/FacebookAPI.php';
require_once INCLUDES_PATH . '/SocialMedia/InstagramAPI.php';
require_once INCLUDES_PATH . '/SocialMedia/LinkedInAPI.php';

Auth::requireLogin();

$accounts = Database::fetchAll(
    'SELECT * FROM social_accounts WHERE user_id = ? ORDER BY created_at DESC',
    [Auth::id()]
);

$pageTitle = 'Sosyal Medya Hesapları';
$currentPage = 'accounts';

include TEMPLATES_PATH . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Sosyal Medya Hesapları</h4>
    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-plus-lg me-1"></i> Hesap Bağla
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= APP_URL ?>/api/oauth/facebook.php">
                <i class="bi bi-facebook text-primary me-2"></i>Facebook Sayfası</a></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/api/oauth/instagram.php">
                <i class="bi bi-instagram text-danger me-2"></i>Instagram Business</a></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/api/oauth/linkedin.php">
                <i class="bi bi-linkedin text-info me-2"></i>LinkedIn Şirket</a></li>
            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#wpModal">
                <i class="bi bi-wordpress text-primary me-2"></i>WordPress Blog</a></li>
        </ul>
    </div>
</div>

<div class="row g-3">
    <?php if (empty($accounts)): ?>
    <div class="col-12">
        <div class="card text-center py-5">
            <i class="bi bi-share display-1 text-muted"></i>
            <h5 class="mt-3">Henüz bağlı hesap yok</h5>
            <p class="text-muted">Sosyal medya hesaplarınızı OAuth ile bağlayın.</p>
        </div>
    </div>
    <?php else: foreach ($accounts as $acc): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                         style="width:48px;height:48px;background:<?= platform_color($acc['platform']) ?>">
                        <i class="bi <?= platform_icon($acc['platform']) ?> text-white fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0"><?= Security::escape($acc['account_name']) ?></h6>
                        <small class="text-muted text-capitalize"><?= Security::escape($acc['platform']) ?></small>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <?= status_badge($acc['status']) ?>
                    <small class="text-muted">
                        <?= $acc['last_sync'] ? 'Son: ' . time_ago($acc['last_sync']) : 'Henüz senkronize edilmedi' ?>
                    </small>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary flex-fill btn-sync" data-id="<?= $acc['id'] ?>">
                    <i class="bi bi-arrow-repeat"></i> Senkronize
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete-account" data-id="<?= $acc['id'] ?>">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<div class="modal fade" id="wpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="wpConnectForm">
                <div class="modal-header">
                    <h5 class="modal-title">WordPress Bağlantısı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Site URL</label>
                        <input type="url" name="site_url" class="form-control" placeholder="https://blog.example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Uygulama Şifresi</label>
                        <input type="password" name="app_password" class="form-control" required>
                        <small class="text-muted">WordPress > Kullanıcılar > Uygulama Şifreleri</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Bağlan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#wpConnectForm').on('submit', function(e) {
    e.preventDefault();
    apiRequest('/api/accounts.php', $(this).serialize() + '&action=connect_wordpress').done(function(res) {
        if (res.success) { showToast('WordPress bağlandı'); location.reload(); }
        else showToast(res.message, 'danger');
    });
});
$('.btn-delete-account').on('click', function() {
    if (!confirm('Hesabı kaldırmak istediğinize emin misiniz?')) return;
    apiRequest('/api/accounts.php', { action: 'delete', id: $(this).data('id') }).done(function(res) {
        if (res.success) location.reload();
    });
});
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
