<!DOCTYPE html>
{{-- Google yorum bağlantısı bulucu: işletmeyi Google'da aratır, doğru işletmeyi seçtirir ve müşterilerin doğrudan yorum yazabileceği bağlantıyı üretir (NFC karta yazmak için). --}}
{{-- Sayfa bağımsız bir HTML sayfasıdır; arama ve bağlantı üretimi sayfa sonundaki betikte yapılır. --}}
{{-- Adımlar: 1 arama, 2 sonuç seçimi, 3 bağlantıyı kopyalama. --}}
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>Google Yorum Linki Bulucu | Generator Studio</title>

{{-- ===== Stil bölümü ===== --}}
<style>
*{box-sizing:border-box}
body{
    margin:0;
    background:#f3f6fb;
    color:#182338;
    font:15px system-ui,sans-serif;
}
header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    background:#fff;
    padding:20px 5%;
    border-bottom:1px solid #e2e8f0;
}
header strong{font-size:21px}
header a{color:#2563eb;text-decoration:none}
main{
    max-width:920px;
    margin:35px auto;
    padding:0 20px;
}
h1{
    font-size:clamp(27px,4vw,38px);
    margin:0 0 10px;
}
.subtitle{
    color:#64748b;
    line-height:1.7;
    margin-bottom:25px;
}
.panel{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:18px;
    padding:26px;
    margin-bottom:20px;
}
label{
    display:block;
    font-weight:700;
    margin-bottom:10px;
}
.search-row{
    display:grid;
    grid-template-columns:1fr auto;
    gap:10px;
}
input{
    width:100%;
    min-width:0;
    padding:14px;
    border:1px solid #cbd5e1;
    border-radius:10px;
    font:inherit;
}
button{
    background:#2563eb;
    color:#fff;
    border:0;
    border-radius:10px;
    padding:13px 20px;
    font:inherit;
    font-weight:700;
    cursor:pointer;
}
button:disabled{
    opacity:.55;
    cursor:wait;
}
.secondary{background:#17253c}
.note{
    font-size:12px;
    color:#64748b;
    line-height:1.7;
}
#status{
    min-height:25px;
    margin-top:14px;
    font-size:13px;
}
.results{
    display:grid;
    gap:12px;
    margin-top:16px;
}
.result{
    width:100%;
    display:block;
    background:#fff;
    color:#182338;
    border:1px solid #e2e8f0;
    text-align:left;
    padding:18px;
    border-radius:12px;
}
.result:hover{
    border-color:#2563eb;
    background:#eff6ff;
}
.result strong{
    display:block;
    margin-bottom:6px;
}
.result small{
    display:block;
    font-size:12px;
    color:#64748b;
    line-height:1.5;
}
.result.selected{
    border-color:#2563eb;
    background:#eff6ff;
}
.link-box{
    background:#f8fafc;
    border:1px solid #cbd5e1;
    border-radius:10px;
    padding:14px;
    overflow-wrap:anywhere;
    font-size:14px;
    margin:15px 0;
}
.actions{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}
.hidden{display:none!important}
@media(max-width:600px){
    main{margin-top:20px}
    .panel{padding:18px}
    .search-row{grid-template-columns:1fr}
    button{width:100%}
    .actions{display:grid}
}
</style>
</head>

<body>

{{-- Üst çubuk: Generator Studio adı ve yönetim paneline bağlantı. --}}
<header>
<strong>▦ Generator Studio</strong>
<a href="/admin">Yönetim Paneli</a>
</header>

<main>

<h1>⭐ Google Yorum Linki Bulucu</h1>

<p class="subtitle">
İşletmeyi Google'da arayın, doğru işletmeyi seçin ve
müşterilerin doğrudan değerlendirme yapabileceği bağlantıyı oluşturun.
</p>

{{-- Adım 1: işletme adı yazılıp arama yapılır. --}}
<section class="panel">
<label for="businessSearch">Google'da işletme ara</label>

<div class="search-row">
<input
    type="search"
    id="businessSearch"
    maxlength="4096"
    placeholder="Örn: OLAP Teknoloji İstanbul"
    autocomplete="off"
>
<button type="button" id="searchButton">
    🔎 Google'da Ara
</button>
</div>

<p class="note">
Daha doğru sonuçlar için işletme adıyla birlikte il veya ilçe yazabilirsiniz.
</p>

<p id="status" role="status" aria-live="polite"></p>
</section>

{{-- Adım 2: sonuçlardan doğru işletme seçilir. Panel, sonuç gelene kadar gizlidir. --}}
<section class="panel hidden" id="resultsPanel">
<h2>Google İşletme Sonuçları</h2>
<p class="note">Doğru işletmeyi seçin. Benzer isimli işletmelerin adreslerini kontrol edin.</p>
<div id="results" class="results"></div>
</section>

{{-- Adım 3: üretilen değerlendirme bağlantısı; kopyalama ve açma düğmeleri burada. --}}
<section class="panel hidden" id="linkPanel">
<h2>✅ Google Yorum Bağlantısı</h2>

<p id="selectedBusiness"></p>

<label>Doğrudan değerlendirme bağlantısı</label>
<div id="reviewLink" class="link-box"></div>

<div class="actions">
<button type="button" id="copyButton">📋 Linki Kopyala</button>
<button type="button" id="openButton" class="secondary">
Google'da Aç
</button>
</div>

<p id="copyStatus" class="note" role="status"></p>

<p class="note">
Bağlantıyı NFC karta yazmadan önce açıp doğru işletmenin
değerlendirme ekranına gittiğini kontrol edin.
</p>
</section>

</main>

<script>
{{-- ===== Ana betik: işletme arama (/google-yorum/ara), sonuç seçimi, bağlantı kopyalama ===== --}}
(()=>{
'use strict';

const el=id=>document.getElementById(id);
const searchButton=el('searchButton');
const status=el('status');
/* reviewUrl: seçilen işletmenin yorum bağlantısı; currentResults: son aramanın sonuçları (indeksle seçilir). */
let reviewUrl='';
let currentResults=[];


/**
 * Arama kutusu girdisini sorguya çevirir. Düz metin aynen döner; bağlantı ise yalnızca
 * HTTPS ve Google alan adlarından (google.com, www, maps) kabul edilir, işletme adı
 * q/query parametresinden ya da /maps/place/ yolundan çıkarılır. Geçersizse hata fırlatır.
 * Böylece sunucuya keyfi adresler değil yalnızca işletme adı gider.
 */
function normalizeBusinessQuery(input){
    const raw=input.trim();

    if(!raw.startsWith('http://') &&
       !raw.startsWith('https://')){
        return raw;
    }

    let url;

    try{
        url=new URL(raw);
    }catch{
        throw Error('Geçerli bir bağlantı giriniz.');
    }

    if(url.protocol!=='https:'){
        throw Error('Yalnızca HTTPS bağlantıları kabul edilir.');
    }

    const host=url.hostname.toLowerCase();

    const googleHosts=[
        'google.com',
        'www.google.com',
        'maps.google.com'
    ];

    if(!googleHosts.includes(host)){
        throw Error('Bu bağlantı bir Google arama bağlantısı değil.');
    }

    let query=url.searchParams.get('q')
        ||url.searchParams.get('query');

    if(!query && url.pathname.startsWith('/maps/place/')){
        const segment=url.pathname
            .substring('/maps/place/'.length)
            .split('/')[0];

        query=decodeURIComponent(
            segment.replace(/\+/g,' ')
        );
    }

    if(!query){
        throw Error(
            'Bağlantıda işletme adı bulunamadı. '+
            'İşletme adını doğrudan yazabilirsiniz.'
        );
    }

    return query.trim();
}

/**
 * Girdiyi doğrular (3-120 karakter) ve /google-yorum/ara'ya JSON POST atar (CSRF başlığıyla).
 * 401/403 yönetici girişi yok, 419 oturum/CSRF süresi dolmuş; hata durum satırında gösterilir.
 * İstek sürerken düğme kilitlenir; sunucu tarafında istek sınırı olabilir, bu da hata olarak görünür.
 * Sonuçlar metin olarak (textContent) eklenir, HTML olarak değil: dış veriden XSS'i önler.
 */
async function search(){
    let query;

    try{
        query=normalizeBusinessQuery(
            el('businessSearch').value
        );
    }catch(error){
        status.textContent=error.message;
        return;
    }

    if(query.length>120){
        status.textContent=
            'İşletme adı 120 karakterden uzun olamaz.';
        return;
    }

    if(query.length<3){
        status.textContent='En az 3 karakter giriniz.';
        return;
    }

    reviewUrl='';
    el('resultsPanel').classList.add('hidden');
    el('linkPanel').classList.add('hidden');
    el('results').replaceChildren();

    searchButton.disabled=true;
    status.textContent='Google üzerinde işletmeler aranıyor...';

    try{
        const response=await fetch('/google-yorum/ara',{
            method:'POST',
            credentials:'same-origin',
            headers:{
                'Content-Type':'application/json',
                'Accept':'application/json',
                'X-CSRF-TOKEN':document.querySelector(
                    'meta[name="csrf-token"]'
                ).content
            },
            body:JSON.stringify({query})
        });

        if(response.status===401 || response.status===403){
            throw Error('Arama yapmak için yönetici girişi gerekli.');
        }

        if(response.status===419){
            throw Error('Oturum süresi doldu. Sayfayı yenileyin.');
        }

        if(!response.ok){
            throw Error('Google araması başarısız. HTTP '+response.status);
        }

        const data=await response.json();
        const places=Array.isArray(data.results)?data.results:[];

        currentResults=places;
        status.textContent=places.length+
            ' işletme bulundu. Doğru işletmeyi seçin.';

        if(!places.length)return;

        el('resultsPanel').classList.remove('hidden');

        places.forEach((place,index)=>{
            const button=document.createElement('button');
            button.type='button';
            button.className='result';

            const name=document.createElement('strong');
            name.textContent=place.name;

            const address=document.createElement('small');
            address.textContent=place.address||'Adres bilgisi yok';

            button.append(name,address);

            button.addEventListener('click',()=>{
                selectPlace(index);
                document.querySelectorAll('.result')
                    .forEach(b=>b.classList.remove('selected'));

                button.classList.add('selected');
            });

            el('results').append(button);
        });
    }catch(error){
        status.textContent=error.message;
    }finally{
        searchButton.disabled=false;
    }
}

/** Seçilen işletmenin place_id değerinden Google yorum yazma bağlantısını üretir ve bağlantı panelini açar. */
function selectPlace(index){
    const place=currentResults[index];
    if(!place || !place.place_id)return;

    reviewUrl=
        'https://search.google.com/local/writereview?placeid='+
        encodeURIComponent(place.place_id);

    el('selectedBusiness').textContent=
        place.name+' — '+place.address;

    el('reviewLink').textContent=reviewUrl;
    el('copyStatus').textContent='';
    el('linkPanel').classList.remove('hidden');
}

searchButton.addEventListener('click',search);

el('businessSearch').addEventListener('keydown',event=>{
    if(event.key==='Enter'){
        event.preventDefault();
        search();
    }
});

/* Panoya kopyalama güvenli bağlam/izin gerektirir; olmazsa kullanıcıya elle seçme önerilir. */
el('copyButton').addEventListener('click',async()=>{
    if(!reviewUrl)return;

    try{
        await navigator.clipboard.writeText(reviewUrl);
        el('copyStatus').textContent='✓ Bağlantı kopyalandı.';
    }catch{
        el('copyStatus').textContent=
            'Kopyalama başarısız. Bağlantıyı elle seçebilirsiniz.';
    }
});

/* noopener,noreferrer: açılan sekmenin bu sayfaya erişimini engeller. */
el('openButton').addEventListener('click',()=>{
    if(reviewUrl){
        window.open(reviewUrl,'_blank','noopener,noreferrer');
    }
});

})();
</script>

<script>
{{-- Aşağıdaki betik: üretilen bağlantıyı /qr?content=... ile QR sayfasına taşıyan düğmeyi ekler (işaret satırını silmeyin). --}}
// GENERATOR_STUDIO_QR_INTEGRATION_2026
(() => {
    const panel = document.getElementById('linkPanel');
    const link = document.getElementById('reviewLink');

    if (!panel || !link) return;

    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = '▦ QR Kod Oluştur';

    button.style.cssText = [
        'display:inline-block',
        'margin-top:14px',
        'padding:12px 20px',
        'border:0',
        'border-radius:10px',
        'background:#2563eb',
        'color:#fff',
        'font-weight:600',
        'cursor:pointer'
    ].join(';');

    button.addEventListener('click', () => {
        const url = link.textContent.trim();

        if (!url.startsWith(
            'https://search.google.com/local/writereview?placeid='
        )) {
            alert('Önce bir Google işletmesi seçin.');
            return;
        }

        window.location.href =
            '/qr?content=' + encodeURIComponent(url);
    });

    panel.appendChild(button);
})();
</script>
{{-- Bağlantı kaydetme yardımcı betiği (public/js/link-save.js). --}}
<script src="/js/link-save.js?v=1"></script>
</body>
</html>
