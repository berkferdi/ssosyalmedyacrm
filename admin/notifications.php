<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$notifications = Database::fetchAll(
    'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50',
    [Auth::id()]
);

$pageTitle = 'Bildirimler';
$currentPage = 'notifications';

include TEMPLATES_PATH . '/header.php';
?>

<h4 class="mb-4">Bildirimler</h4>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h6 class="mb-0">Son Bildirimler</h6>
                <button class="btn btn-sm btn-outline-secondary" id="markAllRead">Tümünü Okundu İşaretle</button>
            </div>
            <div class="list-group list-group-flush">
                <?php if (empty($notifications)): ?>
                <div class="list-group-item text-center text-muted py-4">Bildirim yok</div>
                <?php else: foreach ($notifications as $notif): ?>
                <div class="list-group-item <?= $notif['is_read'] ? '' : 'bg-light' ?>">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong><?= Security::escape($notif['title']) ?></strong>
                            <p class="mb-0 small text-muted"><?= Security::escape($notif['message']) ?></p>
                        </div>
                        <small class="text-muted"><?= time_ago($notif['created_at']) ?></small>
                    </div>
                    <div class="mt-1">
                        <?php if ($notif['sent_email']): ?><span class="badge bg-secondary">E-posta</span><?php endif; ?>
                        <?php if ($notif['sent_telegram']): ?><span class="badge bg-info">Telegram</span><?php endif; ?>
                        <?php if ($notif['sent_whatsapp']): ?><span class="badge bg-success">WhatsApp</span><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Bildirim Ayarları</h6></div>
            <div class="card-body">
                <form id="notifSettingsForm">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="email_notifications" id="emailNotif" checked>
                        <label class="form-check-label" for="emailNotif">E-posta Bildirimleri</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="telegram_notifications" id="telegramNotif">
                        <label class="form-check-label" for="telegramNotif">Telegram Bildirimleri</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telegram Chat ID</label>
                        <input type="text" name="telegram_chat_id" class="form-control" placeholder="123456789">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="whatsapp_notifications" id="whatsappNotif">
                        <label class="form-check-label" for="whatsappNotif">WhatsApp Bildirimleri</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">WhatsApp Telefon</label>
                        <input type="text" name="whatsapp_phone" class="form-control" placeholder="905xxxxxxxxx">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$('#markAllRead').on('click', function() {
    apiRequest('/api/notifications.php', { action: 'mark_all_read' }).done(() => location.reload());
});
$('#notifSettingsForm').on('submit', function(e) {
    e.preventDefault();
    apiRequest('/api/settings.php', $(this).serialize() + '&action=save_notifications').done(res => {
        if (res.success) showToast('Ayarlar kaydedildi');
    });
});
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
