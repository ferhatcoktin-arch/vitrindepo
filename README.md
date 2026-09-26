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
