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

$fbAppId = get_api_setting('facebook_app_id');
$fbSecret = get_api_setting('facebook_app_secret');
$fbRedirect = oauth_redirect_uri('facebook');
$igRedirect = oauth_redirect_uri('instagram');
$siteUrl = app_url();

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
            <li><a class="dropdown-item" href="<?= app_url() ?>/api/oauth/facebook.php">
                <i class="bi bi-facebook text-primary me-2"></i>Facebook Sayfası</a></li>
            <li><a class="dropdown-item" href="<?= app_url() ?>/api/oauth/instagram.php">
                <i class="bi bi-instagram text-danger me-2"></i>Instagram Business</a></li>
            <li><a class="dropdown-item" href="<?= app_url() ?>/api/oauth/linkedin.php">
                <i class="bi bi-linkedin text-info me-2"></i>LinkedIn Şirket</a></li>
            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#wpModal">
                <i class="bi bi-wordpress text-primary me-2"></i>WordPress Blog</a></li>
        </ul>
    </div>
</div>

<?php if (!empty($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i><?= Security::escape($_GET['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($_GET['connected'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i><?= Security::escape(ucfirst($_GET['connected'])) ?> hesabı başarıyla bağlandı.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (Auth::hasRole('super_admin') && ($fbAppId === '' || $fbSecret === '')): ?>
<div class="alert alert-warning">
    <strong>Meta API ayarları eksik.</strong>
    <a href="<?= app_url() ?>/admin/settings.php">Ayarlar</a> sayfasından Facebook App ID ve App Secret girin.
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-meta me-2"></i>Meta (Facebook / Instagram) Kurulum Rehberi</h6></div>
    <div class="card-body">
        <p class="text-muted mb-3">Facebook Developer Console'da aşağıdaki adresleri <strong>birebir</strong> ekleyin. Meta yalnızca <strong>HTTPS</strong> kabul eder.</p>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold">Facebook Redirect URI</label>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control font-monospace" id="fbRedirect" readonly value="<?= Security::escape($fbRedirect) ?>">
                    <button class="btn btn-outline-secondary btn-copy" data-target="fbRedirect" type="button"><i class="bi bi-clipboard"></i></button>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Instagram Redirect URI</label>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control font-monospace" id="igRedirect" readonly value="<?= Security::escape($igRedirect) ?>">
                    <button class="btn btn-outline-secondary btn-copy" data-target="igRedirect" type="button"><i class="bi bi-clipboard"></i></button>
                </div>
            </div>
        </div>
        <hr>
        <ol class="small mb-0">
            <li><a href="https://developers.facebook.com/apps/" target="_blank">developers.facebook.com</a> → Uygulamanız → <strong>Facebook Login → Settings</strong></li>
            <li><strong>Valid OAuth Redirect URIs</strong> alanına yukarıdaki 2 HTTPS adresini ekleyin</li>
            <li><strong>App Settings → Basic</strong> → App Domains: <code><?= Security::escape(parse_url($siteUrl, PHP_URL_HOST)) ?></code></li>
            <li>Instagram ürününü ekleyin → <strong>Instagram Graph API</strong> aktif olsun</li>
            <li>Instagram hesabınız <strong>Business veya Creator</strong> olmalı ve bir Facebook sayfasına bağlı olmalı</li>
            <li>Uygulama modu <strong>Live</strong> olmalı (Development modda sadece test kullanıcıları bağlanabilir)</li>
        </ol>
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
$('.btn-copy').on('click', function() {
    const id = $(this).data('target');
    const val = $('#' + id).val();
    navigator.clipboard.writeText(val);
    showToast('Kopyalandı: ' + val);
});
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
