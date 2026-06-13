<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$imports = Database::fetchAll(
    'SELECT * FROM bulk_imports WHERE user_id = ? ORDER BY created_at DESC LIMIT 20',
    [Auth::id()]
);

$pageTitle = 'Toplu İşlemler';
$currentPage = 'bulk';

include TEMPLATES_PATH . '/header.php';
?>

<h4 class="mb-4">Toplu İşlemler</h4>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV/Excel İçe Aktarma</h6></div>
            <div class="card-body">
                <p class="text-muted">CSV veya Excel dosyası ile toplu içerik yükleyin.</p>
                <form id="importForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls" required>
                    </div>
                    <button type="submit" class="btn btn-primary">İçe Aktar</button>
                </form>
                <hr>
                <a href="<?= APP_URL ?>/assets/sample-import.csv" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-download"></i> Örnek CSV İndir
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>Toplu Planlama</h6></div>
            <div class="card-body">
                <form id="bulkScheduleForm">
                    <div class="mb-3">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Günlük Paylaşım Sayısı</label>
                        <input type="number" name="posts_per_day" class="form-control" value="2" min="1" max="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Saatler (virgülle ayırın)</label>
                        <input type="text" name="times" class="form-control" value="09:00,18:00">
                    </div>
                    <button type="submit" class="btn btn-primary">Toplu Planla</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">İçe Aktarma Geçmişi</h6></div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>Dosya</th><th>Tür</th><th>Satır</th><th>İşlenen</th><th>Durum</th><th>Tarih</th></tr></thead>
            <tbody>
            <?php if (empty($imports)): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">Henüz içe aktarma yok</td></tr>
            <?php else: foreach ($imports as $imp): ?>
            <tr>
                <td><?= Security::escape($imp['filename']) ?></td>
                <td><?= strtoupper($imp['file_type']) ?></td>
                <td><?= $imp['total_rows'] ?></td>
                <td><?= $imp['processed_rows'] ?></td>
                <td><?= status_badge($imp['status']) ?></td>
                <td><?= date('d.m.Y H:i', strtotime($imp['created_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$('#importForm').on('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'import');
    fd.append('_csrf_token', CSRF_TOKEN);
    $.ajax({ url: APP_URL + '/api/bulk.php', method: 'POST', data: fd, processData: false, contentType: false })
        .done(res => { if (res.success) { showToast('İçe aktarma başladı'); location.reload(); }});
});
$('#bulkScheduleForm').on('submit', function(e) {
    e.preventDefault();
    apiRequest('/api/bulk.php', $(this).serialize() + '&action=bulk_schedule').done(res => {
        if (res.success) showToast(res.message);
        else showToast(res.message, 'danger');
    });
});
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
