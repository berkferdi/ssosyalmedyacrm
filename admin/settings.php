<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$userId = Auth::id();
$pageTitle = 'Ayarlar';
$currentPage = 'settings';

$openaiKey = Auth::hasRole('super_admin') ? get_api_setting('openai_api_key') : '';
$openaiModel = Auth::hasRole('super_admin') ? (get_api_setting('openai_model') ?: (defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini')) : '';
$facebookAppId = Auth::hasRole('super_admin') ? get_api_setting('facebook_app_id') : '';
$linkedinClientId = Auth::hasRole('super_admin') ? get_api_setting('linkedin_client_id') : '';
$configuredAppUrl = Auth::hasRole('super_admin') ? get_system_setting('app_url', '') : '';
$detectedAppUrl = app_url();

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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">API Anahtarları</h6>
                <?php if ($openaiKey): ?>
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>OpenAI bağlı</span>
                <?php else: ?>
                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>OpenAI anahtarı yok</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form id="apiKeysForm">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">OpenAI API Key</label>
                            <input type="password" name="openai_api_key" id="openaiApiKey" class="form-control"
                                   placeholder="<?= $openaiKey ? Security::escape(mask_api_key($openaiKey) . ' (kayıtlı — değiştirmek için yeni key girin)') : 'sk-proj-...' ?>"
                                   autocomplete="off">
                            <small class="text-muted">Ayarlar veritabanına kaydedilir. Boş bırakırsanız mevcut key korunur.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">OpenAI Model</label>
                            <select name="openai_model" class="form-select">
                                <?php
                                $models = ['gpt-4o-mini', 'gpt-4o', 'gpt-4-turbo', 'gpt-3.5-turbo'];
                                foreach ($models as $model):
                                ?>
                                <option value="<?= $model ?>" <?= $openaiModel === $model ? 'selected' : '' ?>><?= $model ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Facebook App ID</label>
                            <input type="text" name="facebook_app_id" class="form-control"
                                   value="<?= Security::escape($facebookAppId) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Facebook App Secret</label>
                            <input type="password" name="facebook_app_secret" class="form-control"
                                   placeholder="<?= get_api_setting('facebook_app_secret') ? '•••••••• (kayıtlı)' : '' ?>"
                                   autocomplete="off">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">LinkedIn Client ID</label>
                            <input type="text" name="linkedin_client_id" class="form-control"
                                   value="<?= Security::escape($linkedinClientId) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">LinkedIn Client Secret</label>
                            <input type="password" name="linkedin_client_secret" class="form-control"
                                   placeholder="<?= get_api_setting('linkedin_client_secret') ? '•••••••• (kayıtlı)' : '' ?>"
                                   autocomplete="off">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> API Ayarlarını Kaydet
                        </button>
                        <button type="button" class="btn btn-outline-info" id="testOpenAiBtn">
                            <i class="bi bi-plug me-1"></i> OpenAI Bağlantısını Test Et
                        </button>
                    </div>
                </form>
                <div id="apiTestResult" class="mt-3" style="display:none"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
$('#profileForm').on('submit', function(e) {
    e.preventDefault();
    apiRequest('/api/settings.php', $(this).serialize() + '&action=update_profile').done(function(res) {
        if (res.success) showToast('Profil güncellendi');
        else showToast(res.message || 'Hata oluştu', 'danger');
    }).fail(function() {
        showToast('İstek başarısız', 'danger');
    });
});

$('#passwordForm').on('submit', function(e) {
    e.preventDefault();
    apiRequest('/api/settings.php', $(this).serialize() + '&action=change_password').done(function(res) {
        if (res.success) showToast('Şifre değiştirildi');
        else showToast(res.message, 'danger');
    });
});

$('#apiKeysForm').on('submit', function(e) {
    e.preventDefault();
    const btn = $(this).find('button[type=submit]');
    btn.prop('disabled', true);

    apiRequest('/api/settings.php', $(this).serialize() + '&action=save_api_keys').done(function(res) {
        btn.prop('disabled', false);
        if (res.success) {
            showToast(res.message || 'API ayarları kaydedildi');
            setTimeout(function() { location.reload(); }, 1200);
        } else {
            showToast(res.message || 'Kayıt başarısız', 'danger');
        }
    }).fail(function(xhr) {
        btn.prop('disabled', false);
        const res = xhr.responseJSON;
        showToast(res?.message || 'Kayıt başarısız', 'danger');
    });
});

$('#testOpenAiBtn').on('click', function() {
    const btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Test ediliyor...');

    const data = $('#apiKeysForm').serialize() + '&action=test_openai';

    apiRequest('/api/settings.php', data).done(function(res) {
        btn.prop('disabled', false).html('<i class="bi bi-plug me-1"></i> OpenAI Bağlantısını Test Et');
        const alertClass = res.success ? 'alert-success' : 'alert-danger';
        $('#apiTestResult').html('<div class="alert ' + alertClass + ' mb-0">' + (res.message || '') + '</div>').show();
        if (res.success) showToast('OpenAI bağlantısı başarılı');
    }).fail(function(xhr) {
        btn.prop('disabled', false).html('<i class="bi bi-plug me-1"></i> OpenAI Bağlantısını Test Et');
        const res = xhr.responseJSON;
        $('#apiTestResult').html('<div class="alert alert-danger mb-0">' + (res?.message || 'Bağlantı testi başarısız') + '</div>').show();
    });
});
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
