# vitrindepo

iqosvitrin.com.tr (WordPress + WooCommerce) için kategori düzeni aracı.

## Yeni kategori yapısı

```
IQOS Cihazlar
├── ILUMA i Serisi
└── ILUMA Serisi
TEREA
├── Tütün Aromalı
├── Mentollü
└── Kapsüllü / Meyveli
Setler & Kampanyalar
Aksesuarlar
├── Kılıf & Kapak
└── Şarj & Kablo
```

Filtreler için ürün özellikleri: **Aroma**, **Yoğunluk**, **Paket** (paket / karton), **Renk**.

Yapı ve "hangi ürün hangi kategoriye" kuralları `kategori-yapisi.json` dosyasında.

## Claude'un siteyi düzenleyebilmesi için (bir kere yapılır)

1. **WooCommerce → Ayarlar → Gelişmiş → REST API → Anahtar ekle**
   - Açıklama: `Claude`
   - İzinler: **Okuma/Yazma**
   - "API anahtarı oluştur"a bas; çıkan *Tüketici anahtarı* ve *Tüketici gizli anahtarı*nı kopyala.
   - **Bu anahtarları sohbete yapıştırma.**
2. Claude'da oturum başlığındaki bulut ortamı menüsü → **Edit**:
   - **Network access**: `iqosvitrin.com.tr` ekle.
   - Ortam değişkenleri: `WC_CONSUMER_KEY=<tüketici anahtarı>` ve `WC_CONSUMER_SECRET=<gizli anahtar>`.
3. **Yeni bir oturum** aç (ayarlar yeni oturumda geçerli olur) ve "kategori düzenini uygula" yaz.

İş bitince anahtarı aynı REST API ekranından iptal edebilirsin.

## Araç nasıl çalışır

`tools/woo_kategori.py` siteye yazan her adımda önce ne yapacağını gösterir, sadece `--yes` ile uygular.

| Adım | Komut | Siteyi değiştirir mi? |
|---|---|---|
| Yedek al | `python3 tools/woo_kategori.py backup` | Hayır |
| Plan çıkar | `python3 tools/woo_kategori.py plan` → `plan.csv` | Hayır |
| Kategorileri oluştur | `python3 tools/woo_kategori.py setup --yes` | Evet (sadece ekler) |
| Ürünleri taşı | `python3 tools/woo_kategori.py apply plan.csv --yes` | Evet (önce otomatik yedek alır) |
| Geri al | `python3 tools/woo_kategori.py restore yedek/<tarih> --yes` | Evet |

- `plan.csv`de kategorisi `?` olan ürünlere dokunulmaz; bunlar elle kategori seçilince taşınır.
- Hiçbir kategori ya da ürün silinmez. Eski kategoriler boş kalır, menüden kaldırılabilir.

## API ile yapılmayan, panelden yapılacaklar

- Menü: **Görünüm → Menüler** (ya da Site Düzenleyici) — yeni kategorilerle 5 başlıklı menü.
- Mağaza sayfasına filtre blokları (Aroma, Yoğunluk, Paket, Fiyat).
- Yazarken öneri gösteren arama (örn. FiboSearch).
- Aynı ürünün renk / paket-karton kopyalarını tek "değişken ürün"de birleştirme — ürün listesi görüldükten sonra ayrıca planlanacak.
- 18+ yaş doğrulama penceresi.

## Vitrin Arayüz eklentisi (`vitrin-arayuz/`, 1.1.x)

Flatsome üzerinde çalışan hafif bir eklenti, bağımlılığı yok.

- **Büyük arama kutusu:** Header'ın altında durur. Yazdıkça WooCommerce Store API'den canlı öneri getirir.
- **Ana sayfa kategori rafları:** Eski "Çok Satanlar" tasarımıyla 14 kategori rafı ve en altta Çok Satanlar rafı var (Vozol'un altında). Raflar yavaşça kendiliğinden kayar ve sonsuz döngüyle başa sarar. Masaüstünde fare üstüne gelince durur, sürükleyerek veya oklarla kaydırılır. Mobilde parmakla kaydırılır, bırakınca 3 saniye sonra kaymaya devam eder. Ürünler raf ekrana yaklaşınca API'den yüklenir, bu yüzden ana sayfa HTML'i büyümez. Gündüz/gece renkleri mevcut "Saatli Mavi Stüdyo" snippet'inden gelir.
- **Eski blokların durumu:** Kategori kataloğu (`#ivk-katalog`) ve eski Çok Satanlar (`#iqv-best`) CSS ile gizlendi. WPCode snippet'leri silinmedi. Eklenti kapatılınca ikisi de geri gelir.
- **Rafların listesi ve sırası:** `va_ayar( 'raflar' )` içinde tutuluyor.
- **Önizleme modu:** Etkinken arayüzü sadece yöneticiler görür. Canlıya almak için **Ayarlar → Okuma → "Vitrin Arayüz canlı"** seçeneğini işaretle.
- **Güncelleme:** `VA_VER` değerini artır ve `assets/va-<sürüm>.css` / `.js` dosyalarını yeniden adlandır. Sonra zip'i yükle ve **Purge SG Cache** yap. SiteGround küçültülmüş dosyayı tutamaç adıyla sakladığı için sürüm numarası artırılmazsa ziyaretçiye eski dosya gider.

### 26 Eyl 2026: yayına alındı
- `va_canli` açık, tüm ziyaretçiler görüyor. REST'ten kapatmak için `POST /wp-json/wp/v2/settings {"va_canli": false}` gönder ya da Ayarlar → Okuma'dan kutuyu kaldır.
- Raflar sabit gökyüzü mavisi temada. Masaüstünde sayfanın tam genişliğini kaplıyor.
- Gece/gündüz teması kapatıldı: WPCode snippet 3165'te `data-iqv` artık hep `'day'`. Eski ifade (`n>=420&&n<=1140?'day':'night'`) kodun içinde yorum satırı olarak duruyor. Geri almak için o ifadeyi yerine koymak yeterli.
- 1.3.0 (sadece mobil ana sayfa): Arama kutusunun altına "Kapıda Ödeme · Aynı Gün Teslim · %100 Orijinal" güven şeridi eklendi. Ürün rafları en üste (`#vr-raflar-yer`) taşındı. Büyük tanıtım kutusu silinmedi, rafların altına iner. Masaüstü değişmedi.

### 28 Eyl 2026: 1.5.1 (iqosvitrin.com.tr + iqossepeti.com)
- **Kartlarda Sepete Ekle:** Basit ürünler ana sayfadan sayfa yenilenmeden sepete eklenir (`?wc-ajax=add_to_cart`, ardından `added_to_cart` olayı, yani Flatsome sepet paneli açılır). Seçenekli ürünlerde "Seçenekleri Gör", tükenmişte gri "Tükendi" gösterilir.
- **Stok rozetleri:** Ürün sayfasındaki şeridin aynısı (Hızlı Teslimat / Tükeniyor! / En Çok Ziyaret Edilen / En Çok Değerlendirilen) fotoğrafın altına basılır. Kaynak, WPCode snippet 2893'teki `iqv_sales_badges` AJAX ucu. Snippet kapatılırsa rozetler sessizce kaybolur.
- **Mobil sepet paneli:** Panel `100dvh` ile ölçülüyor, böylece iPhone'da "Kapıda ödeme ile siparişi tamamla" butonu araç çubuğunun altında kalmıyor. Panel açıkken ürün sayfasındaki sabit Sepete Ekle çubuğu, WhatsApp butonu ve alt menü gizleniyor.
- **Yükleme:** Zip dosyası GitHub raw'dan tarayıcı panelinde indirilip `update.php?action=upload-plugin` adresine gönderildi, ardından `overwrite=update-plugin` ile güncellendi. İki sitede de aynı zip kullanıldı.

### 28 Eyl 2026: yeni IQOS Vitrin logosu (IV monogram + IQOS VİTRİN)
- Kaynak görseller `gorseller/` klasöründe. Medya ID'leri: ikon 3251, header webp 3252, paylaşım jpg 3253, tam logo png 3254.
- **Site ikonu:** 3251. WordPress `site_icon` ayarı; Google sonuçlarında, sekmede ve WhatsApp'ta görünen küçük ikon.
- **Paylaşım görseli:** 3253 (1200×630). Ana sayfada (sayfa 212) Rank Math facebook görseli ve öne çıkan görsel olarak ayarlı; ayrıca Rank Math > Başlıklar & Meta > Genel > OpenGraph Küçük Resmi (varsayılan).
- **Kurum logosu:** 3254. Rank Math > Local SEO > Logo alanında; Organization schema için.
- **Header logosu:** Yazı kilidi (`ivg-kilit`) yerine `<img class="ivg-logo-img">` (3252) basılıyor. Bunu iki WPCode snippet'i yapıyor:
  - **3237** (sunucu tarafı, `$kilit` satırı): eski hali `yedek/snippet/3237-acilis-titremesi-eski.php` dosyasında.
  - **2953** (tarayıcı tarafı `var kilit` + CSS): `#logo img.ivg-logo-img` kuralı eklendi. Yükseklik masaüstünde 58px, mobilde 50px. Mobilde logo header'ın ortasına alınıyor (`position:absolute; left:50%`). JS'deki atlama kontrolü `.ivg-kilit` yerine `.ivg-resim` oldu. Geri almak için bu satırlar silinip eski `kilit` metni konur.
- **Güncelleme (aynı gün):** Logo halkalı IV amblemli sürümle değişti. Dosyalar `-v3` adlarıyla yeniden yüklendi (WhatsApp/SG önbelleği için): ikon 3255, header webp 3256, paylaşım jpg 3257, tam logo png 3258. Site ikonu, sayfa 212 OG + öne çıkan görsel, Rank Math varsayılan OG, Local SEO logosu ve snippet 3237/2953'teki header adresi yenilerine çevrildi. Eski medya (3250–3254) silinmedi.

### 28 Eyl 2026: yeni IQOS Sepeti logosu (bordo zemin, altın sepet ikonu)
- Dosyalar `gorseller/iqos-sepeti-*-v3.*`. iqossepeti.com medya ID'leri: ikon 3282, header webp 3283 (533×140, koyu bordo, köşeleri yuvarlatılmış), paylaşım jpg 3284, tam logo png 3285.
- `site_icon` 3282'ye çevrildi. Sayfa 212'nin OG ve öne çıkan görseli ile Rank Math varsayılan OG görseli 3284 yapıldı; Local SEO logosu 3285.
- **Header:** Snippet 3239 ("Bordo Şampanya Renkler ve Logo") `$logo` img'sini 3283'e çeviriyor. Genişlik masaüstünde 240px, mobilde 175px; mobilde logo ortalanıyor. Aynı genişlikler snippet 3279'daki (Kritik CSS) kurallarda da güncellendi. Eski logo dosyası `iqos-sepeti-logo.webp` (1400×583) silinmedi.
- **Aynı gün, 2. tur (kullanıcı beyaz header'ı "cırtlak" buldu):** Header logosu v4 (medya 3286, 572×150): kenarları yumuşak geçişle şeffafa eriyor, kutu görünmüyor. Snippet 3239'a `ivs-header-bordo` bloğu eklendi: header zemini bordo radyal geçiş, menü/kullanıcı/sepet ikonları şampanya (#f1dcb4). Logo genişliği masaüstünde 290px, 849px altında 225px; 480px altında 200px ve `left:46%` (kullanıcı ikonuna değmesin diye).

### 28 Eyl 2026: iqosvitrin header zemini gökyüzü mavisi
- Snippet 2953'e `ivg-header-gok` bloğu eklendi: `.header-bg-color` için #bfe7fc→#a6dcf8 geçişi. Logo yüksekliği masaüstünde 64px, 849px altında 54px; 480px altında 50px ve `left:46%` (kullanıcı ikonuna değmesin diye).

## Google başlık/açıklama (30 Eyl 2026)
- Ana sayfa (sayfa 212) Rank Math başlığı: `IQOS Terea Satın Al | IQOS Iluma ve IQOS Ürünleri Türkiye`
- Açıklama: `Orijinal IQOS Terea ve IQOS Iluma ürünleri. Güncel modeller, Terea çeşitleri, hızlı teslimat ve güvenli ödeme seçenekleriyle hemen sipariş verin.`
- Aynı değerler Facebook/WhatsApp ve Twitter başlık/açıklamasına da yazıldı; SG önbelleği temizlendi; Search Console'dan dizine ekleme istendi.
- Önceki değerler: başlık `IQOS TEREA ILUMA Elektronik Sigara TÜRKİYE TESLİM`, açıklama `Orijinal IQOS ILUMA cihazları ve tüm TEREA çeşitleri güncel stokla IQOS Vitrin'de. İstanbul'da gün içi hızlı teslimat, kapıda ödeme imkanı.`

## PageSpeed + Search Console turu (30 Eyl 2026)
Ölçüm (ana sayfa, PageSpeed): mobil önce Performans 63 / Erişilebilirlik 87 / En İyi Uyg. 100 / SEO 100 → sonra 68 / 100 / 100 / 100 (LCP 13,3 sn → 4,5–7,9 sn, CLS 0). Masaüstü 83–94 / 100 / 100 / 100. Laboratuvar puanı her ölçümde ±10 oynuyor.
- vitrin-arayuz 1.5.3 → 1.5.7:
  - İlk raf (i ONE) sunucuda HTML olarak basılıyor (`va_ilk_raf_html`, veri `va_ilk_raf` transient 10 dk, ürün kaydında silinir); ilk iki görsel `<head>`'de preload + fetchpriority=high. JS bu rafı yeniden çizmez, sadece döngü + rozet ekler.
  - Mobilde `#vr-raflar-yer` 170vh yer ayırır (diğer raflar eklenince alttaki tanıtım kutusu kaymasın, CLS).
  - Erişilebilirlik: `.vr-ray` role=region, rozet/buton/küçük yazı kontrastları koyulaştırıldı, `span>a[href^=tel:]` dokunma alanı, ana sayfa içerik listesindeki bağlantılar altı çizili, blog kartı başlıkları h5→h4 (`do_shortcode_tag` blog_posts), ödeme bilgisi penceresi butonu #0369a1, masaüstü mavi menü yazıları tam beyaz, başlık aramasındaki kategori select'ine aria-label.
  - Ürün schema (sadece iqosvitrin.com.tr): Rank Math Product'a brand (IQOS/TEREA/Vozol), offers.shippingDetails (TR, 300 ₺, 0–1 gün hazırlık, 0–3 gün kargo), hasMerchantReturnPolicy = MerchantReturnNotPermitted (kullanıcı kararı: iade yok), validFrom, açıklama 1–5000 karakter.
- Görseller: 5 blog kapağı PNG → WebP (3205–3209 → 3266–3270, ~1,4 MB → ~80 KB); header logosu 344px sürümü (3273) + srcset (snippet 3237); TEREA/marka şeridi görselleri 700px sürümler (3276–3281, snippet 2946'da slug'lar `terea-slayt-N-700`). Eski medya silinmedi.
- WPCode snippet 1203 (elle eklenmiş ikinci GA4 etiketi G-W4Y3G9RNRW) PASİF yapıldı: Site Kit'in GT-PHCGQVVZ etiketi aynı mülkü zaten yüklüyordu, sayfa görüntülemeleri çift sayılıyordu. Silinmedi.
- Search Console'da doğrulama başlatıldı: Satıcı girişleri (image, hasMerchantReturnPolicy, shippingDetails, validFrom, gtin/marka, description), Ürün snippet'leri (offers/review/aggregateRating), Sayfa dizine ekleme (5xx). image/description/offers hataları 10 Eyl'de silinen puro/sigara sayfalarından geliyor; o sayfalar 404 olduğu için kendiliğinden düşecek.
- Kalan (bilerek yapılmadı): aggregateRating/review uyarısı gerçek müşteri yorumu gerektirir (sahte yorum yok); Merchant Center/Alışveriş sekmesi tütün ürünlerine kapalı; mobil performansı asıl tutan Flatsome + jQuery + birleşik 215 KB CSS, trustindex yorum widget'ı ve Google etiketi.
