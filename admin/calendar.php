<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'İçerik Takvimi';
$currentPage = 'calendar';
$extraScripts = ['calendar.js'];

include TEMPLATES_PATH . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">İçerik Takvimi</h4>
    <div class="btn-group">
        <button class="btn btn-outline-secondary view-btn" id="viewDaily" data-view="daily">Günlük</button>
        <button class="btn btn-outline-secondary view-btn" id="viewWeekly" data-view="weekly">Haftalık</button>
        <button class="btn btn-outline-secondary view-btn active" id="viewMonthly" data-view="monthly">Aylık</button>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <button class="btn btn-sm btn-outline-secondary" id="prevMonth"><i class="bi bi-chevron-left"></i></button>
        <h5 class="mb-0" id="calendarTitle"></h5>
        <button class="btn btn-sm btn-outline-secondary" id="nextMonth"><i class="bi bi-chevron-right"></i></button>
    </div>
    <div class="card-body p-0">
        <div id="calendarGrid" class="calendar-grid"></div>
    </div>
</div>

<p class="text-muted mt-3 small">
    <i class="bi bi-info-circle"></i> Paylaşımları sürükleyip bırakarak tarih değiştirebilirsiniz.
</p>

<?php include TEMPLATES_PATH . '/footer.php'; ?>
