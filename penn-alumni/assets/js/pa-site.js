/* Penn Alumni — shared site behavior. Same file as the HTML prototype (js/pa-site.js);
   only the current-page check is adapted for WordPress permalinks.
   One file replaces the per-page inline scripts. In Experience Cloud each
   block below becomes the JS of the matching LWC (noted per block). */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  /* ── paHeader: dropdowns, search, mobile menu, current page ───────── */
  const tops = $$('.pa-nav-top');
  const closeAll = (except) => tops.forEach(b => {
    if (b !== except) { b.setAttribute('aria-expanded', 'false'); b.parentElement.classList.remove('open'); }
  });
  tops.forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      const open = btn.getAttribute('aria-expanded') !== 'true';
      closeAll(btn);
      btn.setAttribute('aria-expanded', String(open));
      btn.parentElement.classList.toggle('open', open);
    });
  });
  document.addEventListener('click', e => { if (!e.target.closest('.pa-nav-links')) closeAll(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeAll(); toggle('.pa-nav-search', false); } });

  function toggle(sel, force) {
    const btn = $(sel); if (!btn) return;
    const panel = document.getElementById(btn.getAttribute('aria-controls'));
    const open = force !== undefined ? force : btn.getAttribute('aria-expanded') !== 'true';
    btn.setAttribute('aria-expanded', String(open));
    panel && panel.classList.toggle('open', open);
    if (open && panel) { const i = $('input', panel); i && i.focus(); }
  }
  $('.pa-nav-search') && $('.pa-nav-search').addEventListener('click', () => toggle('.pa-nav-search'));
  $('.pa-nav-toggle') && $('.pa-nav-toggle').addEventListener('click', () => toggle('.pa-nav-toggle'));

  // Mark current page in nav. WordPress version: compare URL paths (pretty permalinks), and light up
  // the top-level item whose dropdown contains this page or one of its parents.
  const norm = (u) => { try { return new URL(u, location.href).pathname.replace(/\/+$/, '') || '/'; } catch (e) { return ''; } };
  const here = norm(location.href);
  $$('.pa-mega a, .pa-mobile-menu a').forEach(a => {
    if (a.hash && norm(a.href) === here && a.getAttribute('href').includes('#')) return;
    const p = norm(a.href);
    if (p === here) a.setAttribute('aria-current', 'page');
    if (p !== '/' && (p === here || here.startsWith(p + '/'))) {
      const li = a.closest('[data-nav]'); li && $('.pa-nav-top', li).classList.add('active');
    }
  });

  /* ── paSectionNav: auto-build links + scroll-spy ─────────────────── */
  $$('.pa-secnav').forEach(nav => {
    const inner = $('.pa-secnav-inner', nav);
    if (!inner.children.length) {
      $$('[data-nav-label]').forEach(s => {
        const a = document.createElement('a');
        a.href = '#' + s.id; a.textContent = s.dataset.navLabel; inner.appendChild(a);
      });
    }
    const links = $$('a', inner);
    const targets = links.map(a => document.getElementById(a.hash.slice(1))).filter(Boolean);
    const spy = () => {
      const y = window.scrollY + 180;
      let cur = targets[0];
      targets.forEach(t => { if (t.offsetTop <= y) cur = t; });
      links.forEach(a => a.classList.toggle('active', cur && a.hash === '#' + cur.id));
    };
    window.addEventListener('scroll', spy, { passive: true }); spy();
  });

  /* ── paEventFeed / paClassDirectory / paTourList: filter + search ── */
  // Markup contract: container [data-filter-scope] holds controls with
  // [data-filter="field"] (select/search) and pills [data-pill="field:value"];
  // items carry data-field attributes. Count goes in [data-results-count].
  $$('[data-filter-scope]').forEach(scope => {
    const items = $$('[data-item]', scope);
    const controls = $$('[data-filter]', scope);
    const pills = $$('[data-pill]', scope);
    const count = $('[data-results-count]', scope);
    const empty = $('[data-empty]', scope);
    const state = {};
    const apply = () => {
      controls.forEach(c => { state[c.dataset.filter] = c.value.trim().toLowerCase(); });
      let n = 0;
      items.forEach(it => {
        const ok = Object.entries(state).every(([k, v]) => {
          if (!v || v === 'all') return true;
          if (k === 'q') return it.textContent.toLowerCase().includes(v);
          return (it.dataset[k] || '').toLowerCase().split(' ').includes(v);
        });
        it.hidden = !ok; if (ok) n++;
      });
      // paPeople: hide a department heading when all of its people are filtered out
      $$('.pa-people-group', scope).forEach(g => { const its = $$('[data-item]', g); if (its.length) g.hidden = its.every(i => i.hidden); });
      if (count) count.textContent = `Showing ${n} of ${items.length}`;
      if (empty) empty.hidden = n !== 0;
    };
    controls.forEach(c => c.addEventListener(c.type === 'search' ? 'input' : 'change', apply));
    pills.forEach(p => p.addEventListener('click', () => {
      const [k, v] = p.dataset.pill.split(':');
      pills.filter(x => x.dataset.pill.startsWith(k + ':')).forEach(x => x.setAttribute('aria-pressed', 'false'));
      p.setAttribute('aria-pressed', 'true'); state[k] = v; apply();
    }));
    // Tabs: [data-tab="panelId"] toggles [data-panel]
    const tabs = $$('[data-tab]', scope);
    tabs.forEach(t => t.addEventListener('click', () => {
      tabs.forEach(x => x.setAttribute('aria-selected', String(x === t)));
      $$('[data-panel]', scope).forEach(p => { p.hidden = p.id !== t.dataset.tab; });
    }));
    apply();
  });

  /* ── paPeople: avatar fallback to initials ───────────────────────── */
  $$('.pa-avatar img').forEach(img => {
    const fail = () => { const n = img.alt || ''; const i = img.dataset.initials || n.split(' ').filter(Boolean).map(w => w[0]).slice(0, 2).join(''); img.parentElement.classList.remove('pa-avatar--photo'); img.parentElement.textContent = i; };
    if (img.complete && !img.naturalWidth) fail(); else img.addEventListener('error', fail);
  });
})();
