<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$campaigns = Database::fetchAll(
    'SELECT * FROM auto_campaigns WHERE user_id = ? ORDER BY created_at DESC',
    [Auth::id()]
);
$accounts = Database::fetchAll(
    'SELECT id, platform, account_name FROM social_accounts WHERE user_id = ? AND status = ?',
    [Auth::id(), 'active']
);

$pageTitle = 'Tam Otomatik Mod';
$currentPage = 'auto-mode';

include TEMPLATES_PATH . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Tam Otomatik Mod</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#campaignModal">
        <i class="bi bi-lightning"></i> Yeni Kampanya
    </button>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Nasıl çalışır?</strong> Görselleri yükleyin, sistem her biri için AI içerik üretir,
    sabah 09:00 ve akşam 18:00 saatlerinde otomatik paylaşım planlar.
</div>

<?php if (empty($campaigns)): ?>
<div class="card text-center py-5">
    <i class="bi bi-lightning display-1 text-muted"></i>
    <h5 class="mt-3">Aktif kampanya yok</h5>
    <p class="text-muted">100 görsel yükleyin, gerisini AI halletsin.</p>
</div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($campaigns as $camp): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h5><?= Security::escape($camp['name']) ?></h5>
                    <?= status_badge($camp['status']) ?>
                </div>
                <div class="progress my-3" style="height:8px">
                    <div class="progress-bar" style="width:<?= $camp['total_posts'] ? round($camp['published_posts']/$camp['total_posts']*100) : 0 ?>%"></div>
                </div>
                <div class="row text-center">
                    <div class="col-4">
                        <small class="text-muted">Toplam</small>
                        <div class="fw-bold"><?= $camp['total_posts'] ?></div>
                    </div>
                    <div class="col-4">
                        <small class="text-muted">Paylaşılan</small>
                        <div class="fw-bold text-success"><?= $camp['published_posts'] ?></div>
                    </div>
                    <div class="col-4">
                        <small class="text-muted">Saatler</small>
                        <div class="fw-bold"><?= substr($camp['morning_time'],0,5) ?> / <?= substr($camp['evening_time'],0,5) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="modal fade" id="campaignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="campaignForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Otomatik Kampanya</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kampanya Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hesaplar</label>
                        <?php foreach ($accounts as $acc): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="account_ids[]" value="<?= $acc['id'] ?>">
                            <label class="form-check-label"><?= Security::escape($acc['account_name']) ?> (<?= $acc['platform'] ?>)</label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Sabah Saati</label>
                            <input type="time" name="morning_time" class="form-control" value="09:00">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Akşam Saati</label>
                            <input type="time" name="evening_time" class="form-control" value="18:00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" name="start_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Görseller (çoklu seçim)</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-lightning"></i> Kampanyayı Başlat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#campaignForm').on('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'create_campaign');
    fd.append('_csrf_token', CSRF_TOKEN);

    $.ajax({
        url: APP_URL + '/api/posts.php',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false
    }).done(function(res) {
        if (res.success) { showToast('Kampanya oluşturuldu'); location.reload(); }
        else showToast(res.message, 'danger');
    });
});
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
