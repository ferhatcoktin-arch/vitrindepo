# iqossepeti.com — metinleri baştan yazma kuralları

**Amaç:** iqossepeti.com, iqosvitrin.com.tr'nin kopyası olarak kuruldu. Google iki sitede aynı metni görünce birini bastırıyor. Sepeti'nin **bütün metinleri** aynı bilgileri anlatan ama **kelimesi, cümlesi ve yapısı tamamen farklı** yeni metinler olacak. Girdi dosyalarındaki eski metinler (şu an Sepeti'de, Vitrin'dekiyle neredeyse aynı) sadece **bilgi kaynağıdır**; cümlelerini kopyalama, "eş anlamlıyla değiştirme" (spin) de yapma. Konuyu anladıktan sonra kendi cümlelerinle yaz.

## Dil ve ses
- Türkçe, doğal, akıcı; imla ve Türkçe karakterler doğru (ı, ğ, ş, İ…). "siz" hitabı.
- Marka adı: **IQOS Sepeti**. "Vitrin", "IQOS Vitrin" asla geçmeyecek.
- Sepeti'nin sesi: pratik, sade, "mağazadaki tecrübeli satıcı" gibi. Kısa paragraflar, madde işaretleri. Abartı yok ("muhteşem", "eşsiz", "en iyi" gibi boş övgüler kullanma).

## Kesin yasaklar (kontrol aracı hata verir)
- Sağlık iddiası: "daha az zararlı", "zararsız", "sağlıklı", "%95 daha az" vb. YOK. Hiçbir tütün ürünü risksiz değildir.
- İade/değişim hakkından hiç bahsetme (mağaza iade kabul etmiyor; konuyu açma).
- Kaynakta olmayan teknik bilgi uydurma (batarya mAh, nikotin miligramı, sıcaklık derecesi, ülke/üretim tarihi vb.). Kaynakta varsa kullan.
- Fiyat, stok adedi, indirim oranı yazma (değişiyor).
- Başka site, rakip, pazar yeri adı yazma.
- Emoji kullanma.

## Kullanılabilecek sabit bilgiler
- TEREA çubukları yalnızca IQOS ILUMA serisi (ILUMA / ILUMA i ONE, i DUO, i PRIME) ile çalışır; eski bıçaklı IQOS modelleri ve HEETS ile çalışmaz.
- 1 paket TEREA 20 çubuk; 1 karton 10 paket (200 çubuk).
- Teslimat: İstanbul içi aynı gün moto kurye; **kapıda ödeme sadece İstanbul içi**. İstanbul dışı siparişler havale/EFT ile ödendikten sonra kargoya verilir.
- 4 karton veya cihaz + TEREA siparişlerinde teslimat ücretsiz (sadece set/kampanya/karton ürünlerinde bahsedilebilir).
- İletişim: WhatsApp **+90 554 892 04 04** ve **+90 501 579 45 61** (numaralar birebir bu yazımla).
- Nikotin uyarısı: 18 yaşından küçüklere satılmaz / uygun değildir; nikotin bağımlılık yapar.

## Ürünler (`urun-XX.json`)
Her ürün için şu alanları yaz:

| alan | kural |
|---|---|
| `id` | girdi ile aynı |
| `name` | Ürünü tanıtan yeni ad. Aynı kimlik bilgileri kalsın (model, renk / seri adı, menşe, aroma), sıralama ve kelimeler farklı olsun. 12–72 karakter. Örnek kalıplar: TEREA → `TEREA Purple Wave Kıbrıs – Mentollü Orman Meyvesi`; Vozol → `Vozol Star 40000 Cool Mint Nane Aromalı`; cihaz → `IQOS ILUMA i PRIME Yeşil Cihaz`; set → `IQOS ILUMA i PRIME Lacivert + Karışık TEREA Karton Seti`. Aynı gruptaki ürünlerde aynı kalıbı kullan. |
| `short_description` | HTML. 1 cümlelik özet `<p>` + 3–4 maddelik `<ul>` (ör. Aroma / Menşe / Uyumlu cihaz / Paket; Vozol'da Çekim kapasitesi / Şarj) + son satır `<p><small>Nikotin içerir; 18 yaş altına satılmaz.</small></p>`. Yıldızlı puanlama kullanma. |
| `description` | HTML, düz metin olarak 900–4500 karakter (eski metinle benzer uzunluk). Aşağıdaki **Sepeti yapısı**. |
| `rank_math_title` | 30–62 karakter, anahtar kelime başta, sonu ` \| IQOS Sepeti`. |
| `rank_math_description` | 110–160 karakter, tek cümle ya da iki kısa cümle, satın almaya davet eden, ürüne özgü. |
| `rank_math_focus_keyword` | 2–4 kelime, küçük harf, insanların aradığı şekil (ör. `purple wave terea kıbrıs`, `vozol cool mint`, `iqos iluma i prime yeşil`). |

**Sepeti ürün açıklaması yapısı** (Vitrin tablo kullanıyor; Sepeti **tablo kullanmaz**, liste ve soru-cevap kullanır). Başlık metinlerini ürüne göre çeşitlendir, her üründe birebir aynı başlıkları tekrarlama:
1. `<p>` 2–3 cümlelik giriş: ürünü farklı bir açıdan anlat (hangi anda, kimin elinde, neyle öne çıkıyor).
2. `<h2>` "Kısa Künye" benzeri başlık + `<ul>` 4–6 madde (menşe, seri, aroma ailesi, kapsül var/yok, uyumlu cihaz, paket / cihazda öne çıkan özellikler, Vozol'da çekim/şarj/ekran/hava akışı — kaynakta ne varsa).
3. `<h2>` İçim / kullanım deneyimi: TEREA'da açılış–orta–bitiş; cihazda günlük kullanım; Vozol'da tat ve çekiş; sette kutudan ne çıktığı.
4. `<h2>` Kimler sever / kimlere uygun: `<ul>` 3–4 madde, dürüst ("tatlı sevmeyenler için fazla yumuşak olabilir" gibi bir çekince de olabilir).
5. `<h2>` Sık sorulanlar: 2 soru `<h3>` + cevap `<p>`; cevaplar sadece bu kurallardaki ve kaynaktaki bilgilerle.
6. Kapanış (her üründe aynı iki paragraf olmasın, cümleyi değiştir):
   `<h3>` Sipariş/teslimat başlığı + `<p>` İstanbul içi aynı gün moto kurye ve kapıda ödeme, il dışı havale/EFT sonrası kargo; WhatsApp'tan stok sorulabilir.
   `<p><strong>WhatsApp:</strong> +90 554 892 04 04<br><strong>WhatsApp:</strong> +90 501 579 45 61</p>`
   `<p><strong>…18 yaş… nikotin … bağımlılık yapar.</strong></p>`
- Kaynak açıklamada başka bir sayfaya `<a href>` varsa aynı adresi koruyabilirsin.
- İzinli etiketler: p, h2, h3, ul, ol, li, strong, em, small, br, a.

## Kategoriler (`kategori.json`)
Her kategori için `{id, name, description, rank_math_title, rank_math_description}`:
- `name`: kısa etiket; menüde görünüyor. Anlamı aynı kalsın, gerekirse hafifçe farklı ifade et (ör. "Dubai TEREA Çeşitleri" → "TEREA Dubai Serisi"). Cihaz/set kategori adlarında model adı aynen kalsın.
- `description`: ürünü olan kategorilerde düz metin olarak 1500–3000 karakter: giriş, serinin karakteri, alt çeşitlerin gruplandığı bir `<ul>`, seçim önerisi, 2–3 soruluk SSS (`<h3>` + `<p>`), kısa teslimat notu. Ürünü olmayan (count 0) kategorilerde 300–600 karakter kısa tanıtım yeter. "Uncategorized" (id 18) atlanabilir.
- `rank_math_title` ≤ 60 karakter, sonu ` | IQOS Sepeti`; `rank_math_description` 110–160 karakter.

## Tam yazılacak yazı ve sayfalar (`tam-yazi-sayfa.json`)
Her biri için `{id, title, content, excerpt (sadece yazılarda), rank_math_title, rank_math_description, rank_math_focus_keyword}`:
- Aynı konu, aynı bilgi kapsamı; başlık yapısı, sıralama, örnekler, cümleler yeni. Uzunluk eskisinin %80–130'u.
- `content` sade HTML (p, h2, h3, ul, ol, li, strong, a). Kaynaktaki tüm `href` adreslerini, telefonları, e-posta ve adresleri birebir koru.
- Gizlilik politikası: hukuki kapsamı (toplanan veriler, amaçlar, paylaşım, KVKK hakları, iletişim) eksiksiz koru, sadece anlatımı yeniden kur.
- Şişli / Kadıköy teslimat sayfalarında semt bilgilerini koru.

## Parça parça yazılacaklar (`parca-*.json`)
Bu sayfalar tasarımlı (Flatsome UX Builder / özel HTML); yapı bozulmasın diye sadece metin parçalarını yeniden yazıyorsun. Girdi `parcalar` listesindeki her `{id, tur, metin}` için yeni metin ver.
- Çıktı: `{"title": "...", "excerpt": "... (sadece yazılarda, varsa)", "rank_math_title": "...", "rank_math_description": "...", "rank_math_focus_keyword": "...", "parcalar": {"0": "yeni metin", "1": "...", ...}}`
- Parçalar **düz metin**: HTML etiketi veya [kısa kod] yazma. `&`, tırnak vb. normal karakter olarak yaz.
- Her parçanın görevi aynı kalsın: buton/etiket kısa kalsın ("Tümünü Gör" → "Hepsini İncele" olabilir), başlık başlık gibi, paragraf paragraf gibi. Uzunluk eskisinin %70–140'ı (tasarım taşmasın).
- Model adı / ürün adı / telefon / adres / e-posta / rakam içeren kısa parçalar (ör. "IQOS ILUMA i ONE", "+90 554 892 04 04") olduğu gibi kalabilir — bunları çıktıya yazmasan da olur. 60 karakterden uzun **her** parça yeniden yazılmalı.
- `oznitelik:alt` parçaları görsel açıklamasıdır; görseli doğru anlatsın, kelimeleri değiştir.
- SSS soruları aynı soruyu farklı cümleyle sorabilir; cevaplar yeni.
- Sayfa başlığı (`title`) yeni olsun ama konu aynı. Ana sayfa (212) için `rank_math_title` şu ikisinden de farklı olmalı: "IQOS TEREA ILUMA Elektronik Sigara TÜRKİYE TESLİM", "IQOS Terea Satın Al | IQOS Iluma ve IQOS Ürünleri Türkiye".

## Kontrol
Yazdığın her dosyayı kaydettikten sonra çalıştır:
```
python3 /home/claude/vitrindepo/tools/sepeti_metin.py kontrol /home/claude/vitrindepo/yeniden/sepeti/cikti/<dosya>.json
```
- **HATA** satırı kalmayana kadar düzelt (benzerlik yüksekse ilgili metni daha özgün yeniden yaz; kontrol 6'lı kelime dizilerini eski metinle karşılaştırır).
- UYARI'lara bak; makul olanları düzelt.
- Dosya geçerli JSON olmalı (UTF-8, `ensure_ascii=False` ile yazmak iyi olur). Uzun işlerde Python ile yazmak kolaydır.
