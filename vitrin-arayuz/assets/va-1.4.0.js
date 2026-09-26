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

  /* ================= Kategori rafları =================
   * Kayış gibi akış: raf içeriği scrollLeft ile değil, GPU'da çalışan
   * transform: translate3d ile kaydırılır (piksel altı, titremesiz).
   * Parmak / fare ile sürüklenir, bırakınca atalet ile süzülür,
   * sonra yavaşça hızlanarak kendi akışına döner. */
  if (!V.raflar || !V.raflar.length) return;

  var raflar = [];
  var azHareket = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var OK = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
  var GERI = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>';
  var ILERI = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>';

  function simdi() { return window.performance ? performance.now() : Date.now(); }
  function bekle(r, ms) { r.bekle = Math.max(r.bekle, simdi() + ms); }
  function bosluk(r) { return parseFloat(getComputedStyle(r.bant).columnGap) || 15; }
  function sar(r, x) { return r.setW ? ((x % r.setW) + r.setW) % r.setW : 0; }
  function ciz(r) {
    var x = Math.round(r.x * 100) / 100;
    if (x !== r.cizilen) { r.cizilen = x; r.bant.style.transform = 'translate3d(' + (-x) + 'px,0,0)'; }
  }

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
      '<div class="vr-ray" tabindex="0" aria-label="' + esc(cfg.b) + ' ürünleri"><div class="vr-bant">' +
      new Array(6).join('<div class="vr-kart vr-iskelet" aria-hidden="true"><div class="vr-foto"></div><div class="vr-yazi"><div class="vr-cizgi"></div><div class="vr-cizgi"></div><div class="vr-cizgi kisa"></div></div></div>') +
      '</div></div>';
    var r = {
      cfg: cfg, sec: sec, ray: sec.querySelector('.vr-ray'), bant: sec.querySelector('.vr-bant'),
      x: 0, v: 0, hiz: 0, setW: 0, dongu: false, gorunur: false, fare: false, surukle: false,
      bekle: 0, yuklendi: false, anim: null, cizilen: null
    };
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
        list = list.filter(function (p) { return p.is_in_stock; }).concat(list.filter(function (p) { return !p.is_in_stock; }));
      } else {
        list = list.filter(function (p) { return p.is_in_stock && p.images && p.images.length; }).slice(0, 10);
      }
      if (!list.length) { r.sec.hidden = true; return; }
      if (r.cfg.k) r.sec.querySelector('.vr-ust').textContent = r.cfg.u + ' · ' + res.total + ' ürün';
      r.bant.innerHTML = list.map(function (p, i) { return kart(p, i, r.cfg); }).join('');
      donguKur(r);
    }).catch(function () { r.sec.hidden = true; });
  }

  /* Ürünler bir kez kopyalanır; ilk takımın sonuna gelince görünmez şekilde başa sarılır */
  function donguKur(r) {
    var bant = r.bant, ilk = bant.firstElementChild, sonK = bant.lastElementChild;
    if (!ilk) return;
    var genislik = sonK.offsetLeft + sonK.offsetWidth + bosluk(r) - ilk.offsetLeft;
    if (genislik <= r.ray.clientWidth + 4) { r.dongu = false; r.sec.classList.add('vr-az'); return; }
    var parca = d.createDocumentFragment();
    [].slice.call(bant.children).forEach(function (k) {
      var c = k.cloneNode(true);
      c.classList.add('vr-kopya');
      c.setAttribute('aria-hidden', 'true');
      c.setAttribute('tabindex', '-1');
      parca.appendChild(c);
    });
    bant.appendChild(parca);
    olcu(r);
    baslat();
  }
  function olcu(r) {
    var k = r.bant.querySelector('.vr-kopya'), ilk = r.bant.firstElementChild;
    if (!k || !ilk) return;
    r.setW = k.offsetLeft - ilk.offsetLeft;
    r.dongu = r.setW > r.ray.clientWidth + 4;
    r.sec.classList.toggle('vr-az', !r.dongu);
    r.x = r.dongu ? sar(r, r.x) : 0;
    r.cizilen = null;
    ciz(r);
  }

  function kaydir(r, yon) {
    var k = r.bant.querySelector('.vr-kart');
    if (!k || !r.dongu) return;
    var adim = k.offsetWidth + bosluk(r);
    r.v = 0;
    r.anim = { bas: r.x, fark: yon * adim, t0: simdi(), sure: 520 };
    bekle(r, 3500);
  }

  function olaylar(r) {
    var ray = r.ray, sec = r.sec;

    // Masaüstü: fare üstündeyken yumuşakça durur
    sec.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse') r.fare = true; });
    sec.addEventListener('pointerleave', function (e) { if (e.pointerType === 'mouse') { r.fare = false; bekle(r, 500); } });
    ray.addEventListener('focusin', function () { r.odak = true; });
    ray.addEventListener('focusout', function () { r.odak = false; bekle(r, 1500); });

    // Tarayıcı odak için kutuyu kendisi kaydırırsa (Tab ile gezinme) bant konumuna çevir
    ray.addEventListener('scroll', function () {
      if (ray.scrollLeft) { r.x = sar(r, r.x + ray.scrollLeft); ray.scrollLeft = 0; ciz(r); }
    });

    // Sürükleme: parmak ve fare. Dikey hareket sayfayı kaydırır (touch-action: pan-y).
    var x0 = 0, y0 = 0, bas = 0, sonX = 0, sonT = 0, id = null, niyet = 0, tasindi = false;
    ray.addEventListener('pointerdown', function (e) {
      if (!r.dongu || (e.pointerType === 'mouse' && e.button !== 0)) return;
      id = e.pointerId; x0 = sonX = e.clientX; y0 = e.clientY; sonT = simdi();
      bas = r.x; niyet = 0; tasindi = false; r.v = 0; r.anim = null;
      r.surukle = true;
    });
    ray.addEventListener('pointermove', function (e) {
      if (e.pointerId !== id || !r.surukle) return;
      var dx = e.clientX - x0, dy = e.clientY - y0;
      if (!niyet) {
        if (Math.abs(dx) < 6 && Math.abs(dy) < 6) return;
        niyet = Math.abs(dx) > Math.abs(dy) ? 1 : -1;
        if (niyet < 0) { r.surukle = false; id = null; bekle(r, 800); return; }
        tasindi = true;
        ray.classList.add('suruk');
        try { ray.setPointerCapture(id); } catch (err) {}
      }
      var t = simdi(), dt = Math.max(1, t - sonT);
      r.v = 0.8 * (-(e.clientX - sonX) / dt) + 0.2 * r.v; // px/ms
      sonX = e.clientX; sonT = t;
      r.x = sar(r, bas - dx);
      ciz(r);
    });
    function birak(e) {
      if (e.pointerId !== id) return;
      id = null;
      r.surukle = false;
      ray.classList.remove('suruk');
      if (simdi() - sonT > 90) r.v = 0; // parmak durup bıraktıysa atalet yok
      r.v = Math.max(-3, Math.min(3, r.v));
      r.hiz = 0;
      bekle(r, 2500);
    }
    ray.addEventListener('pointerup', birak);
    ray.addEventListener('pointercancel', birak);
    ray.addEventListener('click', function (e) { if (tasindi) { e.preventDefault(); e.stopPropagation(); tasindi = false; } }, true);
    ray.addEventListener('dragstart', function (e) { e.preventDefault(); });

    // Dokunmatik yüzey / yatay tekerlek
    ray.addEventListener('wheel', function (e) {
      if (!r.dongu || Math.abs(e.deltaX) <= Math.abs(e.deltaY)) return;
      e.preventDefault();
      r.anim = null; r.v = 0; r.hiz = 0;
      r.x = sar(r, r.x + e.deltaX);
      ciz(r);
      bekle(r, 2500);
    }, { passive: false });

    // Klavye
    ray.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { e.preventDefault(); kaydir(r, 1); }
      else if (e.key === 'ArrowLeft') { e.preventDefault(); kaydir(r, -1); }
    });

    // Oklar
    [].forEach.call(sec.querySelectorAll('.vr-ok'), function (b) {
      b.addEventListener('click', function () { kaydir(r, Number(b.getAttribute('data-yon'))); });
    });
  }

  /* Tek animasyon döngüsü tüm rafları sürer */
  var calisiyor = false, onceki = 0;
  function baslat() { if (!calisiyor) { calisiyor = true; requestAnimationFrame(dongu); } }
  function yumusak(t) { return 1 - Math.pow(1 - t, 3); }
  function dongu(t) {
    var dt = onceki ? Math.min(t - onceki, 50) : 16;
    onceki = t;
    var hedefHiz = azHareket ? 0 : (window.innerWidth < 769 ? 30 : 38) / 1000; // px/ms: sakin, sürekli akış
    var su = simdi();
    for (var i = 0; i < raflar.length; i++) {
      var r = raflar[i];
      if (!r.dongu || !r.gorunur || d.hidden) continue;
      if (r.surukle) continue;
      if (r.anim) { // ok / klavye: yumuşak adım
        var p = Math.min(1, (su - r.anim.t0) / r.anim.sure);
        r.x = sar(r, r.anim.bas + r.anim.fark * yumusak(p));
        if (p >= 1) r.anim = null;
        ciz(r);
        continue;
      }
      if (Math.abs(r.v) > 0.01) { // bırakınca süzülme
        r.x = sar(r, r.x + r.v * dt);
        r.v *= Math.pow(0.94, dt / 16.7);
        ciz(r);
        continue;
      }
      r.v = 0;
      var dur = r.fare || r.odak || su < r.bekle;
      var hedef = dur ? 0 : hedefHiz;
      r.hiz += (hedef - r.hiz) * Math.min(1, dt / (dur ? 180 : 700)); // kalkış ve duruş yumuşak
      if (r.hiz < 0.0005 && dur) { r.hiz = 0; continue; }
      r.x = sar(r, r.x + r.hiz * dt);
      ciz(r);
    }
    requestAnimationFrame(dongu);
  }

  /* Yerleşim */
  var kap = d.createElement('div');
  kap.id = 'vr-raflar';
  V.raflar.forEach(function (c) { kap.appendChild(rafKur(c)); });

  var mobil = window.matchMedia ? window.matchMedia('(max-width: 768px)') : null;
  function yerlestir() {
    // Mobil: arama + güven şeridinin hemen altı (tanıtım kutusunun üstü)
    var yer = d.getElementById('vr-raflar-yer');
    if (mobil && mobil.matches && yer) {
      if (kap.parentNode !== yer) yer.appendChild(kap);
      return;
    }
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
      es.forEach(function (e) { e.target._raf.gorunur = e.isIntersecting; });
    }, { rootMargin: '0px' });
    raflar.forEach(function (r) { yakin.observe(r.sec); ekranda.observe(r.sec); });
  } else {
    raflar.forEach(function (r) { r.gorunur = true; yukle(r); });
  }

  function yenidenOlc() { raflar.forEach(function (r) { if (r.bant.querySelector('.vr-kopya')) olcu(r); }); }
  var rz;
  window.addEventListener('resize', function () { clearTimeout(rz); rz = setTimeout(yenidenOlc, 200); });
  if (mobil) {
    var degisti = function () { yerlestir(); yenidenOlc(); };
    if (mobil.addEventListener) mobil.addEventListener('change', degisti); else if (mobil.addListener) mobil.addListener(degisti);
  }
})();
