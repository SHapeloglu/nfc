<!DOCTYPE html>
{{-- Ana sayfa (/): herkese açık; QR, Google yorum, Instagram, WiFi QR ve UTM araçlarını listeler. --}}
{{-- Sayfada betik ve sunucuya istek yoktur; yalnızca statik içerik ve stil vardır. "Yönetim Paneli" bağlantısı /admin'e gider (yönetici girişi orada istenir). --}}
{{-- NFC Studio bağlantısı yalnız oturum açmış yöneticilere gösterilir. --}}
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#080e1c">
    <meta name="description" content="Generator Studio - QR kod, Google yorum ve sosyal medya bağlantı araçları.">

    <title>Generator Studio | Dijital Araçlar</title>

    {{-- ===== Stil bölümü: koyu tema, ızgara düzeni, kartlar ve duyarlı kırılımlar ===== --}}
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #080e1c;
            color: #e9eef8;
            font-family: Inter, system-ui, -apple-system, sans-serif;
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .container {
            width: min(1160px, 92%);
            margin: auto;
        }

        header {
            border-bottom: 1px solid #ffffff12;
            background: #0c1425;
        }

        .navbar {
            min-height: 78px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .brand-icon {
            display: grid;
            place-items: center;
            width: 43px;
            height: 43px;
            border-radius: 13px;
            background: linear-gradient(135deg, #22d3ee, #6366f1);
            color: white;
            font-size: 22px;
        }

        .admin {
            padding: 10px 17px;
            border: 1px solid #334155;
            border-radius: 11px;
            font-size: 14px;
            font-weight: 600;
            transition: .2s;
        }

        .admin:hover {
            border-color: #22d3ee;
            color: #22d3ee;
        }

        .hero {
            padding: 90px 0 75px;
            text-align: center;
        }

        .eyebrow {
            display: inline-block;
            padding: 7px 16px;
            border: 1px solid #22d3ee55;
            border-radius: 30px;
            background: #22d3ee10;
            color: #67e8f9;
            font-size: 13px;
            font-weight: 700;
        }

        h1 {
            margin: 23px auto;
            max-width: 800px;
            font-size: clamp(38px, 6vw, 66px);
            line-height: 1.13;
            letter-spacing: -2px;
        }

        .gradient {
            background: linear-gradient(100deg, #22d3ee, #818cf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero p {
            max-width: 650px;
            margin: auto;
            color: #94a3b8;
            font-size: 17px;
        }

        .section-title {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .section-description {
            color: #94a3b8;
            margin-top: 0;
            margin-bottom: 28px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .card {
            display: flex;
            flex-direction: column;
            min-height: 258px;
            padding: 27px;
            background: #101a2d;
            border: 1px solid #26334c;
            border-radius: 19px;
            transition: .2s;
        }

        a.card:hover {
            transform: translateY(-5px);
            border-color: #22d3ee;
            box-shadow: 0 16px 40px #00000030;
        }

        .card-icon {
            display: grid;
            place-items: center;
            width: 57px;
            height: 57px;
            border-radius: 15px;
            font-size: 27px;
        }

        .green {
            background: #14532d55;
        }

        .pink {
            background: #83184355;
        }

        .blue {
            background: #1e40af55;
        }

        .purple {
            background: #581c8755;
        }

        .orange {
            background: #9a341255;
        }

        .cyan {
            background: #155e7555;
        }

        .card h3 {
            font-size: 19px;
            margin: 21px 0 7px;
        }

        .card p {
            color: #94a3b8;
            font-size: 14px;
            margin: 0;
        }

        .action {
            margin-top: auto;
            padding-top: 22px;
            color: #67e8f9;
            font-weight: 700;
            font-size: 14px;
        }

        .soon {
            margin-top: auto;
            padding-top: 22px;
            color: #fbbf24;
            font-size: 13px;
            font-weight: 700;
        }

        .inactive {
            opacity: .72;
        }

        .upcoming {
            margin-top: 75px;
        }

        footer {
            margin-top: 100px;
            border-top: 1px solid #ffffff12;
            padding: 30px 0;
            color: #64748b;
            text-align: center;
            font-size: 13px;
        }

        @media (max-width: 850px) {
            .grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 580px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .hero {
                padding: 65px 0 55px;
            }

            h1 {
                letter-spacing: -1px;
            }

            .brand {
                font-size: 17px;
            }

            .card {
                min-height: 225px;
            }
        }
    </style>
</head>

<body>

{{-- Üst çubuk: marka ve yönetim paneli bağlantısı. --}}
<header>
    <div class="container navbar">
        <a class="brand" href="/">
            <span class="brand-icon">◈</span>
            <span>Generator Studio</span>
        </a>

        <a class="admin" href="/admin">
            Yönetim Paneli ↗
        </a>
    </div>
</header>

<main class="container">

    {{-- Karşılama bölümü. --}}
    <section class="hero">
        <span class="eyebrow">
            ✨ Dijital Araç Platformu
        </span>

        <h1>
            Dijital dünyada<br>
            <span class="gradient">her şey tek yerde.</span>
        </h1>

        <p>
            QR kodlar oluşturun, Google değerlendirme
            bağlantıları hazırlayın ve sosyal medya
            bağlantılarınızı kolayca yönetin.
        </p>
    </section>

    {{-- Kullanıma hazır araçlar: her kart ilgili aracın sayfasına bağlanır. --}}
    <section aria-labelledby="tools-title">
        <h2 id="tools-title" class="section-title">
            Kullanıma Hazır Araçlar
        </h2>

        <p class="section-description">
            İhtiyacınız olan aracı seçerek hemen başlayın.
        </p>

        <div class="grid">

            <a class="card" href="{{ route('google.review.index') }}">
                <div class="card-icon green">⭐</div>

                <h3>Google Yorum Bağlantısı</h3>

                <p>
                    İşletmeniz için Google değerlendirme
                    bağlantısı oluşturun.
                </p>

                <span class="action">
                    Aracı Aç →
                </span>
            </a>

            <a class="card" href="{{ route('instagram.follow.index') }}">
                <div class="card-icon pink">📸</div>

                <h3>Instagram Takip Bağlantısı</h3>

                <p>
                    Instagram profilinizin paylaşım
                    bağlantısını hazırlayın ve QR kod oluşturun.
                </p>

                <span class="action">
                    Aracı Aç →
                </span>
            </a>

            <a class="card" href="{{ route('qr.index') }}">
                <div class="card-icon blue">▦</div>

                <h3>QR Kod Oluşturucu</h3>

                <p>
                    Renk, şekil ve logo seçenekleriyle
                    kişiselleştirilmiş QR kodlar hazırlayın.
                </p>

                <span class="action">
                    Aracı Aç →
                </span>
            </a>

            <a class="card" href="{{ route('wifi.qr.index') }}">
                <div class="card-icon orange">📶</div>

                <h3>WiFi QR</h3>

                <p>
                    Kablosuz ağ bağlantıları için
                    QR kod oluşturun.
                </p>

                <span class="action">Aracı Aç →</span>
            </a>

            <a class="card" href="{{ route('utm.builder.index') }}">
                <div class="card-icon cyan">🔗</div>

                <h3>UTM Link Builder</h3>

                <p>
                    Pazarlama kampanyalarınız için
                    takip edilebilir bağlantılar hazırlayın.
                </p>

                <span class="action">Aracı Aç →</span>
            </a>

        </div>
    </section>

    {{-- NFC Studio erişimi sunucuda da yönetici yetkisiyle korunur. --}}
    <section class="upcoming" aria-labelledby="admin-tools-title">

        <h2 id="admin-tools-title" class="section-title">
            Yönetim Araçları
        </h2>

        <p class="section-description">
            NFC kart işlemlerini yöneticiler bu bölümden açabilir.
        </p>

        <div class="grid">

            @if(auth()->user()?->is_admin)
            <a class="card" href="{{ route('nfc.studio') }}">
                <div class="card-icon purple">📡</div>

                <h3>NFC Studio</h3>

                <p>
                    NFC kart okuma, bağlantı yazma
                    ve doğrulama işlemleri.
                </p>

                <span class="action">Aracı Aç →</span>
            </a>
            @else
            <article class="card inactive">
                <div class="card-icon purple">📡</div>

                <h3>NFC Studio</h3>

                <p>
                    NFC kart okuma, bağlantı yazma
                    ve etiket yönetimi.
                </p>

                <span class="soon">
                    YÖNETİCİ GİRİŞİ GEREKLİ
                </span>
            </article>
            @endif

        </div>
    </section>

</main>

{{-- Alt bilgi: yıl, sunucu tarihinden otomatik gelir. --}}
<footer>
    <div class="container">
        © {{ date('Y') }} Generator Studio
        — Dijital Araç Platformu
    </div>
</footer>

</body>
</html>
