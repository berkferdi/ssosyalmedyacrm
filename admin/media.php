<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$media = Database::fetchAll(
    'SELECT * FROM media_library WHERE user_id = ? ORDER BY created_at DESC',
    [Auth::id()]
);
$folders = Database::fetchAll(
    'SELECT * FROM media_folders WHERE user_id = ? ORDER BY name',
    [Auth::id()]
);

$pageTitle = 'İçerik Kütüphanesi';
$currentPage = 'media';

include TEMPLATES_PATH . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">İçerik Kütüphanesi</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#folderModal">
            <i class="bi bi-folder-plus"></i> Klasör
        </button>
        <button class="btn btn-primary" id="uploadBtn">
            <i class="bi bi-cloud-upload"></i> Yükle
        </button>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <input type="text" class="form-control" id="mediaSearch" placeholder="Ara...">
    </div>
    <div class="col-md-3">
        <select class="form-select" id="filterType">
            <option value="">Tüm Türler</option>
            <option value="image">Görsel</option>
            <option value="video">Video</option>
            <option value="pdf">PDF</option>
        </select>
    </div>
</div>

<div class="dropzone mb-4" id="dropzone">
    <i class="bi bi-cloud-arrow-up display-4 text-muted"></i>
    <p class="mb-0 mt-2">Dosyaları sürükleyip bırakın veya tıklayarak seçin</p>
    <small class="text-muted">Görsel, Video, PDF (max 50MB)</small>
    <input type="file" id="fileInput" multiple accept="image/*,video/*,.pdf" hidden>
</div>

<?php if (!empty($folders)): ?>
<div class="mb-3">
    <?php foreach ($folders as $folder): ?>
    <span class="badge bg-light text-dark border me-1 p-2">
        <i class="bi bi-folder me-1"></i><?= Security::escape($folder['name']) ?>
    </span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="media-grid" id="mediaGrid">
    <?php foreach ($media as $item): ?>
    <div class="media-item" data-id="<?= $item['id'] ?>" data-type="<?= $item['file_type'] ?>">
        <?php if ($item['file_type'] === 'image'): ?>
        <img src="<?= APP_URL ?>/uploads/<?= Security::escape($item['file_path']) ?>" alt="">
        <?php elseif ($item['file_type'] === 'video'): ?>
        <div class="d-flex align-items-center justify-content-center h-100">
            <i class="bi bi-play-circle display-4 text-white"></i>
        </div>
        <?php else: ?>
        <div class="d-flex align-items-center justify-content-center h-100">
            <i class="bi bi-file-pdf display-4 text-danger"></i>
        </div>
        <?php endif; ?>
        <div class="media-overlay">
            <?= Security::escape($item['original_name']) ?>
            <?php if ($item['ai_processed']): ?>
            <i class="bi bi-robot text-info" title="AI işlendi"></i>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="mediaDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Medya Detayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="mediaDetailBody"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="folderModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form id="folderForm">
                <div class="modal-header"><h5 class="modal-title">Yeni Klasör</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="text" name="name" class="form-control" placeholder="Klasör adı" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Oluştur</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');

dropzone.addEventListener('click', () => fileInput.click());
dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
dropzone.addEventListener('drop', e => { e.preventDefault(); dropzone.classList.remove('dragover'); uploadFiles(e.dataTransfer.files); });
fileInput.addEventListener('change', () => uploadFiles(fileInput.files));
$('#uploadBtn').on('click', () => fileInput.click());

function uploadFiles(files) {
    Array.from(files).forEach(file => {
        const fd = new FormData();
        fd.append('file', file);
        fd.append('action', 'upload');
        $.ajax({ url: APP_URL + '/api/media.php', method: 'POST', data: fd, processData: false, contentType: false })
            .done(res => { if (res.success) { showToast('Yüklendi: ' + file.name); setTimeout(() => location.reload(), 1000); }});
    });
}

$('.media-item').on('click', function() {
    apiRequest('/api/media.php', { action: 'detail', id: $(this).data('id') }).done(function(res) {
        if (!res.success) return;
        const m = res.media;
        $('#mediaDetailBody').html(`
            <div class="row"><div class="col-md-6">
                ${m.file_type === 'image' ? '<img src="'+APP_URL+'/uploads/'+m.file_path+'" class="img-fluid rounded">' : '<p class="text-muted">Önizleme yok</p>'}
            </div><div class="col-md-6">
                <p><strong>Dosya:</strong> ${m.original_name}</p>
                <p><strong>Boyut:</strong> ${m.file_size_formatted}</p>
                <hr>
                <p><strong>AI Başlık:</strong> ${m.ai_title || '-'}</p>
                <p><strong>AI Açıklama:</strong> ${m.ai_description || '-'}</p>
                <p><strong>Hashtag:</strong> ${m.ai_hashtags || '-'}</p>
                <button class="btn btn-sm btn-info mt-2" onclick="generateAI(${m.id})"><i class="bi bi-robot"></i> AI Üret</button>
            </div></div>`);
        new bootstrap.Modal('#mediaDetailModal').show();
    });
});

function generateAI(id) {
    apiRequest('/api/ai.php', { action: 'generate', media_id: id }).done(res => {
        if (res.success) { showToast('AI içerik oluşturuldu'); location.reload(); }
        else showToast(res.message, 'danger');
    });
}

$('#folderForm').on('submit', function(e) {
    e.preventDefault();
    apiRequest('/api/media.php', $(this).serialize() + '&action=create_folder').done(res => {
        if (res.success) location.reload();
    });
});
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
