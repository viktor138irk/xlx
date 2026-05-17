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
            <h1>Полная настройка XLX reflector</h1>
            <p class="lead">Настраивай параметры `xlxd`, управляй сервисом, редактируй blacklist, whitelist, interlink и terminal без ручного SSH-редактирования.</p>
        </div>
    </section>

    <section class="panel">
        <label>Admin token
            <input id="adminToken" type="password" placeholder="X-Admin-Token">
        </label>
        <div class="actions">
            <button class="button" id="loadSettings">Загрузить настройки</button>
            <button class="button ghost" id="saveSettings">Сохранить</button>
            <button class="button ghost" id="applySettings">Сохранить и применить env</button>
        </div>
    </section>

    <section class="panel service-control">
        <div class="config-editor-head">
            <div>
                <p class="eyebrow">Управление reflector</p>
                <h2>Статус и перезапуск `xlxd`</h2>
                <p class="muted-text">После изменения env или файлов XLX можно перезапустить сервис прямо отсюда.</p>
            </div>
            <button class="button ghost" id="loadRuntime" type="button">Обновить статус</button>
        </div>
        <div class="stats-grid" id="runtimeStats">
            <div class="stat-card"><span>Сервис</span><strong>...</strong></div>
            <div class="stat-card"><span>Enabled</span><strong>...</strong></div>
            <div class="stat-card"><span>Активные</span><strong>...</strong></div>
            <div class="stat-card"><span>Оплачено</span><strong>...</strong></div>
        </div>
        <div class="actions">
            <button class="button" data-runtime-action="start" type="button">Start</button>
            <button class="button ghost" data-runtime-action="restart" type="button">Restart</button>
            <button class="button ghost" data-runtime-action="stop" type="button">Stop</button>
            <button class="button ghost" data-runtime-action="status" type="button">Status</button>
        </div>
        <pre class="result" id="runtimeResult"></pre>
    </section>

    <section class="panel split">
        <form class="form settings-form" id="settingsForm">
            <label>Reflector
                <input name="reflector_name" placeholder="XLX138">
            </label>
            <label>Домен или IP
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

    <section class="panel config-editor">
        <div class="config-editor-head">
            <div>
                <p class="eyebrow">Файлы XLX</p>
                <h2>Редактор blacklist, whitelist, interlink и terminal</h2>
                <p class="muted-text">Изменения сохраняются прямо в `/xlxd`. После правки перезапусти `xlxd`, если файл требует перечитывания сервисом.</p>
            </div>
            <button class="button ghost" id="loadConfigFiles" type="button">Загрузить файлы</button>
        </div>

        <div class="file-tabs" id="fileTabs"></div>
        <div class="file-meta" id="fileMeta"><span>Файл не выбран</span></div>

        <label class="editor-label">Содержимое файла
            <textarea id="fileEditor" spellcheck="false" placeholder="Загрузите файл для редактирования"></textarea>
        </label>

        <div class="actions">
            <button class="button" id="saveConfigFile" type="button">Сохранить файл</button>
            <button class="button ghost" id="reloadConfigFile" type="button">Отменить изменения</button>
        </div>
        <pre class="result" id="fileResult"></pre>
    </section>
</main>
<script src="/assets/admin.js"></script>
</body>
</html>
