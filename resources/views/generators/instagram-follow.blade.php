{{--
    Instagram takip bağlantısı hazırlama sayfası.
    Rota: GET /instagram-takip (routes/web.php, 'instagram.follow.index'; Route::view, herkese açık).
    Akış: kullanıcı Instagram'dan kopyaladığı xtok'lu profil bağlantısını yapıştırır; doğrulama
    tamamen tarayıcıda (JS) yapılır, bağlantı değiştirilmeden gösterilir.
    İstek uçları:
      - /qr?content=...  : "QR Kod Oluştur" düğmesi QR sayfasına yönlendirir (GET).
      - POST /baglantilar/kaydet : "Bağlantıyı Kaydet" düğmesini /js/link-save.js ekler;
        bu uç oturum (auth) ister, giriş yoksa kayıt başarısız olur.
    CSRF: <head> içindeki meta[name="csrf-token"] değerini link-save.js okuyup
    X-CSRF-TOKEN başlığıyla gönderir.
--}}
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instagram Takip Linki - Generator Studio</title>

    {{-- Sayfaya özel açık temalı stiller --}}
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #f5f6fa;
            color: #202436;
            font-family: system-ui, Arial, sans-serif;
        }

        header {
            background: #fff;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        header a {
            color: #374151;
            text-decoration: none;
            font-weight: 700;
        }

        main {
            max-width: 760px;
            margin: 50px auto;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 8px 30px #0000000d;
        }

        h1 {
            margin-top: 0;
            font-size: 28px;
        }

        .subtitle {
            color: #64748b;
            line-height: 1.6;
        }

        label {
            display: block;
            margin: 24px 0 8px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 15px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 15px;
        }

        button, .button {
            display: inline-block;
            padding: 14px 20px;
            border: 0;
            border-radius: 10px;
            background: #833ab4;
            color: white;
            cursor: pointer;
            font-size: 15px;
            text-decoration: none;
            text-align: center;
        }

        button:hover, .button:hover {
            background: #6d28a0;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .result {
            display: none;
            margin-top: 25px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            overflow-wrap: anywhere;
        }

        .result.visible {
            display: block;
        }

        .error {
            color: #b91c1c;
            margin-top: 12px;
        }

        .success {
            color: #15803d;
            margin-top: 12px;
        }

        @media(max-width: 600px) {
            main { margin: 15px auto; }
            .card { padding: 20px; }
            h1 { font-size: 23px; }
            .actions > * { flex: 1 1 100%; }
        }
    </style>
{{-- CSRF token: link-save.js kaydetme isteğinde bunu okur --}}
<meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>

<header>
    <a href="/">▦ Generator Studio</a>
</header>

<main>
    <div class="card">

        <h1>📸 Instagram Takip Linki</h1>

        <p class="subtitle">
            Instagram uygulamasından kopyaladığınız profil paylaşım
            bağlantısını yapıştırın. Bağlantınızın orijinal takip
            deneyimi korunarak kullanıma hazırlanır.
        </p>

        {{-- Giriş: profil paylaşım bağlantısı --}}
        <label for="instagramUrl">Instagram paylaşım bağlantısı</label>

        <input
            id="instagramUrl"
            type="url"
            maxlength="2048"
            placeholder="https://www.instagram.com/kullanici?xtok=..."
            autocomplete="off"
        >

        <div class="actions">
            <button type="button" id="generate">
                Bağlantıyı Hazırla
            </button>
        </div>

        <p id="message" role="status" aria-live="polite"></p>

        {{-- Sonuç paneli: doğrulama başarılı olunca görünür; QR ve Kaydet düğmeleri de buraya eklenir --}}
        <div class="result" id="result">

            <p>
                <strong>Instagram hesabı:</strong>
                <span id="username"></span>
            </p>

            <label for="finalUrl">Hazırlanan bağlantı</label>

            <input id="finalUrl" type="text" readonly>

            <div class="actions">
                <button type="button" id="copy">
                    Bağlantıyı Kopyala
                </button>

                <a
                    id="open"
                    class="button"
                    href="#"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Instagram'da Aç
                </a>
            </div>

        </div>

    </div>
</main>

{{-- Ana script: bağlantı doğrulama, kopyalama --}}
<script>
(() => {
    const input = document.getElementById('instagramUrl');
    const result = document.getElementById('result');
    const message = document.getElementById('message');
    const username = document.getElementById('username');
    const finalUrl = document.getElementById('finalUrl');
    const open = document.getElementById('open');

    /**
     * Durum mesajını gösterir.
     * @param {string} text   Gösterilecek metin
     * @param {boolean} error true ise hata (kırmızı), değilse başarı (yeşil) stili
     */
    function showMessage(text, error = false) {
        message.textContent = text;
        message.className = error ? 'error' : 'success';
    }

    // Hazırla: önceki sonucu temizle, bağlantıyı doğrula, geçerliyse göster
    document.getElementById('generate').addEventListener('click', () => {
        result.classList.remove('visible');
        finalUrl.value = '';
        open.href = '#';

        let url;

        try {
            url = new URL(input.value.trim());

            // Yalnız https, instagram.com alan adı; kimlik bilgisi ve port yok

            if (
                url.protocol !== 'https:' ||
                !['instagram.com', 'www.instagram.com'].includes(
                    url.hostname.toLowerCase()
                ) ||
                url.username ||
                url.password ||
                url.port
            ) {
                throw new Error('Instagram bağlantısı geçersiz.');
            }

            // Yol tek parça ve geçerli kullanıcı adı olmalı
            const parts = url.pathname.split('/').filter(Boolean);

            if (
                parts.length !== 1 ||
                !/^[a-zA-Z0-9._]{1,30}$/.test(parts[0])
            ) {
                throw new Error('Geçerli bir profil bağlantısı girin.');
            }

            const account = parts[0];

            // Profil olmayan Instagram yollarını (gönderi, reel vb.) reddet
            const reserved = [
                'p', 'reel', 'reels', 'stories',
                'explore', 'direct', 'accounts'
            ];

            if (reserved.includes(account.toLowerCase())) {
                throw new Error('Bu bağlantı bir Instagram profili değil.');
            }

            // Instagram'ın paylaşım bağlantısındaki xtok parametresi zorunlu
            const token = url.searchParams.get('xtok');

            if (!token || !/^[A-Za-z0-9_+/=-]{8,256}$/.test(token)) {
                throw new Error(
                    'Instagram uygulamasından xtok içeren profil paylaşım bağlantısını kopyalayın.'
                );
            }

            // Instagram tarafından üretilen bağlantıyı değiştirmiyoruz.
            username.textContent = '@' + account;
            finalUrl.value = url.href;
            open.href = url.href;

            result.classList.add('visible');

            showMessage('Instagram bağlantısı hazırlandı.');

        } catch (error) {
            // Doğrulama hatası: mesajı kullanıcıya göster
            showMessage(
                error.message || 'Bağlantı işlenemedi.',
                true
            );
        }
    });

    // Hazırlanan bağlantıyı panoya kopyala; olmazsa elle kopyalamak için seç
    document.getElementById('copy').addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(finalUrl.value);
            showMessage('Bağlantı panoya kopyalandı.');
        } catch {
            finalUrl.focus();
            finalUrl.select();
            showMessage('Bağlantıyı seçip kopyalayabilirsiniz.', true);
        }
    });
})();
</script>


{{-- QR entegrasyonu: sonuç paneline "QR Kod Oluştur" düğmesi ekler --}}
<script>
// GENERATOR_STUDIO_QR_INTEGRATION_2026
(() => {
    const panel = document.getElementById('result');
    const input = document.getElementById('finalUrl');

    if (!panel || !input) return;

    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = '▦ QR Kod Oluştur';

    button.style.cssText = [
        'display:block',
        'margin-top:16px',
        'padding:14px 20px',
        'border:0',
        'border-radius:10px',
        'background:#2563eb',
        'color:#fff',
        'font-weight:600',
        'cursor:pointer'
    ].join(';');

    // Bağlantıyı yeniden doğrula, geçerliyse QR sayfasına content parametresiyle git
    button.addEventListener('click', () => {
        const value = input.value.trim();

        try {
            const url = new URL(value);

            if (
                url.protocol !== 'https:' ||
                !['instagram.com', 'www.instagram.com']
                    .includes(url.hostname.toLowerCase()) ||
                !url.searchParams.has('xtok')
            ) {
                throw new Error('Geçersiz bağlantı.');
            }

            window.location.href =
                '/qr?content=' + encodeURIComponent(value);

        } catch {
            alert('Önce Instagram bağlantısını hazırlayın.');
        }
    });

    panel.appendChild(button);
})();
</script>
{{-- Ortak "Bağlantıyı Kaydet" düğmesi (POST /baglantilar/kaydet) --}}
<script src="/js/link-save.js?v=1"></script>
</body>
</html>
