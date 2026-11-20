// Scripts comunes de MeetHub.

// Confirmar acciones destructivas:  <a href="..." data-confirm="¿Eliminar?">
document.addEventListener('click', function (ev) {
  var el = ev.target.closest('[data-confirm]');
  if (el && !window.confirm(el.getAttribute('data-confirm'))) {
    ev.preventDefault();
  }
});

// Mostrar/ocultar contraseña:  <button data-toggle-password="#idDelInput">
document.addEventListener('click', function (ev) {
  var btn = ev.target.closest('[data-toggle-password]');
  if (!btn) return;
  var input = document.querySelector(btn.getAttribute('data-toggle-password'));
  if (input) input.type = input.type === 'password' ? 'text' : 'password';
});
