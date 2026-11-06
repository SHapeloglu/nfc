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
        .info { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; margin: 0 0 0 8px; padding: 0; border-radius: 50%; font-size: 13px; font-weight: 700; font-style: italic; font-family: Georgia, serif; line-height: 1; background: #1e3a5f; border: 1px solid #67e8f9; color: #67e8f9; vertical-align: middle; }
        .info:hover, .info:focus-visible { background: #67e8f9; color: #080e1c; }
        .help-top { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 14px; }
        .help-top button { margin: 0; }
        dialog { max-width: 520px; width: calc(100% - 32px); background: #111d33; color: #eaf2ff; border: 1px solid #67e8f9; border-radius: 16px; padding: 24px; }
        dialog::backdrop { background: rgba(3, 8, 18, .75); }
        dialog h3 { margin: 0 0 10px; font-size: 20px; }
        dialog .example { background: #0a1425; border: 1px solid #475b79; border-radius: 9px; padding: 10px 12px; margin: 12px 0; font-family: ui-monospace, monospace; font-size: 14px; color: #67e8f9; overflow-wrap: anywhere; }
        dialog .tip { font-size: 13px; color: #fbbf24; }
        dialog .steps { color: #b3c0d6; line-height: 1.7; padding-left: 20px; }
        [hidden] { display: none !important; }
    </style>
</head>
<body>
<main>
    <a href="{{ url('/') }}">← Generator Studio</a>
    <h1>🔗 UTM Link Builder</h1>
    <p>Bağlantınıza kampanya takip parametreleri ekleyin. Var olan diğer sorgu parametreleri ve bağlantı sonundaki <code>#</code> bölümü korunur. İşlem tarayıcınızda yapılır; bağlantılar sunucuya gönderilmez.</p>

    <div class="help-top">
        <button type="button" id="howTo" class="secondary">ⓘ Nasıl kullanılır?</button>
        <button type="button" id="fillExample" class="secondary">Örnekle doldur</button>
    </div>

    <div class="layout">
        <section class="panel" aria-labelledby="builder-heading">
            <h2 id="builder-heading">Kampanya bilgileri</h2>
            <form id="utmForm">
                <div class="field">
                    <label for="targetUrl">Hedef bağlantı (HTTPS)<button type="button" class="info" data-help="targetUrl" aria-label="Hedef bağlantı için yardım">i</button></label>
                    <input id="targetUrl" type="url" placeholder="https://ornek.com/sayfa" required maxlength="2048" autocomplete="url">
                </div>
                <div class="field">
                    <label for="channel">Hazır kanal şablonu<button type="button" class="info" data-help="channel" aria-label="Kanal şablonu için yardım">i</button></label>
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
                    <label for="source">utm_source *<button type="button" class="info" data-help="source" aria-label="utm_source için yardım">i</button></label>
                    <input id="source" required maxlength="100" placeholder="örn. whatsapp">
                </div>
                <div class="field">
                    <label for="medium">utm_medium *<button type="button" class="info" data-help="medium" aria-label="utm_medium için yardım">i</button></label>
                    <input id="medium" required maxlength="100" placeholder="örn. social">
                </div>
                <div class="field">
                    <label for="campaign">utm_campaign *<button type="button" class="info" data-help="campaign" aria-label="utm_campaign için yardım">i</button></label>
                    <input id="campaign" required maxlength="150" placeholder="örn. izmir_nfc_2026">
                </div>
                <div class="field">
                    <label for="term">utm_term (isteğe bağlı)<button type="button" class="info" data-help="term" aria-label="utm_term için yardım">i</button></label>
                    <input id="term" maxlength="150" placeholder="Anahtar kelime">
                </div>
                <div class="field">
                    <label for="content">utm_content (isteğe bağlı)<button type="button" class="info" data-help="content" aria-label="utm_content için yardım">i</button></label>
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
<dialog id="helpDialog" aria-labelledby="helpTitle">
    <h3 id="helpTitle"></h3>
    <div id="helpBody"></div>
    <div class="button-row">
        <button type="button" id="helpUse" hidden>Örneği forma yaz</button>
        <button type="button" id="helpClose" class="secondary">Kapat</button>
    </div>
</dialog>
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
    const qrContentMaxLength = 2000;
    const byId = (id) => document.getElementById(id);
    let templates = {};
    let latestUrl = '';

    function status(message, error = false) {
        byId('resultStatus').textContent = message;
        byId('resultStatus').classList.toggle('error', error);
    }

    function disableQrAction() {
        byId('qrButton').removeAttribute('href');
        byId('qrButton').classList.add('disabled');
        byId('qrButton').setAttribute('aria-disabled', 'true');
        byId('qrButton').setAttribute('tabindex', '-1');
    }

    function invalidateGeneratedUrl() {
        if (!latestUrl) {
            return;
        }
        latestUrl = '';
        byId('resultUrl').value = '';
        byId('copyButton').disabled = true;
        disableQrAction();
        status('Bilgiler değişti. Güncel bağlantı için yeniden oluşturun.');
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
            invalidateGeneratedUrl();
        }
    });

    ['targetUrl', 'source', 'medium', 'campaign', 'term', 'content'].forEach((id) => {
        byId(id).addEventListener('input', invalidateGeneratedUrl);
    });

    byId('utmForm').addEventListener('submit', (event) => {
        event.preventDefault();
        try {
            latestUrl = buildUrl();
            byId('resultUrl').value = latestUrl;
            byId('copyButton').disabled = false;
            if (latestUrl.length > qrContentMaxLength) {
                disableQrAction();
                status('Bağlantı oluşturuldu ancak QR oluşturucunun 2000 karakter sınırını aşıyor. Kopyalayabilirsiniz; QR için daha kısa bir bağlantı kullanın.', true);
            } else {
                byId('qrButton').href = {{ Js::from(route('qr.index')) }} + '?content=' + encodeURIComponent(latestUrl);
                byId('qrButton').classList.remove('disabled');
                byId('qrButton').removeAttribute('aria-disabled');
                byId('qrButton').removeAttribute('tabindex');
                status('Bağlantı oluşturuldu. QR için aşağıdaki düğmeyi kullanabilirsiniz.');
            }
        } catch (error) {
            latestUrl = '';
            byId('resultUrl').value = '';
            byId('copyButton').disabled = true;
            disableQrAction();
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
            invalidateGeneratedUrl();
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

    // Yardım içerikleri: her alan için açıklama, örnek ve ipucu. 'fill' varsa "Örneği forma yaz" düğmesi o alanı doldurur.
    const helpTopics = {
        targetUrl: { title: 'Hedef bağlantı', text: 'Kişinin tıklayınca gideceği sayfa. Yalnızca HTTPS kabul edilir. Bağlantıda zaten başka parametre (?urun=5) veya # bölümü varsa korunur.', example: 'https://ornek.com/kampanya', tip: 'İpucu: Adresi tarayıcıdan kopyalayıp yapıştırın.', fill: { targetUrl: 'https://ornek.com/kampanya' } },
        channel: { title: 'Hazır kanal şablonu', text: 'Sık kullanılan kanallar için source ve medium alanlarını tek tıkla doldurur. Örneğin WhatsApp seçince source=whatsapp, medium=social yazılır. Sonradan elle değiştirebilirsiniz.', example: 'WhatsApp  →  source: whatsapp, medium: social', tip: 'Kendi kanalınız yoksa sağdaki "Özel kanal şablonları" bölümünden kaydedin.' },
        source: { title: 'utm_source — Trafik nereden geliyor?', text: 'Bağlantıyı paylaştığınız yer veya platform. Analitik raporunda "kaynak" olarak görünür.', example: 'whatsapp, instagram, bulten, nfc_kart', tip: 'İpucu: Küçük harf ve alt çizgi kullanın; "WhatsApp" ile "whatsapp" raporda iki ayrı satır olur.', fill: { source: 'nfc_kart' } },
        medium: { title: 'utm_medium — Hangi tür kanal?', text: 'Kanalın genel türü. Raporlarda kaynakları gruplamak için kullanılır.', example: 'social, email, offline, cpc', tip: 'İpucu: Basılı/QR/NFC gibi internet dışı yerler için offline kullanılır.', fill: { medium: 'offline' } },
        campaign: { title: 'utm_campaign — Hangi kampanya?', text: 'Kampanyanın adı. Aynı kampanyanın tüm bağlantılarında aynı yazın ki sonuçlar tek yerde toplansın.', example: 'izmir_nfc_2026', tip: 'İpucu: Boşluk yerine alt çizgi kullanın; tarih ya da şehir eklemek ayırt etmeyi kolaylaştırır.', fill: { campaign: 'izmir_nfc_2026' } },
        term: { title: 'utm_term — Anahtar kelime (isteğe bağlı)', text: 'Genelde ücretli aramada hangi anahtar kelimeyle gelindiğini belirtir. Gerekmiyorsa boş bırakın.', example: 'nfc_kartvizit', tip: 'Boş bırakılırsa bağlantıya eklenmez.', fill: { term: 'nfc_kartvizit' } },
        content: { title: 'utm_content — Hangi sürüm? (isteğe bağlı)', text: 'Aynı kampanyada birden fazla bağlantı/kart/görsel varsa hangisine tıklandığını ayırt eder (A/B testi gibi).', example: 'kart_a, kart_b, logo_buton', tip: 'Boş bırakılırsa bağlantıya eklenmez.', fill: { content: 'kart_a' } },
    };
    const fillIds = ['targetUrl', 'source', 'medium', 'campaign', 'term', 'content'];
    const exampleAll = { targetUrl: 'https://ornek.com/kampanya?urun=5', source: 'nfc_kart', medium: 'offline', campaign: 'izmir_nfc_2026', term: '', content: 'kart_a' };
    const dialog = byId('helpDialog');
    let pendingFill = null;

    function fillFields(values) {
        Object.entries(values).forEach(([id, value]) => { byId(id).value = value; });
        byId('channel').value = '';
        invalidateGeneratedUrl();
    }

    function openHelp(title, html, fill) {
        byId('helpTitle').textContent = title;
        byId('helpBody').innerHTML = html;
        pendingFill = fill || null;
        byId('helpUse').hidden = !fill;
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }
    }

    // Metinler yukarıdaki sabit tablodan geldiği için innerHTML güvenlidir; kullanıcı girdisi buraya girmez.
    function topicHtml(topic) {
        return '<p>' + topic.text + '</p><div class="example">' + topic.example + '</div><p class="tip">' + topic.tip + '</p>';
    }

    document.querySelectorAll('.info').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const topic = helpTopics[button.dataset.help];
            openHelp(topic.title, topicHtml(topic), topic.fill);
        });
    });

    byId('howTo').addEventListener('click', () => {
        openHelp('UTM Link Builder nasıl kullanılır?',
            '<ol class="steps">' +
            '<li>Hedef bağlantıyı yazın (HTTPS).</li>' +
            '<li>Kanal şablonu seçin; source ve medium otomatik dolar.</li>' +
            '<li>Kampanya adını yazın (zorunlu).</li>' +
            '<li>"Bağlantı oluştur"a basın, kopyalayın veya QR kod yapın.</li></ol>' +
            '<p>Sonuç örneği:</p><div class="example">https://ornek.com/kampanya?urun=5&amp;utm_source=nfc_kart&amp;utm_medium=offline&amp;utm_campaign=izmir_nfc_2026&amp;utm_content=kart_a</div>' +
            '<p class="tip">Bilgiler tarayıcınızda işlenir, sunucuya gönderilmez.</p>',
            exampleAll);
    });

    byId('fillExample').addEventListener('click', () => fillFields(exampleAll));

    byId('helpUse').addEventListener('click', () => {
        if (pendingFill) {
            fillFields(pendingFill);
        }
        dialog.close();
    });
    byId('helpClose').addEventListener('click', () => dialog.close());
    // Pencerenin dışına (karartılmış alana) tıklayınca kapat.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    loadTemplates();
})();
</script>
</body>
</html>
