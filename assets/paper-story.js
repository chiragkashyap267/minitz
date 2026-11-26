(() => {
  const preference = matchMedia('(prefers-reduced-motion: reduce)');
  const targets = document.querySelectorAll('main > section > .section-top, .hero-copy, .hero-art, .tale-intro > *, .tale-step > *, .companion-grid article, .culinary-grid article, .tv-copy, .tv-poster, .founders-note > *, .recipe-card, .kitchen-notes > div, .community-copy, .community-heading');
  if ('IntersectionObserver' in window && !preference.matches) {
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('appear-visible'); observer.unobserve(entry.target); }
    }), { threshold: 0.06, rootMargin: '0px 0px -12px 0px' });
    targets.forEach((node, i) => { node.classList.add('appear-ready'); node.style.setProperty('--appear-delay', `${i % 3 * 55}ms`); observer.observe(node); });
    preference.addEventListener('change', () => { if (preference.matches) { targets.forEach(node => node.classList.add('appear-visible')); observer.disconnect(); } });
  }
  document.addEventListener('click', event => {
    const button = event.target.closest('[data-load-youtube]');
    if (!button || !/^[A-Za-z0-9_-]{11}$/.test(button.dataset.youtubeId || '')) return;
    const iframe = document.createElement('iframe');
    iframe.src = `https://www.youtube-nocookie.com/embed/${button.dataset.youtubeId}?autoplay=1&playsinline=1&rel=0`;
    iframe.title = button.dataset.youtubeTitle || 'Minitz TV broadcast';
    iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
    iframe.allowFullscreen = true; iframe.referrerPolicy = 'strict-origin-when-cross-origin';
    iframe.className = 'tv-live-frame'; button.replaceWith(iframe); iframe.focus();
  });
  const story = document.querySelector('[data-paper-tale]');
  if (!story) return;
  const steps = [...story.querySelectorAll('[data-tale-step]')];
  const scenes = [...story.querySelectorAll('[data-tale-scene]')];
  let queued = false, current = -1, visible = true;
  const draw = () => {
    queued = false;
    if (!visible) return;
    const focus = innerWidth <= 800 ? innerHeight * .78 : innerHeight * .55;
    let nearest = 0, distance = Infinity;
    steps.forEach((step, index) => { const box = step.getBoundingClientRect(); const delta = Math.abs(box.top + box.height / 2 - focus); if (delta < distance) { nearest = index; distance = delta; } });
    if (current !== nearest) { current = nearest; scenes.forEach((scene, index) => scene.classList.toggle('is-current', index === current)); }
    const start = steps[0].getBoundingClientRect(), end = steps.at(-1).getBoundingClientRect();
    const progress = Math.max(0, Math.min(1, (focus - start.top - start.height / 2) / Math.max(1, end.top + end.height / 2 - start.top - start.height / 2)));
    story.style.setProperty('--tale-progress', progress.toFixed(4));
    story.style.setProperty('--tale-drift', preference.matches ? 0 : Math.sin(progress * Math.PI * 6).toFixed(4));
  };
  const queue = () => { if (!queued && visible) { queued = true; requestAnimationFrame(draw); } };
  if ('IntersectionObserver' in window) new IntersectionObserver(entries => { visible = entries[0].isIntersecting; if (visible) queue(); }, {rootMargin:'200px'}).observe(story);
  addEventListener('scroll', queue, { passive: true }); addEventListener('resize', queue); preference.addEventListener('change', queue); draw();
})();
