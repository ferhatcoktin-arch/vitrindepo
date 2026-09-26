# 26 Eyl 2026 22:15 – kategori düzeni (EKLEME modu)

- `urun-kategorileri.json`: değişiklikten ÖNCE her ürünün kategori ID'leri (132 ürün).
- Yeni kategoriler: 1502 iqos-cihazlar (1503 iluma-i-serisi, 1504 iluma-serisi), 1505 terea (1506 tütün, 1507 mentollü, 1508 kapsüllü/meyveli), 1509 setler-kampanyalar, 1510 aksesuarlar (1511 kılıf-kapak, 1512 şarj-kablo).
- 107 ürüne yeni kategori EKLENDİ, eski kategoriler korundu. 25 Vozol ürününe dokunulmadı (ayrı ana kategori kalıyor).
- Geri alma: her ürünün `categories` alanını bu dosyadaki ID'lerle PUT et.
