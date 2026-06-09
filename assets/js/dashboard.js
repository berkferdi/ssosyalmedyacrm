$(document).ready(function () {
    loadPlatformChart();
    loadEngagementChart();
});

function loadPlatformChart() {
    const ctx = document.getElementById('platformChart');
    if (!ctx) return;

    apiRequest('/api/analytics.php', { action: 'platform_breakdown' }).done(function (res) {
        if (!res.success) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: res.labels || [],
                datasets: [{
                    data: res.data || [],
                    backgroundColor: ['#E4405F', '#1877F2', '#0A66C2', '#21759B']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    });
}

function loadEngagementChart() {
    const ctx = document.getElementById('engagementChart');
    if (!ctx) return;

    apiRequest('/api/analytics.php', { action: 'daily_engagement', days: 30 }).done(function (res) {
        if (!res.success) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: res.labels || [],
                datasets: [
                    {
                        label: 'Erişim',
                        data: res.reach || [],
                        borderColor: '#0d6efd',
                        tension: 0.3,
                        fill: false
                    },
                    {
                        label: 'Beğeni',
                        data: res.likes || [],
                        borderColor: '#E4405F',
                        tension: 0.3,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    });
}
