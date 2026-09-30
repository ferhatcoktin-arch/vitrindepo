/*
 * iCloud Kişiler — IQOS müşterilerini "IQOS MÜŞTERİ 1, 2, 3 …" diye yeniden numaralar.
 * Kaldığı yerden devam eder: bugün (30 Eyl 2026 20:30'dan sonra) değiştirilmiş kayıtların
 * numarasına dokunmaz, kalanlara boştaki numaraları eklenme tarihine göre verir.
 *
 * Kurallar (kullanıcının seçimi):
 *  - Kapsam: IQOS MÜŞTERİ, IQOS SİPARİŞ, IQOS GÜNCEL MÜŞTERİ, IQOS YENİ GÜNCEL MÜŞTERİ,
 *    IQOS POTANSİYEL MÜŞTERİ, SMARTCORE SİPARİŞ. IQOS SABİT MÜŞTERİ'lere ve diğer kayıtlara dokunmaz.
 *  - Sıra: rehbere eklenme tarihi (en eski = 1). Aynı telefon numarası = aynı kişi = aynı numara
 *    (rehberde çoğu kayıt iki kez var; iPhone "Kopyaları birleştir" ile birleştirilebilir).
 *  - Numaradan sonraki isim korunur: "IQOS SİPARİŞ 57 Akhan" -> "IQOS MÜŞTERİ 12 Akhan".
 *
 * Kullanım: Chrome'da https://www.icloud.com/contacts/ açıkken Geliştirici Araçları > Console,
 * üstteki bağlam menüsünden "index.html (applications/contacts)" iframe'ini seç, bu dosyanın
 * tamamını yapıştır, Enter. İlerleme konsola yazılır.
 * Yedek: İndirilenler/rehber-yedek-20260930.json (işlem öncesi tüm rehber).
 */
(async () => {
  const w = window;
  const bekle = (ms) => new Promise((r) => setTimeout(r, ms));
  const cs = w.performance.getEntriesByType('resource').map((e) => e.name).filter((n) => /contactsws.*\/co\/(startup|changeset)/.test(n)).pop();
  if (!cs) throw new Error('Kişiler uygulamasının iframe bağlamında çalıştır (contactsws isteği bulunamadı)');
  const u = new URL(cs);
  const base = u.origin;
  const temel = new URLSearchParams(u.search);
  ['syncToken', 'prefToken', 'limit', 'offset'].forEach((k) => temel.delete(k));
  const st = await fetch(base + '/co/startup?' + temel, { credentials: 'include' }).then((r) => r.json());
  let sync = st.syncToken;
  const pref = st.prefToken;

  let H = [];
  for (let off = 0; ; off += 500) {
    const p = new URLSearchParams(temel); p.set('syncToken', sync); p.set('prefToken', pref); p.set('offset', off); p.set('limit', 500);
    const j = await fetch(base + '/co/contacts/?' + p, { credentials: 'include', headers: { Accept: 'application/json' } }).then((r) => r.json());
    const c = j.contacts || []; H = H.concat(c); if (c.length < 500) break;
  }
  console.log('Rehber:', H.length, 'kişi');

  const ONEK = /^(?:İQOS|IQOS)\s+(?:YENİ\s+GÜNCEL\s+MÜŞTERİ|GÜNCEL\s+MÜŞTERİ|POTANSİYEL\s+MÜŞTERİ|MÜŞTERİ|SİPARİŞ)\s*|^SMARTCORE\s+SİPARİŞ\s*/;
  const BUGUN = '2026-09-30T17:30:00Z';
  const hedef = [];
  for (const h of H) {
    const f0 = h.firstName || '';
    let s = f0.replace(/^[^A-Za-zİıĞğÜüŞşÖöÇç]+/, '');
    if (/SAB[İI]T/i.test(s)) continue;
    let eslesti = false, m;
    while ((m = s.match(ONEK))) { s = s.slice(m[0].length); eslesti = true; }
    if (!eslesti) continue;
    const nm = s.match(/^(\d+)\s*/); const no = nm ? Number(nm[1]) : null; if (nm) s = s.slice(nm[0].length);
    const d = (((h.phones || [])[0] || {}).field || '').replace(/\D/g, '');
    const key = d.length >= 10 ? d.slice(-10) : 'id:' + h.contactId;
    const yapildi = h.dateModified >= BUGUN && /^IQOS MÜŞTERİ \d+/.test(f0);
    hedef.push({ id: h.contactId, eski: f0, ek: s.trim(), no, key, dc: h.dateCreated, yapildi });
  }
  const grup = {}; hedef.forEach((x) => { (grup[x.key] = grup[x.key] || []).push(x); });
  const gl = Object.values(grup).map((g) => ({ g, ilk: g.map((x) => x.dc).sort()[0], sabit: (g.find((x) => x.yapildi) || {}).no }));
  const kullanilan = new Set(gl.filter((o) => o.sabit).map((o) => o.sabit));
  gl.sort((a, b) => (a.ilk < b.ilk ? -1 : a.ilk > b.ilk ? 1 : a.g[0].id < b.g[0].id ? -1 : 1));
  let n = 1;
  for (const o of gl) o.no = o.sabit || null;
  for (const o of gl) if (!o.no) { while (kullanilan.has(n)) n++; kullanilan.add(n); o.no = n; }
  gl.forEach((o) => o.g.forEach((x) => { x.yeni = ('IQOS MÜŞTERİ ' + o.no + (x.ek ? ' ' + x.ek : '')).trim(); }));
  const kalan = hedef.filter((x) => x.eski !== x.yeni);
  console.log('Hedef kayıt:', hedef.length, '| kişi (tekil telefon):', gl.length, '| kalan:', kalan.length);

  const byId = Object.fromEntries(H.map((h) => [h.contactId, h]));
  const PARCA = 25;
  let tamam = 0;
  for (let i = 0; i < kalan.length; i += PARCA) {
    const parca = kalan.slice(i, i + PARCA);
    const body = JSON.stringify({ contacts: parca.map((x) => { const h = JSON.parse(JSON.stringify(byId[x.id])); h.firstName = x.yeni; return h; }) });
    for (let dene = 1; ; dene++) {
      const p = new URLSearchParams(temel); p.set('syncToken', sync); p.set('prefToken', pref); p.set('method', 'PUT');
      const ac = new AbortController(); const zt = setTimeout(() => ac.abort(), 30000);
      try {
        const r = await fetch(base + '/co/contacts/card/?' + p, { method: 'POST', credentials: 'include', headers: { 'Content-Type': 'text/plain' }, body, signal: ac.signal });
        const j = await r.json().catch(() => ({}));
        clearTimeout(zt);
        if (j.syncToken) sync = j.syncToken;
        if (r.ok && !j.errors) { tamam += parca.length; break; }
        console.warn('Hata', r.status, j.errors || j);
      } catch (e) { clearTimeout(zt); console.warn('Zaman aşımı / ağ', String(e)); }
      if (dene >= 8) { console.error('Bu parça atlandı, sonra tekrar çalıştır:', i); break; }
      const ara = Math.min(300000, 30000 * dene); // Apple yazma sınırı: beklemeyi artır
      console.log('Bekleniyor', ara / 1000, 'sn…'); await bekle(ara);
    }
    console.log('İlerleme:', tamam, '/', kalan.length);
    await bekle(2500);
  }
  console.log('BİTTİ. Güncellenen:', tamam, '/', kalan.length, '— tekrar çalıştırılırsa sadece kalanlar yapılır.');
})();
