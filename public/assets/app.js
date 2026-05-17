const form = document.querySelector('#registerForm');
const result = document.querySelector('#registerResult');

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
