const form = document.querySelector('#registerForm');
const result = document.querySelector('#registerResult');
const statsGrid = document.querySelector('#statsGrid');
const eventList = document.querySelector('#eventList');
const refreshStats = document.querySelector('#refreshStats');

function renderStats(data) {
    const counters = data.counters || {};
    const service = data.service || {};
    statsGrid.innerHTML = `
        <div class="stat-card"><span>Сервис</span><strong>${service.active || 'unknown'}</strong></div>
        <div class="stat-card"><span>Активные</span><strong>${counters.users_active ?? 0}</strong></div>
        <div class="stat-card"><span>Пользователи</span><strong>${counters.users_total ?? 0}</strong></div>
        <div class="stat-card"><span>Ожидают оплату</span><strong>${counters.payments_pending ?? 0}</strong></div>
    `;

    const events = data.latest_events || [];
    if (events.length === 0) {
        eventList.innerHTML = '<div class="event-row muted-text">Пока нет событий в логе или лог недоступен.</div>';
        return;
    }

    eventList.innerHTML = events.map((event) => `
        <div class="event-row">
            <span>${event.time || 'без времени'}</span>
            <strong>${event.callsign || 'XLX'}</strong>
            <p>${event.message}</p>
        </div>
    `).join('');
}

async function loadStats() {
    try {
        const response = await fetch('/api/dashboard/stats');
        const payload = await response.json();
        if (!payload.ok) {
            throw new Error(payload.error || 'Ошибка загрузки статистики');
        }
        renderStats(payload.data);
    } catch (error) {
        eventList.innerHTML = `<div class="event-row muted-text">${error.message}</div>`;
    }
}

refreshStats?.addEventListener('click', loadStats);
loadStats();

form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    result.textContent = 'Отправляем заявку...';

    const data = Object.fromEntries(new FormData(form).entries());
    const response = await fetch('/api/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    });
    const payload = await response.json();

    if (!payload.ok) {
        result.textContent = payload.error || 'Ошибка регистрации';
        return;
    }

    const payment = payload.data.payment || {};
    const lines = [
        `Заявка создана: ${payload.data.callsign}`,
        `Статус: ${payload.data.status}`,
    ];
    if (payment.confirmation_url) {
        lines.push(`YooKassa: ${payment.confirmation_url}`);
    }
    if (payment.manual_transfer?.instructions) {
        lines.push(`Перевод по чеку: ${payment.manual_transfer.instructions}`);
        lines.push(`ID платежа для чека: ${payment.id}`);
    }

    result.textContent = lines.join('\n');
});
