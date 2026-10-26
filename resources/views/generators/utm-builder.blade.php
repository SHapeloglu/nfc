<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Generator Studio UTM bağlantı oluşturucu: kampanya bağlantılarını hazırlayın.">
    <title>UTM Link Builder | Generator Studio</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; color: #eaf2ff; background: #080e1c; }
        main { max-width: 1000px; margin: 0 auto; padding: 30px 18px 70px; }
        a { color: #67e8f9; }
        h1 { font-size: clamp(28px, 5vw, 42px); }
        p { color: #b3c0d6; line-height: 1.6; }
        .layout { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 20px; margin-top: 28px; }
        .panel { background: #111d33; border: 1px solid #293b59; border-radius: 16px; padding: 24px; }
        .field { margin-top: 18px; }
        label { display: block; font-weight: 650; margin-bottom: 7px; }
        input, select, textarea { width: 100%; font: inherit; padding: 12px; background: #0a1425; color: white; border: 1px solid #475b79; border-radius: 9px; }
        textarea { min-height: 130px; resize: vertical; word-break: break-all; }
        button, .action { display: inline-block; font: inherit; font-weight: 700; border: 0; border-radius: 9px; padding: 12px 16px; color: white; background: #2563eb; text-decoration: none; cursor: pointer; margin: 10px 8px 0 0; }
        button.secondary, .action.secondary { background: #334155; }
        button:disabled, .action.disabled { opacity: .5; pointer-events: none; }
        .hint { font-size: 13px; margin-top: 6px; }
        .notice { background: #092b36; border: 1px solid #0e7490; border-radius: 10px; padding: 13px; margin-top: 20px; overflow-wrap: anywhere; }
        .error { border-color: #f87171; color: #fecaca; background: #3c1723; }
        .button-row { display: flex; flex-wrap: wrap; align-items: center; }
        [hidden] { display: none !important; }
    </style>
</head>
<body>
<main>
    <a href="{{ url('/') }}">← Generator Studio</a>
    <h1>🔗 UTM Link Builder</h1>
    <p>Bağlantınıza kampanya takip parametreleri ekleyin. Var olan diğer sorgu parametreleri ve bağlantı sonundaki <code>#</code> bölümü korunur. İşlem tarayıcınızda yapılır; bağlantılar sunucuya gönderilmez.</p>

    <div class="layout">
        <section class="panel" aria-labelledby="builder-heading">
            <h2 id="builder-heading">Kampanya bilgileri</h2>
            <form id="utmForm">
                <div class="field">
                    <label for="targetUrl">Hedef bağlantı (HTTPS)</label>
                    <input id="targetUrl" type="url" placeholder="https://ornek.com/sayfa" required maxlength="2048" autocomplete="url">
                </div>
                <div class="field">
                    <label for="channel">Hazır kanal şablonu</label>
                    <select id="channel">
                        <option value="">Kanal seçin</option>
                        <option value="email">Email</option>
                        <option value="email_signature">Email Signature</option>
                        <option value="newsletter">Newsletter</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="instagram">Instagram</option>
                        <option value="linkedin">LinkedIn</option>
                        <option value="qr_print">QR / Print</option>
                        <option value="tiktok">TikTok</option>
                        <option value="facebook">Facebook</option>
                        <option value="telegram">Telegram</option>
                    </select>
                </div>
                <div class="field">
                    <label for="source">utm_source *</label>
                    <input id="source" required maxlength="100" placeholder="örn. whatsapp">
                </div>
                <div class="field">
                    <label for="medium">utm_medium *</label>
                    <input id="medium" required maxlength="100" placeholder="örn. social">
                </div>
                <div class="field">
                    <label for="campaign">utm_campaign *</label>
                    <input id="campaign" required maxlength="150" placeholder="örn. izmir_nfc_2026">
                </div>
                <div class="field">
                    <label for="term">utm_term (isteğe bağlı)</label>
                    <input id="term" maxlength="150" placeholder="Anahtar kelime">
                </div>
                <div class="field">
                    <label for="content">utm_content (isteğe bağlı)</label>
                    <input id="content" maxlength="150" placeholder="örn. kart_a">
                </div>
                <button type="submit">Bağlantı oluştur</button>
            </form>
        </section>

        <div>
            <section class="panel" aria-labelledby="result-heading">
                <h2 id="result-heading">Oluşturulan bağlantı</h2>
                <label for="resultUrl">UTM bağlantısı</label>
                <textarea id="resultUrl" readonly placeholder="Kampanya bilgilerini doldurarak bağlantı oluşturun."></textarea>
                <div class="button-row">
                    <button id="copyButton" type="button" disabled>Bağlantıyı kopyala</button>
                    <a id="qrButton" class="action secondary disabled" aria-disabled="true" tabindex="-1">QR kod oluştur</a>
                </div>
                <p id="resultStatus" class="notice" role="status" aria-live="polite">Hazır.</p>
            </section>
            <section class="panel" aria-labelledby="custom-heading" style="margin-top:20px;">
                <h2 id="custom-heading">Özel kanal şablonları</h2>
                <p>Kendi kanalınızı tanımlayın. Şablonlar yalnız bu tarayıcının yerel depolamasında saklanır.</p>
                <div class="field">
                    <label for="customName">Şablon adı</label>
                    <input id="customName" maxlength="70" placeholder="örn. Saha NFC Kartı">
                </div>
                <button id="saveTemplate" type="button">Mevcut source/medium ile kaydet</button>
                <div class="field">
                    <label for="savedTemplates">Kaydedilen şablonlar</label>
                    <select id="savedTemplates">
                        <option value="">Özel şablon seçin</option>
                    </select>
                </div>
                <button id="deleteTemplate" type="button" class="secondary" disabled>Seçili şablonu sil</button>
                <p id="templateStatus" class="hint" role="status" aria-live="polite"></p>
            </section>
        </div>
    </div>
</main>
<script>
(() => {
    'use strict';

    const channelPresets = Object.freeze({
        email: ['email', 'email'],
        email_signature: ['email_signature', 'email'],
        newsletter: ['newsletter', 'email'],
        whatsapp: ['whatsapp', 'social'],
        instagram: ['instagram', 'social'],
        linkedin: ['linkedin', 'social'],
        qr_print: ['qr_print', 'offline'],
        tiktok: ['tiktok', 'social'],
        facebook: ['facebook', 'social'],
        telegram: ['telegram', 'social'],
    });
    const storageKey = 'generator-studio-utm-custom-templates-v1';
    const byId = (id) => document.getElementById(id);
    let templates = {};
    let latestUrl = '';

    function status(message, error = false) {
        byId('resultStatus').textContent = message;
        byId('resultStatus').classList.toggle('error', error);
    }

    function loadTemplates() {
        try {
            const parsed = JSON.parse(localStorage.getItem(storageKey) || '{}');
            if (parsed && !Array.isArray(parsed) && typeof parsed === 'object') {
                templates = Object.fromEntries(Object.entries(parsed).filter(([id, item]) =>
                    /^[a-z0-9_-]{1,80}$/.test(id) &&
                    item && typeof item.name === 'string' &&
                    typeof item.source === 'string' &&
                    typeof item.medium === 'string'
                ));
            }
        } catch {
            templates = {};
        }
        refreshTemplates();
    }

    function refreshTemplates() {
        const list = byId('savedTemplates');
        list.replaceChildren(new Option('Özel şablon seçin', ''));
        Object.entries(templates).forEach(([id, entry]) => {
            list.add(new Option(entry.name, id));
        });
        byId('deleteTemplate').disabled = !list.value;
    }

    function persistTemplates() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(templates));
            return true;
        } catch {
            byId('templateStatus').textContent = 'Tarayıcı depolamasına kaydedilemedi.';
            return false;
        }
    }

    function buildUrl() {
        const raw = byId('targetUrl').value.trim();
        let url;
        try {
            url = new URL(raw);
        } catch {
            throw new Error('Geçerli bir HTTPS bağlantısı girin.');
        }
        if (url.protocol !== 'https:' || !url.hostname || url.username || url.password) {
            throw new Error('Yalnızca kullanıcı bilgisi içermeyen HTTPS bağlantıları kabul edilir.');
        }
        const fields = {
            utm_source: byId('source').value.trim(),
            utm_medium: byId('medium').value.trim(),
            utm_campaign: byId('campaign').value.trim(),
            utm_term: byId('term').value.trim(),
            utm_content: byId('content').value.trim(),
        };
        if (!fields.utm_source || !fields.utm_medium || !fields.utm_campaign) {
            throw new Error('Source, medium ve campaign alanları zorunludur.');
        }
        // set() mevcut UTM anahtarlarını günceller; başka sorgu parametrelerini silmez.
        Object.entries(fields).forEach(([key, value]) => {
            if (value) {
                url.searchParams.set(key, value);
            } else {
                url.searchParams.delete(key);
            }
        });
        return url.toString();
    }

    byId('channel').addEventListener('change', (event) => {
        const selected = channelPresets[event.target.value];
        if (selected) {
            [byId('source').value, byId('medium').value] = selected;
        }
    });

    byId('utmForm').addEventListener('submit', (event) => {
        event.preventDefault();
        try {
            latestUrl = buildUrl();
            byId('resultUrl').value = latestUrl;
            byId('copyButton').disabled = false;
            byId('qrButton').href = {{ Js::from(route('qr.index')) }} + '?content=' + encodeURIComponent(latestUrl);
            byId('qrButton').classList.remove('disabled');
            byId('qrButton').removeAttribute('aria-disabled');
            byId('qrButton').removeAttribute('tabindex');
            status('Bağlantı oluşturuldu. QR için aşağıdaki düğmeyi kullanabilirsiniz.');
        } catch (error) {
            latestUrl = '';
            byId('resultUrl').value = '';
            byId('copyButton').disabled = true;
            byId('qrButton').removeAttribute('href');
            byId('qrButton').classList.add('disabled');
            byId('qrButton').setAttribute('aria-disabled', 'true');
            byId('qrButton').setAttribute('tabindex', '-1');
            status(error.message, true);
        }
    });

    byId('copyButton').addEventListener('click', async () => {
        if (!latestUrl) {
            return;
        }
        try {
            await navigator.clipboard.writeText(latestUrl);
            status('Bağlantı panoya kopyalandı.');
        } catch {
            byId('resultUrl').focus();
            byId('resultUrl').select();
            status('Otomatik kopyalanamadı. Seçili bağlantıyı elle kopyalayın.', true);
        }
    });

    byId('saveTemplate').addEventListener('click', () => {
        const name = byId('customName').value.trim();
        const source = byId('source').value.trim();
        const medium = byId('medium').value.trim();
        if (!name || !source || !medium) {
            byId('templateStatus').textContent = 'Şablon adı, source ve medium doldurulmalıdır.';
            return;
        }
        const id = 'custom_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8);
        templates[id] = { name, source, medium };
        if (!persistTemplates()) {
            delete templates[id];
            return;
        }
        refreshTemplates();
        byId('savedTemplates').value = id;
        byId('deleteTemplate').disabled = false;
        byId('templateStatus').textContent = 'Özel şablon kaydedildi.';
    });

    byId('savedTemplates').addEventListener('change', (event) => {
        const selected = templates[event.target.value];
        byId('deleteTemplate').disabled = !selected;
        if (selected) {
            byId('channel').value = '';
            byId('source').value = selected.source;
            byId('medium').value = selected.medium;
        }
    });

    byId('deleteTemplate').addEventListener('click', () => {
        const id = byId('savedTemplates').value;
        if (!templates[id]) {
            return;
        }
        const removed = templates[id];
        delete templates[id];
        if (!persistTemplates()) {
            templates[id] = removed;
            return;
        }
        refreshTemplates();
        byId('templateStatus').textContent = 'Özel şablon silindi.';
    });

    loadTemplates();
})();
</script>
</body>
</html>
