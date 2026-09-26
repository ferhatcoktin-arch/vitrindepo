/* Vitrin Arayüz 1.1 — canlı arama + kayan kategori rafları (bağımlılıksız) */
(function () {
  'use strict';
  var V = window.VA || {};
  var d = document;

  function dec(s) { var t = d.createElement('textarea'); t.innerHTML = s || ''; return t.value; }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  var para = null;
  try { para = new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }); } catch (e) {}
  function tutar(p, v) {
    var u = Number((p.prices || {}).currency_minor_unit || 0), a = Number(v) / Math.pow(10, u);
    return para ? para.format(a) : a.toLocaleString('tr-TR') + ' ₺';
  }
  function api(q) {
    return fetch(V.api + '?' + q, { credentials: 'same-origin' }).then(function (r) {
      if (!r.ok) throw r;
      var toplam = Number(r.headers.get('X-WP-Total'));
      return r.json().then(function (j) { return { list: j, total: toplam || j.length }; });
    });
  }

  /* ================= Canlı arama ================= */
  var kutu = d.getElementById('va-ara-kutu'), oneri = d.getElementById('va-oneri');
  if (kutu && oneri) {
    var zam, son = '', sec = -1;
    var kapat = function () { oneri.hidden = true; sec = -1; };
    kutu.addEventListener('input', function () {
      var q = kutu.value.trim();
      clearTimeout(zam);
      if (q.length < 2) { kapat(); return; }
      zam = setTimeout(function () {
        son = q;
        api('search=' + encodeURIComponent(q) + '&per_page=6&orderby=popularity').then(function (res) {
          if (q !== son) return;
          var list = res.list;
          oneri.innerHTML = list.length ? list.map(function (p) {
            var img = p.images && p.images[0] ? (p.images[0].thumbnail || p.images[0].src) : '';
            return '<a role="option" href="' + esc(p.permalink) + '">' + (img ? '<img src="' + esc(img) + '" alt="" loading="lazy">' : '') +
              '<span class="ad">' + esc(dec(p.name)) + (p.is_in_stock ? '' : ' <small>(tükendi)</small>') + '</span><span class="fy">' +
              (p.prices && p.prices.price ? tutar(p, p.prices.price) : '') + '</span></a>';
          }).join('') + '<a class="tum" href="' + esc(V.arama + '?s=' + encodeURIComponent(q) + '&post_type=product') + '">Tüm sonuçlar →</a>'
            : '<div class="yok">“' + esc(q) + '” için ürün bulunamadı.</div>';
          oneri.hidden = false; sec = -1;
        }).catch(kapat);
      }, 220);
    });
    kutu.addEventListener('keydown', function (e) {
      var a = oneri.hidden ? [] : oneri.querySelectorAll('a');
      if (!a.length) return;
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        sec = (sec + (e.key === 'ArrowDown' ? 1 : -1) + a.length) % a.length;
        [].forEach.call(a, function (x, i) { x.classList.toggle('secili', i === sec); });
      } else if (e.key === 'Enter' && sec > -1) { e.preventDefault(); location.href = a[sec].href; }
      else if (e.key === 'Escape') kapat();
    });
    d.addEventListener('click', function (e) { if (!e.target.closest('.va-ara')) kapat(); });
  }

  /* ================= Kategori rafları ================= */
  if (!V.raflar || !V.raflar.length) return;

  var raflar = [];
  var azHareket = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var OK = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
  var GERI = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>';
  var ILERI = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>';

  function simdi() { return window.performance ? performance.now() : Date.now(); }
  function bekle(r, ms) { r.bekle = Math.max(r.bekle, simdi() + ms); }
  function bosluk(r) { return parseFloat(getComputedStyle(r.ray).columnGap) || 15; }
  function klonVar(r) { return !!r.ray.querySelector('.vr-kopya'); }

  function kart(p, i, cfg) {
    var img = p.images && p.images[0], ids = (cfg.k || '').split(','), kat = '';
    (p.categories || []).some(function (c) { if (ids.indexOf(String(c.id)) < 0) { kat = c.name; return true; } return false; });
    if (!kat && p.categories && p.categories[0]) kat = p.categories[0].name;
    var rozet = '';
    if (!cfg.k) rozet = i === 0 ? 'ÇOK SATAN' : 'POPÜLER';
    else if (!p.is_in_stock) rozet = 'TÜKENDİ';
    else if (p.on_sale) rozet = 'İNDİRİM';
    var pr = p.prices || {}, fiyat = pr.price ? tutar(p, pr.price) : '';
    if (p.on_sale && pr.regular_price && pr.regular_price !== pr.price) fiyat = '<del>' + tutar(p, pr.regular_price) + '</del>' + fiyat;
    return '<a class="vr-kart' + (p.is_in_stock ? '' : ' tukendi') + '" href="' + esc(p.permalink) + '" draggable="false">' +
      '<div class="vr-foto">' + (img ? '<img src="' + esc(img.thumbnail || img.src) + '" alt="' + esc(dec(img.alt || p.name)) + '" width="300" height="300" loading="lazy" decoding="async" draggable="false">' : '') +
      (rozet ? '<span class="vr-rozet' + (rozet === 'TÜKENDİ' ? ' gri' : '') + '">' + rozet + '</span>' : '') + '</div>' +
      '<div class="vr-yazi"><div class="vr-kat">' + esc(dec(kat || 'IQOS Vitrin')) + '</div><div class="vr-ad">' + esc(dec(p.name)) + '</div><div class="vr-fiyat">' + fiyat + '</div></div></a>';
  }

  function rafKur(cfg) {
    var sec = d.createElement('section');
    sec.className = 'vr-raf';
    sec.setAttribute('aria-label', cfg.b);
    sec.innerHTML = '<div class="vr-bas"><div class="vr-baslik"><div class="vr-ust">' + esc(cfg.u) + '</div><h2>' + esc(cfg.b) + '</h2></div>' +
      '<div class="vr-araclar"><a class="vr-tum" href="' + esc(cfg.l) + '"><span>Tümünü Gör</span><i>' + OK + '</i></a>' +
      '<button type="button" class="vr-ok" data-yon="-1" aria-label="Önceki ürünler">' + GERI + '</button>' +
      '<button type="button" class="vr-ok" data-yon="1" aria-label="Sonraki ürünler">' + ILERI + '</button></div></div>' +
      '<div class="vr-ray" tabindex="0" aria-label="' + esc(cfg.b) + ' ürünleri">' +
      new Array(6).join('<div class="vr-kart vr-iskelet" aria-hidden="true"><div class="vr-foto"></div><div class="vr-yazi"><div class="vr-cizgi"></div><div class="vr-cizgi"></div><div class="vr-cizgi kisa"></div></div></div>') +
      '</div>';
    var r = { cfg: cfg, sec: sec, ray: sec.querySelector('.vr-ray'), pos: 0, setW: 0, oto: false, gorunur: false, fare: false, surukle: false, bekle: 0, yuklendi: false };
    sec._raf = r;
    olaylar(r);
    raflar.push(r);
    return sec;
  }

  function yukle(r) {
    if (r.yuklendi) return;
    r.yuklendi = true;
    var q = 'orderby=popularity&order=desc&per_page=' + (r.cfg.k ? 24 : 16) + (r.cfg.k ? '&category=' + r.cfg.k : '');
    api(q).then(function (res) {
      var list = res.list.filter(function (p) { return p.permalink; });
      if (r.cfg.k) {
        // stoktakiler önce, kendi aralarında satış sırası korunur
        list = list.filter(function (p) { return p.is_in_stock; }).concat(list.filter(function (p) { return !p.is_in_stock; }));
      } else {
        list = list.filter(function (p) { return p.is_in_stock && p.images && p.images.length; }).slice(0, 10);
      }
      if (!list.length) { r.sec.hidden = true; return; }
      if (r.cfg.k) r.sec.querySelector('.vr-ust').textContent = r.cfg.u + ' · ' + res.total + ' ürün';
      r.ray.innerHTML = list.map(function (p, i) { return kart(p, i, r.cfg); }).join('');
      donguKur(r);
    }).catch(function () { r.sec.hidden = true; });
  }

  /* Sonsuz döngü: ürünler bir kez kopyalanır, ilk takımın sonuna gelince başa sarılır (görünmez geçiş) */
  function donguKur(r) {
    var ray = r.ray, ilk = ray.firstElementChild, sonK = ray.lastElementChild;
    if (!ilk) return;
    var genislik = sonK.offsetLeft + sonK.offsetWidth + bosluk(r) - ilk.offsetLeft;
    if (genislik <= ray.clientWidth + 4) { r.oto = false; r.sec.classList.add('vr-az'); return; }
    var parca = d.createDocumentFragment();
    [].forEach.call(ray.children, function (k) {
      var c = k.cloneNode(true);
      c.classList.add('vr-kopya');
      c.setAttribute('aria-hidden', 'true');
      c.setAttribute('tabindex', '-1');
      parca.appendChild(c);
    });
    ray.appendChild(parca);
    olcu(r);
    r.pos = ray.scrollLeft;
    r.oto = !azHareket;
    baslat();
  }
  function olcu(r) {
    var k = r.ray.querySelector('.vr-kopya'), ilk = r.ray.firstElementChild;
    if (!k || !ilk) return;
    r.setW = k.offsetLeft - ilk.offsetLeft;
    r.oto = !azHareket && r.setW > r.ray.clientWidth + 4;
  }
  function sar(r) {
    if (r.setW && klonVar(r) && r.ray.scrollLeft >= r.setW) {
      r.ray.scrollLeft -= r.setW;
    }
    r.pos = r.ray.scrollLeft;
  }

  function olaylar(r) {
    var ray = r.ray, sec = r.sec;

    // Masaüstü: fare üstündeyken durur
    sec.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse') r.fare = true; });
    sec.addEventListener('pointerleave', function (e) { if (e.pointerType === 'mouse') { r.fare = false; bekle(r, 700); } });

    // Dokunmatik: parmak değince durur, bırakınca 3 sn sonra devam
    ray.addEventListener('touchstart', function () { r.dokun = true; }, { passive: true });
    ray.addEventListener('touchend', function () { r.dokun = false; bekle(r, 3000); }, { passive: true });
    ray.addEventListener('touchcancel', function () { r.dokun = false; bekle(r, 3000); }, { passive: true });
    ray.addEventListener('wheel', function () { bekle(r, 3000); }, { passive: true });
    ray.addEventListener('focusin', function () { r.odak = true; });
    ray.addEventListener('focusout', function () { r.odak = false; bekle(r, 2000); });

    // Elle kaydırma bitince döngü noktasına sar
    ray.addEventListener('scroll', function () {
      clearTimeout(r.st);
      r.st = setTimeout(function () { if (!r.surukle && !r.dokun) sar(r); }, 180);
    }, { passive: true });

    // Fareyle sürükleme
    var x0 = 0, s0 = 0, tasindi = false;
    function hareket(e) {
      var dx = e.clientX - x0;
      if (!tasindi && Math.abs(dx) > 5) { tasindi = true; ray.classList.add('suruk'); }
      var hedef = s0 - dx;
      if (hedef < 0 && r.setW && klonVar(r)) { s0 += r.setW; hedef += r.setW; }
      ray.scrollLeft = hedef;
    }
    function birak() {
      r.surukle = false;
      ray.classList.remove('suruk');
      window.removeEventListener('pointermove', hareket);
      window.removeEventListener('pointerup', birak);
      window.removeEventListener('pointercancel', birak);
      sar(r);
      bekle(r, 2500);
    }
    ray.addEventListener('pointerdown', function (e) {
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      r.surukle = true; tasindi = false; x0 = e.clientX; s0 = ray.scrollLeft;
      window.addEventListener('pointermove', hareket);
      window.addEventListener('pointerup', birak);
      window.addEventListener('pointercancel', birak);
    });
    ray.addEventListener('click', function (e) { if (tasindi) { e.preventDefault(); e.stopPropagation(); tasindi = false; } }, true);
    ray.addEventListener('dragstart', function (e) { e.preventDefault(); });

    // Oklar
    [].forEach.call(sec.querySelectorAll('.vr-ok'), function (b) {
      b.addEventListener('click', function () {
        var k = ray.querySelector('.vr-kart');
        if (!k) return;
        var adim = k.offsetWidth + bosluk(r), yon = Number(b.getAttribute('data-yon'));
        if (yon < 0 && ray.scrollLeft < adim && r.setW && klonVar(r)) ray.scrollLeft += r.setW;
        bekle(r, 4000);
        ray.scrollBy({ left: yon * adim, behavior: 'smooth' });
      });
    });
  }

  /* Tek bir animasyon döngüsü tüm rafları yavaşça kaydırır */
  var calisiyor = false, onceki = 0;
  function baslat() { if (!calisiyor && !azHareket) { calisiyor = true; requestAnimationFrame(dongu); } }
  function dongu(t) {
    var dt = onceki ? Math.min(t - onceki, 50) : 16;
    onceki = t;
    var hiz = (window.innerWidth < 769 ? 24 : 32) / 1000; // piksel / ms — yavaş akış
    var su = simdi();
    for (var i = 0; i < raflar.length; i++) {
      var r = raflar[i];
      if (!r.oto || !r.gorunur || d.hidden) continue;
      if (r.fare || r.dokun || r.surukle || r.odak || su < r.bekle) { r.pos = r.ray.scrollLeft; continue; }
      if (Math.abs(r.ray.scrollLeft - r.pos) > 2) r.pos = r.ray.scrollLeft; // kullanıcı kaydırdıysa oradan devam
      r.pos += hiz * dt;
      if (r.pos >= r.setW) r.pos -= r.setW;
      r.ray.scrollLeft = r.pos;
    }
    requestAnimationFrame(dongu);
  }

  /* Yerleşim: eski kategori kataloğunun olduğu yere (katalog gizli) */
  var kap = d.createElement('div');
  kap.id = 'vr-raflar';
  V.raflar.forEach(function (c) { kap.appendChild(rafKur(c)); });

  function yerlestir() {
    var katalog = d.getElementById('ivk-katalog');
    if (katalog && katalog.parentNode) {
      if (kap.nextElementSibling !== katalog) katalog.parentNode.insertBefore(kap, katalog);
      return;
    }
    var capa = d.querySelector('.ivs-terea') || d.querySelector('.ivs') || d.querySelector('.ivh');
    if (capa && capa.parentNode) {
      if (capa.nextElementSibling !== kap) capa.parentNode.insertBefore(kap, capa.nextSibling);
    } else if (!kap.parentNode) {
      (d.getElementById('content') || d.querySelector('main') || d.body).appendChild(kap);
    }
  }
  yerlestir();
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', yerlestir);
  window.addEventListener('load', yerlestir);

  if ('IntersectionObserver' in window) {
    var yakin = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { yukle(e.target._raf); yakin.unobserve(e.target); } });
    }, { rootMargin: '900px 0px' });
    var ekranda = new IntersectionObserver(function (es) {
      es.forEach(function (e) { e.target._raf.gorunur = e.isIntersecting; if (e.isIntersecting) bekle(e.target._raf, 400); });
    }, { rootMargin: '0px' });
    raflar.forEach(function (r) { yakin.observe(r.sec); ekranda.observe(r.sec); });
  } else {
    raflar.forEach(function (r) { r.gorunur = true; yukle(r); });
  }

  var rz;
  window.addEventListener('resize', function () {
    clearTimeout(rz);
    rz = setTimeout(function () { raflar.forEach(function (r) { if (klonVar(r)) { olcu(r); sar(r); } }); }, 200);
  });
})();
