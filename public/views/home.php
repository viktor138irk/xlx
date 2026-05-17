<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>XLX Server</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<main class="shell">
    <section class="hero">
        <div>
            <p class="eyebrow">XLX / DMR / Pi-Star</p>
            <h1>Личный доступ к DMR-серверу без ручной настройки</h1>
            <p class="lead">Регистрация по позывному без дублей, оплата через YooKassa или чек перевода, автоматический DMR ID и пароль для Pi-Star.</p>
            <div class="actions">
                <a class="button" href="#register">Подключиться</a>
                <a class="button ghost" href="/admin">Админка</a>
            </div>
        </div>
        <div class="status-panel">
            <div>
                <span>DMR порт</span>
                <strong>62030</strong>
            </div>
            <div>
                <span>Модуль</span>
                <strong>A</strong>
            </div>
            <div>
                <span>Оплата</span>
                <strong>YooKassa</strong>
            </div>
        </div>
    </section>

    <section class="grid">
        <article class="tile">
            <span class="num">01</span>
            <h2>Проверка позывного</h2>
            <p>Система проверяет формат и занятость позывного во внутренней базе.</p>
        </article>
        <article class="tile">
            <span class="num">02</span>
            <h2>Оплата доступа</h2>
            <p>Платеж через YooKassa или перевод с отправкой чека на подтверждение.</p>
        </article>
        <article class="tile">
            <span class="num">03</span>
            <h2>Данные Pi-Star</h2>
            <p>После оплаты выдаются сервер, порт, логин, пароль, DMR ID и модуль.</p>
        </article>
    </section>

    <section class="panel" id="register">
        <div>
            <p class="eyebrow">Регистрация</p>
            <h2>Создать заявку</h2>
            <p>После отправки появится ссылка YooKassa и инструкция для оплаты переводом.</p>
        </div>
        <form class="form" id="registerForm">
            <label>Позывной
                <input name="callsign" placeholder="R0XXX" required>
            </label>
            <label>Email
                <input name="email" type="email" placeholder="user@example.com" required>
            </label>
            <label>Пароль кабинета
                <input name="password" type="password" minlength="10" required>
            </label>
            <button class="button" type="submit">Отправить</button>
            <pre class="result" id="registerResult"></pre>
        </form>
    </section>
</main>
<script src="/assets/app.js"></script>
</body>
</html>
