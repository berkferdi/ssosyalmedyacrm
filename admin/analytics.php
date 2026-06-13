<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'Analitik Raporlar';
$currentPage = 'analytics';

include TEMPLATES_PATH . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Analitik Raporlar</h4>
    <div class="btn-group">
        <button class="btn btn-outline-secondary period-btn active" data-period="daily">Günlük</button>
        <button class="btn btn-outline-secondary period-btn" data-period="weekly">Haftalık</button>
        <button class="btn btn-outline-secondary period-btn" data-period="monthly">Aylık</button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body text-center">
            <div class="text-muted small">Beğeni</div>
            <h3 id="statLikes">0</h3>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body text-center">
            <div class="text-muted small">Yorum</div>
            <h3 id="statComments">0</h3>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body text-center">
            <div class="text-muted small">Erişim</div>
            <h3 id="statReach">0</h3>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body text-center">
            <div class="text-muted small">Gösterim</div>
            <h3 id="statImpressions">0</h3>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Platform Karşılaştırma</h6></div>
            <div class="card-body"><canvas id="analyticsChart" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Platform Detay</h6></div>
            <div class="card-body" id="platformDetails">
                <p class="text-muted text-center">Veri yükleniyor...</p>
            </div>
        </div>
    </div>
</div>

<script>
let analyticsChart;

function loadAnalytics(period) {
    apiRequest('/api/analytics.php', { action: 'report', period: period }).done(function(res) {
        if (!res.success) return;
        $('#statLikes').text(res.totals.likes.toLocaleString());
        $('#statComments').text(res.totals.comments.toLocaleString());
        $('#statReach').text(res.totals.reach.toLocaleString());
        $('#statImpressions').text(res.totals.impressions.toLocaleString());

        if (analyticsChart) analyticsChart.destroy();
        analyticsChart = new Chart(document.getElementById('analyticsChart'), {
            type: 'bar',
            data: {
                labels: res.labels,
                datasets: [
                    { label: 'Beğeni', data: res.likes, backgroundColor: '#E4405F' },
                    { label: 'Erişim', data: res.reach, backgroundColor: '#0d6efd' },
                    { label: 'Gösterim', data: res.impressions, backgroundColor: '#198754' }
                ]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        let html = '';
        (res.platforms || []).forEach(p => {
            html += `<div class="mb-3"><strong>${p.name}</strong>
                <div class="small text-muted">Beğeni: ${p.likes} | Erişim: ${p.reach}</div></div>`;
        });
        $('#platformDetails').html(html || '<p class="text-muted">Veri yok</p>');
    });
}

$('.period-btn').on('click', function() {
    $('.period-btn').removeClass('active');
    $(this).addClass('active');
    loadAnalytics($(this).data('period'));
});

loadAnalytics('daily');
</script>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
