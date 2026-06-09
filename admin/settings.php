<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$userId = Auth::id();
$pageTitle = 'Ayarlar';
$currentPage = 'settings';

include TEMPLATES_PATH . '/header.php';
?>

<h4 class="mb-4">Ayarlar</h4>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Profil</h6></div>
            <div class="card-body">
                <form id="profileForm">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Ad</label>
                            <input type="text" name="first_name" class="form-control"
                                   value="<?= Security::escape(Auth::user()['first_name']) ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Soyad</label>
                            <input type="text" name="last_name" class="form-control"
                                   value="<?= Security::escape(Auth::user()['last_name']) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" class="form-control" value="<?= Security::escape(Auth::user()['email']) ?>" disabled>
                    </div>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Şifre Değiştir</h6></div>
            <div class="card-body">
                <form id="passwordForm">
                    <div class="mb-3">
                        <label class="form-label">Mevcut Şifre</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yeni Şifre</label>
                        <input type="password" name="new_password" class="form-control" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-warning">Şifreyi Değiştir</button>
                </form>
            </div>
        </div>
    </div>
    <?php if (Auth::hasRole('super_admin')): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">API Anahtarları</h6></div>
            <div class="card-body">
                <form id="apiKeysForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">OpenAI API Key</label>
                            <input type="text" name="openai_api_key" class="form-control" placeholder="sk-...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Facebook App ID</label>
                            <input type="text" name="facebook_app_id" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Facebook App Secret</label>
                            <input type="password" name="facebook_app_secret" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">LinkedIn Client ID</label>
                            <input type="text" name="linkedin_client_id" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">API Ayarlarını Kaydet</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
$('#profileForm').on('submit', function(e) {
    e.preventDefault();
    apiRequest('/api/settings.php', $(this).serialize() + '&action=update_profile').done(res => {
        if (res.success) showToast('Profil güncellendi');
    });
});
$('#passwordForm').on('submit', function(e) {
    e.preventDefault();
    apiRequest('/api/settings.php', $(this).serialize() + '&action=change_password').done(res => {
        if (res.success) showToast('Şifre değiştirildi');
        else showToast(res.message, 'danger');
    });
});
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
