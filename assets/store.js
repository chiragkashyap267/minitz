document.documentElement.classList.add('js');
if (document.body.hasAttribute('data-static-preview')) {
  document.querySelectorAll('form[method="post"]').forEach(form => form.addEventListener('submit', event => event.preventDefault()));
}
const menu = document.querySelector('.menu-toggle');
menu?.addEventListener('click', () => {
  const open = menu.getAttribute('aria-expanded') !== 'true';
  menu.setAttribute('aria-expanded', String(open));
  document.querySelector('.main-nav')?.classList.toggle('is-open', open);
});
document.querySelectorAll('[data-dismiss]').forEach(button => button.addEventListener('click', () => button.closest('.toast')?.remove()));
document.querySelectorAll('[data-step]').forEach(button => button.addEventListener('click', () => {
  const input = button.closest('.quantity-control').querySelector('input');
  input.value = Math.max(Number(input.min), Math.min(Number(input.max), Number(input.value) + Number(button.dataset.step)));
}));
document.querySelectorAll('[data-dialog]').forEach(button => button.addEventListener('click', () => document.getElementById(button.dataset.dialog)?.showModal()));
document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
document.querySelectorAll('dialog').forEach(dialog => dialog.addEventListener('click', event => { if (event.target === dialog) { const box = dialog.getBoundingClientRect(); if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) dialog.close(); } }));
document.querySelectorAll('[data-confirm]').forEach(button => button.addEventListener('click', event => { if (!window.confirm(button.dataset.confirm)) event.preventDefault(); }));
document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
  const observer = new IntersectionObserver(entries => entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('in-view'); observer.unobserve(entry.target); } }), { threshold: 0.08 });
  document.querySelectorAll('.reveal').forEach(element => observer.observe(element));
} else document.querySelectorAll('.reveal').forEach(element => element.classList.add('in-view'));

document.addEventListener('change', event => {
  if (event.target.name !== 'related_product_ids[]') return;
  const group = event.target.closest('[data-suggestion-options]');
  if (!group) return;
  const mode = group.closest('form').querySelector('[name="suggestion_mode"]');
  if (mode) mode.value = 'manual';
  const count = group.querySelectorAll('input[type="checkbox"]:checked').length;
  const status = group.closest('form').querySelector('[data-selection-count]');
  if (status) status.textContent = `${count} selected${count > 8 ? ' — choose up to 8' : ''}`;
});
