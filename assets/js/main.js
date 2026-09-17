/* =============================================================================
   Commodities Good Steward: front-end behaviour.
   Vanilla JavaScript, no dependencies. Everything degrades: forms still post
   without this file, links still navigate, the cart still works server side.
   ========================================================================== */
(function () {
  'use strict';

  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  const CFG = window.CGS || { api: '/api/', csrf: '' };

  const ICONS = {
    check: '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>',
    alert: '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5M12 16h.01M12 3l10 18H2z"/></svg>',
    minus: '<svg class="icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M5 12h14"/></svg>',
    plus: '<svg class="icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
    trash: '<svg class="icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>',
    bag: '<svg class="icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l1 12H5L6 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>'
  };

  /* Small coffee bag drawn in JS for the cart drawer. */
  function miniBag(accent, label) {
    const a = accent || '#C98B55';
    return '<svg class="bag" viewBox="0 0 320 430" aria-hidden="true">' +
      '<path d="M62 386 Q160 404 258 386 L250 402 Q160 418 70 402 Z" fill="#070F1C"/>' +
      '<path d="M56 58 Q160 44 264 58 L270 388 Q160 406 50 388 Z" fill="#10213A"/>' +
      '<path d="M56 58 Q160 44 264 58 L264 84 Q160 70 56 84 Z" fill="#0B1729"/>' +
      '<rect x="82" y="146" width="156" height="206" rx="12" fill="#F4ECE1"/>' +
      '<path d="M82 322 h156 v18 a12 12 0 0 1 -12 12 h-132 a12 12 0 0 1 -12 -12 z" fill="' + a + '"/>' +
      '<text x="160" y="342" text-anchor="middle" font-family="DM Sans, sans-serif" font-size="34" font-weight="700" fill="#0F1E33">' + (label || '') + '</text>' +
      '<circle cx="160" cy="215" r="22" fill="#0F1E33"/></svg>';
  }

  /* ---------- API ------------------------------------------------------ */
  async function api(file, payload) {
    const res = await fetch(CFG.api + file, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'fetch' },
      credentials: 'same-origin',
      body: JSON.stringify(Object.assign({ csrf: CFG.csrf }, payload || {}))
    });
    let data = null;
    try { data = await res.json(); } catch (e) { data = { ok: false, error: 'Unexpected response.' }; }
    return data;
  }

  /* ---------- Toast ---------------------------------------------------- */
  let toastTimer = null;
  function toast(message, type) {
    const el = $('#toast');
    if (!el) return;
    el.className = 'toast' + (type === 'error' ? ' toast--error' : '');
    el.innerHTML = (type === 'error' ? ICONS.alert : ICONS.check) + '<span></span>';
    el.querySelector('span').textContent = message;
    requestAnimationFrame(() => el.classList.add('is-show'));
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('is-show'), 3200);
  }

  /* ---------- Header --------------------------------------------------- */
  const header = $('#site-header');
  function onScroll() {
    if (!header) return;
    header.classList.toggle('is-scrolled', window.scrollY > 24);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- Mobile nav ----------------------------------------------- */
  const mobileNav = $('#mobile-nav');
  const navOpen = $('#nav-open');
  const navClose = $('#nav-close');
  function setNav(open) {
    if (!mobileNav) return;
    mobileNav.classList.toggle('is-open', open);
    mobileNav.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (navOpen) navOpen.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.style.overflow = open ? 'hidden' : '';
  }
  navOpen && navOpen.addEventListener('click', () => setNav(true));
  navClose && navClose.addEventListener('click', () => setNav(false));
  mobileNav && $$('a', mobileNav).forEach(a => a.addEventListener('click', () => setNav(false)));

  /* ---------- Reveal on scroll ----------------------------------------- */
  const revealEls = $$('[data-reveal], [data-reveal-stagger]');
  if ('IntersectionObserver' in window && !reduceMotion) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(en => {
        if (en.isIntersecting) {
          en.target.classList.add('is-visible');
          io.unobserve(en.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    revealEls.forEach(el => io.observe(el));
    $$('[data-reveal-stagger]').forEach(group => Array.from(group.children).forEach((c, i) => c.style.setProperty('--i', i)));
  } else {
    revealEls.forEach(el => el.classList.add('is-visible'));
  }

  /* ---------- Counters ------------------------------------------------- */
  const counters = $$('[data-count]');
  if (counters.length) {
    const run = (el) => {
      const target = parseInt(el.dataset.count, 10) || 0;
      if (reduceMotion || target === 0) { el.textContent = target; return; }
      const start = performance.now();
      const dur = 1400;
      const tick = (now) => {
        const p = Math.min(1, (now - start) / dur);
        const eased = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(target * eased);
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    const cio = new IntersectionObserver((entries) => {
      entries.forEach(en => { if (en.isIntersecting) { run(en.target); cio.unobserve(en.target); } });
    }, { threshold: 0.5 });
    counters.forEach(c => cio.observe(c));
  }

  /* ---------- Hero parallax -------------------------------------------- */
  const hero = $('.hero');
  if (hero && finePointer && !reduceMotion) {
    const targets = $$('.hero__bag, .chip', hero);
    let raf = null;
    hero.addEventListener('mousemove', (e) => {
      if (raf) return;
      raf = requestAnimationFrame(() => {
        const r = hero.getBoundingClientRect();
        const mx = ((e.clientX - r.left) / r.width - 0.5) * 2;
        const my = ((e.clientY - r.top) / r.height - 0.5) * 2;
        targets.forEach(t => { t.style.setProperty('--mx', mx.toFixed(3)); t.style.setProperty('--my', my.toFixed(3)); });
        raf = null;
      });
    });
    hero.addEventListener('mouseleave', () => targets.forEach(t => { t.style.setProperty('--mx', 0); t.style.setProperty('--my', 0); }));
  }

  /* ---------- Tilt cards ----------------------------------------------- */
  if (finePointer && !reduceMotion) {
    $$('[data-tilt]').forEach(card => {
      card.addEventListener('mousemove', (e) => {
        const r = card.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width - 0.5;
        const y = (e.clientY - r.top) / r.height - 0.5;
        card.style.setProperty('--ry', (x * 8).toFixed(2) + 'deg');
        card.style.setProperty('--rx', (-y * 8).toFixed(2) + 'deg');
      });
      card.addEventListener('mouseleave', () => { card.style.setProperty('--rx', '0deg'); card.style.setProperty('--ry', '0deg'); });
    });
  }

  /* ---------- Magnetic buttons ----------------------------------------- */
  if (finePointer && !reduceMotion) {
    $$('.btn-magnetic').forEach(btn => {
      btn.addEventListener('mousemove', (e) => {
        const r = btn.getBoundingClientRect();
        const x = e.clientX - (r.left + r.width / 2);
        const y = e.clientY - (r.top + r.height / 2);
        btn.style.transform = 'translate(' + (x * 0.18).toFixed(1) + 'px,' + (y * 0.22).toFixed(1) + 'px)';
      });
      btn.addEventListener('mouseleave', () => { btn.style.transform = ''; });
    });
  }

  /* ---------- Custom cursor -------------------------------------------- */
  if (finePointer && !reduceMotion) {
    const dot = $('.cursor');
    const ring = $('.cursor__ring');
    if (dot && ring) {
      document.documentElement.classList.add('has-cursor');
      let x = -100, y = -100, rx = -100, ry = -100;
      document.addEventListener('mousemove', (e) => {
        x = e.clientX; y = e.clientY;
        dot.style.transform = 'translate(' + x + 'px,' + y + 'px) translate(-50%,-50%)';
      });
      (function loop() {
        rx += (x - rx) * 0.18; ry += (y - ry) * 0.18;
        ring.style.transform = 'translate(' + rx + 'px,' + ry + 'px) translate(-50%,-50%)';
        requestAnimationFrame(loop);
      })();
      const hoverSel = 'a, button, label, [data-tilt], .pill, .tile, input[type="range"]';
      document.addEventListener('mouseover', (e) => { if (e.target.closest(hoverSel)) ring.classList.add('is-hover'); });
      document.addEventListener('mouseout', (e) => { if (e.target.closest(hoverSel)) ring.classList.remove('is-hover'); });
      const hideSel = 'input:not([type="range"]):not([type="radio"]):not([type="checkbox"]), textarea, select';
      document.addEventListener('mouseover', (e) => { const h = !!e.target.closest(hideSel); dot.classList.toggle('is-hidden', h); ring.classList.toggle('is-hidden', h); });
      document.addEventListener('mouseleave', () => { dot.classList.add('is-hidden'); ring.classList.add('is-hidden'); });
      document.addEventListener('mouseenter', () => { dot.classList.remove('is-hidden'); ring.classList.remove('is-hidden'); });
    }
  }

  /* ---------- Page transitions ----------------------------------------- */
  const pt = $('.pt');
  if (pt && !reduceMotion) {
    document.addEventListener('click', (e) => {
      const a = e.target.closest('a[href]');
      if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
      if (a.target === '_blank' || a.hasAttribute('download') || a.getAttribute('href').startsWith('#') || a.getAttribute('href').startsWith('mailto:') || a.getAttribute('href').startsWith('tel:')) return;
      const url = new URL(a.href, location.href);
      if (url.origin !== location.origin) return;
      if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
      e.preventDefault();
      pt.classList.remove('is-out');
      pt.classList.add('is-in');
      setTimeout(() => { location.href = url.href; }, 420);
    });
    window.addEventListener('pageshow', (e) => { if (e.persisted) { pt.classList.remove('is-in'); pt.classList.add('is-out'); } });
  }

  /* ---------- Accordions ----------------------------------------------- */
  $$('.acc__btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.acc__item');
      const open = item.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });

  /* ---------- Cart ----------------------------------------------------- */
  const drawer = $('#cart-drawer');
  const backdrop = $('#backdrop');
  const drawerItems = $('#drawer-items');
  const drawerFoot = $('#drawer-foot');
  const drawerCount = $('#drawer-count');
  const cartCount = $('#cart-count');
  const cartOpenBtn = $('#cart-open');
  let cartState = null;

  function setDrawer(open) {
    if (!drawer) return;
    drawer.classList.toggle('is-open', open);
    backdrop && backdrop.classList.toggle('is-open', open);
    drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
    cartOpenBtn && cartOpenBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.style.overflow = open ? 'hidden' : '';
    if (open && !cartState) refreshCart();
  }
  cartOpenBtn && cartOpenBtn.addEventListener('click', () => setDrawer(true));
  $('#cart-close') && $('#cart-close').addEventListener('click', () => setDrawer(false));
  backdrop && backdrop.addEventListener('click', () => setDrawer(false));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { setDrawer(false); setNav(false); } });

  function renderCart(c) {
    cartState = c;
    if (cartCount) {
      const prev = cartCount.dataset.count;
      cartCount.dataset.count = c.count;
      cartCount.textContent = c.count;
      if (String(c.count) !== prev) { cartCount.classList.remove('is-bump'); void cartCount.offsetWidth; cartCount.classList.add('is-bump'); }
    }
    if (drawerCount) drawerCount.textContent = c.count ? '(' + c.count + ')' : '';
    if (!drawerItems || !drawerFoot) return;

    if (!c.items.length) {
      drawerItems.innerHTML = '<div class="drawer__empty">' + ICONS.bag + '<h4>Your bag is empty</h4><p>Three sizes, five grinds, one coffee worth the trouble.</p></div>';
      drawerFoot.innerHTML = '<a class="btn btn--block" href="' + (CFG.base || '') + '/shop.php">Shop the roast</a>';
      return;
    }
    drawerItems.innerHTML = c.items.map(it => (
      '<div class="cart-line" data-key="' + it.key + '">' +
        '<a class="cart-line__img" href="' + it.url + '">' + (it.image ? '<img src="' + it.image + '" alt="">' : miniBag(it.accent, it.size)) + '</a>' +
        '<div><a class="cart-line__title" href="' + it.url + '">' + esc(it.name) + ' · ' + esc(it.size) + '</a>' +
          '<div class="cart-line__meta">' + esc(it.grind) + ' · ' + esc(it.unit) + ' each</div>' +
          '<div class="qty"><button class="qty__btn" type="button" data-qty="-1" aria-label="Decrease">' + ICONS.minus + '</button><span class="qty__val">' + it.qty + '</span><button class="qty__btn" type="button" data-qty="1" aria-label="Increase">' + ICONS.plus + '</button></div>' +
        '</div>' +
        '<div><div class="cart-line__price">' + esc(it.line) + '</div><button class="cart-line__remove" type="button" data-remove>' + ICONS.trash + ' Remove</button></div>' +
      '</div>'
    )).join('');
    drawerFoot.innerHTML =
      '<p class="progress__msg">' + (c.to_free ? 'Add <strong>' + esc(c.to_free) + '</strong> more for free delivery.' : '<strong>Free delivery unlocked.</strong>') + '</p>' +
      '<div class="progress"><span class="progress__fill" style="--p:' + c.progress + '%"></span></div>' +
      '<div class="drawer__total"><span>Subtotal</span><strong>' + esc(c.subtotal) + '</strong></div>' +
      '<a class="btn btn--block btn--lg" href="' + c.checkout + '">Checkout</a>' +
      '<p class="summary__note"><a href="' + c.cart_url + '">View full bag</a></p>';
  }

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])); }

  async function refreshCart() {
    const res = await api('cart.php', { action: 'get' });
    if (res && res.cart) renderCart(res.cart);
  }

  drawerItems && drawerItems.addEventListener('click', async (e) => {
    const line = e.target.closest('.cart-line');
    if (!line) return;
    const key = line.dataset.key;
    const qtyBtn = e.target.closest('[data-qty]');
    const rm = e.target.closest('[data-remove]');
    if (qtyBtn) {
      const cur = parseInt(line.querySelector('.qty__val').textContent, 10) || 1;
      const res = await api('cart.php', { action: 'update', key, qty: cur + parseInt(qtyBtn.dataset.qty, 10) });
      if (res.cart) renderCart(res.cart);
    } else if (rm) {
      line.style.transition = 'opacity .3s, transform .3s';
      line.style.opacity = '0'; line.style.transform = 'translateX(20px)';
      const res = await api('cart.php', { action: 'remove', key });
      if (res.cart) renderCart(res.cart);
    }
  });

  /* Fly-to-cart animation. */
  function flyToCart(fromEl) {
    if (reduceMotion || !fromEl || !cartOpenBtn) return;
    const src = fromEl.querySelector('svg, img') || fromEl;
    const a = src.getBoundingClientRect();
    const b = cartOpenBtn.getBoundingClientRect();
    const clone = src.cloneNode(true);
    clone.classList.add('fly');
    clone.style.left = a.left + 'px'; clone.style.top = a.top + 'px';
    clone.style.width = a.width + 'px'; clone.style.height = a.height + 'px';
    document.body.appendChild(clone);
    const anim = clone.animate([
      { transform: 'translate(0,0) scale(1)', opacity: 1 },
      { transform: 'translate(' + (b.left + b.width / 2 - a.left - a.width / 2) + 'px,' + (b.top + b.height / 2 - a.top - a.height / 2) + 'px) scale(0.08)', opacity: 0.4 }
    ], { duration: 800, easing: 'cubic-bezier(.22,1,.36,1)' });
    anim.onfinish = () => clone.remove();
  }

  /* Add to bag (product page). */
  const buyForm = $('#buy-form');
  if (buyForm) {
    buyForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = $('#buy-btn');
      const grind = buyForm.querySelector('input[name="grind"]:checked');
      if (!grind) { toast('Please choose a grind.', 'error'); return; }
      btn.classList.add('is-loading');
      const res = await api('cart.php', {
        action: 'add',
        product_id: parseInt($('#buy-product').value, 10),
        grind: grind.value,
        qty: parseInt($('#buy-qty').value, 10) || 1
      });
      btn.classList.remove('is-loading');
      if (!res.ok) { toast(res.error || 'Could not add that to your bag.', 'error'); if (res.cart) renderCart(res.cart); return; }
      renderCart(res.cart);
      flyToCart($('#pdp-stage .pdp__img:not([hidden])'));
      toast('Added to your bag');
      setTimeout(() => setDrawer(true), 700);
    });

    $$('[data-qty]', buyForm).forEach(b => b.addEventListener('click', () => {
      const input = $('#buy-qty');
      const v = Math.max(1, Math.min(50, (parseInt(input.value, 10) || 1) + parseInt(b.dataset.qty, 10)));
      input.value = v;
    }));
  }

  /* Product page: switch bag size in place. */
  const pdp = $('#pdp');
  if (pdp) {
    let variants = [];
    try { variants = JSON.parse(pdp.dataset.variants || '[]'); } catch (e) { variants = []; }
    const pills = $$('.pill[data-variant]', pdp);
    pills.forEach(p => p.addEventListener('click', () => {
      const id = parseInt(p.dataset.variant, 10);
      const v = variants.find(x => x.id === id);
      if (!v) return;
      pills.forEach(q => { q.classList.toggle('is-active', q === p); q.setAttribute('aria-checked', q === p ? 'true' : 'false'); });
      $('#buy-product').value = v.id;
      $('#pdp-price').textContent = v.price;
      $('#pdp-per').textContent = v.per;
      $('#pdp-cups').textContent = v.cups;
      $('#pdp-tagline').textContent = v.tagline;
      $('#crumb-size').textContent = v.size;
      const cmp = $('#pdp-compare');
      if (v.compare) { cmp.textContent = v.compare; cmp.hidden = false; } else { cmp.hidden = true; }
      $('#pdp-stage').style.setProperty('--accent', v.accent);
      $$('[data-variant-img]', pdp).forEach(img => {
        const show = parseInt(img.dataset.variantImg, 10) === id;
        img.hidden = !show;
        if (show && !reduceMotion) { img.classList.remove('is-switching'); void img.offsetWidth; img.classList.add('is-switching'); }
      });
      const btn = $('#buy-btn'), label = $('#buy-label');
      if (v.stock === 0) { btn.disabled = true; label.textContent = 'Sold out'; } else { btn.disabled = false; label.textContent = 'Add to bag'; }
      history.replaceState(null, '', v.url);
      document.title = document.title.replace(/^[^·]+/, v.name + ' ' + v.size + ' ');
    }));
  }

  /* Cart page quantity controls. */
  $$('[data-cart-qty]').forEach(b => b.addEventListener('click', async () => {
    const line = b.closest('[data-cart-line]');
    const val = line.querySelector('[data-cart-qty-val]');
    const cur = parseInt(val.textContent, 10) || 1;
    const res = await api('cart.php', { action: 'update', key: b.dataset.key, qty: cur + parseInt(b.dataset.cartQty, 10) });
    if (!res.cart) return;
    renderCart(res.cart);
    syncCartPage(res.cart, line);
  }));
  $$('[data-cart-remove]').forEach(b => b.addEventListener('click', async () => {
    const line = b.closest('[data-cart-line]');
    const res = await api('cart.php', { action: 'remove', key: b.dataset.cartRemove });
    if (!res.cart) return;
    renderCart(res.cart);
    line.style.transition = 'opacity .3s, transform .3s'; line.style.opacity = '0'; line.style.transform = 'translateX(20px)';
    setTimeout(() => { if (!res.cart.items.length) location.reload(); else { line.remove(); syncCartPage(res.cart); } }, 320);
  }));
  function syncCartPage(c, line) {
    if (line) {
      const it = c.items.find(x => x.key === line.dataset.cartLine);
      if (it) { line.querySelector('[data-cart-qty-val]').textContent = it.qty; line.querySelector('[data-cart-line-total]').textContent = it.line; }
      else { line.remove(); if (!c.items.length) location.reload(); }
    }
    const s = $('#cart-summary');
    if (!s) return;
    $('[data-cart-subtotal]', s).textContent = c.subtotal;
    $('[data-cart-shipping]', s).textContent = c.shipping;
    $('[data-cart-total]', s).textContent = c.total;
    $('[data-cart-progress]', s).style.setProperty('--p', c.progress + '%');
    const msg = $('.progress__msg', s);
    if (msg) msg.innerHTML = c.to_free ? 'Add <strong>' + esc(c.to_free) + '</strong> more for free delivery.' : '<strong>Free delivery unlocked.</strong>';
  }

  /* ---------- Brew calculator ------------------------------------------ */
  const brew = $('#brew-calc');
  if (brew) {
    const range = $('#brew-range');
    const methods = $$('.brew__method', brew);
    let m = methods[0];
    const update = () => {
      const cups = parseInt(range.value, 10);
      const ratio = parseFloat(m.dataset.ratio);
      const espresso = m.dataset.espresso === '1';
      let coffee, water;
      if (espresso) { coffee = 18 * cups; water = 36 * cups; }
      else { water = 250 * cups; coffee = water / ratio; }
      $('#brew-cups').textContent = cups;
      $('#brew-coffee').textContent = Math.round(coffee) + ' g';
      $('#brew-water').textContent = espresso ? Math.round(water) + ' g out' : Math.round(water) + ' ml';
      $('#brew-grind').textContent = m.dataset.grind;
      $('#brew-tip').textContent = espresso
        ? 'Dose ' + (18 * cups) + ' g for ' + cups + (cups === 1 ? ' double shot' : ' double shots') + ', aim for a 1:2 yield in 25 to 30 seconds.'
        : 'Water just off the boil, about 94 °C. Brew time ' + m.dataset.time + '. Ratio 1:' + ratio + '.';
      range.style.setProperty('--p', ((cups - 1) / 11 * 100) + '%');
    };
    methods.forEach(b => b.addEventListener('click', () => { methods.forEach(x => x.classList.remove('is-active')); b.classList.add('is-active'); m = b; update(); }));
    range.addEventListener('input', update);
    update();
  }

  /* ---------- Newsletter ------------------------------------------------ */
  const nl = $('#newsletter-form');
  if (nl) {
    nl.addEventListener('submit', async (e) => {
      e.preventDefault();
      const msg = $('#newsletter-msg');
      const btn = nl.querySelector('button');
      btn.classList.add('is-loading');
      const res = await api('newsletter.php', { email: nl.email.value });
      btn.classList.remove('is-loading');
      msg.textContent = res.ok ? res.message : (res.error || 'Please try again.');
      if (res.ok) nl.reset();
    });
  }

  /* ---------- Checkout -------------------------------------------------- */
  const checkout = $('#checkout-form');
  if (checkout) {
    const address = $('#address-fields');
    const shipping = $('#summary-shipping');
    const total = $('#summary-total');
    const syncDelivery = () => {
      const method = (checkout.querySelector('input[name="delivery_method"]:checked') || {}).value || 'courier';
      const collect = method === 'collection';
      if (address) { address.style.display = collect ? 'none' : ''; $$('input, select', address).forEach(i => { i.disabled = collect; }); }
      if (shipping) shipping.textContent = collect ? shipping.dataset.collection : shipping.dataset.courier;
      if (total) total.textContent = collect ? total.dataset.collection : total.dataset.courier;
    };
    const syncPayment = () => {
      const method = (checkout.querySelector('input[name="payment_method"]:checked') || {}).value || '';
      $$('[data-pay-detail]').forEach(d => d.classList.toggle('is-show', d.dataset.payDetail === method));
    };
    $$('input[name="delivery_method"]', checkout).forEach(r => r.addEventListener('change', syncDelivery));
    $$('input[name="payment_method"]', checkout).forEach(r => r.addEventListener('change', syncPayment));
    syncDelivery(); syncPayment();
    checkout.addEventListener('submit', () => { const b = $('#place-order'); if (b) { b.classList.add('is-loading'); b.textContent = 'Placing your order'; } });
  }

  /* ---------- Boot ------------------------------------------------------ */
  if (cartCount && parseInt(cartCount.dataset.count, 10) > 0) refreshCart();
})();
