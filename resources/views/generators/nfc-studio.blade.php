{{--
    NFC Studio sayfası.
    Rota: GET /nfc-studio (routes/web.php, 'nfc.studio'). Oturum açmış (auth) ve
    yönetici (is_admin) kullanıcı gerekir; aksi halde rota 403 döner.
    Veri: $projects = oturumdaki kullanıcının kayıtlı bağlantıları (id, name, type, target_url).
    NFC okuma ve yazma Android Chrome Web NFC aracılığıyla, kullanıcı dokunuşuyla çalışır.
    Kart yazma ve yeniden okuma sonuçları yönetici oturumuyla kart envanterine bildirilir.
    Tarayıcıdan gelen sonuç fiziksel karta ilişkin bağımsız sunucu kanıtı değildir.
--}}
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

        .secondary-button { background: #475569; }

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
        NFC kartlarınızı okuyun, kayıtlı bağlantıyı karta yazın
        ve yazılan adresi yeniden okutarak doğrulayın.
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

            <label for="card">Hazırlanacak kart</label>
            <select id="card">
                <option value="">Kart seçin</option>
                @foreach($cards as $card)
                    <option value="{{ $card->id }}" data-project-id="{{ $card->link_project_id }}">
                        {{ $card->label }} ({{ $card->status }})
                    </option>
                @endforeach
            </select>
            <p class="muted">Yalnızca seçilen bağlantıya bağlı, henüz teslim edilmemiş kartlar listelenir. Kart oluşturmak için yönetim panelini kullanın.</p>
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

        <section class="card">
            <h2>📖 Kart Okuma</h2>

            <p>
                Kartı telefonunuzun NFC alanına yaklaştırın.
                Karttaki HTTPS bağlantısı gösterilecektir.
            </p>

            <button id="readButton" type="button" disabled>Kart Oku</button>
        </section>

        <section class="card">
            <h2>✍️ Karta Yazma</h2>

            <p>
                Önce bağlantıyı seçin, ardından boş veya yeniden
                yazılabilir NDEF kartını telefonunuza yaklaştırın.
            </p>

            <button id="writeButton" type="button" disabled>Karta Yaz ve Doğrula</button>
            <p id="writeHint" class="muted" role="status"></p>
        </section>

    </div>

    <div id="operationStatus" class="status" role="status" aria-live="polite">
        Kart işlemi başlatılmadı.
    </div>

    <p class="muted">Doğrulama sonucu tarayıcınızdan sunucuya bildirilir. Fiziksel kartın bağımsız veya kriptografik doğrulaması değildir.</p>

    <button id="cancelButton" class="secondary-button" type="button" disabled>
        İşlemi İptal Et
    </button>

</div>

<script>
(function () {
    'use strict';

    const project = document.getElementById('project');
    const card = document.getElementById('card');
    const selectedUrl = document.getElementById('selectedUrl');
    const compatibility = document.getElementById('compatibility');
    const checkButton = document.getElementById('checkButton');
    const checkResult = document.getElementById('checkResult');
    const readButton = document.getElementById('readButton');
    const writeButton = document.getElementById('writeButton');
    const cancelButton = document.getElementById('cancelButton');
    const operationStatus = document.getElementById('operationStatus');
    const writeHint = document.getElementById('writeHint');
    // Kart seçenekleri bir kez alınır; liste seçili bağlantıya göre bu kopyadan süzülür.
    const cardPlaceholder = card.options[0];
    let cardOptions = Array.from(card.options).slice(1);
    let controller = null;
    let pending = false;
    let operationId = 0;
    let verification = null;
    const writtenEndpoint = {{ Js::from(route('nfc.cards.written', ['card' => '__CARD__'])) }};
    const verifiedEndpoint = {{ Js::from(route('nfc.cards.verified', ['card' => '__CARD__'])) }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    /**
     * Web NFC kullanılabilir mi? Güvenli bağlam (HTTPS) ve NDEFReader gerekir.
     * @returns {boolean}
     */
    function supported() {
        return window.isSecureContext &&
               typeof window.NDEFReader === 'function';
    }

    function selectedLink() {
        const option = project.selectedOptions[0];
        const value = option?.dataset.url;

        if (!value) {
            return null;
        }

        try {
            const url = new URL(value);
            return url.protocol === 'https:' ? url.href : null;
        } catch {
            return null;
        }
    }

    function selectedCard() {
        const option = card.selectedOptions[0];
        const projectId = Number(project.value);

        if (!option?.value || !projectId || Number(option.dataset.projectId) !== projectId) {
            return null;
        }

        return { id: Number(option.value), projectId };
    }

    /**
     * Kart listesini seçili bağlantıya göre yeniden doldurur; yalnızca o bağlantıya bağlı kartlar görünür.
     */
    function filterCards() {
        const projectId = project.value;
        const matching = cardOptions.filter((option) => option.dataset.projectId === projectId);

        card.replaceChildren(cardPlaceholder, ...matching);
        card.value = '';
    }

    /**
     * Yazma düğmesi pasifse nedenini döndürür; işlem kullanılabilirse boş metin.
     * @returns {string}
     */
    function writeBlockReason() {
        if (!supported()) {
            return 'Web NFC bu cihazda kullanılamıyor.';
        }

        if (!project.value) {
            return 'Önce bir bağlantı seçin.';
        }

        if (!selectedLink()) {
            return 'Seçilen bağlantı HTTPS değil; karta yazılamaz.';
        }

        if (!selectedCard()) {
            return cardOptions.some((option) => option.dataset.projectId === project.value)
                ? 'Yazmak için bu bağlantıya bağlı bir kart seçin.'
                : 'Bu bağlantıya bağlı yazılabilir kart yok. Kartı yönetim panelinden bu bağlantıya bağlayın.';
        }

        return '';
    }

    async function recordResult(stage, cardId, projectId, url) {
        const endpoint = (stage === 'written' ? writtenEndpoint : verifiedEndpoint)
            .replace('__CARD__', String(cardId));

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                project_id: projectId,
                [stage === 'written' ? 'written_url' : 'verified_url']: url,
            }),
        });

        if (!response.ok) {
            throw new Error('Kart işlemi sunucuya kaydedilemedi (HTTP ' + response.status + '). ' +
                'Fiziksel karta yazılmış olabilir; kartı ve envanter durumunu kontrol edin.');
        }
    }

    function updateButtons() {
        // Yazım sırasında seçili bağlantı değişirse yanlış kart doğrulanabilir.
        project.disabled = pending;
        card.disabled = pending;
        readButton.disabled = pending || !supported();
        writeButton.disabled = pending || !supported() || !selectedLink() || !selectedCard();
        cancelButton.disabled = !pending;
        writeHint.textContent = pending ? '' : writeBlockReason();
    }

    function finish(id, message) {
        if (id !== operationId) {
            return;
        }

        controller?.abort();
        controller = null;
        pending = false;
        operationStatus.textContent = message;
        updateButtons();
    }

    function describeError(error) {
        switch (error?.name) {
            case 'NotAllowedError': return 'NFC izni verilmedi. Tarayıcı izinlerini kontrol edin.';
            case 'NotSupportedError': return 'Bu cihaz veya kart NDEF işlemini desteklemiyor.';
            case 'NotReadableError': return 'Kart okunamadı. NFC özelliğini ve kartı kontrol edin.';
            case 'InvalidStateError': return 'Başka bir NFC işlemi sürüyor olabilir.';
            case 'AbortError': return 'İşlem iptal edildi.';
            default: return 'NFC işlemi başarısız oldu. Kartı yeniden deneyin.';
        }
    }

    function cardUrl(message) {
        for (const record of message.records) {
            if (record.recordType !== 'url' || !record.data) {
                continue;
            }

            const decoded = new TextDecoder().decode(record.data);

            try {
                const url = new URL(decoded);
                if (url.protocol === 'https:') {
                    return url.href;
                }
            } catch {
                // Sonraki NDEF kaydına geç.
            }
        }

        return null;
    }

    async function scan(expectedUrl = null) {
        const id = ++operationId;
        const recording = verification;
        // Bu işlem için yalnız ilk okuma sonucu işlenir; doğrulama isteği sürerken gelen ek olaylar yok sayılır.
        let handled = false;
        controller?.abort();
        controller = new AbortController();
        pending = true;
        updateButtons();

        operationStatus.textContent = expectedUrl
            ? 'Yazma tamamlandı. Doğrulamak için kartı tekrar yaklaştırın.'
            : 'Kartı telefonunuza yaklaştırın...';

        try {
            const reader = new NDEFReader();
            reader.addEventListener('readingerror', () => {
                if (id === operationId) {
                    operationStatus.textContent = 'Kart okunamadı. Yeniden yaklaştırın.';
                }
            });
            reader.addEventListener('reading', async ({ message }) => {
                if (id !== operationId || handled) {
                    return;
                }
                handled = true;

                let url;

                try {
                    url = cardUrl(message);
                } catch {
                    finish(id, 'Kartın NDEF verisi çözümlenemedi. Başka kart deneyin.');
                    return;
                }

                if (expectedUrl !== null) {
                    if (url !== expectedUrl || !recording) {
                        finish(id, '✗ Doğrulama başarısız. Kartta beklenen bağlantı bulunamadı.');
                        return;
                    }

                    try {
                        await recordResult('verified', recording.cardId, recording.projectId, url);
                        finish(id, '✓ Kart yeniden okundu. Doğrulama sonucu envantere kaydedildi: ' + url);
                        verification = null;
                        writeButton.textContent = 'Karta Yaz ve Doğrula';
                        // Doğrulanan kart artık yazılabilir listede yer almaz.
                        cardOptions = cardOptions.filter((option) => option.value !== String(recording.cardId));
                        filterCards();
                        updateButtons();
                    } catch (error) {
                        finish(id, error.message);
                    }
                } else {
                    finish(id, url
                        ? 'Kart okundu. HTTPS bağlantısı: ' + url
                        : 'Kart okundu ancak HTTPS URL kaydı bulunamadı.');
                }
            });
            await reader.scan({ signal: controller.signal });
        } catch (error) {
            // İşlem zaten sonuçlandıysa (ör. başarılı doğrulama) hata mesajı onu ezmez.
            if (!handled) {
                finish(id, describeError(error));
            }
        }
    }

    async function writeAndVerify() {
        const url = selectedLink();
        const selected = selectedCard();

        if (!supported() || !url || !selected || pending) {
            return;
        }

        const id = ++operationId;
        controller = new AbortController();
        pending = true;
        updateButtons();
        operationStatus.textContent = 'Yazılabilir NFC kartını telefonunuza yaklaştırın...';

        try {
            const reader = new NDEFReader();
            await reader.write({ records: [{ recordType: 'url', data: url }] }, {
                signal: controller.signal,
            });

            if (id !== operationId) {
                return;
            }

            await recordResult('written', selected.id, selected.projectId, url);

            if (id !== operationId) {
                return;
            }

            // Web NFC izinleri kullanıcı etkileşimi gerektirebilir: doğrulama ayrı dokunuşla başlar.
            verification = { url, cardId: selected.id, projectId: selected.projectId };
            writeButton.textContent = 'Yazılanı Doğrula';
            finish(id, 'Yazma sonucu envantere bildirildi. Kartı yeniden okutmak için “Yazılanı Doğrula” düğmesine basın.');
        } catch (error) {
            finish(id, error?.name ? describeError(error) : error.message);
        }
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
        updateButtons();
    }

    // Seçilen bağlantının hedef adresini (data-url) kullanıcıya göster
    project.addEventListener('change', function () {
        const option = project.selectedOptions[0];
        selectedUrl.textContent =
            option?.dataset.url || 'Henüz bağlantı seçilmedi.';
        verification = null;
        writeButton.textContent = 'Karta Yaz ve Doğrula';
        filterCards();
        updateButtons();
    });

    card.addEventListener('change', () => {
        verification = null;
        writeButton.textContent = 'Karta Yaz ve Doğrula';
        updateButtons();
    });

    // Butona basınca durumu yeniden değerlendir; fiziksel kart testi yapılmaz
    checkButton.addEventListener('click', function () {
        showCompatibility();

        checkResult.textContent = supported()
            ? 'Web NFC API mevcut. Fiziksel kart testi henüz yapılmadı.'
            : 'Web NFC kullanılamıyor. Cihaz veya tarayıcı desteğini kontrol edin.';
    });

    readButton.addEventListener('click', () => {
        if (supported() && !pending) {
            scan();
        }
    });
    writeButton.addEventListener('click', () => {
        if (verification) {
            scan(verification.url);
        } else {
            writeAndVerify();
        }
    });
    cancelButton.addEventListener('click', () => {
        ++operationId;
        controller?.abort();
        controller = null;
        pending = false;
        operationStatus.textContent = 'İşlem iptal edildi.';
        updateButtons();
    });

    // Sayfa açılır açılmaz ilk kontrolü yap
    filterCards();
    showCompatibility();
    updateButtons();
})();
</script>
</body>
</html>
