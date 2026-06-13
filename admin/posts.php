<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$posts = Database::fetchAll(
    'SELECT sp.*, sa.platform, sa.account_name, ml.original_name as media_name
     FROM scheduled_posts sp
     JOIN social_accounts sa ON sp.social_account_id = sa.id
     LEFT JOIN media_library ml ON sp.media_id = ml.id
     WHERE sp.user_id = ? ORDER BY sp.scheduled_at DESC',
    [Auth::id()]
);
$accounts = Database::fetchAll(
    'SELECT id, platform, account_name FROM social_accounts WHERE user_id = ? AND status = ?',
    [Auth::id(), 'active']
);

$pageTitle = 'Paylaşımlar';
$currentPage = 'posts';

include TEMPLATES_PATH . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Paylaşımlar</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPostModal">
        <i class="bi bi-plus-lg"></i> Yeni Paylaşım
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Platform</th>
                    <th>İçerik</th>
                    <th>Planlanan</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($posts as $post): ?>
            <tr>
                <td>
                    <span class="platform-badge" style="background:<?= platform_color($post['platform']) ?>">
                        <i class="bi <?= platform_icon($post['platform']) ?>"></i>
                        <?= Security::escape($post['account_name']) ?>
                    </span>
                </td>
                <td><?= Security::escape(mb_substr($post['content'], 0, 80)) ?></td>
                <td><?= date('d.m.Y H:i', strtotime($post['scheduled_at'])) ?></td>
                <td><?= status_badge($post['status']) ?></td>
                <td>
                    <?php if ($post['status'] === 'pending'): ?>
                    <button class="btn btn-sm btn-outline-danger btn-delete-post" data-id="<?= $post['id'] ?>">
                        <i class="bi bi-trash"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="newPostModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="newPostForm">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Paylaşım Planla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hesap</label>
                            <select name="social_account_id" class="form-select" required>
                                <option value="">Seçin...</option>
                                <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= Security::escape($acc['account_name']) ?> (<?= $acc['platform'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tarih</label>
                            <input type="date" name="schedule_date" class="form-control" required
                                   min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Saat</label>
                            <input type="time" name="schedule_time" class="form-control" required value="09:00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Başlık</label>
                        <input type="text" name="title" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İçerik</label>
                        <textarea name="content" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hashtag</label>
                        <input type="text" name="hashtags" class="form-control" placeholder="#etiket1 #etiket2">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Planla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#newPostForm').on('submit', function(e) {
    e.preventDefault();
    apiRequest('/api/posts.php', $(this).serialize() + '&action=create').done(function(res) {
        if (res.success) { showToast('Paylaşım planlandı'); location.reload(); }
        else showToast(res.message, 'danger');
    });
});
$('.btn-delete-post').on('click', function() {
    if (!confirm('Silmek istediğinize emin misiniz?')) return;
    apiRequest('/api/posts.php', { action: 'delete', id: $(this).data('id') }).done(res => {
        if (res.success) location.reload();
    });
});
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
