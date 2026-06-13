let currentDate = new Date();

$(document).ready(function () {
    renderCalendar();
    loadEvents();

    $('#prevMonth').on('click', function () {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
        loadEvents();
    });

    $('#nextMonth').on('click', function () {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
        loadEvents();
    });

    $('#viewDaily, #viewWeekly, #viewMonthly').on('click', function () {
        $('.view-btn').removeClass('active');
        $(this).addClass('active');
        const view = $(this).data('view');
        if (view === 'monthly') {
            renderCalendar();
        }
    });
});

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const monthNames = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
        'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

    $('#calendarTitle').text(monthNames[month] + ' ' + year);

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();

    let html = '';
    const dayNames = ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'];

    dayNames.forEach(d => {
        html += `<div class="calendar-header text-center fw-bold p-2 bg-light">${d}</div>`;
    });

    const startDay = firstDay === 0 ? 6 : firstDay - 1;
    for (let i = 0; i < startDay; i++) {
        html += '<div class="calendar-day other-month"></div>';
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const isToday = today.getDate() === day && today.getMonth() === month && today.getFullYear() === year;
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        html += `<div class="calendar-day ${isToday ? 'today' : ''}" data-date="${dateStr}">
            <strong>${day}</strong>
            <div class="events-container" data-date="${dateStr}"></div>
        </div>`;
    }

    $('#calendarGrid').html(html);
}

function loadEvents() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth() + 1;

    apiRequest('/api/posts.php', {
        action: 'calendar_events',
        year: year,
        month: month
    }).done(function (res) {
        if (!res.success) return;

        $('.events-container').empty();
        (res.events || []).forEach(function (event) {
            const date = event.scheduled_at.substring(0, 10);
            const container = $(`.events-container[data-date="${date}"]`);
            if (container.length) {
                container.append(`
                    <div class="calendar-event" draggable="true" data-id="${event.id}"
                         title="${event.title || event.content.substring(0, 50)}">
                        ${event.scheduled_at.substring(11, 16)} ${(event.title || event.content).substring(0, 20)}
                    </div>
                `);
            }
        });

        initDragDrop();
    });
}

function initDragDrop() {
    $('.calendar-event').on('dragstart', function (e) {
        e.originalEvent.dataTransfer.setData('post_id', $(this).data('id'));
    });

    $('.calendar-day').on('dragover', function (e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    }).on('dragleave', function () {
        $(this).removeClass('drag-over');
    }).on('drop', function (e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        const postId = e.originalEvent.dataTransfer.getData('post_id');
        const newDate = $(this).data('date');

        apiRequest('/api/posts.php', {
            action: 'reschedule',
            post_id: postId,
            new_date: newDate
        }).done(function (res) {
            if (res.success) {
                showToast('Paylaşım tarihi güncellendi');
                loadEvents();
            } else {
                showToast(res.message || 'Hata oluştu', 'danger');
            }
        });
    });
}
