const tokenInput = document.querySelector('#adminToken');
const form = document.querySelector('#settingsForm');
const envPreview = document.querySelector('#envPreview');
const adminResult = document.querySelector('#adminResult');
const loadButton = document.querySelector('#loadSettings');
const saveButton = document.querySelector('#saveSettings');
const applyButton = document.querySelector('#applySettings');
const loadRuntimeButton = document.querySelector('#loadRuntime');
const runtimeStats = document.querySelector('#runtimeStats');
const runtimeResult = document.querySelector('#runtimeResult');
const loadConfigFilesButton = document.querySelector('#loadConfigFiles');
const saveConfigFileButton = document.querySelector('#saveConfigFile');
const reloadConfigFileButton = document.querySelector('#reloadConfigFile');
const fileTabs = document.querySelector('#fileTabs');
const fileMeta = document.querySelector('#fileMeta');
const fileEditor = document.querySelector('#fileEditor');
const fileResult = document.querySelector('#fileResult');
let configFiles = [];
let selectedFileKey = null;

function token() {
    return tokenInput.value.trim();
}

function setResult(text) {
    adminResult.textContent = text;
}

function formData() {
    return Object.fromEntries(new FormData(form).entries());
}

function fillForm(settings) {
    for (const [key, value] of Object.entries(settings)) {
        const input = form.elements.namedItem(key);
        if (input) {
            input.value = value ?? '';
        }
    }
}

async function loadSettings() {
    setResult('Загружаем настройки...');
    const response = await fetch('/api/admin/xlxd/settings', {
        headers: { 'X-Admin-Token': token() },
    });
    const payload = await response.json();
    if (!payload.ok) {
        setResult(payload.error || 'Ошибка загрузки');
        return;
    }

    fillForm(payload.data.settings);
    envPreview.textContent = payload.data.env;
    setResult('Настройки загружены.');
}

async function saveSettings(apply) {
    setResult(apply ? 'Сохраняем и применяем env...' : 'Сохраняем...');
    const response = await fetch('/api/admin/xlxd/settings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Admin-Token': token(),
        },
        body: JSON.stringify({ settings: formData(), apply }),
    });
    const payload = await response.json();
    if (!payload.ok) {
        setResult(payload.error || 'Ошибка сохранения');
        return;
    }

    envPreview.textContent = payload.data.env;
    setResult(payload.data.message);
}

function renderRuntime(data) {
    const service = data.service || {};
    const counters = data.counters || {};
    runtimeStats.innerHTML = `
        <div class="stat-card"><span>Сервис</span><strong>${service.active || 'unknown'}</strong></div>
        <div class="stat-card"><span>Enabled</span><strong>${service.enabled || 'unknown'}</strong></div>
        <div class="stat-card"><span>Активные</span><strong>${counters.users_active ?? 0}</strong></div>
        <div class="stat-card"><span>Оплачено</span><strong>${counters.payments_paid ?? 0}</strong></div>
    `;

    const events = data.latest_events || [];
    runtimeResult.textContent = [
        `service: ${service.service_name || 'xlxd'}`,
        `active: ${service.active || 'unknown'}`,
        `enabled: ${service.enabled || 'unknown'}`,
        `log: ${service.log_path || ''}`,
        '',
        'Последние события:',
        ...events.map((event) => event.message),
    ].join('\n');
}

async function loadRuntime() {
    runtimeResult.textContent = 'Загружаем runtime-статус...';
    const response = await fetch('/api/admin/xlxd/runtime', {
        headers: { 'X-Admin-Token': token() },
    });
    const payload = await response.json();
    if (!payload.ok) {
        runtimeResult.textContent = payload.error || 'Ошибка загрузки статуса';
        return;
    }
    renderRuntime(payload.data);
}

async function runRuntimeAction(action) {
    runtimeResult.textContent = `Выполняем ${action}...`;
    const response = await fetch('/api/admin/xlxd/runtime', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Admin-Token': token(),
        },
        body: JSON.stringify({ action }),
    });
    const payload = await response.json();
    if (!payload.ok) {
        runtimeResult.textContent = payload.error || `Ошибка ${action}`;
        return;
    }

    await loadRuntime();
    runtimeResult.textContent = [
        `action: ${payload.data.action}`,
        `exit_code: ${payload.data.exit_code}`,
        payload.data.output || 'ok',
    ].join('\n');
}

function selectedFile() {
    return configFiles.find((file) => file.key === selectedFileKey) || null;
}

function setFileResult(text) {
    fileResult.textContent = text;
}

function renderFileTabs() {
    fileTabs.innerHTML = '';
    for (const file of configFiles) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `file-tab${file.key === selectedFileKey ? ' active' : ''}`;
        button.textContent = file.name;
        button.addEventListener('click', () => selectFile(file.key));
        fileTabs.append(button);
    }
}

function renderFileMeta(file) {
    if (!file) {
        fileMeta.innerHTML = '<span>Файл не выбран</span>';
        return;
    }

    const writable = file.writable ? 'доступен для записи' : 'нет прав на запись';
    const exists = file.exists ? 'существует' : 'будет создан при сохранении';
    fileMeta.innerHTML = `
        <span>${file.path}</span>
        <span>${exists}</span>
        <span>${writable}</span>
        <span>${file.updated_at || 'без даты'}</span>
    `;
}

function selectFile(key) {
    selectedFileKey = key;
    const file = selectedFile();
    renderFileTabs();
    renderFileMeta(file);
    fileEditor.value = file?.content || '';
    setFileResult(file ? file.description : '');
}

async function loadConfigFiles() {
    setFileResult('Загружаем файлы XLX...');
    const response = await fetch('/api/admin/xlxd/config-files', {
        headers: { 'X-Admin-Token': token() },
    });
    const payload = await response.json();
    if (!payload.ok) {
        setFileResult(payload.error || 'Ошибка загрузки файлов');
        return;
    }

    configFiles = payload.data;
    selectedFileKey = selectedFileKey || configFiles[0]?.key || null;
    renderFileTabs();
    selectFile(selectedFileKey);
}

async function saveConfigFile() {
    const file = selectedFile();
    if (!file) {
        setFileResult('Сначала выбери файл.');
        return;
    }

    setFileResult(`Сохраняем ${file.filename}...`);
    const response = await fetch('/api/admin/xlxd/config-files', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Admin-Token': token(),
        },
        body: JSON.stringify({ file: file.key, content: fileEditor.value }),
    });
    const payload = await response.json();
    if (!payload.ok) {
        setFileResult(payload.error || 'Ошибка сохранения файла');
        return;
    }

    configFiles = configFiles.map((item) => item.key === payload.data.key ? payload.data : item);
    selectFile(payload.data.key);
    setFileResult(`${payload.data.filename} сохранен.`);
}

loadButton?.addEventListener('click', loadSettings);
saveButton?.addEventListener('click', () => saveSettings(false));
applyButton?.addEventListener('click', () => saveSettings(true));
loadRuntimeButton?.addEventListener('click', loadRuntime);
document.querySelectorAll('[data-runtime-action]').forEach((button) => {
    button.addEventListener('click', () => runRuntimeAction(button.dataset.runtimeAction));
});
loadConfigFilesButton?.addEventListener('click', loadConfigFiles);
saveConfigFileButton?.addEventListener('click', saveConfigFile);
reloadConfigFileButton?.addEventListener('click', () => {
    const file = selectedFile();
    if (file) {
        fileEditor.value = file.content || '';
        setFileResult('Изменения в редакторе отменены.');
    }
});

form?.addEventListener('input', () => {
    const data = formData();
    const lines = {
        XLX_REFLECTOR_NAME: data.reflector_name,
        XLX_SERVER_HOST: data.server_host,
        XLX_SYSOP_CALLSIGN: data.sysop_callsign,
        XLX_SYSOP_EMAIL: data.sysop_email,
        XLX_COUNTRY: data.country,
        XLX_DASHBOARD_PORT: data.dashboard_port,
        XLX_DMR_PORT: data.dmr_port,
        XLX_YSF_PORT: data.ysf_port,
        XLX_DEFAULT_MODULE: data.default_module,
        XLX_MODULES: data.modules,
        XLX_INSTALL_PATH: data.install_path,
        XLX_SOURCE_PATH: data.source_path,
        XLX_LOG_PATH: data.log_path,
        XLX_SERVICE_NAME: data.service_name,
        XLX_REPO_URL: data.repo_url,
    };
    envPreview.textContent = Object.entries(lines).map(([key, value]) => `${key}=${value || ''}`).join('\n');
});
