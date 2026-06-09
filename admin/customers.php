<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole(['super_admin', 'agency_manager']);

$customers = Database::fetchAll(
    'SELECT c.*, a.name as agency_name FROM customers c
     JOIN agencies a ON c.agency_id = a.id ORDER BY c.created_at DESC'
);

$pageTitle = 'Müşteriler';
$currentPage = 'customers';

include TEMPLATES_PATH . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Müşteriler</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal">
        <i class="bi bi-plus-lg"></i> Yeni Müşteri
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr><th>Şirket</th><th>İletişim</th><th>E-posta</th><th>Ajans</th><th>Durum</th><th>İşlem</th></tr>
            </thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
            <tr>
                <td><?= Security::escape($c['company_name']) ?></td>
                <td><?= Security::escape($c['contact_name'] ?? '-') ?></td>
                <td><?= Security::escape($c['email']) ?></td>
                <td><?= Security::escape($c['agency_name']) ?></td>
                <td><?= status_badge($c['status']) ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary btn-edit-customer" data-id="<?= $c['id'] ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="customerForm">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Müşteri</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Şirket Adı</label>
                        <input type="text" name="company_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İletişim Kişisi</label>
                        <input type="text" name="contact_name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#customerForm').on('submit', function(e) {
    e.preventDefault();
    apiRequest('/api/customers.php', $(this).serialize() + '&action=create').done(res => {
        if (res.success) location.reload();
    });
});
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
