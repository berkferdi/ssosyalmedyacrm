<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'Yapay Zeka Modülü';
$currentPage = 'ai';

include TEMPLATES_PATH . '/header.php';
?>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-robot me-2"></i>AI İçerik Üretici</h6></div>
            <div class="card-body">
                <form id="aiForm">
                    <div class="mb-3">
                        <label class="form-label">Görsel / İçerik Açıklaması</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Örn: E-imza hizmeti tanıtım görseli" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ek Bağlam (opsiyonel)</label>
                        <input type="text" name="context" class="form-control" placeholder="Hedef kitle, sektör vb.">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-magic"></i> İçerik Oluştur
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card" id="aiResult" style="display:none">
            <div class="card-header"><h6 class="mb-0">AI Çıktısı</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Başlık</label>
                    <div id="aiTitle" class="form-control bg-light"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Açıklama</label>
                    <div id="aiDescription" class="form-control bg-light" style="min-height:80px"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Hashtag</label>
                    <div id="aiHashtags" class="form-control bg-light"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">SEO Metni</label>
                    <div id="aiSeo" class="form-control bg-light"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">CTA</label>
                    <div id="aiCta" class="form-control bg-light"></div>
                </div>
                <button class="btn btn-outline-primary btn-sm" id="copyAll">
                    <i class="bi bi-clipboard"></i> Tümünü Kopyala
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$('#aiForm').on('submit', function(e) {
    e.preventDefault();
    const btn = $(this).find('button[type=submit]');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Üretiliyor...');

    apiRequest('/api/ai.php', $(this).serialize() + '&action=generate_text').done(function(res) {
        btn.prop('disabled', false).html('<i class="bi bi-magic"></i> İçerik Oluştur');
        if (res.success) {
            $('#aiTitle').text(res.title);
            $('#aiDescription').text(res.description);
            $('#aiHashtags').text(res.hashtags);
            $('#aiSeo').text(res.seo_text);
            $('#aiCta').text(res.cta);
            $('#aiResult').show();
        } else {
            showToast(res.message, 'danger');
        }
    });
});

$('#copyAll').on('click', function() {
    const text = $('#aiTitle').text() + '\n\n' + $('#aiDescription').text() + '\n\n' + $('#aiHashtags').text();
    navigator.clipboard.writeText(text);
    showToast('Kopyalandı');
});
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
