/* Vitrin Hızlı: arama önerileri ve mobil alt çubuk (Ara, Kategoriler). jQuery gerektirmez. */
(function () {
	'use strict';

	var cfg = window.vitrin || {};
	var input = document.getElementById('vt-s');

	// Yazarken öneri: WooCommerce Store API (/wp-json/wc/store/v1/products). Olmazsa form normal aramaya gider.
	if (input && input.form && cfg.search && window.fetch) {
		var form = input.form;
		var box = form.querySelector('.vt-suggest');
		var list = document.getElementById('vt-oneri');
		var empty = form.querySelector('.vt-suggest__empty');
		var cache = {};
		var timer = null;
		var ctrl = null;
		var active = -1;

		var decode = function (html) {
			return new DOMParser().parseFromString(html || '', 'text/html').documentElement.textContent;
		};

		var money = function (p) {
			var amount = p && (p.price_range ? p.price_range.min_amount : p.price);
			if (amount === undefined || amount === null || amount === '') {
				return '';
			}
			var minor = parseInt(p.currency_minor_unit, 10) || 0;
			var parts = (parseInt(amount, 10) / Math.pow(10, minor)).toFixed(minor).split('.');
			parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, p.currency_thousand_separator || '');
			return (p.currency_prefix || '') + parts.join(p.currency_decimal_separator || ',') + (p.currency_suffix || '');
		};

		var options = function () {
			return list.querySelectorAll('[role="option"]');
		};

		var setActive = function (i) {
			var opts = options();
			active = i;
			for (var k = 0; k < opts.length; k++) {
				opts[k].setAttribute('aria-selected', k === i ? 'true' : 'false');
			}
			if (i > -1 && opts[i]) {
				input.setAttribute('aria-activedescendant', opts[i].id);
			} else {
				input.removeAttribute('aria-activedescendant');
			}
		};

		var setOpen = function (open) {
			box.hidden = !open;
			input.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (!open) {
				setActive(-1);
			}
		};

		var render = function (items) {
			list.textContent = '';
			items.forEach(function (p, i) {
				var a = document.createElement('a');
				a.className = 'vt-suggest__item';
				a.href = p.permalink;
				a.id = 'vt-oneri-' + i;
				a.setAttribute('role', 'option');
				a.setAttribute('aria-selected', 'false');

				var img = p.images && p.images[0];
				if (img) {
					var pic = document.createElement('img');
					pic.src = img.thumbnail || img.src;
					pic.alt = '';
					pic.width = 44;
					pic.height = 44;
					a.appendChild(pic);
				}
				var name = document.createElement('span');
				name.className = 'vt-suggest__name';
				name.textContent = decode(p.name);
				a.appendChild(name);

				var price = document.createElement('span');
				price.className = 'vt-suggest__price';
				price.textContent = money(p.prices);
				a.appendChild(price);

				list.appendChild(a);
			});
			empty.hidden = items.length > 0;
			setActive(-1);
			setOpen(true);
		};

		var search = function (q) {
			if (cache[q]) {
				render(cache[q]);
				return;
			}
			if (ctrl) {
				ctrl.abort();
			}
			ctrl = window.AbortController ? new AbortController() : null;
			var url = cfg.search + (cfg.search.indexOf('?') < 0 ? '?' : '&') + 'per_page=6&search=' + encodeURIComponent(q);
			fetch(url, { signal: ctrl ? ctrl.signal : undefined, headers: { Accept: 'application/json' } })
				.then(function (r) {
					if (!r.ok) {
						throw new Error(r.status);
					}
					return r.json();
				})
				.then(function (items) {
					if (!Array.isArray(items)) {
						return;
					}
					cache[q] = items;
					if (input.value.trim() === q) {
						render(items);
					}
				})
				.catch(function () {});
		};

		input.addEventListener('input', function () {
			var q = input.value.trim();
			clearTimeout(timer);
			if (q.length < 2) {
				setOpen(false);
				return;
			}
			timer = setTimeout(function () {
				search(q);
			}, 200);
		});

		input.addEventListener('keydown', function (e) {
			var opts = options();
			if (e.key === 'Escape' && !box.hidden) {
				e.preventDefault();
				setOpen(false);
				return;
			}
			if (box.hidden || !opts.length) {
				return;
			}
			if (e.key === 'ArrowDown') {
				e.preventDefault();
				setActive(active + 1 < opts.length ? active + 1 : -1);
			} else if (e.key === 'ArrowUp') {
				e.preventDefault();
				setActive(active > -1 ? active - 1 : opts.length - 1);
			} else if (e.key === 'Enter' && active > -1) {
				e.preventDefault();
				window.location.href = opts[active].href;
			}
		});

		input.addEventListener('focus', function () {
			if (input.value.trim().length > 1 && (list.firstChild || !empty.hidden)) {
				setOpen(true);
			}
		});

		// Öneriye dokununca kutu odağı kaybedip kapanmasın (Safari bağlantılara odak vermez).
		box.addEventListener('mousedown', function (e) {
			e.preventDefault();
		});

		form.addEventListener('focusout', function (e) {
			if (!e.relatedTarget || !form.contains(e.relatedTarget)) {
				setOpen(false);
			}
		});

		document.addEventListener('click', function (e) {
			if (!form.contains(e.target)) {
				setOpen(false);
			}
		});
	}

	// Alt çubuk: "Ara" arama kutusuna götürür, "Kategoriler" paneli açar (JS yoksa bağlantı mağazaya gider).
	document.addEventListener('click', function (e) {
		var el = e.target.closest ? e.target.closest('[data-vt-search],[data-vt-open]') : null;
		if (!el) {
			return;
		}
		if (el.hasAttribute('data-vt-search')) {
			if (input) {
				e.preventDefault();
				window.scrollTo({ top: 0, behavior: 'smooth' });
				input.focus({ preventScroll: true });
			}
			return;
		}
		var dialog = document.getElementById(el.getAttribute('data-vt-open'));
		if (dialog && typeof dialog.showModal === 'function') {
			e.preventDefault();
			dialog.showModal();
		}
	});

	// Panelin dışındaki karartılmış alana basınca kapanır.
	Array.prototype.forEach.call(document.querySelectorAll('dialog.vt-sheet'), function (dialog) {
		dialog.addEventListener('click', function (e) {
			if (e.target === dialog) {
				dialog.close();
			}
		});
	});
})();
