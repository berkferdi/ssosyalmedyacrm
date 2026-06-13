$(document).ready(function () {
    $('#aiForm').on('submit', function (e) {
        e.preventDefault();

        const btn = $(this).find('button[type=submit]');
        const description = $('textarea[name=description]').val().trim();

        if (!description) {
            showToast('Lütfen bir açıklama girin', 'danger');
            return;
        }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Üretiliyor...');
        $('#aiResult').hide();
        $('#aiError').remove();

        apiRequest('/api/ai.php', $(this).serialize() + '&action=generate_text')
            .done(function (res) {
                btn.prop('disabled', false).html('<i class="bi bi-magic"></i> İçerik Oluştur');

                if (res.success) {
                    $('#aiTitle').text(res.title || '');
                    $('#aiDescription').text(res.description || '');
                    $('#aiHashtags').text(res.hashtags || '');
                    $('#aiSeo').text(res.seo_text || '');
                    $('#aiCta').text(res.cta || '');
                    if (res.emojis) {
                        $('#aiEmojis').text(res.emojis);
                        $('#aiEmojisWrap').show();
                    }
                    $('#aiResult').show();
                    $('#aiPlaceholder').hide();
                    showToast('İçerik oluşturuldu');

                    if (res.fallback) {
                        $('#aiForm').before(
                            '<div id="aiError" class="alert alert-warning">OpenAI yanıt vermedi, varsayılan şablon kullanıldı. API anahtarınızı kontrol edin.</div>'
                        );
                    }
                } else {
                    showToast(res.message || 'İçerik oluşturulamadı', 'danger');
                    $('#aiForm').before(
                        '<div id="aiError" class="alert alert-danger">' + (res.message || 'Bir hata oluştu') + '</div>'
                    );
                }
            })
            .fail(function (xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-magic"></i> İçerik Oluştur');
                const res = xhr.responseJSON;
                const msg = res?.message || 'Sunucu hatası. Lütfen tekrar deneyin.';
                showToast(msg, 'danger');
                $('#aiForm').before('<div id="aiError" class="alert alert-danger">' + msg + '</div>');
            });
    });

    $('#copyAll').on('click', function () {
        const text = [
            $('#aiTitle').text(),
            $('#aiDescription').text(),
            $('#aiHashtags').text(),
            $('#aiCta').text()
        ].filter(Boolean).join('\n\n');
        navigator.clipboard.writeText(text);
        showToast('Kopyalandı');
    });

    // URL'den gelen GET parametrelerini temizle ve otomatik üret
    const params = new URLSearchParams(window.location.search);
    const description = params.get('description');
    if (description) {
        $('textarea[name=description]').val(description);
        if (params.get('context')) {
            $('input[name=context]').val(params.get('context'));
        }
        window.history.replaceState({}, '', window.location.pathname);
        $('#aiForm').trigger('submit');
    }
});
