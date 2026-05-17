const tokenInput = document.querySelector('#adminToken');
const form = document.querySelector('#settingsForm');
const envPreview = document.querySelector('#envPreview');
const adminResult = document.querySelector('#adminResult');
const loadButton = document.querySelector('#loadSettings');
const saveButton = document.querySelector('#saveSettings');
const applyButton = document.querySelector('#applySettings');

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
    setResult('Загружаем...');
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
    setResult(apply ? 'Сохраняем и применяем...' : 'Сохраняем...');
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

loadButton?.addEventListener('click', loadSettings);
saveButton?.addEventListener('click', () => saveSettings(false));
applyButton?.addEventListener('click', () => saveSettings(true));

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
