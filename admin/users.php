<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole(['super_admin', 'agency_manager']);

$users = Database::fetchAll('SELECT * FROM users ORDER BY created_at DESC');

$pageTitle = 'Kullanıcılar';
$currentPage = 'users';

include TEMPLATES_PATH . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Kullanıcılar</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
        <i class="bi bi-person-plus"></i> Yeni Kullanıcı
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr><th>Ad Soyad</th><th>E-posta</th><th>Rol</th><th>Durum</th><th>Son Giriş</th><th>IP</th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= Security::escape($u['first_name'] . ' ' . $u['last_name']) ?></td>
                <td><?= Security::escape($u['email']) ?></td>
                <td><span class="badge bg-primary"><?= role_label($u['role']) ?></span></td>
                <td><?= status_badge($u['status']) ?></td>
                <td><?= $u['last_login'] ? time_ago($u['last_login']) : '-' ?></td>
                <td><small><?= Security::escape($u['last_ip'] ?? '-') ?></small></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="userForm">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Kullanıcı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Ad</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Soyad</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şifre</label>
                        <input type="password" name="password" class="form-control" minlength="8" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select name="role" class="form-select">
                            <option value="editor">Editör</option>
                            <option value="agency_manager">Ajans Yöneticisi</option>
                            <option value="customer">Müşteri</option>
                        </select>
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
$('#userForm').on('submit', function(e) {
    e.preventDefault();
    apiRequest('/api/users.php', $(this).serialize() + '&action=create').done(res => {
        if (res.success) location.reload();
        else showToast(res.message, 'danger');
    });
});
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
