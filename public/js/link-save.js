/* Generator Studio - Ortak bağlantı kaydetme
 *
 * Instagram takip sayfası (#finalUrl var) ve Google yorum sayfasına
 * (#linkPanel / #reviewLink var) "Bağlantıyı Kaydet" düğmesi ekler.
 * İstek: POST /baglantilar/kaydet (JSON: name, type, target_url);
 *   type: 'instagram_follow' veya 'google_review'.
 * Yetki: uç noktası oturum (auth) ister; oturum yoksa sunucu hata döner ve
 *   mesaj kullanıcıya gösterilir.
 * CSRF: sayfanın <head> bölümündeki meta[name="csrf-token"] değeri okunur ve
 *   X-CSRF-TOKEN başlığıyla gönderilir; meta yoksa istek atılmaz.
 * Gerekli öğe bulunmayan sayfada hiçbir şey yapmaz.
 */
(() => {
    // Hangi sayfadayız? Instagram sayfasında #finalUrl bulunur
    const instagram = !!document.getElementById('finalUrl');

    // Düğmenin ekleneceği panel, bağlantının okunacağı öğe ve varsayılan ad kaynağı

    const panel = document.getElementById(
        instagram ? 'result' : 'linkPanel'
    );

    const link = document.getElementById(
        instagram ? 'finalUrl' : 'reviewLink'
    );

    const title = document.getElementById(
        instagram ? 'username' : 'selectedBusiness'
    );

    // Beklenen öğeler yoksa (ilgisiz sayfa) hiçbir şey ekleme
    if (!panel || !link || !title) return;

    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = '💾 Bağlantıyı Kaydet';

    button.style.cssText =
        'display:inline-block;margin:12px 8px 0 0;' +
        'padding:13px 20px;border:0;border-radius:10px;' +
        'background:#16a34a;color:white;cursor:pointer;' +
        'font-weight:600';

    // Durum/hata mesajı alanı (ekran okuyucular için aria-live)
    const status = document.createElement('p');
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');

    // Tıklama: bağlantıyı ve adı al, doğrula, CSRF ile sunucuya kaydet
    button.addEventListener('click', async () => {
        // Instagram sayfasında bağlantı input değeridir, Google sayfasında metin
        const url = instagram
            ? link.value.trim()
            : link.textContent.trim();

        if (!url) {
            status.textContent = 'Önce bağlantıyı hazırlayın.';
            return;
        }

        // Varsayılan ad: başlıktaki " — " öncesi kısım
        const defaultName = title.textContent
            .split(' — ')[0].trim();

        const entered = prompt('Bağlantı adı:', defaultName);

        // Kullanıcı prompt'u iptal ettiyse vazgeç
        if (entered === null) return;

        const name = entered.trim();

        if (!name || name.length > 150) {
            status.textContent = 'Geçerli bir ad girin.';
            return;
        }

        // Laravel CSRF token'ı meta etiketinden okunur
        const csrf = document.querySelector(
            'meta[name="csrf-token"]'
        )?.content;

        if (!csrf) {
            status.textContent = 'Güvenlik doğrulaması bulunamadı.';
            return;
        }

        // Çift tıklamayı önlemek için istek süresince düğmeyi kilitle
        button.disabled = true;
        status.textContent = 'Kaydediliyor...';

        try {
            const response = await fetch('/baglantilar/kaydet', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    name,
                    type: instagram
                        ? 'instagram_follow'
                        : 'google_review',
                    target_url: url
                })
            });

            // Sunucu JSON döner: başarıda message, doğrulama hatasında errors
            const data = await response.json();

            // Hata iletilerini (varsa alan hataları) tek metne birleştir
            if (!response.ok) {
                const error = data.errors
                    ? Object.values(data.errors).flat().join(' ')
                    : data.message;

                throw new Error(error || 'Kayıt başarısız.');
            }

            status.textContent = data.message;
            status.style.color = '#15803d';

        } catch (error) {
            // Ağ veya sunucu hatası: kullanıcıya göster
            status.textContent = 'Hata: ' + error.message;
            status.style.color = '#dc2626';
        } finally {
            button.disabled = false;
        }
    });

    // Düğme ve durum alanını panele ekle
    panel.appendChild(button);
    panel.appendChild(status);
})();
