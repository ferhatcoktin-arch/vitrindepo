# vitrindepo

iqosvitrin.com.tr (WordPress + WooCommerce) için kategori düzeni aracı ve hızlı, sade tema.

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

Bulut oturumunda Claude tarayıcı açıp senin wp-admin girişini kullanamaz; siteye WooCommerce REST API anahtarıyla bağlanır.

1. **WooCommerce → Ayarlar → Gelişmiş → REST API → Anahtar ekle**
   - Açıklama: `Claude`
   - İzinler: **Okuma/Yazma** (istersen önce **Okuma** ver: inceleme ve plan için yeter; planı onaylayınca aynı anahtarı açıp izni **Okuma/Yazma** yap, anahtar değişmez)
   - "API anahtarı oluştur"a bas; çıkan *Tüketici anahtarı* ve *Tüketici gizli anahtarı*nı kopyala.
   - **Bu anahtarları sohbete yapıştırma.**
2. Claude'da oturum başlığındaki bulut ortamı menüsü → **Edit**:
   - **Network access**: `iqosvitrin.com.tr` ekle.
   - Ortam değişkenleri: `WC_CONSUMER_KEY=<tüketici anahtarı>` ve `WC_CONSUMER_SECRET=<gizli anahtar>`.
3. **Yeni bir oturum** aç (ayarlar yeni oturumda geçerli olur) ve "siteyi incele, kategori planını göster" yaz.

İş bitince anahtarı aynı REST API ekranından iptal edebilirsin.

## Araç nasıl çalışır

`tools/woo_kategori.py` siteye yazan her adımda önce ne yapacağını gösterir, sadece `--yes` ile uygular.

| Adım | Komut | Siteyi değiştirir mi? |
|---|---|---|
| İncele (tema, eklentiler, sürümler, ürünler, kategoriler) | `python3 tools/woo_kategori.py inspect` | Hayır |
| Yedek al | `python3 tools/woo_kategori.py backup` | Hayır |
| Plan çıkar | `python3 tools/woo_kategori.py plan` → `plan.csv` | Hayır |
| Kategorileri oluştur | `python3 tools/woo_kategori.py setup --yes` | Evet (sadece ekler) |
| Ürünleri taşı | `python3 tools/woo_kategori.py apply plan.csv --yes` | Evet (önce otomatik yedek alır) |
| Geri al | `python3 tools/woo_kategori.py restore yedek/<tarih> --yes` | Evet |

- `plan.csv`de kategorisi `?` olan ürünlere dokunulmaz; bunlar elle kategori seçilince taşınır.
- Sitede aynı adla (ör. "TEREA") zaten bir kategori varsa yenisi açılmaz, o kullanılır; `setup` bunu "same name" diye gösterir.
- Ürünler taşındıktan ve geri alındıktan sonra kategori ürün sayıları yeniden hesaplanır.
- Hiçbir kategori ya da ürün silinmez. Eski kategoriler boş kalır, menüden kaldırılabilir.
- Test: `python3 tools/test_woo_kategori.py` (sahte bir WooCommerce API'sine karşı bütün adımları çalıştırır).

## Tema: Vitrin Hızlı

`tema/vitrin-hizli/` — tek CSS ve tek küçük JS dosyası; jQuery, sayfa oluşturucu, web yazı tipi yok.

- Üstte büyük arama kutusu; yazarken ürün önerileri (görsel + fiyat) WooCommerce'in kendi Store API'sinden gelir, ek eklenti gerekmez.
- 5 başlıklı menü: masaüstünde açılır alt menülü, mobilde yana kayan çipler.
- Ana sayfa: kategori kutuları, aroma seçici (Tütün Aromalı / Mentollü / Kapsüllü-Meyveli sekmeleri, TEREA alt kategorilerinden), çok satanlar (WooCommerce satış sayısına göre).
- Mobilde alt menü çubuğu: Ana Sayfa, Kategoriler (alttan açılan panel), Ara, Sepet (ürün sayısıyla), Hesabım.
- Kategori sayfalarında alt kategori çipleri (ör. TEREA'da: Tümü · Tütün Aromalı · Mentollü · Kapsüllü / Meyveli).
- İlk girişte 18+ yaş doğrulama penceresi (kapatılabilir).

### Kurulum

1. `tema/vitrin-hizli.zip` dosyasını indir.
2. **Görünüm → Temalar → Yeni ekle → Tema yükle** → zip'i seç → **Şimdi kur**.
3. Önce **Canlı önizleme** ile bak, beğenince **Etkinleştir**. Eski tema silinmez; geri dönmek için onu yeniden etkinleştirmek yeter.

### Ayarlar

- **Görünüm → Özelleştir → Vitrin teması**: vurgu rengi (buton, arama kutusu), 18+ penceresi aç/kapa. Logo: **Site kimliği**.
- **Görünüm → Menüler**: "Ana menü (5 başlık)" konumuna menü atanmazsa tema kendisi 5 başlık yapar: 4 ana kategori + Tüm Ürünler. Tema değişince eski temanın menüsü bu konuma kendiliğinden atanmış olabilir; öyleyse kaldır ya da düzenle. "Alt bilgi menüsü" sayfanın altında çıkar.
- **Görünüm → Bileşenler**: "Ana sayfa üst alan" (kampanya görseli için), "Alt bilgi" (iletişim, adres). Eski temanın bileşenleri buraya taşınmış olabilir, kontrol et.
- Kategori kutularında simge yerine görsel için: **Ürünler → Kategoriler → kategori → Küçük resim**.
- Aroma seçici ve kutular yeni kategori düzeni uygulanınca dolar; o zamana kadar tema sitedeki mevcut üst kategorileri gösterir.

### Geliştirme

- Temada bir değişiklikten sonra zip'i yeniden oluştur: `cd tema && rm -f vitrin-hizli.zip && zip -rqX vitrin-hizli.zip vitrin-hizli`
- Yerel deneme: `tema/test/kur.sh` yerel bir WordPress, WooCommerce'in HTML çıktısını taklit eden bir test eklentisi ve örnek ürünler kurar (http://127.0.0.1:8080). Ekran görüntüleri: `playwright-core` kurulu bir klasörden `node <repo>/tema/test/ekran.mjs`.

## Panelden yapılacaklar

- Mağaza sayfasına filtreler (Aroma, Yoğunluk, Paket, Fiyat) — ürünlere özellikler atandıktan sonra.
- Aynı ürünün renk / paket-karton kopyalarını tek "değişken ürün"de birleştirme — ürün listesi görüldükten sonra ayrıca planlanacak.
