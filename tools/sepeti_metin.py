#!/usr/bin/env python3
"""
iqossepeti.com metinlerini yeniden yazma hattı (stdlib).

  python3 tools/sepeti_metin.py hazirla      # yedek/sepeti-20260930/export.json -> yeniden/sepeti/girdi/*.json
  python3 tools/sepeti_metin.py kontrol DOSYA # tek çıktı dosyasını doğrula (yazarlar kullanır)
  python3 tools/sepeti_metin.py birlestir    # yeniden/sepeti/cikti/*.json -> yeniden/sepeti/yukle.json + rapor

İki tür iş var:
  * Tam yazım: ürün, kategori, düz Gutenberg yazı/sayfa. Yazar HTML'i baştan yazar.
  * Parça yazım: Flatsome UX Builder / özel HTML içeren sayfa ve yazılar. Metin parçaları
    (etiketler arası yazı + alt/title öznitelikleri + [title text=".."] gibi kısa kod metinleri)
    numaralanır, yazar sadece bu parçaları yeniden yazar; yapı birebir korunur.
"""
import glob
import html
import json
import os
import re
import sys

KOK = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
EXPORT = os.path.join(KOK, 'yedek', 'sepeti-20260930', 'export.json')
GIRDI = os.path.join(KOK, 'yeniden', 'sepeti', 'girdi')
CIKTI = os.path.join(KOK, 'yeniden', 'sepeti', 'cikti')

TELEFONLAR = ['+90 554 892 04 04', '+90 501 579 45 61']

# ---------------------------------------------------------------- yardımcılar

def duz(h):
    """HTML -> düz metin (karşılaştırma için)."""
    h = re.sub(r'<script.*?</script>|<style.*?</style>', ' ', h or '', flags=re.S | re.I)
    h = re.sub(r'\[/?[a-z_]+[^\]]*\]', ' ', h)
    h = re.sub(r'<[^>]+>', ' ', h)
    h = html.unescape(h)
    return re.sub(r'\s+', ' ', h).strip()


def kelimeler(t):
    return re.findall(r"[0-9a-zçğıöşüâîû]+", t.lower().replace('İ', 'i').replace('I', 'ı'))


def benzerlik(yeni, eski, n=6):
    """Yeni metindeki n'li kelime dizilerinin eski metinde de geçen oranı (0..1)."""
    a, b = kelimeler(duz(yeni)), kelimeler(duz(eski))
    if len(a) < n:
        return 0.0
    eski_set = {tuple(b[i:i + n]) for i in range(len(b) - n + 1)}
    yeni_l = [tuple(a[i:i + n]) for i in range(len(a) - n + 1)]
    return sum(1 for g in yeni_l if g in eski_set) / len(yeni_l)


# ---------------------------------------------------------------- parçalama

# Sırayla eşleşen belirteçler: script/style blokları, yorumlar, kısa kodlar, HTML etiketleri
BELIRTEC = re.compile(r'(<script\b.*?</script>|<style\b.*?</style>|<!--.*?-->|\[[^\[\]]*\]|<[^>]+>)', re.S | re.I)
OZNITELIK_HTML = re.compile(r'\b(alt|title|placeholder|aria-label)="([^"]*)"')
OZNITELIK_KOD = re.compile(r'\b(text|title|sub_title|tag_text|button_text)="([^"]*)"')
HARF = re.compile(r'[A-Za-zÇĞİÖŞÜçğıöşü]{2,}')


def parcala(icerik):
    """İçeriği [('sabit', str) | ('metin', id, str) | ...] listesine böler; metin parçalarını döndürür."""
    yapi, parcalar = [], []

    def metin_ekle(s):
        if HARF.search(s) and s.strip():
            bas = len(s) - len(s.lstrip())
            son = len(s.rstrip())
            if bas:
                yapi.append(('sabit', s[:bas]))
            pid = len(parcalar)
            parcalar.append({'id': pid, 'tur': 'metin', 'metin': s[bas:son]})
            yapi.append(('parca', pid))
            if son < len(s):
                yapi.append(('sabit', s[son:]))
        elif s:
            yapi.append(('sabit', s))

    def oznitelikli(tag, desen, tur):
        son = 0
        for m in desen.finditer(tag):
            if not HARF.search(m.group(2)):
                continue
            yapi.append(('sabit', tag[son:m.start(2)]))
            pid = len(parcalar)
            parcalar.append({'id': pid, 'tur': tur + ':' + m.group(1), 'metin': m.group(2)})
            yapi.append(('parca', pid))
            son = m.end(2)
        yapi.append(('sabit', tag[son:]))

    konum = 0
    for m in BELIRTEC.finditer(icerik):
        metin_ekle(icerik[konum:m.start()])
        tok = m.group(0)
        if tok.startswith('<script') or tok.startswith('<style') or tok.startswith('<!--'):
            yapi.append(('sabit', tok))
        elif tok.startswith('['):
            oznitelikli(tok, OZNITELIK_KOD, 'kod')
        else:
            oznitelikli(tok, OZNITELIK_HTML, 'oznitelik')
        konum = m.end()
    metin_ekle(icerik[konum:])
    return yapi, parcalar


def birlestir_yapi(yapi, parcalar, yeni):
    out = []
    for y in yapi:
        if y[0] == 'sabit':
            out.append(y[1])
        else:
            p = parcalar[y[1]]
            metin = yeni.get(str(y[1]), yeni.get(y[1], p['metin']))
            if p['tur'].startswith(('oznitelik', 'kod')):
                metin = metin.replace('"', '”')
            out.append(metin)
    return ''.join(out)


# ---------------------------------------------------------------- hazırlık

PARCA_SAYFA = [212, 194, 45, 46]
PARCA_YAZI = [1664, 1671, 1669, 1667, 2900, 2901, 2902, 2903]
TAM_YAZI = [42, 43, 44, 76]
TAM_SAYFA = [3, 1198, 1196]


def hazirla():
    d = json.load(open(EXPORT, encoding='utf-8'))
    os.makedirs(GIRDI, exist_ok=True)
    os.makedirs(CIKTI, exist_ok=True)

    urunler = sorted(d['urunler'], key=lambda p: (p['categories'][:1], p['id']))
    grup = 8
    boy = -(-len(urunler) // grup)
    for i in range(grup):
        dilim = urunler[i * boy:(i + 1) * boy]
        json.dump([{k: p[k] for k in ('id', 'name', 'sku', 'price', 'categories', 'short_description', 'description', 'rm', 'attributes')} for p in dilim],
                  open(os.path.join(GIRDI, f'urun-{i + 1:02d}.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)

    json.dump(d['kategoriler'], open(os.path.join(GIRDI, 'kategori.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)

    yazilar = {p['id']: p for p in d['yazilar']}
    sayfalar = {p['id']: p for p in d['sayfalar']}
    json.dump([yazilar[i] for i in TAM_YAZI] + [dict(sayfalar[i], tur='sayfa') for i in TAM_SAYFA],
              open(os.path.join(GIRDI, 'tam-yazi-sayfa.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)

    for tur, ids, kaynak in (('sayfa', PARCA_SAYFA, sayfalar), ('yazi', PARCA_YAZI, yazilar)):
        for i in ids:
            p = kaynak[i]
            _, parcalar = parcala(p['content'])
            json.dump({'id': i, 'tur': tur, 'slug': p['slug'], 'baslik': p['title'], 'rm': p.get('rm', {}),
                       'excerpt': p.get('excerpt', ''), 'parcalar': parcalar},
                      open(os.path.join(GIRDI, f'parca-{tur}-{i}.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    print('hazır:', sorted(os.listdir(GIRDI)))


# ---------------------------------------------------------------- kontrol

def _eski_bul(d):
    return ({p['id']: p for p in d['urunler']}, {c['id']: c for c in d['kategoriler']},
            {p['id']: p for p in d['yazilar']}, {p['id']: p for p in d['sayfalar']})


def kontrol(yol, sessiz=False):
    """Bir çıktı dosyasını doğrular; (hatalar, uyarılar, satırlar) döndürür."""
    d = json.load(open(EXPORT, encoding='utf-8'))
    urun, kat, yazi, sayfa = _eski_bul(d)
    veri = json.load(open(yol, encoding='utf-8'))
    ad = os.path.basename(yol)
    hata, uyari, satir = [], [], []

    def uzunluk(etiket, s, en_az, en_cok):
        n = len(s or '')
        if not (en_az <= n <= en_cok):
            uyari.append(f'{etiket}: uzunluk {n} ({en_az}-{en_cok} olmalı)')

    def yasakli(etiket, s):
        if re.search(r'vitrin', s or '', re.I):
            hata.append(f'{etiket}: "Vitrin" geçiyor')
        if re.search(r'daha az zararl|zararsız|sağlıklı alternatif|%\s?9\d', s or '', re.I):
            hata.append(f'{etiket}: sağlık iddiası')
        if re.search(r'iade', s or '', re.I):
            uyari.append(f'{etiket}: "iade" geçiyor (iade kabul edilmiyor, bahsetme)')

    if ad.startswith('urun-'):
        for y in veri:
            e = urun.get(y.get('id'))
            if not e:
                hata.append(f'bilinmeyen ürün id {y.get("id")}')
                continue
            et = f'ürün {e["id"]}'
            for k in ('name', 'short_description', 'description', 'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword'):
                if not y.get(k):
                    hata.append(f'{et}: {k} boş')
            b = benzerlik(y.get('description', ''), e['description'])
            bk = benzerlik(y.get('short_description', ''), e['short_description'])
            if b > 0.08:
                hata.append(f'{et}: açıklama eskisine çok benziyor ({b:.0%})')
            if bk > 0.15:
                hata.append(f'{et}: kısa açıklama eskisine çok benziyor ({bk:.0%})')
            if y.get('name', '').strip() == e['name'].strip():
                uyari.append(f'{et}: ad aynı kalmış')
            for tel in TELEFONLAR:
                if tel not in y.get('description', ''):
                    hata.append(f'{et}: açıklamada {tel} yok')
            if 'bağımlılık' not in duz(y.get('description', '')).lower():
                hata.append(f'{et}: nikotin bağımlılık uyarısı yok')
            uzunluk(f'{et} ad', y.get('name'), 12, 72)
            uzunluk(f'{et} seo başlık', y.get('rank_math_title'), 30, 62)
            uzunluk(f'{et} meta', y.get('rank_math_description'), 110, 160)
            uzunluk(f'{et} açıklama(düz)', duz(y.get('description')), 900, 4500)
            for k in ('name', 'short_description', 'description', 'rank_math_title', 'rank_math_description'):
                yasakli(f'{et} {k}', y.get(k))
            satir.append((et, b, bk))
        eksik = set()
        girdi = os.path.join(GIRDI, ad)
        if os.path.exists(girdi):
            beklenen = {p['id'] for p in json.load(open(girdi, encoding='utf-8'))}
            eksik = beklenen - {y.get('id') for y in veri}
        if eksik:
            hata.append(f'eksik ürünler: {sorted(eksik)}')

    elif ad.startswith('kategori'):
        for y in veri:
            e = kat.get(y.get('id'))
            if not e:
                hata.append(f'bilinmeyen kategori id {y.get("id")}')
                continue
            et = f'kategori {e["id"]}'
            b = benzerlik(y.get('description', ''), e['description'])
            if b > 0.08:
                hata.append(f'{et}: eskisine çok benziyor ({b:.0%})')
            if e['count'] and len(duz(y.get('description', ''))) < 900:
                uyari.append(f'{et}: ürünlü kategori metni kısa')
            for k in ('description', 'rank_math_title', 'rank_math_description'):
                if not y.get(k):
                    hata.append(f'{et}: {k} boş')
                yasakli(f'{et} {k}', y.get(k))
            satir.append((et, b, 0))
        eksik = set(kat) - {y.get('id') for y in veri} - {18}
        if eksik:
            uyari.append(f'yazılmayan kategoriler: {sorted(eksik)}')

    elif ad.startswith('tam-'):
        for y in veri:
            e = yazi.get(y.get('id')) or sayfa.get(y.get('id'))
            if not e:
                hata.append(f'bilinmeyen id {y.get("id")}')
                continue
            et = f'{"yazı" if y.get("id") in yazi else "sayfa"} {e["id"]}'
            b = benzerlik(y.get('content', ''), e['content'])
            if b > 0.08:
                hata.append(f'{et}: eskisine çok benziyor ({b:.0%})')
            for k in ('title', 'content', 'rank_math_title', 'rank_math_description'):
                if not y.get(k):
                    hata.append(f'{et}: {k} boş')
                yasakli(f'{et} {k}', y.get(k))
            for tel in TELEFONLAR:
                if tel in e['content'] and tel not in y.get('content', ''):
                    hata.append(f'{et}: {tel} kaybolmuş')
            for href in re.findall(r'href="([^"]+)"', e['content']):
                if href not in y.get('content', ''):
                    uyari.append(f'{et}: bağlantı kaybolmuş {href}')
            satir.append((et, b, 0))

    elif ad.startswith('parca-'):
        g = json.load(open(os.path.join(GIRDI, ad), encoding='utf-8'))
        e = (sayfa if g['tur'] == 'sayfa' else yazi)[g['id']]
        et = f'{g["tur"]} {g["id"]}'
        yeni = {str(k): v for k, v in (veri.get('parcalar') or {}).items()}
        ids = {str(p['id']) for p in g['parcalar']}
        fazla = set(yeni) - ids
        if fazla:
            hata.append(f'{et}: olmayan parça numaraları {sorted(fazla)[:10]}')
        yapi, parcalar = parcala(e['content'])
        icerik = birlestir_yapi(yapi, parcalar, yeni)
        b = benzerlik(icerik, e['content'])
        if b > 0.10:
            hata.append(f'{et}: eskisine çok benziyor ({b:.0%}) — daha çok parçayı yeniden yaz')
        degismeyen = [p for p in g['parcalar'] if len(p['metin']) > 60 and yeni.get(str(p['id']), p['metin']) == p['metin']]
        if degismeyen:
            uyari.append(f'{et}: {len(degismeyen)} uzun parça aynı kalmış: {[p["id"] for p in degismeyen][:15]}')
        for p in g['parcalar']:
            v = yeni.get(str(p['id']))
            if v is not None and re.search(r'<[a-z/]|\[[a-z/]', v):
                hata.append(f'{et}: parça {p["id"]} içinde HTML/kısa kod var (sadece düz metin yaz)')
        for tel in TELEFONLAR:
            if tel in e['content'] and tel not in icerik:
                hata.append(f'{et}: {tel} kaybolmuş')
        for k in ('title', 'rank_math_title', 'rank_math_description'):
            if not veri.get(k):
                hata.append(f'{et}: {k} boş')
            yasakli(f'{et} {k}', veri.get(k))
        yasakli(f'{et} içerik', duz(icerik))
        satir.append((et, b, 0))
    else:
        hata.append('dosya adı tanınmadı')

    if not sessiz:
        for s in satir:
            print(f'  {s[0]}: benzerlik açıklama {s[1]:.1%}' + (f', kısa {s[2]:.1%}' if s[2] else ''))
        for u in uyari:
            print('  UYARI', u)
        for h in hata:
            print('  HATA ', h)
        print(f'{ad}: {len(hata)} hata, {len(uyari)} uyarı')
    return hata, uyari, satir


# ---------------------------------------------------------------- birleştirme

def birlestir():
    d = json.load(open(EXPORT, encoding='utf-8'))
    urun, kat, yazi, sayfa = _eski_bul(d)
    paket = {'urunler': [], 'kategoriler': [], 'yazilar': [], 'sayfalar': [], 'site': None}
    toplam_hata = 0
    for yol in sorted(glob.glob(os.path.join(CIKTI, '*.json'))):
        ad = os.path.basename(yol)
        if ad == 'site.json':
            paket['site'] = json.load(open(yol, encoding='utf-8'))
            continue
        hata, _, _ = kontrol(yol, sessiz=True)
        if hata:
            print('ATLANDI (hata var):', ad, hata[:3])
            toplam_hata += len(hata)
            continue
        veri = json.load(open(yol, encoding='utf-8'))
        if ad.startswith('urun-'):
            paket['urunler'] += veri
        elif ad.startswith('kategori'):
            paket['kategoriler'] += veri
        elif ad.startswith('tam-'):
            for y in veri:
                (paket['yazilar'] if y['id'] in yazi else paket['sayfalar']).append(y)
        elif ad.startswith('parca-'):
            g = json.load(open(os.path.join(GIRDI, ad), encoding='utf-8'))
            e = (sayfa if g['tur'] == 'sayfa' else yazi)[g['id']]
            yeni = {str(k): v for k, v in veri['parcalar'].items()}
            yapi, parcalar = parcala(e['content'])
            icerik = birlestir_yapi(yapi, parcalar, yeni)
            # Sayfa içi JSON-LD (SSS schema) görünen metinle aynı kalsın: eski parça metni -> yeni
            def ld_duzelt(m):
                s = m.group(0)
                for p in parcalar:
                    v = yeni.get(str(p['id']))
                    if v and len(p['metin']) > 12:
                        s = s.replace(json.dumps(p['metin'], ensure_ascii=False)[1:-1], json.dumps(v, ensure_ascii=False)[1:-1])
                return s
            icerik = re.sub(r'<script type="application/ld\+json">.*?</script>', ld_duzelt, icerik, flags=re.S)
            kayit = {'id': g['id'], 'title': veri['title'], 'content': icerik,
                     'rank_math_title': veri['rank_math_title'], 'rank_math_description': veri['rank_math_description'],
                     'rank_math_focus_keyword': veri.get('rank_math_focus_keyword', '')}
            if veri.get('excerpt'):
                kayit['excerpt'] = veri['excerpt']
            (paket['sayfalar'] if g['tur'] == 'sayfa' else paket['yazilar']).append(kayit)
    hedef = os.path.join(KOK, 'yeniden', 'sepeti', 'yukle.json')
    json.dump(paket, open(hedef, 'w', encoding='utf-8'), ensure_ascii=False)
    print({k: (len(v) if isinstance(v, list) else bool(v)) for k, v in paket.items()}, 'hata:', toplam_hata, '->', hedef)


if __name__ == '__main__':
    komut = sys.argv[1] if len(sys.argv) > 1 else ''
    if komut == 'hazirla':
        hazirla()
    elif komut == 'kontrol':
        h, _, _ = kontrol(sys.argv[2])
        sys.exit(1 if h else 0)
    elif komut == 'birlestir':
        birlestir()
    else:
        print(__doc__)
