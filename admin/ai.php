<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'Yapay Zeka Modülü';
$currentPage = 'ai';
$extraScripts = ['ai.js'];

$prefillDescription = Security::escape($_GET['description'] ?? '');
$prefillContext = Security::escape($_GET['context'] ?? '');

include TEMPLATES_PATH . '/header.php';
?>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-robot me-2"></i>AI İçerik Üretici</h6></div>
            <div class="card-body">
                <form id="aiForm" method="post" action="#" onsubmit="return false;">
                    <div class="mb-3">
                        <label class="form-label">Görsel / İçerik Açıklaması</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Örn: E-imza hizmeti tanıtım görseli" required><?= $prefillDescription ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ek Bağlam (opsiyonel)</label>
                        <input type="text" name="context" class="form-control"
                               placeholder="Hedef kitle, sektör vb." value="<?= $prefillContext ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" id="aiSubmitBtn">
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
                <div class="mb-3" id="aiEmojisWrap" style="display:none">
                    <label class="form-label fw-bold">Emojiler</label>
                    <div id="aiEmojis" class="form-control bg-light"></div>
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
                <button type="button" class="btn btn-outline-primary btn-sm" id="copyAll">
                    <i class="bi bi-clipboard"></i> Tümünü Kopyala
                </button>
            </div>
        </div>
        <div class="card" id="aiPlaceholder">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-robot display-4"></i>
                <p class="mt-3 mb-0">Açıklama yazıp "İçerik Oluştur"a tıklayın.<br>AI başlık, açıklama ve hashtag üretecek.</p>
            </div>
        </div>
    </div>
</div>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
