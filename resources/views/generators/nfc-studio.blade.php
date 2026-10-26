{{--
    NFC Studio sayfası.
    Rota: GET /nfc-studio (routes/web.php, 'nfc.studio'). Oturum açmış (auth) ve
    yönetici (is_admin) kullanıcı gerekir; aksi halde rota 403 döner.
    Veri: $projects = oturumdaki kullanıcının kayıtlı bağlantıları (id, name, type, target_url).
    Bu sürüm yalnızca bağlantı seçimi ve tarayıcının Web NFC desteğini kontrol eder;
    sunucuya istek atmaz, bu yüzden CSRF token'ı gerekmez (kart okuma/yazma sonraki adım).
--}}
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NFC Studio | Generator Studio</title>

    {{-- Koyu temalı sayfa stilleri (bu sayfaya özel, dış CSS yok) --}}
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #080e1c;
            color: #e9eef8;
            font-family: system-ui, sans-serif;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            padding: 28px 18px;
        }

        a { color: #93c5fd; }

        h1 { font-size: clamp(28px, 5vw, 42px); }

        .subtitle { color: #a9b7ce; }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
            margin-top: 28px;
        }

        .card {
            background: #111d33;
            border: 1px solid #293b59;
            border-radius: 18px;
            padding: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        select, input {
            width: 100%;
            padding: 13px;
            border: 1px solid #455675;
            border-radius: 10px;
            background: #0a1425;
            color: white;
            font-size: 15px;
        }

        button {
            padding: 13px 18px;
            border: 0;
            border-radius: 10px;
            background: #2563eb;
            color: white;
            font-weight: 700;
            cursor: pointer;
            margin-top: 14px;
        }

        button:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .status {
            padding: 16px;
            background: #12263d;
            border: 1px solid #315176;
            border-radius: 12px;
            margin-top: 22px;
            overflow-wrap: anywhere;
        }

        .muted { color: #9caec9; }

        .url {
            overflow-wrap: anywhere;
            font-size: 14px;
            color: #b4d5ff;
        }
    </style>
</head>
<body>
<div class="container">

    <a href="/">← Generator Studio</a>

    <h1>📡 NFC Studio</h1>

    <p class="subtitle">
        NFC kartlarınızı okuyun, bağlantı seçin ve
        cihazınızın NFC desteğini kontrol edin.
    </p>

    {{-- NFC uyumluluk durumu; metni aşağıdaki script doldurur --}}
    <div id="compatibility" class="status" role="status">
        NFC uyumluluğu kontrol ediliyor...
    </div>

    <div class="grid">

        {{-- Bağlantı seçimi: seçenekler sunucudan gelen kayıtlı projelerdir --}}
        <section class="card">
            <h2>🔗 Bağlantı Seçimi</h2>

            <label for="project">Kayıtlı bağlantılar</label>

            <select id="project">
                <option value="">Bağlantı seçin</option>

                @foreach($projects as $project)
                    {{-- Hedef adres data-url'de tutulur; seçilince script gösterir --}}
                    <option value="{{ $project->id }}"
                            data-url="{{ $project->target_url }}">
                        {{ $project->name }}
                        ({{ $project->type === 'google_review' ? 'Google Yorum' : 'Instagram' }})
                    </option>
                @endforeach
            </select>

            <p class="muted">Seçilen bağlantı:</p>

            <p id="selectedUrl" class="url">
                Henüz bağlantı seçilmedi.
            </p>
        </section>

        {{-- Cihazın Web NFC desteğini elle yeniden kontrol etme alanı --}}
        <section class="card">
            <h2>📱 NFC Cihaz Kontrolü</h2>

            <p>
                Bu sürümde cihazın Web NFC desteğini
                kontrol ediyoruz.
            </p>

            <button id="checkButton" type="button">
                NFC Desteğini Kontrol Et
            </button>

            <p id="checkResult" class="muted"
               role="status" aria-live="polite"></p>
        </section>

        {{-- Henüz etkin değil: NDEF kart okuma sonraki adımda eklenecek --}}
        <section class="card">
            <h2>📖 Kart Okuma</h2>

            <p>
                Bir sonraki adımda kartın NDEF
                kayıtlarını burada okuyacağız.
            </p>

            <button disabled>Kart Oku — Sonraki Adım</button>
        </section>

        {{-- Henüz etkin değil: karta yazma, bağlantı seçimi ve NFC doğrulanınca açılacak --}}
        <section class="card">
            <h2>✍️ Karta Yazma</h2>

            <p>
                Bağlantı seçimi ve NFC uyumluluğu
                doğrulandıktan sonra aktif edilecek.
            </p>

            <button disabled>Karta Yaz — Sonraki Adım</button>
        </section>

    </div>

</div>

<script>
(function () {
    'use strict';

    const project = document.getElementById('project');
    const selectedUrl = document.getElementById('selectedUrl');
    const compatibility = document.getElementById('compatibility');
    const checkButton = document.getElementById('checkButton');
    const checkResult = document.getElementById('checkResult');

    /**
     * Web NFC kullanılabilir mi? Güvenli bağlam (HTTPS) ve NDEFReader gerekir.
     * @returns {boolean}
     */
    function supported() {
        return window.isSecureContext &&
               typeof window.NDEFReader === 'function';
    }

    /**
     * Uyumluluk durumunu (HTTPS yok / API yok / kullanılabilir) üstteki kutuya yazar.
     * Parametre almaz, değer döndürmez.
     */
    function showCompatibility() {
        if (!window.isSecureContext) {
            compatibility.textContent =
                'HTTPS gerekli. NFC erişimi güvenli bağlantıda kullanılabilir.';
            return;
        }

        if (!supported()) {
            compatibility.textContent =
                'Bu tarayıcı Web NFC API desteği sunmuyor. ' +
                'NFC destekli Android cihazda Chrome ile deneyin.';
            return;
        }

        compatibility.textContent =
            'Web NFC API kullanılabilir görünüyor. ' +
            'Gerçek kart erişimi için cihazın NFC özelliği açık olmalıdır.';
    }

    // Seçilen bağlantının hedef adresini (data-url) kullanıcıya göster
    project.addEventListener('change', function () {
        const option = project.selectedOptions[0];
        selectedUrl.textContent =
            option?.dataset.url || 'Henüz bağlantı seçilmedi.';
    });

    // Butona basınca durumu yeniden değerlendir; fiziksel kart testi yapılmaz
    checkButton.addEventListener('click', function () {
        showCompatibility();

        checkResult.textContent = supported()
            ? 'Web NFC API mevcut. Fiziksel kart testi henüz yapılmadı.'
            : 'Web NFC kullanılamıyor. Cihaz veya tarayıcı desteğini kontrol edin.';
    });

    // Sayfa açılır açılmaz ilk kontrolü yap
    showCompatibility();
})();
</script>
</body>
</html>
