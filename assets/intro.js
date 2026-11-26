(() => {
  const root = document.documentElement;
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (motion.matches) return;
  const key = 'minitz-intro-seen-v1';
  const reload = performance.getEntriesByType('navigation')[0]?.type === 'reload';
  try { if (sessionStorage.getItem(key) && !reload) return; sessionStorage.setItem(key, '1'); } catch (_) { /* Intro still works when storage is unavailable. */ }
  let leaving = false, exitTimer, fallbackTimer, removalTimer;
  const remove = () => {
    clearTimeout(exitTimer); clearTimeout(fallbackTimer); clearTimeout(removalTimer);
    document.getElementById('minitz-intro')?.remove();
    root.classList.remove('has-brand-intro');
    document.removeEventListener('keydown', onKey);
    window.removeEventListener('pagehide', remove);
    window.removeEventListener('pageshow', onPageShow);
    motion.removeEventListener('change', onMotion);
  };
  const finish = () => {
    if (leaving) return;
    leaving = true;
    const overlay = document.getElementById('minitz-intro');
    if (!overlay || motion.matches) { remove(); return; }
    overlay.classList.add('is-leaving');
    removalTimer = setTimeout(remove, 560);
  };
  const onKey = event => { if (event.key === 'Escape' || event.key === 'Tab') remove(); };
  const onMotion = () => { if (motion.matches) remove(); };
  const onPageShow = event => { if (event.persisted) remove(); };
  root.classList.add('has-brand-intro');
  document.addEventListener('keydown', onKey);
  motion.addEventListener('change', onMotion);
  window.addEventListener('pagehide', remove);
  window.addEventListener('pageshow', onPageShow);
  fallbackTimer = setTimeout(finish, 2600);
  const ready = () => {
    if (leaving) return;
    const overlay = document.getElementById('minitz-intro');
    overlay?.addEventListener('pointerdown', remove, { once: true });
    exitTimer = setTimeout(finish, 1950);
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready, { once: true });
  else ready();
})();
