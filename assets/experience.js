(() => {
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
  const drawer = document.querySelector('#cart-drawer');
  let drawerBusy = false;
  let drawerOpener;
  const announce = (message, error = false) => {
    const target = drawer?.querySelector('[data-cart-feedback]');
    if (target) { target.textContent = message || ''; target.classList.toggle('is-error', error); }
  };
  const renderCart = data => {
    const focusName = drawer?.contains(document.activeElement) ? document.activeElement.getAttribute('name') : null;
    const focusStep = drawer?.contains(document.activeElement) ? document.activeElement.dataset.cartStep : null;
    const stepName = focusStep ? document.activeElement.closest('.quantity-control')?.querySelector('input')?.name : null;
    if (drawer && data.html) drawer.innerHTML = data.html;
    document.querySelectorAll('[data-cart-count]').forEach(badge => { badge.textContent = data.count; badge.hidden = !data.count; });
    announce(data.message);
    if (drawer?.open) (focusName ? drawer.querySelector(`[name="${CSS.escape(focusName)}"]`) : null)?.focus({ preventScroll: true });
    if (drawer?.open && stepName) drawer.querySelector(`[name="${CSS.escape(stepName)}"]`)?.closest('.quantity-control')?.querySelector(`[data-cart-step="${focusStep}"]`)?.focus({ preventScroll: true });
    if (drawer?.open && !drawer.contains(document.activeElement)) drawer.querySelector('[aria-label="Close cart"]')?.focus({ preventScroll: true });
  };
  const openDrawer = () => {
    if (!drawer || drawer.open) return;
    drawerOpener = document.activeElement;
    drawer.showModal(); document.body.classList.add('has-cart');
  };
  const cartRequest = async (url, body) => {
    if (drawerBusy) return;
    drawerBusy = true;
    drawer?.setAttribute('aria-busy', 'true');
    try {
      const response = await fetch(url, { method: body ? 'POST' : 'GET', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(body ? { 'X-CSRF-TOKEN': csrf() || '' } : {}) }, ...(body ? { body } : {}) });
      const data = await response.json();
      if (!response.ok) throw new Error(response.status === 419 ? 'Your session has expired. Refresh the page and try again.' : Object.values(data.errors || {}).flat()[0] || data.message || 'Something went wrong. Please try again.');
      renderCart(data);
      return data;
    } catch (error) { announce(error.message, true); return null; }
    finally { drawerBusy = false; drawer?.removeAttribute('aria-busy'); }
  };
  drawer?.addEventListener('close', () => { document.body.classList.remove('has-cart'); drawerOpener?.focus?.(); });
  document.addEventListener('click', async event => {
    const trigger = event.target.closest('[data-open-cart]');
    if (trigger && drawer && !event.ctrlKey && !event.metaKey) { event.preventDefault(); openDrawer(); await cartRequest('/cart/drawer'); }
    const close = event.target.closest('[data-close]');
    if (close) close.closest('dialog')?.close();
    const step = event.target.closest('[data-cart-step]');
    const remove = event.target.closest('[data-cart-remove]');
    if ((step || remove) && !drawerBusy) {
      const form = (step || remove).closest('form');
      const input = step ? step.closest('.quantity-control').querySelector('input') : form.querySelector(`[name="quantities[${remove.dataset.cartRemove}]"]`);
      input.value = remove ? 0 : Math.max(0, Math.min(Number(input.max), Number(input.value) + Number(step.dataset.cartStep)));
      await cartRequest(form.action, new FormData(form));
    }
    const demo = event.target.closest('[data-demo-login]');
    if (demo) {
      const form = document.querySelector('form[action="/account/login"]');
      if (form) { form.elements.email.value = 'cook@minitz.test'; form.elements.password.value = 'Minitz-Cook-2026!'; form.querySelector('button').focus(); }
    }
    if (event.target.closest('[data-demo-checkout]')) {
      const form = document.querySelector('form[action="/checkout"]');
      if (form) Object.entries({ name: 'Sample Cook', email: 'cook@minitz.test', phone: '9990000000', line1: 'Sample Kitchen, 12 Preview Lane', city: 'New Delhi', state: 'Delhi', pincode: '110001' }).forEach(([key, value]) => { if (!form.elements[key].readOnly && !form.elements[key].value) form.elements[key].value = value; });
    }
    const print = event.target.closest('[data-print]');
    // Existing print buttons are bound by store.js. Dynamic receipts use delegation.
    if (print && print.closest('#recipe-receipt-outlet')) window.print();
    const receiptClose = event.target.closest('[data-receipt-close]');
    if (receiptClose) {
      const outlet = receiptClose.closest('.recipe-receipt-outlet'); outlet.hidden = true;
      document.querySelectorAll('[data-recipe-receipt]').forEach(a => a.setAttribute('aria-expanded', 'false'));
      document.querySelector('[data-recipe-receipt].is-selected')?.focus();
    }
  });
  document.addEventListener('submit', async event => {
    const form = event.target;
    if (event.submitter?.hasAttribute('data-buy-now')) return;
    const isAdd = form.matches('form[action="/cart/add"]');
    const isCart = form.matches('[data-cart-form]');
    if (!drawer || (!isAdd && !isCart) || document.body.hasAttribute('data-static-preview')) return;
    event.preventDefault();
    if (drawerBusy) return;
    const button = form.querySelector('button[type="submit"], button:not([type])');
    if (button) button.disabled = true;
    if (isAdd) openDrawer();
    await cartRequest(form.action, new FormData(form));
    if (button) button.disabled = false;
  });
  document.addEventListener('change', async event => {
    const input = event.target.closest('[data-cart-quantity]');
    if (input) { input.value = Math.max(0, Math.min(Number(input.max), Number(input.value) || 0)); await cartRequest(input.form.action, new FormData(input.form)); }
  });

  let receiptAbort;
  document.addEventListener('click', async event => {
    const link = event.target.closest('[data-recipe-receipt]');
    const outlet = document.querySelector('#recipe-receipt-outlet');
    if (!link || !outlet || event.ctrlKey || event.metaKey || event.shiftKey) return;
    event.preventDefault(); receiptAbort?.abort(); receiptAbort = new AbortController();
    document.querySelectorAll('[data-recipe-receipt]').forEach(a => { a.setAttribute('aria-expanded', String(a === link)); a.classList.toggle('is-selected', a === link); });
    outlet.hidden = false; outlet.setAttribute('aria-busy', 'true'); outlet.innerHTML = '<p class="receipt-loading" role="status">A little recipe is printing…</p>';
    try {
      const response = await fetch(`${link.getAttribute('href')}/receipt`, { headers: { Accept: 'application/json' }, signal: receiptAbort.signal });
      if (!response.ok) throw new Error('Recipe unavailable');
      const data = await response.json();
      outlet.innerHTML = '<button class="receipt-close icon-button" data-receipt-close aria-label="Close recipe receipt">×</button>' + data.html;
      outlet.scrollIntoView({ behavior: reducedMotion.matches ? 'instant' : 'smooth', block: 'start' });
      outlet.querySelector('h2')?.focus({ preventScroll: true });
    } catch (error) {
      if (error.name !== 'AbortError') { outlet.textContent = 'This recipe could not be printed. '; const fallback = document.createElement('a'); fallback.href = link.href; fallback.textContent = 'Open the recipe page'; outlet.append(fallback); }
    } finally { outlet.removeAttribute('aria-busy'); }
  });

  const marquee = document.querySelector('.video-marquee');
  const marqueeToggle = document.querySelector('[data-marquee-toggle]');
  marqueeToggle?.addEventListener('click', () => {
    const paused = marquee.classList.toggle('is-paused'); marqueeToggle.setAttribute('aria-pressed', String(paused)); marqueeToggle.textContent = paused ? 'Play the stories' : 'Pause the stories';
  });
  const videoDialog = document.querySelector('#video-dialog');
  let restoreMarquee = false;
  document.addEventListener('click', event => {
    const button = event.target.closest('[data-kitchen-video]'); if (!button || !videoDialog) return;
    videoDialog.querySelector('h2').textContent = button.dataset.videoTitle;
    const video = videoDialog.querySelector('video'); const placeholder = videoDialog.querySelector('.video-coming-soon');
    video.hidden = !button.dataset.videoSrc; placeholder.hidden = !!button.dataset.videoSrc;
    if (button.dataset.videoSrc) { video.src = button.dataset.videoSrc; video.poster = button.dataset.videoPoster || ''; }
    restoreMarquee = !marquee?.classList.contains('is-paused'); marquee?.classList.add('is-paused'); videoDialog.showModal();
  });
  videoDialog?.addEventListener('close', () => { const video = videoDialog.querySelector('video'); video.pause(); video.removeAttribute('src'); video.load(); if (restoreMarquee) marquee?.classList.remove('is-paused'); });

  const moving = [...document.querySelectorAll('[data-parallax], .chapter-scene')];
  let scheduled = false;
  const updateMotion = () => {
    scheduled = false;
    if (reducedMotion.matches) { moving.forEach(node => node.style.removeProperty('--scroll-y')); return; }
    const height = window.innerHeight;
    moving.forEach(node => {
      const rect = node.getBoundingClientRect();
      if (rect.bottom < -100 || rect.top > height + 100) return;
      const amount = Number(node.dataset.parallax || -0.018);
      node.style.setProperty('--scroll-y', `${Math.max(-18, Math.min(18, (height / 2 - rect.top - rect.height / 2) * amount))}px`);
    });
  };
  addEventListener('scroll', () => { if (!scheduled) { scheduled = true; requestAnimationFrame(updateMotion); } }, { passive: true });
  addEventListener('resize', updateMotion); reducedMotion.addEventListener('change', updateMotion); updateMotion();
})();
