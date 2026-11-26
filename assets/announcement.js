document.addEventListener('click', event => {
  const button = event.target.closest('.announcement-pause');
  if (!button) return;
  const paused = button.closest('.announcement-marquee').classList.toggle('is-paused');
  button.setAttribute('aria-pressed', String(paused));
  button.setAttribute('aria-label', paused ? 'Resume announcements' : 'Pause announcements');
  button.textContent = paused ? '▶' : 'Ⅱ';
});
