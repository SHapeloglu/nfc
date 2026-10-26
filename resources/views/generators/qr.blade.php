<!DOCTYPE html>
{{-- QR Tasarım Stüdyosu: QR kodunu tasarlar (içerik, desen, renk, logo), canlı önizler ve indirir. --}}
{{-- QR çizimi sayfa sonundaki betikte, tarayıcıda qr-code-styling kütüphanesiyle yapılır. --}}
{{-- Tasarımı kaydetmek yönetici girişi gerektirir; logo dosyaları kalıcı saklanmaz (sayfadaki not). --}}
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>QR Tasarım Stüdyosu | Generator Studio</title>
{{-- ===== Stil bölümü: sayfa düzeni, sekmeli editör, önizleme ve logo kontrolleri ===== --}}
<style>
*{box-sizing:border-box}
body{margin:0;background:#f3f5fa;color:#182338;font:15px system-ui,sans-serif}
header{background:#fff;border-bottom:1px solid #e2e8f0;padding:20px 5%;display:flex;justify-content:space-between;align-items:center}
header strong{font-size:21px}
header a{color:#2563eb;text-decoration:none}
main{max-width:1200px;margin:35px auto;padding:0 20px}
h1{font-size:clamp(26px,4vw,38px);margin:0 0 8px}
.subtitle{color:#64748b;margin-bottom:26px}
.layout{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:22px;align-items:start}
.panel{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:24px;min-width:0}
h2{font-size:19px;margin:0 0 18px}
.group{padding:16px 0;border-bottom:1px solid #edf0f5}
.group:last-child{border:0}
.group-title{font-weight:750;margin-bottom:12px}
label{display:block;font-size:13px;font-weight:650;margin:10px 0 6px}
input,select,textarea{font:inherit}
input[type=text],input[type=number],input[type=file],select,textarea{width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:9px;background:#fff}
textarea{min-height:85px;resize:vertical}
input[type=color]{height:43px;width:100%;cursor:pointer;border:1px solid #ddd;border-radius:8px;background:#fff}
.two{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.three{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.preview-panel{position:sticky;top:20px;text-align:center}
.preview{min-height:340px;background:#f8fafc;border-radius:14px;display:flex;justify-content:center;align-items:center;padding:20px;overflow:hidden}
#qrCanvas{
    width:100%;
    max-width:300px;
    aspect-ratio:1/1;
    margin:auto;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
}
#qrCanvas svg,
#qrCanvas canvas{
    display:block;
    width:100%!important;
    max-width:100%!important;
    height:auto!important;
    aspect-ratio:1/1;
    object-fit:contain;
}
.actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:18px}
button{background:#2563eb;color:white;border:0;border-radius:10px;padding:13px;font-weight:750;cursor:pointer}
button.secondary{background:#152238}
button:disabled{opacity:.5;cursor:wait}
.note{font-size:12px;color:#64748b;line-height:1.6}
#status{font-size:13px;min-height:20px;color:#475569}
@media(max-width:850px){
.layout{grid-template-columns:1fr}
.preview-panel{position:static;grid-row:1}
main{margin-top:20px}
.panel{padding:18px}
}

/* Generator Studio - tabbed editor */
.studio-tabs {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}
.studio-tabs button {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #dbe2ea;
    padding: 10px 13px;
    border-radius: 10px;
    font-size: 13px;
}
.studio-tabs button.active {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}
.studio-tab-panel {
    display: none;
}
.studio-tab-panel.active {
    display: block;
}
#qrCanvas {
    width: 100%;
    max-width: 300px;
    aspect-ratio: 1 / 1;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
#qrCanvas svg,
#qrCanvas canvas {
    display: block;
    width: 100% !important;
    max-width: 100% !important;
    height: auto !important;
    aspect-ratio: 1 / 1;
    object-fit: contain;
}
@media (max-width: 480px) {
    .studio-tabs button { flex: 1; }
    #qrCanvas { max-width: 270px; }
}


/* QR preview sizing */
#qrCanvas {
    width: 300px;
    max-width: 100%;
    height: auto;
    aspect-ratio: 1 / 1;
    overflow: visible;
    margin: 0 auto;
}
#qrCanvas svg,
#qrCanvas canvas {
    width: 100% !important;
    height: 100% !important;
    max-width: 100% !important;
    display: block;
}


/* QR şablonları ve logo kontrolleri */
.template-section{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:12px;
    padding:14px;
    margin-bottom:16px;
}
.logo-controls{
    margin:16px 0;
    padding:14px;
    background:#f8fafc;
    border-radius:12px;
}
#logoSize{
    width:100%;
    accent-color:#2563eb;
    cursor:pointer;
}
#status{
    line-height:1.6;
}


.design-storage{
    background:#eff6ff;
    border:1px solid #bfdbfe;
    padding:16px;
    border-radius:14px;
    margin-bottom:20px;
}
.design-storage-actions{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
    margin-top:12px;
}
#loadDesign{
    width:100%;
    margin-top:10px;
}
#designStorageStatus{
    margin-top:12px;
}

</style>
</head>
<body>
{{-- Üst çubuk: Generator Studio adı ve yönetim paneline bağlantı. --}}
<header>
<strong>▦ Generator Studio</strong>
<a href="/admin">Yönetim</a>
</header>

<main>
<h1>QR Tasarım Stüdyosu</h1>
<p class="subtitle">QR kodunu tasarla, canlı önizle ve yüksek kalitede indir.</p>

<div class="layout">
<section class="panel">
<h2>Tasarım Ayarları</h2>

<div class="design-storage">
    <div class="group-title">Tasarım Yönetimi</div>

    <label for="designName">Tasarım adı</label>
    <input type="text" id="designName"
           maxlength="150" value="Yeni QR Tasarımı">

    <div class="design-storage-actions">
        <button type="button" id="saveDesign">Kaydet</button>
        <button type="button" id="newDesign"
                class="secondary">Yeni Tasarım</button>
    </div>

    <label for="savedDesigns">Kayıtlı tasarımlar</label>
    <select id="savedDesigns">
        <option value="">Tasarım seçin</option>
    </select>

    <button type="button" id="loadDesign"
            class="secondary">Seçilen Tasarımı Aç</button>

    <p class="note" id="designStorageStatus"
       role="status" aria-live="polite"></p>
    <p class="note">
        Kayıt işlemleri yönetici girişi gerektirir.
        Logo dosyaları henüz kalıcı olarak saklanmaz.
    </p>
</div>



{{-- Ayar sekmeleri: İçerik, Desen, Renk, Logo ve Çıktı. Her sekme kendi panelini (studio-tab-panel) açar. --}}
<nav id="studioTabs" class="studio-tabs" aria-label="QR ayarları">
  <button type="button" class="active" data-target="tab-0">İçerik</button>
  <button type="button" data-target="tab-1">Desen</button>
  <button type="button" data-target="tab-2">Renk</button>
  <button type="button" data-target="tab-3">Logo</button>
  <button type="button" data-target="tab-4">Çıktı</button>
</nav>
<div class="group studio-tab-panel" id="tab-0" aria-label="İçerik">
<div class="group-title">1. QR İçeriği</div>
<label for="content">Bağlantı veya metin</label>
<textarea id="content" maxlength="2000">https://nfc.carimatik.com.tr</textarea>
</div>

<div class="group studio-tab-panel" id="tab-1" aria-label="Desen">
<div class="group-title">2. QR Deseni</div>

<div class="template-section">
<label for="qrTemplates">Hazır QR şablonu</label>
<select id="qrTemplates">
<option value="classic">Klasik Siyah</option>
<option value="modern" selected>Modern Mavi</option>
<option value="corporate">Kurumsal Lacivert</option>
<option value="colorful">Renkli Gradient</option>
<option value="premium">Premium Altın</option>
<option value="google">Google Değerlendirme</option>
</select>
<p class="note">Şablon seçimi QR içeriğini veya yüklenen logoyu değiştirmez.</p>
</div>

<div class="two">
<div>
<label for="dots">Nokta şekli</label>
<select id="dots">
<option value="square">Kare</option>
<option value="rounded" selected>Yuvarlak</option>
<option value="dots">Noktalı</option>
<option value="classy">Classy</option>
<option value="classy-rounded">Classy Rounded</option>
<option value="extra-rounded">Extra Rounded</option>
</select>
</div>
<div>
<label for="corners">Köşe şekli</label>
<select id="corners">
<option value="square">Kare</option>
<option value="extra-rounded" selected>Yuvarlatılmış</option>
<option value="dot">Daire</option>
</select>
</div>
</div>
</div>

<div class="group studio-tab-panel" id="tab-2" aria-label="Renk">
<div class="group-title">3. Renkler</div>
<div class="three">
<div><label for="fg">QR rengi</label><input type="color" id="fg" value="#152238"></div>
<div><label for="bg">Arka plan</label><input type="color" id="bg" value="#ffffff"></div>
<div><label for="cornerColor">Köşe rengi</label><input type="color" id="cornerColor" value="#152238"></div>
</div>
<label for="gradient">Renk geçişi</label>
<select id="gradient">
<option value="none">Tek renk</option>
<option value="linear">Doğrusal gradient</option>
<option value="radial">Dairesel gradient</option>
</select>
<label for="gradientEnd">Gradient ikinci renk</label>
<input type="color" id="gradientEnd" value="#2563eb">
</div>

<div class="group studio-tab-panel" id="tab-3" aria-label="Logo">
<div class="group-title">4. Logo</div>
<label for="logo">Logo yükle (PNG veya JPEG)</label>
<input type="file" id="logo" accept="image/png,image/jpeg">
<p class="note">En fazla 1 MB. Logo QR kodun ortasına yerleştirilir. Logo kullanıldığında taranabilirliği mutlaka test edin.</p>

<div class="logo-controls">
<label for="logoSize">Logo büyüklüğü: <output id="logoSizeValue">22%</output></label>
<input type="range" id="logoSize" min="10" max="30" value="22" step="1">

<label for="logoMargin">Logo çevresi boşluğu</label>
<select id="logoMargin">
<option value="0">Boşluk yok</option>
<option value="3">Dar</option>
<option value="5" selected>Normal</option>
<option value="8">Geniş</option>
</select>
</div>
<button type="button" class="secondary" id="removeLogo">Logoyu kaldır</button>
</div>

<div class="group studio-tab-panel" id="tab-4" aria-label="Çıktı">
<div class="group-title">5. Çıktı Ayarları</div>
<div class="two">
<div>
<label for="size">QR boyutu</label>
<select id="size">
<option value="300">300 px</option>
<option value="500" selected>500 px</option>
<option value="800">800 px</option>
<option value="1000">1000 px</option>
<option value="2000">2000 px</option>
</select>
</div>
<div>
<label for="margin">Kenar boşluğu</label>
<select id="margin">
<option value="10">10 px</option>
<option value="20" selected>20 px</option>
<option value="40">40 px</option>
<option value="60">60 px</option>
</select>
</div>
</div>
</div>
</section>

<aside class="panel preview-panel">
<h2>Canlı Önizleme</h2>
<div class="preview"><div id="qrCanvas"></div></div>
<p id="status" role="status" aria-live="polite"></p>
<div class="actions">
<button type="button" id="png">PNG İndir</button>
<button type="button" id="svg" class="secondary">SVG İndir</button>
</div>
<p class="note">QR kodu basmadan önce Android ve iPhone cihazlarda tarayarak doğrulayın.</p>
</aside>
</div>
</main>

{{-- CSRF belirteci: logo yükleme ve tasarım kaydı istekleri bunu X-CSRF-TOKEN başlığıyla gönderir (aksi halde 419 döner). --}}
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- İstemci tarafı QR çizim kütüphanesi (yerel kopya, CDN yok). Yüklenmezse betik durum mesajı verip durur. --}}
<script src="/vendor/qr-styling/qr-code-styling.js"></script>
<script>
{{-- ===== Ana betik: önizleme, uyarılar, indirme, logo yükleme ve tasarım kaydı ===== --}}
(function(){
'use strict';

/*
   Bu sayfa QR'ı sunucuda değil tarayıcıda üretir (önizleme ve PNG/SVG indirme dahil);
   sunucuya yalnızca logo yükleme (/qr/logos) ve tasarım listesi/kaydı (/qr/designs) istekleri gider.
*/
const el = id => document.getElementById(id);
const status = el('status');
// logoData: önizleme için tarayıcıdaki logo (data URL veya kayıtlı logo adresi);
// savedLogoFile: sunucuya yüklenmiş logonun dosya adı (tasarımla birlikte saklanır);
// logoUploadPromise: süren yüklemeyi kaydetmeden önce beklemek için.
let logoData = null;
let savedLogoFile = null;
let logoUploadPromise = null;
let timer = null;

if (typeof QRCodeStyling === 'undefined') {
    status.textContent = 'QR tasarım kütüphanesi yüklenemedi.';
    return;
}

const qr = new QRCodeStyling({
    width:500,
    height:500,
    type:'svg',
    data:'https://nfc.carimatik.com.tr',
    margin:20,
    qrOptions:{errorCorrectionLevel:'H'}
});

qr.append(el('qrCanvas'));

/**
 * Form alanlarından qr-code-styling seçeneklerini üretir; içerik boşsa hata fırlatır
 * (refresh() bunu yakalayıp durum satırında gösterir).
 * Hata düzeltme seviyesi 'H' (en yüksek): ortaya logo konunca kapanan noktalar
 * yüzünden taranabilirliğin bozulmaması için gereklidir.
 */
function options(){
    const data=el('content').value.trim();
    if(!data) throw Error('QR içeriği boş olamaz.');

    /* Önizleme sabit boyutludur; seçilen gerçek boyut yalnızca indirmede uygulanır. */
    const size=300; // Sabit onizleme boyutu
    const color=el('fg').value;
    const second=el('gradientEnd').value;
    const gradientType=el('gradient').value;

    const dotsOptions={
        type:el('dots').value,
        color:color
    };

    if(gradientType!=='none'){
        dotsOptions.gradient={
            type:gradientType,
            rotation:0,
            colorStops:[
                {offset:0,color:color},
                {offset:1,color:second}
            ]
        };
    }

    return {
        width:size,
        height:size,
        type:'svg',
        data:data,
        margin:Number(el('margin').value),
        image:logoData||'',
        qrOptions:{errorCorrectionLevel:'H'},
        dotsOptions:dotsOptions,
        cornersSquareOptions:{
            type:el('corners').value,
            color:el('cornerColor').value
        },
        cornersDotOptions:{
            type:'dot',
            color:el('cornerColor').value
        },
        backgroundOptions:{
            color:el('bg').value
        },
        imageOptions:{
            hideBackgroundDots:true,
            imageSize:Number(el('logoSize').value)/100,
            margin:Number(el('logoMargin').value),
            saveAsBlob:true
        }
    };
}


/* Hazır şablonlar: anahtarlar form alanı kimlikleridir; applyPreset her birini doğrudan o alana yazar. */
const presets = {
    classic:{
        dots:'square', corners:'square',
        fg:'#000000', bg:'#ffffff',
        cornerColor:'#000000',
        gradient:'none', gradientEnd:'#000000'
    },
    modern:{
        dots:'rounded', corners:'extra-rounded',
        fg:'#152238', bg:'#ffffff',
        cornerColor:'#152238',
        gradient:'none', gradientEnd:'#2563eb'
    },
    corporate:{
        dots:'classy-rounded', corners:'square',
        fg:'#102a43', bg:'#ffffff',
        cornerColor:'#102a43',
        gradient:'none', gradientEnd:'#22577a'
    },
    colorful:{
        dots:'rounded', corners:'extra-rounded',
        fg:'#7c3aed', bg:'#ffffff',
        cornerColor:'#7c3aed',
        gradient:'linear', gradientEnd:'#2563eb'
    },
    premium:{
        dots:'classy', corners:'extra-rounded',
        fg:'#75530d', bg:'#ffffff',
        cornerColor:'#75530d',
        gradient:'none', gradientEnd:'#c49a37'
    },
    google:{
        dots:'rounded', corners:'extra-rounded',
        fg:'#1a73e8', bg:'#ffffff',
        cornerColor:'#1a73e8',
        gradient:'none', gradientEnd:'#34a853'
    }
};

/** Seçilen şablonun değerlerini form alanlarına uygular ve önizlemeyi yeniler. */
function applyPreset(name){
    const preset=presets[name];
    if(!preset)return;

    Object.entries(preset).forEach(([id,value])=>{
        el(id).value=value;
    });

    refresh();
}

el('qrTemplates').addEventListener('change',event=>{
    applyPreset(event.target.value);
});

function rgb(hex){
    return [
        parseInt(hex.slice(1,3),16)/255,
        parseInt(hex.slice(3,5),16)/255,
        parseInt(hex.slice(5,7),16)/255
    ];
}

/* WCAG göreli parlaklık ve kontrast oranı hesabı; düşük kontrast uyarısı için kullanılır. */
function luminance(hex){
    const values=rgb(hex).map(v=>
        v<=0.04045 ? v/12.92 : Math.pow((v+0.055)/1.055,2.4)
    );
    return values[0]*0.2126+
           values[1]*0.7152+
           values[2]*0.0722;
}

function contrast(a,b){
    const x=luminance(a), y=luminance(b);
    return (Math.max(x,y)+0.05)/(Math.min(x,y)+0.05);
}

/**
 * Taranabilirliği riske atan ayarlar için kullanıcıya gösterilecek uyarı listesi döndürür
 * (düşük kontrast, büyük logo, çok uzun içerik). Engellemez, yalnızca uyarır.
 */
function warnings(){
    const bg=el('bg').value;
    const colors=[
        el('fg').value,
        el('cornerColor').value
    ];

    if(el('gradient').value!=='none'){
        colors.push(el('gradientEnd').value);
    }

    const messages=[];

    if(Math.min(...colors.map(c=>contrast(c,bg)))<4.5){
        messages.push('Düşük renk kontrastı');
    }

    if(logoData && Number(el('logoSize').value)>25){
        messages.push('Büyük logo taramayı zorlaştırabilir');
    }

    if(el('content').value.length>800){
        messages.push('Uzun içerik QR yoğunluğunu artırır');
    }

    return messages;
}

el('logoSize').addEventListener('input',()=>{
    el('logoSizeValue').textContent=el('logoSize').value+'%';
});

/** Önizlemeyi günceller ve durum satırına uyarı ya da başarı mesajı yazar. */
function refresh(){
    try{
        qr.update(options());
        const issues=warnings();
        status.textContent=issues.length
            ? '⚠ ' + issues.join(' · ') + '. Gerçek cihazla tarayın.'
            : '✓ Önizleme güncellendi.';
    }catch(error){
        status.textContent=error.message;
    }
}

/* Her değişiklikte 180 ms bekleyip (debounce) yenile; yazarken gereksiz yeniden çizimi önler. Dosya alanı hariçtir, o ayrı işlenir. */
document.querySelectorAll('input:not([type=file]),select,textarea')
.forEach(input=>{
    input.addEventListener('input',()=>{
        clearTimeout(timer);
        timer=setTimeout(refresh,180);
    });
});


/*
 Logo seçimi: önce tarayıcıda doğrulanır (yalnız PNG/JPEG, 1 MB altı), önizleme için
 data URL olarak okunur; aynı anda /qr/logos'a (oturum açmış yönetici gerekir) yüklenir.
 Kalıcı kopya sunucuda özel diskte tutulur, herkese açık dizinde değildir; kayıtlı tasarım
 yalnızca dosya adına başvurur. Yükleme hatası önizlemeyi bozmaz, yalnızca kayıt için
 logo adı boş kalır.
 */
el('logo').addEventListener('change',event=>{
    const file=event.target.files[0];
    if(!file)return;

    if(!['image/png','image/jpeg'].includes(file.type) ||
       file.size>1024*1024){
        status.textContent='Yalnızca 1 MB altındaki PNG/JPEG kabul edilir.';
        event.target.value='';
        return;
    }

    savedLogoFile=null;

    const reader=new FileReader();

    reader.onload=()=>{
        logoData=reader.result;
        refresh();
    };

    reader.onerror=()=>{
        status.textContent='Logo önizlemesi oluşturulamadı.';
    };

    reader.readAsDataURL(file);

    const formData=new FormData();
    formData.append('logo',file);

    logoUploadPromise=(async()=>{
        const response=await fetch('/qr/logos',{
            method:'POST',
            credentials:'same-origin',
            headers:{
                'Accept':'application/json',
                'X-CSRF-TOKEN':document.querySelector(
                    'meta[name="csrf-token"]'
                ).content
            },
            body:formData
        });

        const result=await readResponse(response);

        savedLogoFile=result.filename;
        status.textContent='✓ Logo sunucuya yüklendi.';
    })();

    logoUploadPromise.catch(error=>{
        savedLogoFile=null;
        status.textContent='Logo yüklenemedi: '+error.message;
    });
});

el('removeLogo').addEventListener('click',()=>{
    logoData=null;
    savedLogoFile=null;
    el('logo').value='';
    refresh();
});

/**
 * Seçili boyutta PNG/SVG indirir. Önizleme 300 px olduğundan kenar boşlukları
 * seçilen boyuta oranlanır; böylece indirilen görsel önizlemeyle aynı görünür.
 * Ayrı bir QRCodeStyling örneği kullanılır ki ekrandaki önizleme değişmesin.
 * İşlem sürerken düğme kilitlenir (çift tıklamayı önler).
 */
async function download(extension){
    const button=el(extension);
    button.disabled=true;
    try{
        const exportOptions = options();
        const selectedSize = Number(el('size').value);
        const scale = selectedSize / 300;

        exportOptions.margin = Math.round(exportOptions.margin * scale);
        exportOptions.imageOptions = {
            ...exportOptions.imageOptions,
            margin: Math.round(exportOptions.imageOptions.margin * scale)
        };

        const exportQr = new QRCodeStyling({
            ...exportOptions,
            width: selectedSize,
            height: selectedSize
        });

        await exportQr.download({
            name:'generator-studio-qr',
            extension:extension
        });
        status.textContent=extension.toUpperCase()+' dosyası hazırlandı.';
    }catch(error){
        status.textContent='İndirme başarısız: '+error.message;
    }finally{
        button.disabled=false;
    }
}

el('png').addEventListener('click',()=>download('png'));
el('svg').addEventListener('click',()=>download('svg'));



/* ---- Kayıtlı tasarımlar: /qr/designs uç noktası (GET liste, POST kaydet/güncelle); yönetici denetimi sunucudadır ---- */
// Kaydedilebilir QR tasarımları
let currentDesignId = null;
let cachedDesigns = [];

const storageStatus = el('designStorageStatus');

/* Kaydedilen/yüklenen basit form alanları; logo alanları ayrıca ele alınır. */
const settingIds = [
    'content', 'dots', 'corners', 'fg', 'bg',
    'cornerColor', 'gradient', 'gradientEnd',
    'size', 'margin'
];

/** Kayıt için form durumunu düz nesne olarak toplar (logo için data URL değil, sunucudaki dosya adı). */
function collectSettings(){
    const settings = {};

    settingIds.forEach(id => {
        settings[id] = el(id).value;
    });

    settings.logoFile = savedLogoFile;
    settings.logoSize = Number(el('logoSize').value);
    settings.logoMargin = Number(el('logoMargin').value);

    return settings;
}

/**
 * Sunucu yanıtını işler: 401/403 yönetici girişi yok, 419 CSRF/oturum süresi dolmuş,
 * diğer hatalarda durum koduyla ileti fırlatır; başarılıysa JSON döndürür.
 */
async function readResponse(response){
    if(response.status === 401 || response.status === 403){
        throw Error('Lütfen önce yönetici paneline giriş yapın.');
    }

    if(response.status === 419){
        throw Error('Oturum süresi doldu. Sayfayı yenileyin.');
    }

    if(!response.ok){
        throw Error('Sunucu hatası: ' + response.status);
    }

    return response.json();
}

/** Kayıtlı tasarımları sunucudan çeker, listeyi doldurur ve mevcut seçimi korur. */
async function listDesigns(){
    const response = await fetch('/qr/designs', {
        credentials:'same-origin',
        headers:{'Accept':'application/json'}
    });

    const designs = await readResponse(response);

    if(!Array.isArray(designs)){
        throw Error('Beklenmeyen tasarım listesi.');
    }

    cachedDesigns = designs;

    const select = el('savedDesigns');
    const previous = String(currentDesignId || '');

    select.replaceChildren(new Option('Tasarım seçin', ''));

    designs.forEach(design => {
        select.add(new Option(design.name, String(design.id)));
    });

    select.value = previous;
}

/* Kaydet: devam eden logo yüklemesi bitmeden kayıt yapılmaz (aksi halde logo adı eksik kalırdı); id varsa mevcut kayıt güncellenir. */
el('saveDesign').addEventListener('click', async () => {
    const button = el('saveDesign');
    button.disabled = true;

    try {
        const name = el('designName').value.trim();

        if(!name){
            throw Error('Tasarım adı boş olamaz.');
        }

        if(logoUploadPromise){
            await logoUploadPromise;
            logoUploadPromise=null;
        }

        const payload = {
            name,
            settings:collectSettings()
        };

        if(currentDesignId){
            payload.id = currentDesignId;
        }

        const response = await fetch('/qr/designs', {
            method:'POST',
            credentials:'same-origin',
            headers:{
                'Content-Type':'application/json',
                'Accept':'application/json',
                'X-CSRF-TOKEN':document.querySelector(
                    'meta[name="csrf-token"]'
                ).content
            },
            body:JSON.stringify(payload)
        });

        const result = await readResponse(response);
        currentDesignId = result.id;

        await listDesigns();

        storageStatus.textContent =
            '✓ Tasarım kaydedildi: ' + result.name;
    } catch(error){
        storageStatus.textContent = error.message;
    } finally {
        button.disabled = false;
    }
});

/* Aç: kayıtlı ayarları forma yükler; kayıtlı logo sunucudan /qr/logos/{dosya} adresiyle gösterilir. */
el('loadDesign').addEventListener('click', () => {
    const id = Number(el('savedDesigns').value);

    const design = cachedDesigns.find(
        item => Number(item.id) === id
    );

    if(!design){
        storageStatus.textContent = 'Önce bir tasarım seçin.';
        return;
    }

    settingIds.forEach(key => {
        if(
            Object.prototype.hasOwnProperty.call(design.settings, key)
            && el(key)
        ){
            el(key).value = design.settings[key];
        }
    });

    currentDesignId = design.id;
    el('designName').value = design.name;

    // Henüz kaydedilmeyen logo başka tasarıma taşınmasın.
    logoData = null;
    savedLogoFile = design.settings.logoFile || null;
    logoUploadPromise = null;
    el('logo').value = '';

    if(design.settings.logoSize !== undefined){
        el('logoSize').value = design.settings.logoSize;
    }

    if(design.settings.logoMargin !== undefined){
        el('logoMargin').value = design.settings.logoMargin;
    }

    el('logoSizeValue').textContent =
        el('logoSize').value + '%';

    if(savedLogoFile){
        logoData='/qr/logos/'+encodeURIComponent(savedLogoFile);
    }

    refresh();
    storageStatus.textContent = '✓ Tasarım açıldı: ' + design.name;
});

el('newDesign').addEventListener('click', () => {
    currentDesignId = null;
    el('designName').value = 'Yeni QR Tasarımı';
    el('savedDesigns').value = '';

    storageStatus.textContent =
        'Yeni kayıt hazır. Mevcut QR ayarları korunuyor.';
});

/* Açılışta liste yüklenir; yönetici değilse hata yalnızca depolama durum satırında görünür, QR üretimi etkilenmez. */
listDesigns().catch(error => {
    storageStatus.textContent = error.message;
});


/* ---- Ayar sekmeleri (yalnızca görünüm; veri akışını etkilemez) ---- */
// QR ayar sekmeleri
const tabButtons = document.querySelectorAll(
    '#studioTabs button[data-target]'
);
const tabPanels = document.querySelectorAll(
    '.studio-tab-panel'
);

/** Verilen kimlikli sekme panelini gösterir, diğerlerini gizler. */
function activateStudioTab(id) {
    tabPanels.forEach(panel => {
        const active = panel.id === id;
        panel.classList.toggle('active', active);
        panel.hidden = !active;
    });
    tabButtons.forEach(button => {
        const active = button.dataset.target === id;
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', String(active));
    });
}

tabButtons.forEach(button => {
    button.addEventListener('click', () => {
        activateStudioTab(button.dataset.target);
    });
});

activateStudioTab('tab-0');

refresh();
})();
</script>
</body>
<script>
// GENERATOR_STUDIO_QR_INTEGRATION_2026
// Not: yukarıdaki GENERATOR_STUDIO_QR_INTEGRATION_2026 işaret satırı harici bir araç tarafından kullanılabilir; silmeyin.
// Amaç: ?content= parametresiyle gelen bağlantıyı (ör. Google yorum sayfasından) içerik alanına aktarmak.
(() => {
    const params = new URLSearchParams(window.location.search);
    const incoming = params.get('content');

    if (!incoming) return;

    if (incoming.length > 2000) return;

    // Yalnızca HTTPS bağlantılarını otomatik aktar.
    try {
        const url = new URL(incoming);
        if (url.protocol !== 'https:') return;
    } catch {
        return;
    }

    const input = document.getElementById('content');
    if (!input) return;

    input.value = incoming;

    // QR önizlemesini mevcut olay mekanizmasıyla güncelle.
    input.dispatchEvent(new Event('input', { bubbles: true }));
})();
</script>

</html>
