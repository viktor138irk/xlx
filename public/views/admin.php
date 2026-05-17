<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>XLX Admin</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<main class="shell admin-shell">
    <section class="hero compact">
        <div>
            <p class="eyebrow">Админка</p>
            <h1>Настройка XLX без ручного редактирования конфигов</h1>
            <p class="lead">Меняй имя reflector, домен, порты, модули и пути. Настройки сохраняются в БД и могут сразу применяться в `/etc/xlx/xlxd.env`.</p>
        </div>
    </section>

    <section class="panel">
        <label>Admin token
            <input id="adminToken" type="password" placeholder="X-Admin-Token">
        </label>
        <div class="actions">
            <button class="button" id="loadSettings">Загрузить настройки</button>
            <button class="button ghost" id="saveSettings">Сохранить</button>
            <button class="button ghost" id="applySettings">Сохранить и применить</button>
        </div>
    </section>

    <section class="panel split">
        <form class="form settings-form" id="settingsForm">
            <label>Reflector
                <input name="reflector_name" placeholder="XLX138">
            </label>
            <label>Домен
                <input name="server_host" placeholder="xlx.example.com">
            </label>
            <label>Позывной sysop
                <input name="sysop_callsign" placeholder="R0XXX">
            </label>
            <label>Email sysop
                <input name="sysop_email" placeholder="admin@example.com">
            </label>
            <label>Страна
                <input name="country" placeholder="RU">
            </label>
            <label>DMR порт
                <input name="dmr_port" type="number" min="1" max="65535">
            </label>
            <label>YSF порт
                <input name="ysf_port" type="number" min="1" max="65535">
            </label>
            <label>Dashboard порт
                <input name="dashboard_port" type="number" min="1" max="65535">
            </label>
            <label>Модуль по умолчанию
                <input name="default_module" maxlength="1">
            </label>
            <label>Активные модули
                <input name="modules" placeholder="ABCDEFGHIJKLMNOPQRSTUVWXYZ">
            </label>
            <label>Путь установки
                <input name="install_path" placeholder="/xlxd">
            </label>
            <label>Путь исходников
                <input name="source_path" placeholder="/usr/src/xlxd">
            </label>
            <label>Лог
                <input name="log_path" placeholder="/var/log/xlxd.log">
            </label>
            <label>Systemd service
                <input name="service_name" placeholder="xlxd">
            </label>
            <label>Репозиторий xlxd
                <input name="repo_url" placeholder="https://github.com/LX3JL/xlxd.git">
            </label>
        </form>
        <div>
            <p class="eyebrow">Предпросмотр env</p>
            <pre class="result env-preview" id="envPreview"></pre>
            <pre class="result" id="adminResult"></pre>
        </div>
    </section>
</main>
<script src="/assets/admin.js"></script>
</body>
</html>
