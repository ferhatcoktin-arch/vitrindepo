/* Vitrin Arayüz — bağımlılıksız, ~4 KB */
(function () {
  'use strict';
  var V = window.VA || {};
  var d = document;

  function dec(s) { var t = d.createElement('textarea'); t.innerHTML = s || ''; return t.value; }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function fiyat(p) {
    var pr = p.prices || {}, u = pr.currency_minor_unit || 0;
    var f = function (v) { return (pr.currency_prefix || '') + (Number(v) / Math.pow(10, u)).toLocaleString('tr-TR', { maximumFractionDigits: u }) + (pr.currency_suffix || ''); };
    if (!pr.price) return '';
    return p.on_sale && pr.regular_price !== pr.price ? '<del>' + f(pr.regular_price) + '</del> <ins>' + f(pr.price) + '</ins>' : f(pr.price);
  }
  function api(q) {
    return fetch(V.api + '?' + q, { credentials: 'same-origin' }).then(function (r) { if (!r.ok) throw r; return r.json(); });
  }

  /* ---------- Canlı arama ---------- */
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
        api('search=' + encodeURIComponent(q) + '&per_page=6&orderby=popularity').then(function (list) {
          if (q !== son) return;
          var h = list.length ? list.map(function (p) {
            var img = p.images && p.images[0] ? (p.images[0].thumbnail || p.images[0].src) : '';
            return '<a role="option" href="' + esc(p.permalink) + '">' + (img ? '<img src="' + esc(img) + '" alt="" loading="lazy">' : '') +
              '<span class="ad">' + esc(dec(p.name)) + (p.is_in_stock ? '' : ' <small>(tükendi)</small>') + '</span><span class="fy">' + fiyat(p) + '</span></a>';
          }).join('') + '<a class="tum" href="' + esc(V.arama + '?s=' + encodeURIComponent(q) + '&post_type=product') + '">Tüm sonuçlar →</a>'
            : '<div class="yok">“' + esc(q) + '” için ürün bulunamadı.</div>';
          oneri.innerHTML = h; oneri.hidden = false; sec = -1;
        }).catch(kapat);
      }, 220);
    });
    kutu.addEventListener('keydown', function (e) {
      var a = oneri.hidden ? [] : oneri.querySelectorAll('a');
      if (!a.length) return;
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        sec = (sec + (e.key === 'ArrowDown' ? 1 : -1) + a.length) % a.length;
        a.forEach(function (x, i) { x.classList.toggle('secili', i === sec); });
      } else if (e.key === 'Enter' && sec > -1) { e.preventDefault(); location.href = a[sec].href; }
      else if (e.key === 'Escape') kapat();
    });
    d.addEventListener('click', function (e) { if (!e.target.closest('.va-ara')) kapat(); });
  }

  /* ---------- Aroma seçici ---------- */
  var liste = d.getElementById('va-aroma-liste'), tum = d.getElementById('va-aroma-tum'), onb = {};
  function kart(p) {
    var img = p.images && p.images[0] ? p.images[0] : null, stok = p.is_in_stock;
    var btn = stok && p.type === 'simple'
      ? '<a class="va-sepet add_to_cart_button ajax_add_to_cart" href="' + esc(V.arama + '?add-to-cart=' + p.id) + '" data-quantity="1" data-product_id="' + p.id + '" rel="nofollow">Sepete Ekle</a>'
      : '<a class="va-sepet ikincil" href="' + esc(p.permalink) + '">' + (stok ? 'İncele' : 'Haber Ver') + '</a>';
    return '<div class="va-kart' + (stok ? '' : ' tukendi') + '"><a class="va-kart-gorsel" href="' + esc(p.permalink) + '">' +
      (img ? '<img src="' + esc(img.thumbnail || img.src) + '" alt="' + esc(dec(img.alt || p.name)) + '" loading="lazy" decoding="async">' : '') +
      (p.on_sale ? '<span class="va-rozet">İndirim</span>' : '') + '</a>' +
      '<a class="va-kart-ad" href="' + esc(p.permalink) + '">' + esc(dec(p.name)) + '</a><div class="va-kart-fiyat">' + fiyat(p) + '</div>' + btn + '</div>';
  }
  if (liste) {
    var ilk = d.querySelector('.va-sekme.aktif');
    if (ilk) onb[ilk.dataset.kat] = liste.innerHTML;
    d.querySelectorAll('.va-sekme').forEach(function (b) {
      b.addEventListener('click', function () {
        d.querySelectorAll('.va-sekme').forEach(function (x) { x.classList.remove('aktif'); x.setAttribute('aria-selected', 'false'); });
        b.classList.add('aktif'); b.setAttribute('aria-selected', 'true');
        if (tum) tum.href = b.dataset.link;
        var k = b.dataset.kat;
        liste.scrollLeft = 0;
        if (onb[k]) { liste.innerHTML = onb[k]; return; }
        liste.classList.add('yukleniyor');
        api('category=' + k + '&per_page=12&orderby=popularity&order=desc').then(function (list) {
          list.sort(function (a, b) { return (b.is_in_stock ? 1 : 0) - (a.is_in_stock ? 1 : 0); });
          onb[k] = list.map(kart).join('') || '<p>Bu aromada şu an ürün yok.</p>';
          if (b.classList.contains('aktif')) liste.innerHTML = onb[k];
        }).catch(function () { location.href = b.dataset.link; })
          .then(function () { liste.classList.remove('yukleniyor'); });
      });
    });
  }

  /* ---------- Mobil alt çubuk ---------- */
  var cek = d.getElementById('va-cekmece');
  d.addEventListener('click', function (e) {
    var a = e.target.closest('[data-va]');
    if (!a) { if (cek && e.target === cek) cek.hidden = true; return; }
    var t = a.dataset.va;
    if (t === 'kategoriler' && cek) { e.preventDefault(); cek.hidden = false; }
    else if (t === 'kapat' && cek) { cek.hidden = true; }
    else if (t === 'ara' && kutu) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
      setTimeout(function () { kutu.focus(); }, 250);
    }
  });
})();
