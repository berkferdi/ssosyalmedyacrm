$(document).ready(function () {
    // Sidebar toggle (mobile)
    $('#sidebarToggle').on('click', function () {
        $('#sidebar').toggleClass('show');
    });

    // Theme toggle
    $('#themeToggle').on('click', function () {
        const html = $('html');
        const current = html.attr('data-bs-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        html.attr('data-bs-theme', next);
        $(this).find('i').toggleClass('bi-moon bi-sun');

        $.post(APP_URL + '/api/settings.php', {
            action: 'set_theme',
            theme: next,
            _csrf_token: CSRF_TOKEN
        });
    });

    // DataTables default
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/tr.json'
            },
            pageLength: 25,
            responsive: true
        });
    }

    // AJAX CSRF setup
    $.ajaxSetup({
        beforeSend: function (xhr, settings) {
            if (settings.type === 'POST' && !settings.crossDomain) {
                if (settings.data instanceof FormData) {
                    settings.data.append('_csrf_token', CSRF_TOKEN);
                } else if (typeof settings.data === 'string') {
                    settings.data += '&_csrf_token=' + CSRF_TOKEN;
                }
            }
        }
    });

    // Auto-dismiss alerts
    setTimeout(function () {
        $('.alert-dismissible').fadeOut();
    }, 5000);

    // Confirm delete
    $(document).on('click', '.btn-delete', function (e) {
        if (!confirm('Bu kaydı silmek istediğinize emin misiniz?')) {
            e.preventDefault();
        }
    });
});

function showToast(message, type = 'success') {
    const toast = $(`
        <div class="toast align-items-center text-bg-${type} border-0 position-fixed bottom-0 end-0 m-3" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);
    $('body').append(toast);
    new bootstrap.Toast(toast[0]).show();
    setTimeout(() => toast.remove(), 4000);
}

function apiRequest(url, data, method = 'POST') {
    return $.ajax({
        url: APP_URL + url,
        method: method,
        data: data,
        dataType: 'json'
    });
}
