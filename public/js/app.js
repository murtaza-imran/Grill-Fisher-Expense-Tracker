// ============ Grill Fisher & BBQ — Business Dashboard (Laravel) ============
'use strict';
const $ = (s, el = document) => el.querySelector(s);
const $$ = (s, el = document) => [...el.querySelectorAll(s)];
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const rs = n => 'Rs. ' + Math.round(n || 0).toLocaleString('en-US');
const rsShort = n => (Math.abs(n) >= 1000 ? (n / 1000).toFixed(n % 1000 === 0 ? 0 : 1) + 'k' : String(Math.round(n)));
const niceDate = s => new Date(s + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
const shortDate = s => new Date(s + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });

/* ---------- own pop-up (replaces browser confirm/alert) ---------- */
const modal = $('#modal');
let modalDone = null, modalReturn = null;
function popup({ title, text = '', ok = 'Confirm', kind = 'danger', alertOnly = false, amount = '', alt = null }) {
  return new Promise(resolve => {
    $('#modalTitle').textContent = title;
    $('#modalAmount').textContent = amount;
    $('#modalAmount').hidden = !amount;
    $('#modalCancel').textContent = alt ? alt.label : 'Cancel';
    modal.dataset.alt = alt ? '1' : '';
    $('#modalText').textContent = text;
    $('#modalOk').textContent = ok;
    $('#modalIc').className = 'modal-ic ' + kind;
    modal.dataset.kind = kind;
    $('#modalCancel').hidden = alertOnly;
    modalReturn = document.activeElement;
    modalDone = resolve;
    modal.hidden = false;
    requestAnimationFrame(() => modal.classList.add('show'));
    (alertOnly || alt ? $('#modalOk') : $('#modalCancel')).focus();
  });
}
function closePopup(result) {
  if (!modalDone) return;
  modal.classList.remove('show');
  setTimeout(() => { modal.hidden = true; }, 180);
  const done = modalDone; modalDone = null;
  if (modalReturn) modalReturn.focus();
  done(result);
}
if (modal) {
  $('#modalOk').addEventListener('click', () => closePopup(true));
  $('#modalCancel').addEventListener('click', () => closePopup(modal.dataset.alt ? 'alt' : false));
  modal.addEventListener('click', e => { if (e.target === modal) closePopup(false); });
  document.addEventListener('keydown', e => {
    if (!modalDone) return;
    if (e.key === 'Escape') closePopup(false);
    if (e.key === 'Tab') { // keep focus inside the pop-up
      const f = $$('button:not([hidden])', modal), i = f.indexOf(document.activeElement);
      e.preventDefault(); f[(i + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus();
    }
  });
  // any form with data-confirm asks first
  document.addEventListener('submit', e => {
    const form = e.target;
    if (!form.dataset.confirm || form.dataset.confirmed) return;
    e.preventDefault();
    popup({ title: form.dataset.confirm, text: form.dataset.confirmText, ok: form.dataset.confirmOk, kind: form.dataset.confirmKind || 'danger' })
      .then(yes => { if (yes) { form.dataset.confirmed = '1'; form.submit(); } });
  });
}

// success pop-up after saving (stays on the same page)
const saved = $('#savedPopup');
if (saved && modal) {
  const d = JSON.parse(saved.textContent);
  popup({ title: d.title, amount: d.amount, text: d.text, ok: d.ok, kind: 'success', alt: d.alt })
    .then(res => { if (res === 'alt') location.href = d.alt.href; else if (res === true && d.again) openForm(d.again); });
}

/* ---------- own validation messages (replaces browser bubbles) ---------- */
function fieldMessage(el) {
  const v = el.validity, label = (el.labels && el.labels[0] ? el.labels[0].childNodes[0].textContent : el.getAttribute('aria-label') || 'This field').trim();
  if (v.valueMissing) return el.tagName === 'SELECT' ? `Please choose ${label.toLowerCase()}.` : `${label} is required.`;
  if (v.typeMismatch && el.type === 'email') return 'Enter a valid email address.';
  if (v.rangeUnderflow) return `${label} must be at least ${el.min}.`;
  if (v.rangeOverflow) return `${label} must be ${el.max} or less.`;
  if (v.badInput || v.stepMismatch) return 'Enter a valid number.';
  if (v.tooShort) return `Use at least ${el.minLength} characters.`;
  return el.validationMessage;
}
let firstInvalid = null;
document.addEventListener('invalid', e => {
  const el = e.target;
  e.preventDefault();
  el.classList.add('is-bad');
  const host = el.closest('.field') || el.parentElement;
  let msg = host.querySelector(':scope > .field-err');
  if (!msg) { msg = document.createElement('span'); msg.className = 'field-err'; host.appendChild(msg); }
  msg.textContent = fieldMessage(el);
  if (!firstInvalid) {
    firstInvalid = el;
    el.focus({ preventScroll: true });
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => { firstInvalid = null; }, 0);
  }
}, true);
document.addEventListener('input', e => {
  const el = e.target;
  if (!el.classList || !el.classList.contains('is-bad') || !el.checkValidity()) return;
  el.classList.remove('is-bad');
  const msg = (el.closest('.field') || el.parentElement).querySelector(':scope > .field-err');
  if (msg) msg.remove();
});

/* ---------- phone: "+" choice sheet ---------- */
const sheet = $('#addSheet');
function closeSheet() { if (sheet && !sheet.hidden) { sheet.classList.remove('show'); setTimeout(() => { sheet.hidden = true; }, 200); } }
document.addEventListener('click', e => {
  if (e.target.closest('[data-open-sheet]')) { sheet.hidden = false; requestAnimationFrame(() => sheet.classList.add('show')); return; }
  if (sheet && !sheet.hidden && (e.target === sheet || e.target.closest('[data-close-sheet]') || e.target.closest('.sheet-opt'))) closeSheet();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSheet(); });

/* ---------- expense form: Daily / Monthly follows the category ---------- */
document.addEventListener('change', e => {
  const sel = e.target.closest('select[data-kind-sync]');
  if (!sel) return;
  const kind = sel.selectedOptions[0] && sel.selectedOptions[0].dataset.kind;
  const radio = kind && sel.form.querySelector(`input[name="kind"][value="${kind}"]`);
  if (radio) radio.checked = true;
});

/* ---------- phone: "Custom" date range toggle ---------- */
document.addEventListener('click', e => {
  const b = e.target.closest('[data-toggle-dates]');
  if (!b) return;
  const box = $('.date-range', b.closest('form'));
  box.classList.toggle('open');
  $$('[data-toggle-dates]', b.closest('form')).forEach(x => x.classList.toggle('on', box.classList.contains('open')));
  if (box.classList.contains('open')) { const d = $('input[type=date]', box); if (d) d.focus(); }
});

/* ---------- dropdown menus (export) ---------- */
function closeMenus(except) {
  $$('.dd-menu').forEach(m => { if (m !== except) { m.hidden = true; m.previousElementSibling.setAttribute('aria-expanded', 'false'); } });
}
document.addEventListener('click', e => {
  const btn = e.target.closest('[data-dd]');
  if (btn) {
    const menu = btn.nextElementSibling, open = menu.hidden;
    closeMenus(menu);
    menu.hidden = !open; btn.setAttribute('aria-expanded', String(open));
    if (open) { const first = $('a', menu); if (first) first.focus(); }
    return;
  }
  if (e.target.closest('.dd-menu a')) { setTimeout(() => closeMenus(), 50); return; }
  if (!e.target.closest('.dd-menu')) closeMenus();
});
document.addEventListener('keydown', e => {
  const menu = e.target.closest && e.target.closest('.dd-menu');
  if (e.key === 'Escape' && menu) { closeMenus(); menu.previousElementSibling.focus(); }
  if (menu && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
    e.preventDefault();
    const items = $$('a', menu), i = items.indexOf(document.activeElement);
    items[(i + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length].focus();
  }
});

/* ---------- phone app behaviour ---------- */
const isPhone = () => matchMedia('(max-width: 960px)').matches;
// settings sections open/close on phones
$$('[data-acc]').forEach(card => {
  const head = $('.set-head', card);
  if (isPhone()) card.classList.add('collapsed');
  head.addEventListener('click', e => {
    if (!isPhone() || e.target.closest('a, button, input, select')) return;
    const opening = card.classList.contains('collapsed');
    $$('[data-acc]').forEach(c => c.classList.add('collapsed'));
    if (opening) { card.classList.remove('collapsed'); setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'start' }), 60); }
  });
});
if (location.hash === '#export' && $('#export')) $('#export').classList.remove('collapsed');
// thin gold loading bar when moving between pages
const loadbar = $('#loadbar');
document.addEventListener('click', e => {
  const a = e.target.closest('a[href]');
  if (!a || !loadbar || a.hasAttribute('data-modal-form') || a.target || e.ctrlKey || e.metaKey || a.hasAttribute('download')) return;
  const url = new URL(a.href, location.href);
  if (url.origin !== location.origin || url.pathname.startsWith('/export') || (url.pathname === location.pathname && url.hash)) return;
  loadbar.classList.add('go');
});
window.addEventListener('pageshow', () => loadbar && loadbar.classList.remove('go'));

/* ---------- flash ---------- */
const flash = $('.flash');
if (flash) setTimeout(() => { flash.style.transition = 'opacity .5s'; flash.style.opacity = '0'; setTimeout(() => flash.remove(), 600); }, 4000);

/* ---------- tooltip ---------- */
const tip = $('#tip');
function showTip(html, ev) {
  tip.innerHTML = html; tip.hidden = false;
  const w = tip.offsetWidth, h = tip.offsetHeight;
  let x = ev.clientX + 14, y = ev.clientY - h - 10;
  if (x + w > innerWidth - 8) x = ev.clientX - w - 14;
  if (y < 8) y = ev.clientY + 16;
  tip.style.left = x + 'px'; tip.style.top = y + 'px';
}
const hideTip = () => { tip.hidden = true; };
function bindTips(el, htmlFor) {
  $$('.col', el).forEach(c => {
    const html = htmlFor(+c.dataset.i);
    c.addEventListener('mousemove', ev => showTip(html, ev));
    c.addEventListener('mouseleave', hideTip);
  });
}

/* ---------- charts ---------- */
function niceMax(v) {
  if (v <= 0) return 1000;
  const p = Math.pow(10, Math.floor(Math.log10(v)));
  return [1, 2, 2.5, 5, 10].map(m => m * p).find(m => m >= v);
}
function topRounded(x, y, w, h, r) {
  if (h <= 0) return '';
  r = Math.min(r, w / 2, h);
  return `M${x},${y + h}V${y + r}Q${x},${y} ${x + r},${y}H${x + w - r}Q${x + w},${y} ${x + w},${y + r}V${y + h}Z`;
}
function dailyChart(el, rows) {
  const W = 640, H = 240, L = 46, R = 8, T = 10, B = 26;
  const max = niceMax(Math.max(...rows.map(r => Math.max(r.s, r.e))));
  const cw = (W - L - R) / rows.length, bw = Math.max(3, Math.min(16, (cw - 6) / 2 - 1));
  const y = v => T + (H - T - B) * (1 - v / max);
  let axis = '', bars = '';
  for (let i = 0; i <= 4; i++) {
    const v = max * i / 4, yy = y(v);
    axis += `<line class="gridline" x1="${L}" x2="${W - R}" y1="${yy}" y2="${yy}"/><text x="${L - 6}" y="${yy + 4}" text-anchor="end">${rsShort(v)}</text>`;
  }
  const step = Math.ceil(rows.length / 10);
  rows.forEach((r, i) => {
    const cx = L + cw * i + cw / 2;
    bars += `<g class="col" data-i="${i}"><rect class="hover-bg" x="${L + cw * i}" y="${T}" width="${cw}" height="${H - T - B}" rx="4"/>`
      + `<path d="${topRounded(cx - bw - 1, y(r.s), bw, y(0) - y(r.s), 4)}" fill="var(--series-sales)"/>`
      + `<path d="${topRounded(cx + 1, y(r.e), bw, y(0) - y(r.e), 4)}" fill="var(--series-exp)"/>`
      + `<rect class="hit" x="${L + cw * i}" y="${T}" width="${cw}" height="${H - T - B}"/></g>`;
    if (i % step === 0 || i === rows.length - 1) axis += `<text x="${cx}" y="${H - 8}" text-anchor="middle">${shortDate(r.d)}</text>`;
  });
  el.innerHTML = `<svg viewBox="0 0 ${W} ${H}" role="img" aria-label="Daily sales and expenses"><g class="axis">${axis}</g>${bars}<line x1="${L}" x2="${W - R}" y1="${y(0)}" y2="${y(0)}" stroke="rgba(255,255,255,.18)"/></svg>`;
  bindTips(el, i => {
    const r = rows[i];
    return `<b>${niceDate(r.d)}</b><div class="r"><span><i class="sw sw-sales"></i>Sales</span>${rs(r.s)}</div><div class="r"><span><i class="sw sw-exp"></i>Expenses</span>${rs(r.e)}</div><div class="r"><span>Profit</span>${rs(r.s - r.e)}</div>`;
  });
}
function hbarChart(el, data, color, emptyText) {
  data = data.filter(d => d.v > 0).sort((a, b) => b.v - a.v);
  if (!data.length) { el.innerHTML = `<p class="empty-msg">${esc(emptyText)}</p>`; el.style.minHeight = '0'; return; }
  const top = data.slice(0, 7);
  if (data.length > 7) top.push({ k: 'Other', v: data.slice(7).reduce((t, d) => t + d.v, 0) });
  const total = data.reduce((t, d) => t + d.v, 0);
  const W = Math.max(300, Math.round(el.clientWidth || 520)), rowH = 34, LW = Math.min(170, Math.round(W * .36)), RW = 70, H = top.length * rowH + 4;
  const max = Math.max(...top.map(d => d.v));
  let s = '';
  top.forEach((d, i) => {
    const y = 4 + i * rowH, w = Math.max(3, (W - LW - RW) * d.v / max);
    s += `<g class="col" data-i="${i}"><rect class="hover-bg" x="0" y="${y - 2}" width="${W}" height="${rowH}" rx="6"/>`
      + `<text x="${LW - 10}" y="${y + 19}" text-anchor="end" class="bar-label">${esc(d.k.length > 20 ? d.k.slice(0, 19) + '…' : d.k)}</text>`
      + `<path d="M${LW},${y + 6}H${LW + w - 4}Q${LW + w},${y + 6} ${LW + w},${y + 10}V${y + 20}Q${LW + w},${y + 24} ${LW + w - 4},${y + 24}H${LW}Z" fill="${color}"/>`
      + `<text x="${LW + w + 8}" y="${y + 19}" class="bar-label v">${rsShort(d.v)}</text>`
      + `<rect class="hit" x="0" y="${y - 2}" width="${W}" height="${rowH}"/></g>`;
  });
  el.innerHTML = `<svg viewBox="0 0 ${W} ${H}" role="img" aria-label="Breakdown">${s}</svg>`;
  bindTips(el, i => `<b>${esc(top[i].k)}</b><div class="r"><span>Amount</span>${rs(top[i].v)}</div><div class="r"><span>Share</span>${Math.round(top[i].v / total * 100)}%</div>`);
}
// daily sales as glowing capsules inside soft tracks (dashboard)
function capsuleChart(el, rows) {
  const W = Math.max(320, Math.round(el.clientWidth || 640)), H = 240, L = 44, R = 6, T = 14, B = 28;
  const max = niceMax(Math.max(...rows.map(r => r.s)));
  const cw = (W - L - R) / rows.length;
  const tw = Math.max(8, Math.min(28, cw * .62)), fw = Math.max(4, Math.min(8, tw * .34));
  const y = v => T + (H - T - B) * (1 - v / max), base = y(0);
  const peak = rows.reduce((m, r, i) => (r.s > rows[m].s ? i : m), 0);
  let axis = '', cols = '';
  for (let i = 1; i <= 4; i++) {
    const v = max * i / 4;
    axis += `<text x="${L - 10}" y="${y(v) + 4}" text-anchor="end">${rsShort(v)}</text>`;
  }
  const step = Math.ceil(rows.length / 8);
  rows.forEach((r, i) => {
    const cx = L + cw * i + cw / 2, fy = y(r.s);
    cols += `<g class="col" data-i="${i}"><rect class="track" x="${cx - tw / 2}" y="${T}" width="${tw}" height="${base - T}" rx="${tw / 2}" fill="url(#track)"/>`
      + (r.s > 0 ? `<rect x="${cx - fw / 2}" y="${fy}" width="${fw}" height="${Math.max(fw, base - fy)}" rx="${fw / 2}" fill="url(#fill)" filter="url(#glow)"/>` : '')
      + (i === peak && r.s > 0 ? `<circle cx="${cx}" cy="${fy - 9}" r="5" fill="var(--gold-2)" filter="url(#glow)"/>` : '')
      + `<rect class="hit" x="${L + cw * i}" y="${T}" width="${cw}" height="${base - T}"/></g>`;
    if (i % step === 0 || i === rows.length - 1) {
      axis += `<line class="gridline" x1="${cx}" x2="${cx}" y1="${base + 4}" y2="${base + 10}"/><text x="${cx}" y="${H - 4}" text-anchor="middle">${new Date(r.d + 'T00:00:00').getDate().toString().padStart(2, '0')}</text>`;
    }
  });
  el.innerHTML = `<svg viewBox="0 0 ${W} ${H}" role="img" aria-label="Daily sales">
    <defs>
      <linearGradient id="track" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff4e1" stop-opacity=".1"/><stop offset="1" stop-color="#fff4e1" stop-opacity="0"/></linearGradient>
      <linearGradient id="trackHover" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff4e1" stop-opacity=".2"/><stop offset="1" stop-color="#fff4e1" stop-opacity=".02"/></linearGradient>
      <linearGradient id="fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f6c454"/><stop offset=".6" stop-color="#e8a91a"/><stop offset="1" stop-color="#c98500" stop-opacity=".25"/></linearGradient>
      <filter id="glow" x="-200%" y="-20%" width="500%" height="140%"><feGaussianBlur stdDeviation="3" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
    </defs><g class="axis">${axis}</g>${cols}</svg>`;
  bindTips(el, i => {
    const r = rows[i];
    return `<b>${niceDate(r.d)}</b><div class="r"><span><i class="sw sw-sales"></i>Sales</span>${rs(r.s)}</div><div class="r"><span><i class="sw sw-exp"></i>Expenses</span>${rs(r.e)}</div><div class="r"><span>Profit</span>${rs(r.s - r.e)}</div>`;
  });
}
$$('[data-chart]').forEach(el => {
  const data = JSON.parse(el.dataset.json || '[]');
  if (el.dataset.chart === 'daily') dailyChart(el, data);
  else if (el.dataset.chart === 'capsule') capsuleChart(el, data);
  else hbarChart(el, data, el.dataset.color, el.dataset.empty);
});

/* ---------- heatmap tooltips ---------- */
$$('[data-tip]').forEach(c => {
  c.addEventListener('mousemove', ev => showTip(c.dataset.tip, ev));
  c.addEventListener('mouseleave', hideTip);
});

/* ---------- sale form: item rows (full page and pop-up) ---------- */
const trashSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>';
function initSaleForm(form) {
  const body = $('.js-rows', form);
  if (!body || form.dataset.ready) return;
  form.dataset.ready = '1';
  let rows = JSON.parse(($('.js-initial', form) || {}).textContent || '[]').map(r => ({ name: r.name, qty: +r.qty || 1, price: +r.price || 0 }));
  const total = () => rows.reduce((t, r) => t + r.qty * r.price, 0);
  function draw() {
    body.innerHTML = rows.map((r, k) => `<tr data-k="${k}">
      <td><input name="items[${k}][name]" value="${esc(r.name)}" aria-label="Item name" required></td>
      <td><div class="stepper"><button type="button" class="st" data-step="-1" aria-label="One less">−</button><input name="items[${k}][qty]" type="number" min="1" inputmode="numeric" value="${r.qty}" aria-label="Quantity" required><button type="button" class="st" data-step="1" aria-label="One more">+</button></div></td>
      <td><input name="items[${k}][price]" type="number" min="0" inputmode="numeric" value="${r.price}" aria-label="Price" required></td>
      <td class="line">${rs(r.qty * r.price)}</td>
      <td class="act"><button type="button" class="del" aria-label="Remove item" title="Remove">${trashSvg}</button></td></tr>`).join('');
    $('.js-norows', form).hidden = rows.length > 0;
    totals();
    // badge on each menu button with how many are in the order
    $$('.js-quick button', form).forEach(b => {
      const q = rows.filter(r => r.name === b.dataset.name && r.price === +b.dataset.price).reduce((t, r) => t + r.qty, 0);
      if (q) b.dataset.qty = q; else delete b.dataset.qty;
    });
  }
  function totals() {
    $$('.js-grand', form).forEach(el => { el.textContent = rs(total()); });
    const n = rows.reduce((t, r) => t + r.qty, 0);
    $$('.js-count', form).forEach(el => { el.textContent = n ? n + (n === 1 ? ' item' : ' items') : 'No items'; });
  }
  function read() {
    $$('tr[data-k]', body).forEach(tr => {
      const r = rows[+tr.dataset.k], [n, q, p] = $$('input', tr);
      r.name = n.value; r.qty = Math.max(0, +q.value || 0); r.price = Math.max(0, +p.value || 0);
    });
  }
  draw();
  $('.js-quick', form).addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b) return;
    read();
    const name = b.dataset.name, price = +b.dataset.price;
    const ex = rows.find(r => r.name === name && r.price === price);
    if (ex) ex.qty++; else rows.push({ name, qty: 1, price });
    draw();
    b.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.08)' }, { transform: 'scale(1)' }], { duration: 220 });
  });
  $('.js-add', form).addEventListener('click', () => { read(); rows.push({ name: '', qty: 1, price: 0 }); draw(); $$('tr[data-k] input', body).slice(-3)[0].focus(); });
  body.addEventListener('input', () => {
    read();
    $$('tr[data-k]', body).forEach(tr => { const r = rows[+tr.dataset.k]; $('.line', tr).textContent = rs(r.qty * r.price); });
    totals();
  });
  body.addEventListener('click', e => {
    const st = e.target.closest('.st');
    if (st) { read(); const r = rows[+st.closest('tr').dataset.k]; r.qty = Math.max(1, r.qty + +st.dataset.step); draw(); return; }
    if (e.target.closest('.del')) { read(); rows.splice(+e.target.closest('tr').dataset.k, 1); draw(); }
  });
  form.addEventListener('submit', e => {
    read();
    if (!rows.length) {
      e.preventDefault(); e.stopImmediatePropagation();
      popup({ title: 'No items yet', text: 'Tap an item from the menu or add a custom item before saving the sale.', ok: 'Got it', kind: 'info', alertOnly: true });
    }
  });
}
$$('form.sale-form').forEach(initSaleForm);

/* ---------- sale form: BBQ / Fish / Other — one or more, each with an amount ---------- */
function initCatForm(form) {
  if (!form || !$('.cat-toggle', form) || form.dataset.catReady) return;
  form.dataset.catReady = '1';
  const update = () => {
    let sum = 0, shown = 0;
    $$('.cat-amount', form).forEach(box => {
      const on = $(`.cat-toggle[value="${box.dataset.cat}"]`, form).checked, input = $('input', box);
      box.hidden = !on; input.required = on;
      if (!on) input.value = '';
      if (on) { shown++; sum += Math.max(0, +input.value || 0); }
    });
    $('.js-cat-hint', form).hidden = shown > 0;
    $('.js-cat-totalrow', form).hidden = shown < 2;
    $$('.js-cat-total', form).forEach(el => { el.textContent = rs(sum); });
  };
  $$('.cat-toggle', form).forEach(t => t.addEventListener('change', () => {
    update();
    if (t.checked) { const i = $(`.cat-amount[data-cat="${t.value}"] input`, form); if (i) i.focus(); }
  }));
  $$('.cat-amount input', form).forEach(i => i.addEventListener('input', update));
  form.addEventListener('submit', e => {
    if (!$$('.cat-toggle', form).some(t => t.checked)) {
      e.preventDefault(); e.stopImmediatePropagation();
      popup({ title: 'What did you sell?', text: 'Tap BBQ, Fish or Other (one or more) and enter the amount.', ok: 'Got it', kind: 'info', alertOnly: true });
    }
  });
  update();
}
$$('form.sale-form').forEach(initCatForm);

/* ---------- add / edit in a pop-up form ---------- */
const formModal = $('#formModal'), formBody = $('#formModalBody');
let formReturn = null;
function closeForm() {
  if (!formModal || formModal.hidden) return;
  formModal.classList.remove('show');
  setTimeout(() => { formModal.hidden = true; formBody.innerHTML = ''; document.body.classList.remove('locked'); }, 180);
  if (formReturn) formReturn.focus();
}
async function openForm(url) {
  formReturn = document.activeElement;
  formBody.innerHTML = '<div class="fm-loading"><span class="spinner"></span>Loading…</div>';
  formModal.hidden = false;
  document.body.classList.add('locked');
  requestAnimationFrame(() => formModal.classList.add('show'));
  try {
    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    if (res.redirected && /\/login/.test(res.url)) { location.href = res.url; return; }
    if (!res.ok) throw new Error(res.status);
    formBody.innerHTML = await res.text();
    const form = $('form.js-ajax', formBody);
    initSaleForm(form);
    initCatForm(form);
    const first = $('[autofocus]', form) || $('input:not([type=hidden]):not([type=date])', form);
    if (first) first.focus();
  } catch (err) {
    closeForm();
    popup({ title: 'Could not open the form', text: 'Please check that the dashboard is running and try again.', ok: 'OK', kind: 'info', alertOnly: true });
  }
}
function showFormErrors(form, errors) {
  $$('.field-err', form).forEach(m => m.remove());
  $$('.is-bad', form).forEach(el => el.classList.remove('is-bad'));
  const general = [];
  Object.entries(errors).forEach(([key, msgs]) => {
    const name = key.replace(/\.(\w+)/g, '[$1]');
    const el = form.querySelector(`[name="${name}"]`);
    if (!el) { general.push(msgs[0]); return; }
    el.classList.add('is-bad');
    const host = el.closest('.field') || el.parentElement;
    const m = document.createElement('span'); m.className = 'field-err'; m.textContent = msgs[0];
    host.appendChild(m);
  });
  let box = $('.js-form-errors', form);
  if (box) box.remove();
  if (general.length) {
    box = document.createElement('div'); box.className = 'errors js-form-errors';
    box.textContent = general.join(' ');
    form.insertBefore(box, form.querySelector('.form-grid'));
  }
  const bad = $('.is-bad', form) || box;
  if (bad) bad.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
if (formModal) {
  document.addEventListener('click', e => {
    const a = e.target.closest('a[data-modal-form]');
    if (a && !e.ctrlKey && !e.metaKey && !e.shiftKey && e.button === 0) { e.preventDefault(); openForm(a.href); return; }
    if (e.target.closest('[data-close-form]')) closeForm();
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modalDone && !formModal.hidden) closeForm(); });
  // save through the pop-up without leaving the page
  document.addEventListener('submit', async e => {
    const form = e.target;
    if (!form.classList.contains('js-ajax') || e.defaultPrevented) return;
    e.preventDefault();
    const btn = $('button[type=submit]:not([form])', form), label = btn.textContent;
    btn.disabled = true; btn.textContent = 'Saving…';
    try {
      const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
      if (res.status === 422) { showFormErrors(form, (await res.json()).errors || {}); return; }
      if (res.status === 419) { location.reload(); return; }
      if (!res.ok) throw new Error(res.status);
      location.reload(); // the success pop-up shows after the page refreshes with the new numbers
    } catch (err) {
      popup({ title: 'Could not save', text: 'Something went wrong. Please try again.', ok: 'OK', kind: 'info', alertOnly: true });
    } finally {
      btn.disabled = false; btn.textContent = label;
    }
  });
}
