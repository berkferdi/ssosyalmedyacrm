<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole(['super_admin', 'agency_manager']);

$projects = Database::fetchAll(
    'SELECT p.*, c.company_name, a.name as agency_name FROM projects p
     JOIN customers c ON p.customer_id = c.id
     JOIN agencies a ON p.agency_id = a.id ORDER BY p.created_at DESC'
);

$pageTitle = 'Projeler';
$currentPage = 'projects';

include TEMPLATES_PATH . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Projeler</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#projectModal">
        <i class="bi bi-plus-lg"></i> Yeni Proje
    </button>
</div>

<div class="row g-3">
    <?php foreach ($projects as $p): ?>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5><?= Security::escape($p['name']) ?></h5>
                <p class="text-muted small"><?= Security::escape($p['description'] ?? '') ?></p>
                <div class="d-flex justify-content-between align-items-center">
                    <small><i class="bi bi-building"></i> <?= Security::escape($p['company_name']) ?></small>
                    <?= status_badge($p['status']) ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="projectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="projectForm">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Proje</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Proje Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Müşteri ID</label>
                        <input type="number" name="customer_id" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Oluştur</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#projectForm').on('submit', function(e) {
    e.preventDefault();
    apiRequest('/api/projects.php', $(this).serialize() + '&action=create').done(res => {
        if (res.success) location.reload();
    });
});
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
