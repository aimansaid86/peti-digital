/* Peti Digital — aplikasi portal (tanpa langkah binaan). */
(function () {
	'use strict';

	var C = window.PD;
	var viewEl = document.getElementById('pd-view');
	var layer = document.getElementById('pd-layer');
	var appEl = document.getElementById('pd-app');

	// =================================================================== dwibahasa

	// Teks sumber dalam Bahasa Melayu; C.i18n ialah kamus English (kosong jika BM).
	// Entri [tunggal, jamak] dipilih mengikut vars.n. Pemegang tempat: {nama}.
	// Akhiran konteks "@lajur" membezakan teks BM yang sama tetapi berlainan maksud dalam English;
	// akhiran itu dibuang jika tiada terjemahan.
	var DICT = C.i18n || {};
	function t(s, vars) {
		var tr = Object.prototype.hasOwnProperty.call(DICT, s) ? DICT[s] : s.replace(/@[a-z]+$/, '');
		if (Array.isArray(tr)) tr = vars && Number(String(vars.n).replace(/[^\d.-]/g, '')) === 1 ? tr[0] : (tr[1] || tr[0]);
		if (vars) tr = String(tr).replace(/\{(\w+)\}/g, function (m, k) { return vars[k] !== undefined && vars[k] !== null ? String(vars[k]) : m; });
		return tr;
	}

	// =================================================================== utiliti DOM

	function h(tag, props) {
		var el = document.createElement(tag);
		if (props) {
			Object.keys(props).forEach(function (k) {
				var v = props[k];
				if (v === null || v === undefined || v === false) return;
				if (k === 'class') el.className = v;
				else if (k === 'style') el.style.cssText = v;
				else if (k === 'html') el.innerHTML = v;
				else if (k.slice(0, 2) === 'on' && typeof v === 'function') el.addEventListener(k.slice(2).toLowerCase(), v);
				else if (k === 'value') el.value = v;
				else el.setAttribute(k, v === true ? '' : v);
			});
		}
		append(el, Array.prototype.slice.call(arguments, 2));
		return el;
	}

	function append(el, kids) {
		(Array.isArray(kids) ? kids : [kids]).forEach(function (k) {
			if (Array.isArray(k)) return append(el, k);
			if (k === null || k === undefined || k === false) return;
			el.append(k instanceof Node ? k : document.createTextNode(String(k)));
		});
		return el;
	}

	function clear(el) {
		while (el.firstChild) el.removeChild(el.firstChild);
		return el;
	}

	function fromHTML(html) {
		var tpl = document.createElement('template');
		tpl.innerHTML = html.trim();
		return tpl.content.firstChild;
	}

	var ICONS = {
		home: 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z',
		folder: 'M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z',
		folderPlus: 'M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM12 10v6M9 13h6',
		trash: 'M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3',
		activity: 'M3 12h4l3-8 4 16 3-8h4',
		users: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
		sliders: 'M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6',
		upload: 'M12 16V4M7 9l5-5 5 5M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2',
		download: 'M12 4v12M7 11l5 5 5-5M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2',
		edit: 'M4 20h4L19 9l-4-4L4 16zM14 6l4 4',
		x: 'M6 6l12 12M18 6 6 18',
		menu: 'M4 6h16M4 12h16M4 18h16',
		search: 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4',
		grid: 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
		list: 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01',
		more: 'M12 5h.01M12 12h.01M12 19h.01',
		chevR: 'M9 6l6 6-6 6',
		chevL: 'M15 6l-6 6 6 6',
		logout: 'M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l-5-5 5-5M5 12h11',
		user: 'M20 21v-1a5 5 0 0 0-5-5H9a5 5 0 0 0-5 5v1M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
		restore: 'M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5',
		eye: 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
		copy: 'M9 9h11v11H9zM5 15H4V4h11v1',
		arrowUp: 'M12 19V5M5 12l7-7 7 7',
		arrowDown: 'M12 5v14M19 12l-7 7-7-7',
		external: 'M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5',
		drive: 'M3 13h18M3 13l3-8h12l3 8v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM7 17h.01',
		clock: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
		file: 'M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8zM14 3v5h5',
		check: 'M5 12l5 5 9-10',
		refresh: 'M20 11a8 8 0 0 0-14.9-3M4 4v4h4M4 13a8 8 0 0 0 14.9 3M20 20v-4h-4',
		plus: 'M12 5v14M5 12h14'
	};

	function icon(name, size) {
		size = size || 18;
		var sw = name === 'more' ? 3 : 2;
		return fromHTML('<svg viewBox="0 0 24 24" width="' + size + '" height="' + size + '" fill="none" stroke="currentColor" stroke-width="' + sw + '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' + ICONS[name] + '"/></svg>');
	}

	var KIND = {
		pdf: { c: '#d93025', l: 'PDF' },
		doc: { c: '#2b6cd9', l: 'Dokumen' },
		sheet: { c: '#188038', l: 'Hamparan' },
		slide: { c: '#e0662a', l: 'Persembahan' },
		image: { c: '#8e44ad', l: 'Gambar' },
		video: { c: '#c2185b', l: 'Video' },
		audio: { c: '#00897b', l: 'Audio' },
		archive: { c: '#b7791f', l: 'Arkib' },
		text: { c: '#607d8b', l: 'Teks' },
		code: { c: '#37474f', l: 'Kod' },
		other: { c: '#78909c', l: 'Lain-lain' }
	};

	function folderIcon(size) {
		var w = size, hh = Math.round(size * 0.83);
		return fromHTML('<span class="pd-ficon"><svg viewBox="0 0 48 40" width="' + w + '" height="' + hh + '" aria-hidden="true"><path d="M2 6a3 3 0 0 1 3-3h12l4 4h22a3 3 0 0 1 3 3v24a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3z" fill="var(--folder-tab)"/><path d="M2 12a3 3 0 0 1 3-3h38a3 3 0 0 1 3 3v22a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3z" fill="var(--folder)"/></svg></span>');
	}

	function fileIcon(item, size) {
		if (item.type === 'dir' || item.kind === 'dir') return folderIcon(size);
		var k = KIND[item.kind] || KIND.other;
		var small = size < 32;
		var el = fromHTML(
			'<span class="pd-ficon' + (small ? ' pd-ficon--sm' : '') + '"><svg viewBox="0 0 40 48" width="' + Math.round(size * 0.84) + '" height="' + size + '" aria-hidden="true">' +
			'<path d="M5 1.5h21l11.5 11.5v31a2.5 2.5 0 0 1-2.5 2.5H5A2.5 2.5 0 0 1 2.5 44V4A2.5 2.5 0 0 1 5 1.5z" fill="#fff" stroke="' + (small ? k.c : '#cdd5e3') + '" stroke-width="' + (small ? 3 : 1.6) + '"/>' +
			'<path d="M26 1.5V10a3 3 0 0 0 3 3h8.5" fill="' + (small ? '#fff' : '#eef1f7') + '" stroke="' + (small ? k.c : '#cdd5e3') + '" stroke-width="' + (small ? 3 : 1.6) + '"/>' +
			(small ? '' : '<rect x="9" y="21" width="20" height="2.6" rx="1.3" fill="' + k.c + '" opacity=".28"/><rect x="9" y="27" width="16" height="2.6" rx="1.3" fill="' + k.c + '" opacity=".28"/>') +
			'</svg></span>'
		);
		if (!small) {
			var badge = h('span', { class: 'pd-ficon__badge', style: 'background:' + k.c }, (item.ext || t('fail')).slice(0, 5));
			el.append(badge);
		}
		return el;
	}

	// =================================================================== format

	var LOCALE = C.locale || 'ms-MY';

	function fmtSize(b) {
		b = Number(b) || 0;
		if (b < 1024) return b + ' B';
		var u = ['KB', 'MB', 'GB', 'TB'], i = -1;
		do { b /= 1024; i++; } while (b >= 1024 && i < u.length - 1);
		return (b >= 100 ? Math.round(b) : b.toFixed(1).replace(/\.0$/, '')) + ' ' + u[i];
	}

	function fmtNum(n) {
		return Number(n || 0).toLocaleString(LOCALE);
	}

	function fmtDate(iso, withTime) {
		if (!iso) return '—';
		var d = new Date(iso);
		var opt = { day: 'numeric', month: 'short', year: 'numeric' };
		if (withTime !== false) { opt.hour = '2-digit'; opt.minute = '2-digit'; }
		try { return d.toLocaleString(LOCALE, opt); } catch (e) { return d.toLocaleString(); }
	}

	function relTime(iso) {
		if (!iso) return '—';
		var s = (Date.now() - new Date(iso).getTime()) / 1000;
		if (s < 60) return t('baru sahaja');
		if (s < 3600) return t('{n} minit lalu', { n: Math.floor(s / 60) });
		if (s < 86400) return t('{n} jam lalu', { n: Math.floor(s / 3600) });
		if (s < 172800) return t('semalam');
		if (s < 7 * 86400) return t('{n} hari lalu', { n: Math.floor(s / 86400) });
		return fmtDate(iso, false);
	}

	function greeting() {
		var hr = new Date().getHours();
		if (hr < 12) return t('Selamat pagi');
		if (hr < 14) return t('Selamat tengah hari');
		if (hr < 19) return t('Selamat petang');
		return t('Selamat malam');
	}

	function parentOf(path) {
		var i = path.lastIndexOf('/');
		return i < 0 ? '' : path.slice(0, i);
	}

	// =================================================================== API

	function withQuery(url, params) {
		var qs = [];
		Object.keys(params || {}).forEach(function (k) {
			var v = params[k];
			if (v === null || v === undefined || v === '') return;
			if (Array.isArray(v)) v.forEach(function (x) { qs.push(encodeURIComponent(k + '[]') + '=' + encodeURIComponent(x)); });
			else qs.push(encodeURIComponent(k) + '=' + encodeURIComponent(v));
		});
		if (!qs.length) return url;
		return url + (url.indexOf('?') >= 0 ? '&' : '?') + qs.join('&');
	}

	function api(path, opts) {
		opts = opts || {};
		var url = withQuery(C.rest + path, opts.query);
		var init = { method: opts.method || 'GET', credentials: 'same-origin', headers: { 'X-WP-Nonce': C.nonce, Accept: 'application/json' } };
		if (opts.body) {
			init.headers['Content-Type'] = 'application/json';
			init.body = JSON.stringify(opts.body);
		}
		return fetch(url, init).then(function (r) {
			return r.json().catch(function () { return null; }).then(function (data) {
				if (!r.ok) {
					if (r.status === 401 || r.status === 403 && data && /nonce|cookie/i.test(data.code || '')) sessionExpired();
					throw new Error((data && data.message) || t('Ralat pelayan ({status}). Sila cuba lagi.', { status: r.status }));
				}
				return data;
			});
		}, function () {
			throw new Error(t('Tiada sambungan ke pelayan. Semak rangkaian anda.'));
		});
	}

	var expiredShown = false;
	function sessionExpired() {
		if (expiredShown) return;
		expiredShown = true;
		openModal({
			title: t('Sesi tamat'),
			content: h('p', null, t('Sesi anda telah tamat atau anda telah log keluar. Sila log masuk semula untuk meneruskan.')),
			submitLabel: t('Log masuk semula'),
			cancelLabel: null,
			onSubmit: function () { location.reload(); return false; }
		});
	}

	function fileUrl(path, dl) {
		return withQuery(C.urls.file, { path: path, dl: dl ? 1 : null });
	}
	function thumbUrl(item) {
		return withQuery(C.urls.thumb, { path: item.path, v: item.modified });
	}

	function download(items) {
		if (!items.length) return;
		if (items.length === 1 && items[0].type === 'file') {
			var a = h('a', { href: fileUrl(items[0].path, true), download: '' });
			document.body.append(a);
			a.click();
			a.remove();
			return;
		}
		// Zip: hantar senarai melalui POST. Senarai panjang dalam URL boleh melebihi had
		// panjang URL pelayan (ralat 414). Respons ialah lampiran, jadi halaman tidak bertukar.
		var form = h('form', { method: 'post', action: C.urls.zip, style: 'display:none' },
			h('input', { type: 'hidden', name: '_pdnonce', value: C.zipNonce }));
		items.forEach(function (i) { form.append(h('input', { type: 'hidden', name: 'paths[]', value: i.path })); });
		document.body.append(form);
		form.submit();
		form.remove();
		toast(t('Menyediakan fail zip… muat turun akan bermula sebentar lagi.'));
	}

	// =================================================================== toast, modal, menu

	function toast(msg, opts) {
		opts = opts || {};
		var box = document.getElementById('pd-toasts');
		var el = h('div', { class: 'pd-toast' + (opts.error ? ' is-error' : ''), role: opts.error ? 'alert' : 'status' }, h('span', null, msg));
		if (opts.action) {
			el.append(h('button', { type: 'button', onclick: function () { el.remove(); opts.action.fn(); } }, opts.action.label));
		}
		box.append(el);
		setTimeout(function () { el.remove(); }, opts.error ? 6000 : 4000);
	}

	function openModal(o) {
		var back = h('div', { class: 'pd-modal-back' });
		var err = h('div', { class: 'pd-alert pd-alert--error', role: 'alert', hidden: true });
		var submitBtn = o.submitLabel ? h('button', { type: 'submit', class: 'pd-btn pd-btn--' + (o.submitKind || 'primary') }, o.submitLabel) : null;
		var cancel = o.cancelLabel === null ? null : h('button', { type: 'button', class: 'pd-btn', onclick: function () { close(); } }, o.cancelLabel || t('Batal'));
		var form = h('form', { class: 'pd-modal' + (o.wide ? ' pd-modal--wide' : ''), novalidate: true, role: 'dialog', 'aria-modal': 'true', 'aria-label': o.title },
			h('div', { class: 'pd-modal__head' },
				h('h2', null, o.title),
				h('button', { type: 'button', class: 'pd-iconbtn', 'aria-label': t('Tutup'), onclick: function () { close(); } }, icon('x'))
			),
			h('div', { class: 'pd-modal__body' }, err, o.content),
			(cancel || submitBtn) ? h('div', { class: 'pd-modal__foot' }, cancel, submitBtn) : null
		);
		var busy = false;
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (!o.onSubmit) return close();
			if (busy) return;
			busy = true;
			if (submitBtn) { submitBtn.disabled = true; }
			err.hidden = true;
			Promise.resolve().then(function () { return o.onSubmit(form); }).then(function (r) {
				if (r !== false) close();
			}, function (x) {
				err.textContent = x.message;
				err.hidden = false;
			}).then(function () {
				busy = false;
				if (submitBtn) submitBtn.disabled = false;
			});
		});
		back.append(form);
		back.addEventListener('mousedown', function (e) { if (e.target === back && !o.sticky) close(); });
		var prev = document.activeElement;
		function onKey(e) {
			if (e.key === 'Escape' && layer.lastElementChild === back) { e.stopPropagation(); e.preventDefault(); close(); }
		}
		document.addEventListener('keydown', onKey, true);
		var closed = false;
		function close() {
			if (closed) return;
			closed = true;
			back.remove();
			document.removeEventListener('keydown', onKey, true);
			if (o.onClose) o.onClose();
			if (prev && prev.focus) prev.focus();
		}
		layer.append(back);
		setTimeout(function () {
			var f = form.querySelector('[autofocus]') || form.querySelector('.pd-modal__body input:not([type=hidden]):not([disabled]):not([type=checkbox]), .pd-modal__body select') || submitBtn || cancel;
			if (f) { f.focus(); if (f.select && f.dataset.stem) { f.setSelectionRange(0, Number(f.dataset.stem)); } }
		}, 30);
		return { close: close, form: form };
	}

	function confirmDialog(title, message, label, kind) {
		return new Promise(function (resolve) {
			var ok = false;
			openModal({
				title: title,
				content: h('p', null, message),
				submitLabel: label || t('Teruskan'),
				submitKind: kind || 'danger',
				onSubmit: function () { ok = true; },
				onClose: function () { resolve(ok); }
			});
		});
	}

	function field(label, input, hint) {
		return h('label', { class: 'pd-field' }, h('span', null, label), input, hint ? h('small', null, hint) : null);
	}

	var openMenuEl = null;
	var smallScreen = window.matchMedia('(max-width: 640px)');
	function closeMenu() {
		if (openMenuEl) {
			if (openMenuEl._back) openMenuEl._back.remove();
			openMenuEl.remove();
			openMenuEl = null;
		}
	}
	function showMenu(x, y, entries, title) {
		closeMenu();
		var sheet = smallScreen.matches;
		var m = h('div', { class: 'pd-menu' + (sheet ? ' pd-menu--sheet' : ''), role: 'menu' });
		if (sheet && title) m.append(h('div', { class: 'pd-menu__title' }, title));
		entries.forEach(function (en) {
			if (!en) return;
			if (en === '-') return m.append(h('hr'));
			m.append(h('button', { type: 'button', role: 'menuitem', class: en.danger ? 'is-danger' : '', onclick: function () { closeMenu(); en.fn(); } }, icon(en.icon), en.label));
		});
		if (sheet) {
			// Lapik penuh skrin supaya ketikan di luar helaian tidak tertekan item di bawahnya.
			m._back = h('div', { class: 'pd-sheet-back', onclick: function (e) { e.preventDefault(); closeMenu(); } });
			layer.append(m._back);
			m.append(h('button', { type: 'button', class: 'pd-menu__cancel', onclick: closeMenu }, t('Batal')));
		}
		layer.append(m);
		openMenuEl = m;
		if (!sheet) {
			var r = m.getBoundingClientRect();
			m.style.left = Math.max(8, Math.min(x, innerWidth - r.width - 8)) + 'px';
			m.style.top = Math.max(8, (y + r.height > innerHeight - 8 ? y - r.height : y)) + 'px';
			var first = m.querySelector('button');
			if (first) first.focus();
		}
	}
	document.addEventListener('mousedown', function (e) { if (openMenuEl && !openMenuEl._back && !openMenuEl.contains(e.target)) closeMenu(); });
	document.addEventListener('keydown', function (e) {
		if (!openMenuEl) return;
		if (e.key === 'Escape') { closeMenu(); e.stopPropagation(); }
		if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
			var btns = Array.prototype.slice.call(openMenuEl.querySelectorAll('button'));
			var i = btns.indexOf(document.activeElement);
			btns[(i + (e.key === 'ArrowDown' ? 1 : btns.length - 1)) % btns.length].focus();
			e.preventDefault();
		}
	}, true);
	window.addEventListener('scroll', function (e) { if (!openMenuEl || !openMenuEl.contains(e.target)) closeMenu(); }, true);
	window.addEventListener('resize', closeMenu);

	// =================================================================== storan (pref)

	function pref(key, val) {
		try {
			if (val === undefined) return localStorage.getItem('pd_' + key);
			localStorage.setItem('pd_' + key, val);
		} catch (e) { return null; }
	}

	// =================================================================== sidebar

	function renderNav() {
		var nav = clear(document.getElementById('pd-nav'));
		var items = [
			['#/utama', 'home', t('Halaman Utama')],
			['#/fail', 'folder', t('Semua Fail')],
			['#/sampah', 'trash', t('Tong Sampah')],
			['#/aktiviti', 'activity', C.canManage ? t('Log Aktiviti') : t('Aktiviti Saya')]
		];
		items.forEach(function (it) { nav.append(h('a', { href: it[0], 'data-nav': it[0] }, icon(it[1], 19), it[2])); });
		var tabs = clear(document.getElementById('pd-tabbar'));
		var short = { '#/utama': t('Utama'), '#/fail': t('Fail'), '#/sampah': t('Sampah'), '#/aktiviti': t('Aktiviti') };
		items.forEach(function (it) { tabs.append(h('a', { href: it[0], 'data-nav': it[0] }, icon(it[1], 22), h('span', null, short[it[0]]))); });
		tabs.append(h('button', { type: 'button', 'aria-label': t('Menu lain'), onclick: openNav }, icon('menu', 22), h('span', null, t('Lagi'))));
		if (C.canManage) {
			nav.append(h('div', { class: 'pd-nav__label' }, t('Pentadbiran')));
			nav.append(h('a', { href: '#/pengguna', 'data-nav': '#/pengguna' }, icon('users', 19), t('Pengguna')));
			nav.append(h('a', { href: '#/tetapan', 'data-nav': '#/tetapan' }, icon('sliders', 19), t('Tetapan')));
			if (C.isWpAdmin) nav.append(h('a', { href: C.urls.wpAdmin, target: '_blank', rel: 'noopener' }, icon('external', 19), 'WordPress Admin'));
		}
	}

	function markNav(route) {
		document.querySelectorAll('#pd-nav a[data-nav], #pd-tabbar a[data-nav]').forEach(function (a) {
			var on = a.getAttribute('data-nav') === '#/' + route;
			a.classList.toggle('is-active', on);
			if (on) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
		});
	}

	function renderUserCard() {
		var u = C.user;
		clear(document.getElementById('pd-usercard')).append(
			h('span', { class: 'pd-avatar' }, u.initials),
			h('div', { class: 'pd-side__user-info' }, h('strong', null, u.name), h('small', null, u.role_label)),
			h('button', { type: 'button', class: 'pd-iconbtn', title: t('Profil saya'), 'aria-label': t('Profil saya'), onclick: openProfile }, icon('user')),
			h('a', { class: 'pd-iconbtn', href: C.urls.logout, title: t('Log keluar'), 'aria-label': t('Log keluar') }, icon('logout'))
		);
	}

	function renderLang() {
		var langs = C.langs || {};
		if (Object.keys(langs).length < 2) return;
		var box = document.getElementById('pd-lang');
		if (!box) {
			box = h('div', { class: 'pd-lang pd-lang--side', id: 'pd-lang', role: 'group', 'aria-label': t('Bahasa') });
			var card = document.getElementById('pd-usercard');
			card.parentNode.insertBefore(box, card);
		}
		clear(box);
		Object.keys(langs).forEach(function (code) {
			var on = code === C.lang;
			box.append(h('button', { type: 'button', class: on ? 'is-on' : '', lang: code, 'aria-pressed': String(on), onclick: function () {
				if (on) return;
				if (up.active && !window.confirm(t('Muat naik masih berjalan.'))) return;
				location.href = withQuery(C.urls.lang, { pd_lang: code }) + location.hash;
			} }, langs[code]));
		});
	}

	var storageTimer = null;
	function refreshStorage(data) {
		if (data) return paintStorage(data);
		clearTimeout(storageTimer);
		// /storage hanya membaca kiraan ruang yang disimpan, tanpa mengimbas semua fail.
		storageTimer = setTimeout(function () { api('/storage').then(paintStorage, function () {}); }, 400);
	}
	function paintStorage(d) {
		var box = clear(document.getElementById('pd-storage'));
		var pct = d.quota ? Math.min(100, d.size / d.quota * 100) : 0;
		box.append(
			h('strong', null, t('Storan')),
			h('p', null, d.quota ? t('{used} daripada {quota}', { used: fmtSize(d.size), quota: fmtSize(d.quota) }) : t('{used} digunakan', { used: fmtSize(d.size) }) + (d.files !== undefined ? ' · ' + nFiles(d.files) : ''))
		);
		if (d.quota) {
			box.append(h('div', { class: 'pd-meter' + (pct >= 95 ? ' is-full' : pct >= 80 ? ' is-warn' : ''), role: 'meter', 'aria-valuemin': 0, 'aria-valuemax': 100, 'aria-valuenow': Math.round(pct), 'aria-label': t('Penggunaan storan') }, h('span', { style: 'width:' + pct.toFixed(1) + '%' })));
		}
	}

	function openNav() { appEl.classList.add('is-nav-open'); }
	function closeNav() { appEl.classList.remove('is-nav-open'); }
	document.getElementById('pd-menu-btn').addEventListener('click', openNav);
	document.getElementById('pd-scrim').addEventListener('click', closeNav);
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && appEl.classList.contains('is-nav-open')) closeNav(); });
	// Leret ke kiri untuk menutup menu sisi.
	(function () {
		var side = document.getElementById('pd-side'), sx = null, sy = 0;
		side.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; sy = e.touches[0].clientY; }, { passive: true });
		side.addEventListener('touchend', function (e) {
			if (sx === null) return;
			var tc = e.changedTouches[0], dx = tc.clientX - sx, dy = Math.abs(tc.clientY - sy);
			if (dx < -60 && dy < 50) closeNav();
			sx = null;
		}, { passive: true });
	})();

	// =================================================================== router

	var current = { route: null, cleanup: null };

	function router() {
		closeMenu();
		appEl.classList.remove('is-nav-open');
		var hash = location.hash.replace(/^#\/?/, '');
		var parts = hash.split('/');
		var route = parts.shift() || 'utama';
		if (route === 'papan') { history.replaceState(null, '', '#/utama'); route = 'utama'; } // pautan lama
		if (current.cleanup) { current.cleanup(); current.cleanup = null; }
		var views = { utama: viewDashboard, fail: viewFiles, sampah: viewTrash, aktiviti: viewActivity };
		if (C.canManage) { views.pengguna = viewUsers; views.tetapan = viewSettings; }
		if (!views[route]) { location.hash = '#/utama'; return; }
		markNav(route);
		current.route = route;
		var path = parts.map(function (p) { try { return decodeURIComponent(p); } catch (e) { return p; } }).filter(Boolean).join('/');
		views[route](path);
		viewEl.focus({ preventScroll: true });
	}

	function folderHash(path) {
		return '#/fail' + (path ? '/' + path.split('/').map(encodeURIComponent).join('/') : '');
	}

	function loading() {
		clear(viewEl).append(h('div', { class: 'pd-loading' }, h('span', { class: 'pd-spinner', 'aria-label': t('Memuatkan') })));
	}

	function errorView(msg, retry) {
		clear(viewEl).append(h('div', { class: 'pd-card' }, h('div', { class: 'pd-empty' },
			h('h3', null, t('Tidak dapat memuatkan')), h('p', null, msg),
			retry ? h('button', { class: 'pd-btn', type: 'button', onclick: retry }, icon('refresh'), t('Cuba lagi')) : null)));
	}

	function pageHead(title, sub, actions) {
		return h('div', { class: 'pd-head' },
			h('div', null, h('h1', null, title), sub ? h('p', null, sub) : null),
			actions ? h('div', { class: 'pd-head__actions' }, actions) : null);
	}

	// =================================================================== halaman utama

	function viewDashboard() {
		loading();
		document.title = t('Halaman Utama') + ' · ' + C.brand.title;
		api('/dashboard').then(function (d) {
			if (current.route !== 'utama') return;
			paintStorage(d);
			var pct = d.quota ? Math.min(100, d.size / d.quota * 100) : 0;
			var stats = h('div', { class: 'pd-stats' },
				stat('file', t('Jumlah fail'), fmtNum(d.files), t('{n} folder', { n: d.folders }).replace(String(d.folders), fmtNum(d.folders))),
				h('div', { class: 'pd-card pd-stat' },
					h('div', { class: 'pd-stat__label' }, icon('drive', 16), t('Ruang digunakan')),
					h('div', { class: 'pd-stat__value' }, fmtSize(d.size)),
					h('div', { class: 'pd-stat__sub' }, d.quota ? t('{pct}% daripada kuota {quota}', { pct: Math.round(pct), quota: fmtSize(d.quota) }) : t('Tiada had kuota ditetapkan')),
					d.quota ? h('div', { class: 'pd-meter pd-meter--light' + (pct >= 95 ? ' is-full' : pct >= 80 ? ' is-warn' : ''), role: 'meter', 'aria-valuemin': 0, 'aria-valuemax': 100, 'aria-valuenow': Math.round(pct), 'aria-label': t('Penggunaan kuota') }, h('span', { style: 'width:' + pct.toFixed(1) + '%' })) : null
				),
				stat('upload', t('Muat naik minggu ini'), fmtNum(d.uploads_week), t('7 hari lepas')),
				stat('users', t('Pengguna aktif'), fmtNum(d.users), t('mempunyai akses portal'))
			);

			var folders = h('div', { class: 'pd-folders' });
			if (!d.top.length) folders.append(h('p', { class: 'pd-note' }, t('Belum ada folder. Cipta folder pertama di Semua Fail.')));
			d.top.forEach(function (f) {
				folders.append(h('a', { class: 'pd-folder-card', href: folderHash(f.path) }, folderIcon(38),
					h('div', null, h('strong', null, f.name), h('small', null, nFiles(f.files) + ' · ' + fmtSize(f.size)))));
			});

			var recent = h('ul', { class: 'pd-list' });
			if (!d.recent.length) recent.append(h('li', { class: 'pd-note' }, t('Belum ada fail.')));
			d.recent.forEach(function (f, i) {
				recent.append(h('li', null, fileIcon(f, 28),
					h('div', { class: 'pd-list__main' },
						h('a', { href: '#', onclick: function (e) { e.preventDefault(); openPreview(d.recent, i); } }, f.name),
						h('small', null, '/' + parentOf(f.path))),
					h('span', { class: 'pd-list__aside' }, relTime(f.modified))));
			});

			var act = h('ul', { class: 'pd-list' });
			if (!d.activity.length) act.append(h('li', { class: 'pd-note' }, t('Belum ada aktiviti direkodkan.')));
			d.activity.forEach(function (a) {
				act.append(h('li', null, h('span', { class: 'pd-avatar', style: 'width:30px;height:30px;font-size:11.5px' }, initials(a.user_name)),
					h('div', { class: 'pd-list__main' },
						h('strong', null, a.user_name || t('Sistem'), h('span', { style: 'font-weight:400;color:var(--ink-2)' }, ' · ' + a.action_label.toLowerCase())),
						h('small', null, a.target || a.details)),
					h('span', { class: 'pd-list__aside' }, relTime(a.created_at))));
			});

			var kinds = h('ul', { class: 'pd-list pd-kinds' });
			var kk = Object.keys(d.kinds).sort(function (a, b) { return d.kinds[b].size - d.kinds[a].size; });
			if (!kk.length) kinds.append(h('li', { class: 'pd-note' }, t('Tiada fail.')));
			kk.forEach(function (k) {
				kinds.append(h('li', null, fileIcon({ type: 'file', kind: k }, 22),
					h('div', { class: 'pd-list__main' }, h('strong', null, t((KIND[k] || KIND.other).l))),
					h('span', { class: 'pd-list__aside' }, nFiles(d.kinds[k].count) + ' · ' + fmtSize(d.kinds[k].size))));
			});

			var warn = null;
			if (d.protection === 'exposed') {
				warn = h('div', { class: 'pd-alert pd-alert--error', role: 'alert', style: 'margin-bottom:20px' },
					h('strong', null, t('Amaran keselamatan: ')),
					t('fail dalam folder storan boleh dibuka terus melalui URL tanpa log masuk. Pelayan ini mengabaikan fail .htaccess (cth nginx atau AllowOverride None). Sekat capaian ke folder storan dalam konfigurasi pelayan. Sementara itu, muat naik fail .html, .svg dan .js disekat. '),
					h('button', { type: 'button', class: 'pd-btn pd-btn--sm', onclick: function (e) {
						var b = e.currentTarget; b.disabled = true;
						api('/protection', { method: 'POST' }).then(function (r) {
							if (r.protection === 'exposed') { toast(t('Masih terdedah.'), { error: true }); b.disabled = false; }
							else { toast(t('Storan kini dilindungi.')); warn.remove(); }
						}, function (x) { toast(x.message, { error: true }); b.disabled = false; });
					} }, t('Semak semula')));
			}
			clear(viewEl).append(
				warn || '',
				pageHead(greeting() + ', ' + C.user.name, C.brand.subtitle, [
					h('a', { class: 'pd-btn', href: '#/fail' }, icon('folder'), t('Buka fail')),
					h('button', { class: 'pd-btn pd-btn--primary', type: 'button', onclick: function () { pickFiles(''); } }, icon('upload'), t('Muat naik'))
				]),
				stats,
				h('div', { class: 'pd-card', style: 'margin-bottom:20px' },
					h('div', { class: 'pd-card__head' }, h('h2', null, t('Folder')), h('a', { href: '#/fail' }, t('Lihat semua'))),
					h('div', { class: 'pd-card__body' }, folders)),
				h('div', { class: 'pd-grid-2' },
					h('div', { class: 'pd-card' }, h('div', { class: 'pd-card__head' }, h('h2', null, t('Fail terkini'))), h('div', { class: 'pd-card__body' }, recent)),
					h('div', { class: 'pd-card' }, h('div', { class: 'pd-card__head' }, h('h2', null, t('Aktiviti terkini')), h('a', { href: '#/aktiviti' }, t('Semua'))), h('div', { class: 'pd-card__body' }, act))
				),
				h('div', { class: 'pd-card' }, h('div', { class: 'pd-card__head' }, h('h2', null, t('Jenis fail'))), h('div', { class: 'pd-card__body' }, kinds))
			);
		}, function (e) { errorView(e.message, viewDashboard); });
	}

	function stat(ic, label, value, sub) {
		return h('div', { class: 'pd-card pd-stat' },
			h('div', { class: 'pd-stat__label' }, icon(ic, 16), label),
			h('div', { class: 'pd-stat__value' }, value),
			h('div', { class: 'pd-stat__sub' }, sub));
	}

	/** "12 fail" / "12 files" dengan nombor diformat mengikut bahasa. */
	function nFiles(n) { return t('{n} fail', { n: n }).replace(String(n), fmtNum(n)); }
	function nItems(n) { return t('{n} item', { n: n }).replace(String(n), fmtNum(n)); }

	function initials(name) {
		var w = String(name || '?').trim().split(/\s+/).slice(0, 2);
		return w.map(function (x) { return x.charAt(0).toUpperCase(); }).join('') || '?';
	}

	// =================================================================== pengurus fail

	var fm = null; // keadaan paparan fail semasa

	function viewFiles(path) {
		fm = {
			path: path,
			items: [],
			selected: new Set(),
			anchor: null,
			mode: pref('view') === 'list' ? 'list' : 'grid',
			sort: (pref('sort') || 'name:asc').split(':'),
			search: '',
			results: null,
			loading: true
		};
		document.title = (path ? path.split('/').pop() : t('Semua Fail')) + ' · ' + C.brand.title;

		var fileInput = h('input', { type: 'file', multiple: true, hidden: true, onchange: function () { queueUploads(fileInput.files, fm.path); fileInput.value = ''; } });
		fm.els = {
			crumbs: h('nav', { class: 'pd-crumbs', 'aria-label': t('Lokasi') }),
			selbar: h('div', { class: 'pd-selbar', hidden: true }),
			body: h('div', { class: 'pd-fm__body' }),
			foot: h('div', { class: 'pd-fm__foot' }),
			modeBtns: {},
			fileInput: fileInput
		};
		var searchInput = h('input', { class: 'pd-input', type: 'search', placeholder: t('Cari dalam semua fail…'), 'aria-label': t('Cari fail') });
		var searchClear = h('button', { type: 'button', class: 'pd-iconbtn', 'aria-label': t('Kosongkan carian'), hidden: true, onclick: function () { searchInput.value = ''; onSearch(''); searchInput.focus(); } }, icon('x', 16));
		var st;
		searchInput.addEventListener('input', function () {
			clearTimeout(st);
			searchClear.hidden = !searchInput.value;
			st = setTimeout(function () { onSearch(searchInput.value.trim()); }, 300);
		});
		searchInput.addEventListener('keydown', function (e) { if (e.key === 'Escape' && searchInput.value) { e.stopPropagation(); searchInput.value = ''; onSearch(''); } });
		fm.els.searchInput = searchInput;

		['grid', 'list'].forEach(function (m) {
			fm.els.modeBtns[m] = h('button', { type: 'button', class: 'pd-iconbtn', title: m === 'grid' ? t('Paparan grid') : t('Paparan senarai'), 'aria-label': m === 'grid' ? t('Paparan grid') : t('Paparan senarai'), onclick: function () { setMode(m); } }, icon(m));
		});

		var card = h('div', { class: 'pd-card pd-fm' },
			h('div', { class: 'pd-fm__bar' },
				fm.els.crumbs,
				h('div', { class: 'pd-search' }, icon('search', 16), searchInput, searchClear),
				h('div', { class: 'pd-seg', role: 'group', 'aria-label': t('Jenis paparan') }, fm.els.modeBtns.grid, fm.els.modeBtns.list),
				h('button', { type: 'button', class: 'pd-iconbtn', title: t('Muat semula'), 'aria-label': t('Muat semula'), onclick: function () { loadDir(); } }, icon('refresh'))
			),
			fm.els.selbar,
			fm.els.body,
			fm.els.foot,
			fileInput
		);
		setupDrop(card);
		card.addEventListener('contextmenu', function (e) {
			if (e.target.closest('[data-idx]') || e.target.closest('input')) return;
			e.preventDefault();
			showMenu(e.clientX, e.clientY, [
				{ icon: 'upload', label: t('Muat naik fail'), fn: function () { fileInput.click(); } },
				{ icon: 'folderPlus', label: t('Folder baharu'), fn: newFolder },
				'-',
				{ icon: 'refresh', label: t('Muat semula'), fn: loadDir }
			]);
		});
		fm.els.body.addEventListener('click', function (e) { if (e.target === fm.els.body || e.target.classList.contains('pd-grid')) setSelection([]); });

		clear(viewEl).append(
			pageHead(t('Semua Fail'), C.brand.subtitle, [
				h('button', { type: 'button', class: 'pd-btn', onclick: newFolder }, icon('folderPlus'), t('Folder baharu')),
				h('button', { type: 'button', class: 'pd-btn pd-btn--primary', onclick: function () { fileInput.click(); } }, icon('upload'), t('Muat naik'))
			]),
			card
		);
		renderCrumbs();
		paintModeBtns();
		loadDir();

		document.addEventListener('keydown', fmKeys);
		current.cleanup = function () { document.removeEventListener('keydown', fmKeys); fm = null; };
	}

	function loadDir() {
		if (!fm) return;
		var myFm = fm;
		if (!fm.items.length) { clear(fm.els.body).append(h('div', { class: 'pd-loading' }, h('span', { class: 'pd-spinner' }))); }
		api('/files', { query: { path: fm.path } }).then(function (d) {
			if (fm !== myFm) return;
			fm.items = d.items;
			fm.loading = false;
			var keep = new Set();
			fm.selected.forEach(function (p) { if (d.items.some(function (i) { return i.path === p; })) keep.add(p); });
			fm.selected = keep;
			if (!fm.results) renderItems();
		}, function (e) {
			if (fm !== myFm) return;
			clear(fm.els.body).append(h('div', { class: 'pd-empty' }, h('h3', null, t('Tidak dapat membuka folder')), h('p', null, e.message),
				h('a', { class: 'pd-btn', href: '#/fail' }, t('Ke folder utama'))));
		});
	}

	function renderCrumbs() {
		var c = clear(fm.els.crumbs);
		var segs = fm.path ? fm.path.split('/') : [];
		var rootLabel = C.brand.title;
		if (fm.results) {
			c.append(h('a', { href: folderHash(fm.path), onclick: function (e) { e.preventDefault(); fm.els.searchInput.value = ''; onSearch(''); } }, rootLabel), sep(), h('span', { class: 'is-current' }, t('Hasil carian')));
			return;
		}
		if (!segs.length) { c.append(h('span', { class: 'is-current' }, rootLabel)); return; }
		c.append(h('a', { href: '#/fail' }, rootLabel));
		segs.forEach(function (s, i) {
			c.append(sep());
			if (i === segs.length - 1) c.append(h('span', { class: 'is-current', 'aria-current': 'page' }, s));
			else c.append(h('a', { href: folderHash(segs.slice(0, i + 1).join('/')) }, s));
		});
		setTimeout(function () { c.scrollLeft = c.scrollWidth; }, 0);
		function sep() { return h('span', { class: 'pd-crumbs__sep', 'aria-hidden': 'true' }, icon('chevR', 14)); }
	}

	function onSearch(q) {
		if (!fm) return;
		fm.search = q;
		if (q.length < 2) {
			fm.results = null;
			renderCrumbs();
			renderItems();
			return;
		}
		var myFm = fm;
		api('/search', { query: { q: q } }).then(function (d) {
			if (fm !== myFm || fm.search !== q) return;
			fm.results = d.items;
			fm.selected.clear();
			renderCrumbs();
			renderItems();
		}, function (e) { toast(e.message, { error: true }); });
	}

	function setMode(m) {
		fm.mode = m;
		pref('view', m);
		paintModeBtns();
		renderItems();
	}
	function paintModeBtns() {
		Object.keys(fm.els.modeBtns).forEach(function (m) {
			fm.els.modeBtns[m].classList.toggle('is-on', fm.mode === m);
			fm.els.modeBtns[m].setAttribute('aria-pressed', fm.mode === m);
		});
	}

	function sortedItems() {
		var list = (fm.results || fm.items).slice();
		var key = fm.sort[0], dir = fm.sort[1] === 'desc' ? -1 : 1;
		list.sort(function (a, b) {
			if (!fm.results && a.type !== b.type) return a.type === 'dir' ? -1 : 1;
			var r;
			if (key === 'size') r = (a.size || 0) - (b.size || 0);
			else if (key === 'modified') r = new Date(a.modified) - new Date(b.modified);
			else r = a.name.localeCompare(b.name, LOCALE, { numeric: true, sensitivity: 'base' });
			return r * dir || a.name.localeCompare(b.name, LOCALE, { numeric: true });
		});
		return list;
	}

	function setSort(key) {
		var dir = fm.sort[0] === key && fm.sort[1] === 'asc' ? 'desc' : 'asc';
		fm.sort = [key, dir];
		pref('sort', key + ':' + dir);
		renderItems();
	}

	function renderItems() {
		if (!fm) return;
		var body = clear(fm.els.body);
		var list = sortedItems();
		fm.view = list;
		if (fm.loading && !fm.results) {
			body.append(h('div', { class: 'pd-loading' }, h('span', { class: 'pd-spinner' })));
			return;
		}
		if (!list.length) {
			body.append(fm.results
				? h('div', { class: 'pd-empty' }, icon('search', 44), h('h3', null, t('Tiada padanan')), h('p', null, t('Tiada fail atau folder bernama "{q}".', { q: fm.search })))
				: h('div', { class: 'pd-empty' }, icon('folder', 48), h('h3', null, t('Folder ini kosong')), h('p', null, t('Seret dan lepas fail ke sini, atau gunakan butang di bawah.')),
					h('div', { style: 'display:flex;gap:8px;justify-content:center;flex-wrap:wrap' },
						h('button', { type: 'button', class: 'pd-btn', onclick: newFolder }, icon('folderPlus'), t('Folder baharu')),
						h('button', { type: 'button', class: 'pd-btn pd-btn--primary', onclick: function () { fm.els.fileInput.click(); } }, icon('upload'), t('Muat naik fail')))));
		} else if (fm.mode === 'grid') {
			var grid = h('div', { class: 'pd-grid', role: 'listbox', 'aria-multiselectable': 'true', 'aria-label': t('Fail dan folder') });
			list.forEach(function (it, i) { grid.append(tile(it, i)); });
			body.append(grid);
		} else {
			body.append(h('div', { class: 'pd-table-wrap' }, table(list)));
		}
		renderFoot();
		renderSelbar();
	}

	function tile(it, i) {
		var thumb = h('div', { class: 'pd-tile__thumb' });
		if (it.kind === 'image') {
			var img = h('img', { src: thumbUrl(it), alt: '', loading: 'lazy', decoding: 'async' });
			img.addEventListener('error', function () { clear(thumb).append(fileIcon(it, 64)); });
			thumb.append(img);
		} else {
			thumb.append(it.type === 'dir' ? folderIcon(72) : fileIcon(it, 64));
		}
		var el = h('div', { class: 'pd-tile' + (fm.selected.has(it.path) ? ' is-selected' : ''), 'data-idx': i, role: 'option', tabindex: 0, 'aria-selected': fm.selected.has(it.path), title: it.name },
			thumb,
			h('div', { class: 'pd-tile__name' }, it.name),
			h('div', { class: 'pd-tile__meta' }, it.type === 'dir' ? nItems(it.children) : fmtSize(it.size)),
			h('button', { type: 'button', class: 'pd-iconbtn pd-tile__more', 'aria-label': t('Tindakan untuk {name}', { name: it.name }), onclick: function (e) { e.stopPropagation(); itemMenu(it, e.currentTarget); } }, icon('more', 16))
		);
		bindItem(el, it, i);
		return el;
	}

	function table(list) {
		var showLoc = !!fm.results;
		function th(key, label, cls) {
			var on = fm.sort[0] === key;
			return h('th', { class: cls || '', 'aria-sort': on ? (fm.sort[1] === 'asc' ? 'ascending' : 'descending') : null },
				h('button', { type: 'button', onclick: function () { setSort(key); } }, label, on ? icon(fm.sort[1] === 'asc' ? 'arrowUp' : 'arrowDown', 13) : null));
		}
		var tbody = h('tbody');
		list.forEach(function (it, i) {
			var tr = h('tr', { class: 'pd-row-file' + (fm.selected.has(it.path) ? ' is-selected' : ''), 'data-idx': i, tabindex: 0, 'aria-selected': fm.selected.has(it.path) },
				h('td', null, h('div', { class: 'pd-namecell' }, fileIcon(it, 26), h('span', { title: it.name }, it.name))),
				showLoc ? h('td', { class: 'pd-muted pd-col-hide' }, '/' + parentOf(it.path)) : null,
				h('td', { class: 'pd-num pd-muted' }, it.type === 'dir' ? nItems(it.children) : fmtSize(it.size)),
				h('td', { class: 'pd-muted pd-col-hide' }, fmtDate(it.modified)),
				h('td', { class: 'pd-actions' }, h('button', { type: 'button', class: 'pd-iconbtn', 'aria-label': t('Tindakan untuk {name}', { name: it.name }), onclick: function (e) { e.stopPropagation(); itemMenu(it, e.currentTarget); } }, icon('more', 16)))
			);
			bindItem(tr, it, i);
			tbody.append(tr);
		});
		return h('table', { class: 'pd-table' },
			h('thead', null, h('tr', null,
				th('name', t('Nama')),
				showLoc ? h('th', { class: 'pd-col-hide' }, t('Lokasi')) : null,
				th('size', t('Saiz'), 'pd-num'),
				th('modified', t('Diubah'), 'pd-col-hide'),
				h('th', null, h('span', { class: 'pd-sr' }, t('Tindakan')))
			)),
			tbody);
	}

	var touchUI = window.matchMedia('(hover: none)').matches;

	function bindItem(el, it, i) {
		el.addEventListener('click', function (e) {
			if (e.ctrlKey || e.metaKey) { toggleSel(it.path); fm.anchor = i; return; }
			if (e.shiftKey && fm.anchor !== null) {
				var a = Math.min(fm.anchor, i), b = Math.max(fm.anchor, i);
				setSelection(fm.view.slice(a, b + 1).map(function (x) { return x.path; }));
				return;
			}
			if (touchUI) {
				if (fm.selected.size) { toggleSel(it.path); return; }
				openItem(it);
				return;
			}
			fm.anchor = i;
			setSelection([it.path]);
		});
		el.addEventListener('dblclick', function () { openItem(it); });
		el.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') { e.preventDefault(); openItem(it); }
			if (e.key === ' ') { e.preventDefault(); toggleSel(it.path); fm.anchor = i; }
		});
		el.addEventListener('contextmenu', function (e) {
			e.preventDefault();
			if (touchUI) return; // tekan lama = pilih (dikendalikan oleh touchstart)
			if (!fm.selected.has(it.path)) { fm.anchor = i; setSelection([it.path]); }
			if (fm.selected.size > 1) selectionMenu(e.clientX, e.clientY);
			else itemMenu(it, null, e.clientX, e.clientY);
		});
		var pressTimer;
		el.addEventListener('touchstart', function () {
			pressTimer = setTimeout(function () { toggleSel(it.path); pressTimer = 'fired'; }, 500);
		}, { passive: true });
		el.addEventListener('touchend', function (e) {
			if (pressTimer === 'fired') e.preventDefault();
			clearTimeout(pressTimer);
			pressTimer = null;
		});
		el.addEventListener('touchmove', function () { clearTimeout(pressTimer); }, { passive: true });
	}

	function toggleSel(path) {
		var s = new Set(fm.selected);
		if (s.has(path)) s.delete(path); else s.add(path);
		setSelection(Array.from(s));
	}

	function setSelection(paths) {
		if (!fm) return;
		fm.selected = new Set(paths);
		fm.els.body.querySelectorAll('[data-idx]').forEach(function (el) {
			var it = fm.view[Number(el.getAttribute('data-idx'))];
			var on = it && fm.selected.has(it.path);
			el.classList.toggle('is-selected', !!on);
			el.setAttribute('aria-selected', !!on);
		});
		renderSelbar();
	}

	function selectedItems() {
		var src = fm.results || fm.items;
		return src.filter(function (i) { return fm.selected.has(i.path); });
	}

	function renderSelbar() {
		var bar = fm.els.selbar;
		var n = fm.selected.size;
		bar.hidden = n === 0;
		if (!n) return;
		var items = selectedItems();
		clear(bar).append(
			h('strong', null, t('{n} dipilih', { n: n })),
			n === 1 && items[0] ? h('button', { type: 'button', class: 'pd-btn pd-btn--sm', 'aria-label': t('Nama semula'), onclick: function () { renameItem(items[0]); } }, icon('edit', 15), h('span', { class: 'pd-btn__label' }, t('Nama semula'))) : null,
			h('button', { type: 'button', class: 'pd-btn pd-btn--sm', 'aria-label': t('Muat turun'), onclick: function () { download(selectedItems()); } }, icon('download', 15), h('span', { class: 'pd-btn__label' }, t('Muat turun'))),
			h('button', { type: 'button', class: 'pd-btn pd-btn--sm pd-btn--danger-soft', 'aria-label': t('Padam'), onclick: function () { deleteItems(selectedItems()); } }, icon('trash', 15), h('span', { class: 'pd-btn__label' }, t('Padam'))),
			h('button', { type: 'button', class: 'pd-iconbtn', 'aria-label': t('Batal pilihan'), onclick: function () { setSelection([]); } }, icon('x'))
		);
	}

	function renderFoot() {
		var list = fm.results || fm.items;
		var files = list.filter(function (i) { return i.type === 'file'; });
		var size = files.reduce(function (s, i) { return s + (i.size || 0); }, 0);
		clear(fm.els.foot).append(
			h('span', null, nItems(list.length) + (files.length ? ' · ' + fmtSize(size) : '')),
			h('span', { class: 'pd-col-hide' }, fm.results ? t('Carian merangkumi semua folder') : t('Seret & lepas fail ke sini untuk muat naik'))
		);
	}

	function itemMenu(it, anchorEl, x, y) {
		if (anchorEl) {
			var r = anchorEl.getBoundingClientRect();
			x = r.right - 200; y = r.bottom + 4;
			// Pada peranti sentuh, membuka menu tidak memilih item (memilih = mod pilihan berbilang).
			if (!fm.selected.has(it.path) && !touchUI) setSelection([it.path]);
		}
		var entries = [
			{ icon: it.type === 'dir' ? 'folder' : 'eye', label: it.type === 'dir' ? t('Buka') : t('Pratonton'), fn: function () { openItem(it); } },
			fm.results ? { icon: 'folder', label: t('Buka lokasi'), fn: function () { location.hash = folderHash(parentOf(it.path)); } } : null,
			{ icon: 'download', label: it.type === 'dir' ? t('Muat turun (zip)') : t('Muat turun'), fn: function () { download([it]); } },
			{ icon: 'edit', label: t('Nama semula'), fn: function () { renameItem(it); } },
			{ icon: 'copy', label: t('Salin pautan'), fn: function () { copyLink(it); } },
			touchUI ? { icon: 'check', label: fm.selected.has(it.path) ? t('Nyahpilih') : t('Pilih'), fn: function () { toggleSel(it.path); } } : null,
			'-',
			{ icon: 'trash', label: t('Padam'), danger: true, fn: function () { deleteItems([it]); } }
		];
		showMenu(x, y, entries, it.name);
	}

	function selectionMenu(x, y) {
		showMenu(x, y, [
			{ icon: 'download', label: t('Muat turun {n} item (zip)', { n: fm.selected.size }), fn: function () { download(selectedItems()); } },
			'-',
			{ icon: 'trash', label: t('Padam {n} item', { n: fm.selected.size }), danger: true, fn: function () { deleteItems(selectedItems()); } }
		], t('{n} item dipilih', { n: fm.selected.size }));
	}

	function copyLink(it) {
		var url = it.type === 'dir' ? location.origin + location.pathname + folderHash(it.path) : new URL(fileUrl(it.path), location.href).href;
		var done = function () { toast(t('Pautan disalin. Hanya pengguna yang log masuk boleh membukanya.')); };
		if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(url).then(done, function () { fallbackCopy(url); done(); });
		else { fallbackCopy(url); done(); }
	}
	function fallbackCopy(text) {
		var ta = h('textarea', { style: 'position:fixed;opacity:0' });
		ta.value = text;
		document.body.append(ta);
		ta.select();
		try { document.execCommand('copy'); } catch (e) { /* abai */ }
		ta.remove();
	}

	function openItem(it) {
		if (it.type === 'dir') { location.hash = folderHash(it.path); return; }
		var files = (fm ? fm.view : [it]).filter(function (x) { return x.type === 'file'; });
		openPreview(files, Math.max(0, files.indexOf(it)));
	}

	function fmKeys(e) {
		if (!fm || layer.querySelector('.pd-modal-back, .pd-preview') || openMenuEl) return;
		var tag = (e.target.tagName || '').toLowerCase();
		if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
		var items = selectedItems();
		if (e.key === 'Delete' && items.length) { e.preventDefault(); deleteItems(items); }
		else if (e.key === 'F2' && items.length === 1) { e.preventDefault(); renameItem(items[0]); }
		else if (e.key === 'Escape' && items.length) { setSelection([]); }
		else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'a' && fm.view && fm.view.length) {
			e.preventDefault();
			setSelection(fm.view.map(function (i) { return i.path; }));
		} else if (e.key === 'Backspace' && fm.path && !fm.results) {
			e.preventDefault();
			location.hash = folderHash(parentOf(fm.path));
		}
	}

	// ---------- tindakan

	function newFolder() {
		var path = fm ? fm.path : '';
		var input = h('input', { type: 'text', name: 'name', value: t('Folder baharu'), maxlength: 150, required: true, autocomplete: 'off' });
		input.dataset.stem = t('Folder baharu').length;
		openModal({
			title: t('Folder baharu'),
			content: field(t('Nama folder'), input),
			submitLabel: t('Cipta'),
			onSubmit: function () {
				return api('/folder', { method: 'POST', body: { path: path, name: input.value } }).then(function (item) {
					toast(t('Folder "{name}" dicipta.', { name: item.name }));
					if (fm && fm.path === path) { loadDir(); }
					refreshStorage();
				});
			}
		});
	}

	function renameItem(it) {
		var dot = it.type === 'file' ? it.name.lastIndexOf('.') : -1;
		var input = h('input', { type: 'text', name: 'name', value: it.name, maxlength: 150, required: true, autocomplete: 'off' });
		input.dataset.stem = dot > 0 ? dot : it.name.length;
		openModal({
			title: t('Nama semula'),
			content: field(t('Nama baharu'), input, it.type === 'file' ? t('Kekalkan sambungan fail (cth .pdf) supaya fail boleh dibuka dengan betul.') : null),
			submitLabel: t('Simpan'),
			onSubmit: function () {
				return api('/rename', { method: 'POST', body: { path: it.path, name: input.value } }).then(function () {
					toast(t('Nama telah ditukar.'));
					if (fm) {
						fm.selected.clear();
						if (fm.results) onSearch(fm.search); else loadDir();
					}
				});
			}
		});
	}

	function deleteItems(items) {
		if (!items.length) return;
		var label = items.length === 1 ? '"' + items[0].name + '"' : nItems(items.length);
		var hasDir = items.some(function (i) { return i.type === 'dir'; });
		confirmDialog(t('Padam {label}?', { label: label }),
			(hasDir ? t('Folder akan dipadam bersama semua kandungannya.') + ' ' : '') + (C.settings.trashDays ? t('Item akan dipindahkan ke Tong Sampah dan boleh dipulihkan dalam tempoh {n} hari.', { n: C.settings.trashDays }) : t('Item akan dipindahkan ke Tong Sampah.')),
			t('Padam')
		).then(function (ok) {
			if (!ok) return;
			api('/delete', { method: 'POST', body: { paths: items.map(function (i) { return i.path; }) } }).then(function (r) {
				toast(t('{n} item dipindahkan ke Tong Sampah.', { n: r.deleted.length }), { action: { label: t('Lihat'), fn: function () { location.hash = '#/sampah'; } } });
				if (r.errors && r.errors.length) toast(r.errors[0], { error: true });
				if (fm) { fm.selected.clear(); if (fm.results) onSearch(fm.search); else loadDir(); }
				refreshStorage();
			}, function (e) { toast(e.message, { error: true }); });
		});
	}

	// ---------- muat naik

	// batchStart: indeks pertama kumpulan muat naik semasa dalam up.list. Panel menyimpan semua
	// fail sehingga ditutup, tetapi notifikasi hanya mengira kumpulan terkini.
	var up = { list: [], active: 0, batchStart: 0, el: null, listEl: null, titleEl: null, reloadTimer: null };

	function pickFiles(path) {
		var inp = h('input', { type: 'file', multiple: true });
		inp.addEventListener('change', function () { queueUploads(inp.files, path); });
		inp.click();
	}

	function setupDrop(card) {
		var depth = 0, overlay = null;
		function hasFiles(e) { return e.dataTransfer && Array.prototype.indexOf.call(e.dataTransfer.types || [], 'Files') >= 0; }
		card.addEventListener('dragenter', function (e) {
			if (!hasFiles(e)) return;
			e.preventDefault();
			depth++;
			if (!overlay) {
				overlay = h('div', { class: 'pd-drop' }, h('div', null, icon('upload', 32), h('div', null, t('Lepaskan untuk muat naik ke')), h('div', null, '/' + (fm ? fm.path : ''))));
				card.append(overlay);
			}
		});
		card.addEventListener('dragover', function (e) { if (hasFiles(e)) { e.preventDefault(); e.dataTransfer.dropEffect = 'copy'; } });
		card.addEventListener('dragleave', function () { depth--; if (depth <= 0 && overlay) { overlay.remove(); overlay = null; depth = 0; } });
		card.addEventListener('drop', function (e) {
			if (!hasFiles(e)) return;
			e.preventDefault();
			depth = 0;
			if (overlay) { overlay.remove(); overlay = null; }
			var files = [], skippedDir = false;
			var items = e.dataTransfer.items;
			if (items && items.length && items[0].webkitGetAsEntry) {
				for (var i = 0; i < items.length; i++) {
					var en = items[i].webkitGetAsEntry();
					if (en && en.isDirectory) { skippedDir = true; continue; }
					var f = items[i].getAsFile();
					if (f) files.push(f);
				}
			} else {
				files = Array.prototype.slice.call(e.dataTransfer.files);
			}
			if (skippedDir) toast(t('Muat naik folder tidak disokong. Cipta folder dahulu, kemudian muat naik fail ke dalamnya.'), { error: true });
			if (files.length) queueUploads(files, fm ? fm.path : '');
		});
	}

	function queueUploads(fileList, path) {
		var files = Array.prototype.slice.call(fileList || []);
		if (!files.length) return;
		// Barisan melahu: mulakan kumpulan baharu. Jika masih berjalan, fail baharu menyertai kumpulan itu.
		if (!up.active && !up.list.some(function (x) { return x.status === 'wait'; })) up.batchStart = up.list.length;
		files.forEach(function (f) { up.list.push({ file: f, path: path, status: 'wait', pct: 0, msg: '' }); });
		renderUploads();
		pump();
	}

	function pump() {
		while (up.active < 3) {
			var next = up.list.find(function (u) { return u.status === 'wait'; });
			if (!next) break;
			startUpload(next);
		}
		renderUploads();
	}

	function startUpload(u) {
		if (u.file.size > C.settings.maxUpload) {
			u.status = 'error';
			u.msg = t('Melebihi had {max}', { max: fmtSize(C.settings.maxUpload) });
			return;
		}
		up.active++;
		u.status = 'up';
		var fd = new FormData();
		fd.append('file', u.file, u.file.name);
		fd.append('path', u.path);
		var xhr = new XMLHttpRequest();
		xhr.open('POST', C.rest + '/upload');
		xhr.setRequestHeader('X-WP-Nonce', C.nonce);
		xhr.upload.addEventListener('progress', function (e) {
			if (e.lengthComputable) { u.pct = Math.round(e.loaded / e.total * 100); paintUpload(u); }
		});
		xhr.addEventListener('load', function () {
			var data = null;
			try { data = JSON.parse(xhr.responseText); } catch (e) { /* abai */ }
			if (xhr.status >= 200 && xhr.status < 300) { u.status = 'done'; u.msg = t('Selesai'); }
			else {
				u.status = 'error';
				u.msg = (data && data.message) || (xhr.status === 413 ? t('Fail terlalu besar untuk server.') : t('Gagal ({status})', { status: xhr.status }));
				if (xhr.status === 401) sessionExpired();
			}
			finish();
		});
		xhr.addEventListener('error', function () { u.status = 'error'; u.msg = t('Sambungan terputus.'); finish(); });
		xhr.send(fd);
		function finish() {
			up.active--;
			if (u.status === 'done' && fm && fm.path === u.path && !fm.results) {
				clearTimeout(up.reloadTimer);
				up.reloadTimer = setTimeout(loadDir, 250);
			}
			pump();
			if (!up.active && !up.list.some(function (x) { return x.status === 'wait'; })) {
				refreshStorage();
				var batch = up.list.slice(up.batchStart);
				var ok = batch.filter(function (x) { return x.status === 'done'; }).length;
				var bad = batch.filter(function (x) { return x.status === 'error'; }).length;
				if (current.route === 'utama') viewDashboard();
				toast(!bad ? t('{n} fail berjaya dimuat naik.', { n: ok }) : ok ? t('{n} fail dimuat naik, {bad} gagal.', { n: ok, bad: bad }) : t('{n} fail gagal dimuat naik.', { n: bad }), bad ? { error: true } : null);
			}
		}
	}

	function renderUploads() {
		if (!up.list.length) { if (up.el) { up.el.remove(); up.el = null; } return; }
		if (!up.el) {
			up.titleEl = h('strong');
			up.listEl = h('ul');
			up.el = h('div', { class: 'pd-uploads', role: 'region', 'aria-label': t('Muat naik') },
				h('div', { class: 'pd-uploads__head' }, up.titleEl,
					h('button', { type: 'button', class: 'pd-iconbtn', 'aria-label': t('Tutup'), onclick: function () {
						if (up.active) { toast(t('Muat naik masih berjalan.')); return; }
						up.list = []; up.batchStart = 0; renderUploads();
					} }, icon('x', 16))),
				up.listEl);
			document.body.append(up.el);
		}
		var done = up.list.filter(function (u) { return u.status === 'done' || u.status === 'error'; }).length;
		up.titleEl.textContent = done < up.list.length ? t('Memuat naik {done} / {total}', { done: done, total: up.list.length }) : t('Muat naik selesai ({n})', { n: up.list.length });
		up.list.forEach(function (u) {
			if (!u.el) {
				u.bar = h('span', { style: 'width:0%' });
				u.msgEl = h('small');
				u.el = h('li', null, h('div', null, h('span', { title: u.file.name }, u.file.name), u.msgEl), h('div', { class: 'pd-meter pd-meter--light' }, u.bar));
				up.listEl.append(u.el);
			}
			paintUpload(u);
		});
	}

	function paintUpload(u) {
		if (!u.el) return;
		u.el.className = u.status === 'error' ? 'is-error' : u.status === 'done' ? 'is-done' : '';
		u.msgEl.textContent = u.status === 'up' ? u.pct + '%' : u.status === 'wait' ? t('Menunggu') : u.msg;
		u.bar.style.width = (u.status === 'done' ? 100 : u.status === 'error' ? 0 : u.pct) + '%';
		u.bar.parentNode.hidden = u.status === 'done' || u.status === 'error';
	}

	window.addEventListener('beforeunload', function (e) {
		if (up.active) { e.preventDefault(); e.returnValue = ''; }
	});

	// ---------- pratonton

	function openPreview(files, idx) {
		var i = idx;
		var stage = h('div', { class: 'pd-preview__stage' });
		var titleName = h('strong'), titleMeta = h('small');
		var dlBtn = h('a', { class: 'pd-iconbtn', title: t('Muat turun'), 'aria-label': t('Muat turun') }, icon('download', 20));
		var prevBtn = h('button', { type: 'button', class: 'pd-iconbtn pd-preview__nav pd-preview__nav--prev', 'aria-label': t('Sebelumnya'), onclick: function () { go(-1); } }, icon('chevL', 22));
		var nextBtn = h('button', { type: 'button', class: 'pd-iconbtn pd-preview__nav pd-preview__nav--next', 'aria-label': t('Seterusnya'), onclick: function () { go(1); } }, icon('chevR', 22));
		var wrap = h('div', { class: 'pd-preview', role: 'dialog', 'aria-modal': 'true', 'aria-label': t('Pratonton fail') },
			h('div', { class: 'pd-preview__bar' },
				h('div', { class: 'pd-preview__title' }, titleName, titleMeta),
				dlBtn,
				h('button', { type: 'button', class: 'pd-iconbtn', 'aria-label': t('Tutup'), onclick: close }, icon('x', 22))),
			stage);
		layer.append(wrap);
		var prevFocus = document.activeElement;
		function onKey(e) {
			if (layer.lastElementChild !== wrap) return;
			if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(); }
			if (e.key === 'ArrowLeft') go(-1);
			if (e.key === 'ArrowRight') go(1);
		}
		document.addEventListener('keydown', onKey, true);
		function close() {
			wrap.remove();
			document.removeEventListener('keydown', onKey, true);
			if (prevFocus && prevFocus.focus) prevFocus.focus();
		}
		function go(d) {
			if (files.length < 2) return;
			i = (i + d + files.length) % files.length;
			show();
		}
		function show() {
			var f = files[i];
			titleName.textContent = f.name;
			titleMeta.textContent = fmtSize(f.size) + ' · ' + fmtDate(f.modified) + (files.length > 1 ? ' · ' + (i + 1) + ' / ' + files.length : '');
			dlBtn.href = fileUrl(f.path, true);
			dlBtn.setAttribute('download', '');
			prevBtn.hidden = nextBtn.hidden = files.length < 2;
			clear(stage);
			var src = fileUrl(f.path);
			var mime = f.mime || '';
			if (f.preview && mime.indexOf('image/') === 0) {
				stage.append(h('img', { src: src, alt: f.name }));
			} else if (f.preview && mime === 'application/pdf') {
				stage.append(h('iframe', { src: src, title: f.name }));
			} else if (f.preview && mime.indexOf('video/') === 0) {
				stage.append(h('video', { src: src, controls: true, autoplay: true, playsinline: true }));
			} else if (f.preview && mime.indexOf('audio/') === 0) {
				stage.append(h('audio', { src: src, controls: true, autoplay: true }));
			} else if (f.preview && mime === 'text/plain') {
				if (f.size > 1024 * 1024) { stage.append(noPreview(f, t('Fail teks ini terlalu besar untuk dipratonton.'))); }
				else {
					var pre = h('pre', null, t('Memuatkan…'));
					stage.append(pre);
					fetch(src, { credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (txt) { pre.textContent = txt; }, function () { pre.textContent = t('Tidak dapat memuatkan fail.'); });
				}
			} else {
				stage.append(noPreview(f, t('Pratonton tidak tersedia untuk jenis fail ini.')));
			}
			stage.append(prevBtn, nextBtn);
		}
		function noPreview(f, msg) {
			return h('div', { class: 'pd-preview__none' }, fileIcon(f, 96), h('p', null, msg),
				h('a', { class: 'pd-btn pd-btn--primary', href: fileUrl(f.path, true), download: '' }, icon('download'), t('Muat turun ({size})', { size: fmtSize(f.size) })));
		}
		show();
	}

	// =================================================================== tong sampah

	function viewTrash() {
		document.title = t('Tong Sampah') + ' · ' + C.brand.title;
		loading();
		api('/trash').then(function (d) {
			if (current.route !== 'sampah') return;
			var actions = [];
			if (C.canManage && d.items.length) {
				actions.push(h('button', { type: 'button', class: 'pd-btn', onclick: function () {
					confirmDialog(t('Kosongkan tong sampah?'), t('Semua {n} item akan dipadam secara kekal dan tidak boleh dipulihkan.', { n: d.items.length }), t('Padam kekal')).then(function (ok) {
						if (!ok) return;
						api('/trash/purge', { method: 'POST', body: { all: true } }).then(function () { toast(t('Tong sampah dikosongkan.')); viewTrash(); }, function (e) { toast(e.message, { error: true }); });
					});
				} }, icon('trash'), t('Kosongkan')));
			}
			var sub = d.days ? t('Item dipadam secara kekal selepas {n} hari.', { n: d.days }) : t('Item kekal di sini sehingga dipadam oleh pentadbir.');
			var body;
			if (!d.items.length) {
				body = h('div', { class: 'pd-empty' }, icon('trash', 44), h('h3', null, t('Tong sampah kosong')), h('p', null, t('Fail dan folder yang dipadam akan muncul di sini.')));
			} else {
				var tbody = h('tbody');
				d.items.forEach(function (it) {
					tbody.append(h('tr', null,
						h('td', null, h('div', { class: 'pd-namecell' }, fileIcon(it, 26), h('span', { title: it.name }, it.name))),
						h('td', { class: 'pd-muted pd-col-hide' }, it.location),
						h('td', { class: 'pd-muted pd-col-hide' }, it.deleted_by_name || '—'),
						h('td', { class: 'pd-muted' }, relTime(it.deleted_at)),
						h('td', { class: 'pd-num pd-muted pd-col-hide' }, fmtSize(it.size)),
						h('td', { class: 'pd-actions' },
							h('button', { type: 'button', class: 'pd-btn pd-btn--sm', onclick: function (e) { restore(it, e.currentTarget); } }, icon('restore', 15), t('Pulih')),
							C.canManage ? h('button', { type: 'button', class: 'pd-iconbtn', title: t('Padam kekal'), 'aria-label': t('Padam kekal {name}', { name: it.name }), style: 'margin-left:4px', onclick: function () { purge(it); } }, icon('x')) : null)
					));
				});
				body = h('div', { class: 'pd-table-wrap' }, h('table', { class: 'pd-table' },
					h('thead', null, h('tr', null, h('th', null, t('Nama')), h('th', { class: 'pd-col-hide' }, t('Lokasi asal')), h('th', { class: 'pd-col-hide' }, t('Dipadam oleh')), h('th', null, t('Dipadam')), h('th', { class: 'pd-num pd-col-hide' }, t('Saiz')), h('th', null, h('span', { class: 'pd-sr' }, t('Tindakan'))))),
					tbody));
			}
			clear(viewEl).append(pageHead(t('Tong Sampah'), sub, actions), h('div', { class: 'pd-card' }, body));
		}, function (e) { errorView(e.message, viewTrash); });

		function restore(it, btn) {
			btn.disabled = true;
			api('/trash/restore', { method: 'POST', body: { id: it.id } }).then(function (item) {
				toast(t('"{name}" dipulihkan.', { name: item.name }), { action: { label: t('Buka folder'), fn: function () { location.hash = folderHash(parentOf(item.path)); } } });
				refreshStorage();
				viewTrash();
			}, function (e) { btn.disabled = false; toast(e.message, { error: true }); });
		}
		function purge(it) {
			confirmDialog(t('Padam kekal?'), t('"{name}" akan dipadam secara kekal dan tidak boleh dipulihkan.', { name: it.name }), t('Padam kekal')).then(function (ok) {
				if (!ok) return;
				api('/trash/purge', { method: 'POST', body: { id: it.id } }).then(function () { toast(t('Dipadam secara kekal.')); viewTrash(); }, function (e) { toast(e.message, { error: true }); });
			});
		}
	}

	// =================================================================== log aktiviti

	function viewActivity() {
		document.title = (C.canManage ? t('Log Aktiviti') : t('Aktiviti Saya')) + ' · ' + C.brand.title;
		var state = { page: 1, action: '', user_id: '', search: '' };
		var tableBox = h('div', null, h('div', { class: 'pd-loading' }, h('span', { class: 'pd-spinner' })));
		var pager = h('div', { class: 'pd-pager' });
		var actionSel = h('select', { class: 'pd-input', 'aria-label': t('Tapis tindakan'), onchange: function () { state.action = actionSel.value; state.page = 1; load(); } }, h('option', { value: '' }, t('Semua tindakan')));
		var userSel = C.canManage ? h('select', { class: 'pd-input', 'aria-label': t('Tapis pengguna'), onchange: function () { state.user_id = userSel.value; state.page = 1; load(); } }, h('option', { value: '' }, t('Semua pengguna'))) : null;
		var search = h('input', { class: 'pd-input', type: 'search', placeholder: t('Cari fail, butiran atau nama…'), 'aria-label': t('Cari log') });
		var searchTimer;
		search.addEventListener('input', function () { clearTimeout(searchTimer); searchTimer = setTimeout(function () { state.search = search.value.trim(); state.page = 1; load(); }, 350); });

		clear(viewEl).append(
			pageHead(C.canManage ? t('Log Aktiviti') : t('Aktiviti Saya'), C.canManage ? t('Rekod log masuk dan semua tindakan ke atas fail.') : t('Rekod log masuk dan tindakan anda ke atas fail.')),
			h('div', { class: 'pd-card' },
				h('div', { class: 'pd-filters' }, h('div', { class: 'pd-search' }, icon('search', 16), search), userSel, actionSel),
				tableBox, pager)
		);
		if (userSel) {
			api('/users').then(function (d) { d.items.forEach(function (u) { userSel.append(h('option', { value: u.id }, u.name + ' (' + u.username + ')')); }); }, function () {});
		}
		var labelsLoaded = false;
		load();

		function load() {
			api('/activity', { query: state }).then(function (d) {
				if (current.route !== 'aktiviti') return;
				if (!labelsLoaded) {
					labelsLoaded = true;
					Object.keys(d.labels).forEach(function (k) {
						if (!C.canManage && /^(user_|settings|login_failed)/.test(k)) return;
						actionSel.append(h('option', { value: k }, d.labels[k]));
					});
				}
				clear(tableBox);
				if (!d.items.length) {
					tableBox.append(h('div', { class: 'pd-empty' }, icon('activity', 40), h('h3', null, t('Tiada rekod')), h('p', null, t('Tiada aktiviti yang sepadan dengan tapisan.'))));
				} else {
					var tbody = h('tbody');
					d.items.forEach(function (a) {
						tbody.append(h('tr', null,
							h('td', { class: 'pd-muted', title: fmtDate(a.created_at) }, fmtDate(a.created_at)),
							h('td', null, a.user_name || h('span', { class: 'pd-muted' }, '—')),
							h('td', null, h('span', { class: 'pd-badge ' + badgeFor(a.action) }, a.action_label)),
							h('td', { style: 'max-width:360px;overflow-wrap:anywhere' }, a.target || '', a.details ? h('div', { class: 'pd-note' }, a.details) : null),
							C.canManage ? h('td', { class: 'pd-muted pd-col-hide' }, a.ip || '') : null
						));
					});
					tableBox.append(h('div', { class: 'pd-table-wrap' }, h('table', { class: 'pd-table' },
						h('thead', null, h('tr', null, h('th', null, t('Masa')), h('th', null, t('Pengguna@lajur')), h('th', null, t('Tindakan@lajur')), h('th', null, t('Sasaran')), C.canManage ? h('th', { class: 'pd-col-hide' }, 'IP') : null)),
						tbody)));
				}
				clear(pager).append(
					h('span', null, t('{n} rekod · halaman {page} / {pages}', { n: d.total, page: d.page, pages: d.pages }).replace(String(d.total), fmtNum(d.total))),
					h('div', null,
						h('button', { type: 'button', class: 'pd-btn pd-btn--sm', disabled: d.page <= 1, onclick: function () { state.page--; load(); } }, icon('chevL', 15), t('Sebelum')),
						h('button', { type: 'button', class: 'pd-btn pd-btn--sm', disabled: d.page >= d.pages, onclick: function () { state.page++; load(); } }, t('Seterus'), icon('chevR', 15)))
				);
			}, function (e) { clear(tableBox).append(h('div', { class: 'pd-empty' }, h('p', null, e.message))); });
		}
	}

	function badgeFor(action) {
		if (action === 'delete' || action === 'purge' || action === 'login_failed') return 'pd-badge--danger';
		if (action === 'upload' || action === 'restore' || action === 'mkdir') return 'pd-badge--ok';
		if (action === 'login' || action === 'logout') return '';
		if (action === 'download') return 'pd-badge--brand';
		return 'pd-badge--warn';
	}

	// =================================================================== pengguna

	function viewUsers() {
		document.title = t('Pengguna') + ' · ' + C.brand.title;
		loading();
		var filter = '';
		var all = [];
		var tableBox = h('div');
		api('/users').then(function (d) {
			if (current.route !== 'pengguna') return;
			all = d.items;
			var search = h('input', { class: 'pd-input', type: 'search', placeholder: t('Cari nama, nama pengguna atau emel…'), 'aria-label': t('Cari pengguna') });
			search.addEventListener('input', function () { filter = search.value.trim().toLowerCase(); paint(); });
			clear(viewEl).append(
				pageHead(t('Pengguna'), t('Urus siapa boleh log masuk ke portal dan peranan mereka.'), [
					h('button', { type: 'button', class: 'pd-btn pd-btn--primary', onclick: addUser }, icon('plus'), t('Tambah pengguna'))
				]),
				h('div', { class: 'pd-card' }, h('div', { class: 'pd-filters' }, h('div', { class: 'pd-search' }, icon('search', 16), search)), tableBox)
			);
			paint();
		}, function (e) { errorView(e.message, viewUsers); });

		function paint() {
			var list = all.filter(function (u) {
				return !filter || (u.name + ' ' + u.username + ' ' + u.email).toLowerCase().indexOf(filter) >= 0;
			});
			var tbody = h('tbody');
			list.forEach(function (u) {
				tbody.append(h('tr', null,
					h('td', null, h('div', { class: 'pd-namecell' }, h('span', { class: 'pd-avatar' }, u.initials),
						h('div', { style: 'min-width:0' }, h('strong', { style: 'display:block' }, u.name), h('small', { class: 'pd-note' }, u.username)))),
					h('td', { class: 'pd-muted pd-col-hide' }, u.email),
					h('td', null, h('span', { class: 'pd-badge ' + (u.role === 'pd_staff' ? '' : u.role ? 'pd-badge--brand' : 'pd-badge--warn') }, u.role_label)),
					h('td', null, u.disabled ? h('span', { class: 'pd-badge pd-badge--danger' }, t('Tidak aktif')) : u.access ? h('span', { class: 'pd-badge pd-badge--ok' }, t('Aktif')) : h('span', { class: 'pd-badge' }, t('Tiada akses'))),
					h('td', { class: 'pd-muted pd-col-hide' }, u.last_login ? relTime(u.last_login) : t('Belum pernah')),
					h('td', { class: 'pd-actions' }, h('button', { type: 'button', class: 'pd-btn pd-btn--sm', onclick: function () { editUser(u); } }, icon('edit', 15), 'Edit'))
				));
			});
			clear(tableBox).append(list.length
				? h('div', { class: 'pd-table-wrap' }, h('table', { class: 'pd-table' },
					h('thead', null, h('tr', null, h('th', null, t('Pengguna@lajur')), h('th', { class: 'pd-col-hide' }, t('Emel')), h('th', null, t('Peranan')), h('th', null, t('Status')), h('th', { class: 'pd-col-hide' }, t('Log masuk terakhir')), h('th', null, h('span', { class: 'pd-sr' }, t('Tindakan'))))),
					tbody))
				: h('div', { class: 'pd-empty' }, h('p', null, t('Tiada pengguna sepadan.'))));
		}

		function roleSelect(value, disabled) {
			return h('select', { name: 'role', disabled: disabled },
				h('option', { value: 'pd_staff', selected: value === 'pd_staff' }, t('Staf: urus fail')),
				h('option', { value: 'pd_admin', selected: value === 'pd_admin' }, t('Pentadbir Portal: urus fail, pengguna & tetapan')),
				h('option', { value: 'none', selected: !value }, t('Tiada akses portal')));
		}

		function addUser() {
			var f = {
				name: h('input', { type: 'text', required: true, maxlength: 100, autocomplete: 'off' }),
				username: h('input', { type: 'text', required: true, maxlength: 60, autocomplete: 'off', autocapitalize: 'off', spellcheck: 'false' }),
				email: h('input', { type: 'email', required: true, autocomplete: 'off' }),
				role: roleSelect('pd_staff'),
				password: h('input', { type: 'text', autocomplete: 'new-password', placeholder: t('Biarkan kosong untuk jana automatik') }),
				folder: h('input', { type: 'checkbox', checked: true })
			};
			var touched = false;
			f.username.addEventListener('input', function () { touched = true; });
			f.name.addEventListener('input', function () {
				if (!touched) f.username.value = f.name.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '.').replace(/^\.|\.$/g, '').slice(0, 30);
			});
			openModal({
				title: t('Tambah pengguna'),
				wide: true,
				content: h('div', { class: 'pd-form-grid' },
					h('div', { class: 'pd-span-2' }, field(t('Nama penuh'), f.name)),
					field(t('Nama pengguna'), f.username, t('Digunakan untuk log masuk.')),
					field(t('Emel'), f.email),
					field(t('Peranan'), f.role),
					field(t('Kata laluan'), f.password, t('Minimum 8 aksara.')),
					h('div', { class: 'pd-span-2' }, h('label', { class: 'pd-check', style: 'margin-bottom:12px' }, f.folder, t('Cipta folder dengan nama pengguna ini dalam {portal}', { portal: C.brand.title })))
				),
				submitLabel: t('Tambah'),
				onSubmit: function () {
					return api('/users', { method: 'POST', body: {
						name: f.name.value, username: f.username.value, email: f.email.value,
						role: f.role.value, password: f.password.value, create_folder: f.folder.checked
					} }).then(function (r) {
						all.push(r.user);
						all.sort(function (a, b) { return a.name.localeCompare(b.name, LOCALE); });
						paint();
						refreshStorage();
						if (r.password) showPassword(r.user, r.password);
						else toast(t('Pengguna {name} ditambah.', { name: r.user.name }));
					});
				}
			});
		}

		function showPassword(user, pass) {
			openModal({
				title: t('Pengguna ditambah'),
				content: h('div', null,
					h('p', null, t('Berikan maklumat log masuk ini kepada {name}. Kata laluan hanya dipaparkan sekali sahaja.', { name: user.name })),
					field(t('Nama pengguna'), h('div', { class: 'pd-copy' }, h('code', null, user.username))),
					field(t('Kata laluan'), h('div', { class: 'pd-copy' }, h('code', null, pass),
						h('button', { type: 'button', class: 'pd-btn pd-btn--sm', onclick: function () {
							if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(pass); else fallbackCopy(pass);
							toast(t('Kata laluan disalin.'));
						} }, icon('copy', 15), t('Salin'))))
				),
				submitLabel: t('Selesai'),
				cancelLabel: null,
				sticky: true
			});
		}

		function editUser(u) {
			var isWpAdmin = u.role === 'administrator';
			var isMe = u.id === C.user.id;
			if (isWpAdmin && !isMe && !C.isWpAdmin) {
				openModal({
					title: u.name,
					content: h('p', null, t('Akaun ini ialah pentadbir WordPress. Hanya pentadbir WordPress boleh menukar emel, kata laluan atau status akaun ini.')),
					submitLabel: t('Faham'),
					cancelLabel: null
				});
				return;
			}
			var f = {
				name: h('input', { type: 'text', value: u.name, required: true, maxlength: 100 }),
				email: h('input', { type: 'email', value: u.email, required: true }),
				role: roleSelect(u.role, isWpAdmin || isMe),
				password: h('input', { type: 'text', autocomplete: 'new-password', placeholder: t('Biarkan kosong jika tidak ditukar') }),
				active: h('input', { type: 'checkbox', checked: !u.disabled, disabled: isMe })
			};
			if (isWpAdmin) f.role = h('input', { type: 'text', value: t('Pentadbir WordPress'), disabled: true });
			openModal({
				title: t('Edit pengguna: {name}', { name: u.username }),
				wide: true,
				content: h('div', { class: 'pd-form-grid' },
					field(t('Nama penuh'), f.name),
					field(t('Emel'), f.email),
					field(t('Peranan'), f.role, isWpAdmin ? t('Peranan pentadbir WordPress diurus dalam WordPress Admin.') : isMe ? t('Anda tidak boleh menukar peranan sendiri.') : null),
					field(t('Kata laluan baharu'), f.password, t('Minimum 8 aksara.')),
					h('div', { class: 'pd-span-2' }, h('label', { class: 'pd-check', style: 'margin-bottom:6px' }, f.active, t('Akaun aktif')),
						h('p', { class: 'pd-note', style: 'margin:0 0 12px' }, t('Akaun tidak aktif tidak boleh log masuk dan sesinya ditamatkan serta-merta.')))
				),
				submitLabel: t('Simpan'),
				onSubmit: function () {
					var body = { name: f.name.value, email: f.email.value };
					if (!isWpAdmin && !isMe) body.role = f.role.value;
					if (f.password.value) body.password = f.password.value;
					if (!isMe) body.disabled = !f.active.checked;
					return api('/users/' + u.id, { method: 'POST', body: body }).then(function (nu) {
						all = all.map(function (x) { return x.id === nu.id ? nu : x; });
						paint();
						toast(t('Maklumat {name} dikemas kini.', { name: nu.name }));
					});
				}
			});
		}
	}

	// =================================================================== tetapan

	function viewSettings() {
		document.title = t('Tetapan') + ' · ' + C.brand.title;
		loading();
		api('/settings').then(function (s) {
			if (current.route !== 'tetapan') return;
			var f = {
				portal_title: h('input', { type: 'text', value: s.portal_title, maxlength: 60, required: true }),
				portal_subtitle: h('input', { type: 'text', value: s.portal_subtitle, maxlength: 200 }),
				org_name: h('input', { type: 'text', value: s.org_name, maxlength: 80 }),
				max_upload_mb: h('input', { type: 'number', min: 1, max: 10240, value: s.max_upload_mb }),
				quota_gb: h('input', { type: 'number', min: 0, step: '0.5', value: s.quota_gb }),
				trash_days: h('input', { type: 'number', min: 0, max: 3650, value: s.trash_days }),
				home_redirect: h('input', { type: 'checkbox', checked: !!s.home_redirect }),
				portal_subtitle_en: h('input', { type: 'text', value: s.portal_subtitle_en || '', maxlength: 200, lang: 'en' }),
				org_name_en: h('input', { type: 'text', value: s.org_name_en || '', maxlength: 80, lang: 'en' }),
				default_lang: h('select', null, Object.keys(C.langs || {}).map(function (code) { return h('option', { value: code, selected: s.default_lang === code }, C.langs[code]); }))
			};
			var err = h('div', { class: 'pd-alert pd-alert--error', hidden: true });
			var saveBtn = h('button', { type: 'submit', class: 'pd-btn pd-btn--primary' }, t('Simpan tetapan'));
			var form = h('form', { class: 'pd-settings', novalidate: true },
				err,
				h('div', { class: 'pd-card' }, h('div', { class: 'pd-card__head' }, h('h2', null, t('Penjenamaan'))), h('div', { class: 'pd-card__body' },
					h('div', { class: 'pd-form-grid' },
						field(t('Nama portal'), f.portal_title),
						field(t('Nama organisasi / jabatan'), f.org_name),
						h('div', { class: 'pd-span-2' }, field(t('Penerangan'), f.portal_subtitle, t('Dipaparkan di bawah tajuk halaman dan pada halaman log masuk.')))))),
				h('div', { class: 'pd-card' }, h('div', { class: 'pd-card__head' }, h('h2', null, t('Storan & fail'))), h('div', { class: 'pd-card__body' },
					h('div', { class: 'pd-form-grid' },
						field(t('Had saiz muat naik (MB)'), f.max_upload_mb, t('Had server PHP sekarang: {max}. Had sebenar ialah yang lebih kecil.', { max: fmtSize(s.php_max_upload) })),
						field(t('Kuota storan (GB)'), f.quota_gb, t('0 = tiada had. Digunakan sekarang: {used}.', { used: fmtSize(s.used || 0) })),
						field(t('Simpan dalam tong sampah (hari)'), f.trash_days, t('0 = tidak dipadam secara automatik.'))))),
				h('div', { class: 'pd-card' }, h('div', { class: 'pd-card__head' }, h('h2', null, t('Laman'))), h('div', { class: 'pd-card__body' },
					h('label', { class: 'pd-check pd-toggle-row' }, f.home_redirect,
						h('span', null, h('strong', null, t('Halaman utama laman terus ke portal')), h('br'), h('span', { class: 'pd-note' }, t('Pelawat ke {site} akan dibawa ke {portal}.', { site: location.origin + '/', portal: C.brand.title })))))),
				h('div', { class: 'pd-card' }, h('div', { class: 'pd-card__head' }, h('h2', null, t('Bahasa'))), h('div', { class: 'pd-card__body' },
					h('div', { class: 'pd-form-grid' },
						field(t('Bahasa lalai'), f.default_lang, t('Untuk pengguna yang belum memilih bahasa. Setiap pengguna boleh menukar bahasa di menu sisi.')),
						field(t('Nama organisasi / jabatan (English)'), f.org_name_en, t('Kosongkan untuk guna nama dalam Bahasa Melayu.')),
						h('div', { class: 'pd-span-2' }, field(t('Penerangan (English)'), f.portal_subtitle_en, t('Dipaparkan kepada pengguna yang memilih English. Kosongkan untuk guna terjemahan automatik.')))))),
				h('div', { style: 'margin-top:16px;display:flex;gap:8px' }, saveBtn)
			);
			api('/dashboard').then(function (d) {
				var hint = f.quota_gb.parentNode.querySelector('small');
				if (hint) hint.textContent = t('0 = tiada had. Digunakan sekarang: {used}.', { used: fmtSize(d.size) });
			}, function () {});
			form.addEventListener('submit', function (e) {
				e.preventDefault();
				saveBtn.disabled = true;
				err.hidden = true;
				api('/settings', { method: 'POST', body: {
					portal_title: f.portal_title.value, portal_subtitle: f.portal_subtitle.value, org_name: f.org_name.value,
					max_upload_mb: f.max_upload_mb.value, quota_gb: f.quota_gb.value, trash_days: f.trash_days.value,
					home_redirect: f.home_redirect.checked,
					portal_subtitle_en: f.portal_subtitle_en.value, org_name_en: f.org_name_en.value, default_lang: f.default_lang.value
				} }).then(function (ns) {
					var en = C.lang === 'en';
					C.brand = {
						title: ns.portal_title,
						subtitle: en ? (ns.portal_subtitle_en || t(ns.portal_subtitle)) : ns.portal_subtitle,
						org: en ? (ns.org_name_en || t(ns.org_name)) : ns.org_name
					};
					C.settings.maxUpload = ns.max_upload_bytes;
					C.settings.trashDays = ns.trash_days;
					var b = document.querySelector('.pd-side__brand');
					b.querySelector('strong').textContent = ns.portal_title;
					b.querySelector('small').textContent = C.brand.org;
					document.querySelector('.pd-topbar strong').textContent = ns.portal_title;
					refreshStorage();
					toast(t('Tetapan disimpan.'));
				}, function (x) { err.textContent = x.message; err.hidden = false; window.scrollTo(0, 0); })
					.then(function () { saveBtn.disabled = false; });
			});
			clear(viewEl).append(pageHead(t('Tetapan'), t('Konfigurasi portal {portal}.', { portal: C.brand.title })), form);
		}, function (e) { errorView(e.message, viewSettings); });
	}

	// =================================================================== profil

	function openProfile() {
		appEl.classList.remove('is-nav-open');
		var u = C.user;
		var f = {
			name: h('input', { type: 'text', value: u.name, required: true, maxlength: 100 }),
			email: h('input', { type: 'email', value: u.email, required: true }),
			current: h('input', { type: 'password', autocomplete: 'current-password' }),
			next: h('input', { type: 'password', autocomplete: 'new-password' }),
			confirm: h('input', { type: 'password', autocomplete: 'new-password' })
		};
		openModal({
			title: t('Profil saya'),
			wide: true,
			content: h('div', null,
				h('div', { class: 'pd-form-grid' },
					field(t('Nama penuh'), f.name),
					field(t('Emel'), f.email),
					h('div', { class: 'pd-span-2' }, field(t('Nama pengguna'), h('input', { type: 'text', value: u.username, disabled: true })))),
				h('h3', { class: 'pd-section-title' }, t('Tukar kata laluan')),
				h('div', { class: 'pd-form-grid' },
					h('div', { class: 'pd-span-2' }, field(t('Kata laluan semasa'), f.current, t('Diperlukan jika anda menukar emel atau kata laluan.'))),
					field(t('Kata laluan baharu'), f.next, t('Minimum 8 aksara.')),
					field(t('Sahkan kata laluan baharu'), f.confirm))
			),
			submitLabel: t('Simpan'),
			onSubmit: function () {
				var body = { name: f.name.value, email: f.email.value };
				var emailChanged = f.email.value.trim().toLowerCase() !== String(u.email).toLowerCase();
				if (f.next.value || f.confirm.value) {
					if (f.next.value !== f.confirm.value) throw new Error(t('Pengesahan kata laluan tidak sepadan.'));
					body.new_password = f.next.value;
				}
				if (body.new_password || emailChanged) {
					if (!f.current.value) throw new Error(t('Masukkan kata laluan semasa untuk menukar emel atau kata laluan.'));
					body.current_password = f.current.value;
				}
				return api('/me', { method: 'POST', body: body }).then(function (nu) {
					if (body.new_password) {
						toast(t('Kata laluan ditukar. Memuat semula…'));
						setTimeout(function () { location.reload(); }, 900);
						return;
					}
					C.user = nu;
					renderUserCard();
					toast(t('Profil dikemas kini.'));
				});
			}
		});
	}

	// =================================================================== mula

	renderNav();
	renderLang();
	renderUserCard();
	refreshStorage();
	window.addEventListener('hashchange', router);
	if (!location.hash) history.replaceState(null, '', '#/utama');
	router();
})();
