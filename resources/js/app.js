import './bootstrap';

document.addEventListener('click', (e) => {
    const btn = e.target.closest('.rating .star');
    if (!btn) return;
    const container = btn.closest('.rating');
    const value = parseInt(btn.getAttribute('data-value'), 10);
    const inputId = container.getAttribute('data-input');
    const input = document.getElementById(inputId);
    if (input) input.value = value;
    container.querySelectorAll('.star').forEach((el) => {
        const v = parseInt(el.getAttribute('data-value'), 10);
        el.textContent = v <= value ? '★' : '☆';
    });
});
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.toggle-password');
  if (!btn) return;
  const targetId = btn.getAttribute('data-target');
  const input = document.getElementById(targetId);
  if (!input) return;
  const isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  btn.textContent = isHidden ? 'Ocultar' : 'Mostrar';
  btn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
});
