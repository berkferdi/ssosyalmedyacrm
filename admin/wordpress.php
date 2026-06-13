<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$wpPosts = Database::fetchAll(
    'SELECT wp.*, sa.account_name FROM wordpress_posts wp
     JOIN social_accounts sa ON wp.social_account_id = sa.id
     WHERE wp.user_id = ? ORDER BY wp.created_at DESC',
    [Auth::id()]
);
$wpAccounts = Database::fetchAll(
    'SELECT id, account_name, metadata FROM social_accounts WHERE user_id = ? AND platform = ? AND status = ?',
    [Auth::id(), 'wordpress', 'active']
);

$pageTitle = 'WordPress Entegrasyonu';
$currentPage = 'wordpress';

include TEMPLATES_PATH . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">WordPress</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#wpPostModal">
        <i class="bi bi-plus-lg"></i> Yeni Yazı
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr><th>Başlık</th><th>Site</th><th>Durum</th><th>Tarih</th><th>İşlem</th></tr>
            </thead>
            <tbody>
            <?php foreach ($wpPosts as $post): ?>
            <tr>
                <td><?= Security::escape($post['title']) ?></td>
                <td><?= Security::escape($post['account_name']) ?></td>
                <td><?= status_badge($post['status']) ?></td>
                <td><?= date('d.m.Y H:i', strtotime($post['created_at'])) ?></td>
                <td>
                    <?php if ($post['status'] === 'draft'): ?>
                    <button class="btn btn-sm btn-success btn-publish-wp" data-id="<?= $post['id'] ?>">
                        <i class="bi bi-send"></i> Yayınla
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="wpPostModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="wpPostForm">
                <div class="modal-header">
                    <h5 class="modal-title">WordPress Yazısı Oluştur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">WordPress Sitesi</label>
                        <select name="social_account_id" class="form-select" required>
                            <?php foreach ($wpAccounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= Security::escape($acc['account_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Başlık</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İçerik</label>
                        <textarea name="content" class="form-control" rows="8" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SEO Açıklaması</label>
                        <textarea name="seo_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select">
                            <option value="draft">Taslak</option>
                            <option value="publish">Yayınla</option>
                        </select>
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
$('#wpPostForm').on('submit', function(e) {
    e.preventDefault();
    apiRequest('/api/wordpress.php', $(this).serialize() + '&action=create').done(res => {
        if (res.success) { showToast('Yazı oluşturuldu'); location.reload(); }
        else showToast(res.message, 'danger');
    });
});
$('.btn-publish-wp').on('click', function() {
    apiRequest('/api/wordpress.php', { action: 'publish', id: $(this).data('id') }).done(res => {
        if (res.success) { showToast('Yayınlandı'); location.reload(); }
        else showToast(res.message, 'danger');
    });
});
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
