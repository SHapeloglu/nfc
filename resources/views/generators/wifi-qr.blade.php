<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="WiFi ağınız için güvenli QR kod oluşturun.">
    <title>WiFi QR | Generator Studio</title>
    <style>
        :root { color-scheme: dark; font-family: system-ui, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #080e1c; color: #e9eef8; line-height: 1.5; }
        main { max-width: 860px; margin: auto; padding: 36px 20px; }
        a { color: #67e8f9; }
        .grid { display: grid; gap: 22px; grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); }
        section { background: #101a2d; border: 1px solid #26334c; padding: 24px; border-radius: 18px; }
        label { display: block; margin-top: 16px; font-weight: 650; }
        input:not([type="checkbox"]), select { width: 100%; padding: 12px; margin-top: 6px; border-radius: 9px; border: 1px solid #455675; background: #0a1425; color: white; font-size: 16px; }
        button, .download { display: inline-block; padding: 12px 18px; margin-top: 19px; background: #2563eb; border: none; border-radius: 9px; color: white; font-size: 15px; cursor: pointer; text-decoration: none; }
        button:disabled { opacity: .6; cursor: wait; }
        .muted { color: #a9b7ce; }
        .error { color: #fca5a5; min-height: 1.5em; }
        .preview { width: 100%; max-width: 300px; background: white; border-radius: 12px; display: block; margin: 15px auto; }
        [hidden] { display: none !important; }
    </style>
</head>
<body>
<main>
    <a href="/">← Generator Studio</a>
    <h1>📶 WiFi QR Oluşturucu</h1>
    <p class="muted">Misafirlerinizin ağınıza kolayca bağlanabilmesi için QR oluşturun. WiFi bilgileriniz sunucuda kaydedilmez.</p>
    <div class="grid">
        <section>
            <form id="wifiForm" action="{{ route('wifi.qr.generate') }}" method="POST">
                @csrf
                <label for="ssid">Ağ adı (SSID)</label>
                <input id="ssid" name="ssid" maxlength="32" required autocomplete="off" placeholder="Örn. Misafir WiFi">
                <label for="security">Güvenlik</label>
                <select id="security" name="security">
                    <option value="WPA">WPA / WPA2 / WPA3 parola</option>
                    <option value="nopass">Şifresiz ağ</option>
                </select>
                <div id="passwordField">
                    <label for="password">WiFi şifresi</label>
                    <input id="password" name="password" type="password" minlength="8" maxlength="63" required autocomplete="off">
                    <label><input id="showPassword" type="checkbox"> Şifreyi göster</label>
                </div>
                <label><input type="checkbox" name="hidden" value="1"> Gizli ağ</label>
                <button id="generate" type="submit">QR Oluştur</button>
                <p id="error" class="error" role="alert" aria-live="polite"></p>
            </form>
        </section>
        <section>
            <h2>QR Önizleme</h2>
            <p class="muted">Kod oluşunca telefonunuzla okutmayı deneyin.</p>
            <img id="preview" class="preview" alt="Oluşturulan WiFi QR kodu" hidden>
            <a id="download" class="download" download="generator-studio-wifi.svg" hidden>SVG İndir</a>
            <p class="muted">QR kodu, WiFi şifrenizi içinde barındırır. Yalnızca güvendiğiniz kişilerle paylaşın.</p>
        </section>
    </div>
</main>
<script>
(() => {
    'use strict';
    const form = document.getElementById('wifiForm');
    const security = document.getElementById('security');
    const passwordField = document.getElementById('passwordField');
    const password = document.getElementById('password');
    const preview = document.getElementById('preview');
    const download = document.getElementById('download');
    const error = document.getElementById('error');
    const button = document.getElementById('generate');
    let objectUrl = null;

    function updateSecurity() {
        const secured = security.value === 'WPA';
        passwordField.hidden = !secured;
        password.required = secured;
        if (!secured) password.value = '';
    }
    security.addEventListener('change', updateSecurity);
    document.getElementById('showPassword').addEventListener('change', event => {
        password.type = event.target.checked ? 'text' : 'password';
    });
    updateSecurity();

    form.addEventListener('submit', async event => {
        event.preventDefault();
        error.textContent = '';
        button.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                const body = await response.json().catch(() => null);
                throw new Error(body?.errors ? Object.values(body.errors).flat().join(' ') : 'QR oluşturulamadı. Lütfen bilgileri kontrol edin.');
            }
            const url = URL.createObjectURL(await response.blob());
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = url;
            preview.src = url;
            preview.hidden = false;
            download.href = url;
            download.hidden = false;
        } catch (exception) {
            error.textContent = exception.message || 'Bir hata oluştu.';
        } finally {
            button.disabled = false;
        }
    });
    window.addEventListener('pagehide', () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
    });
})();
</script>
</body>
</html>
