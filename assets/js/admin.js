(() => {
  const sidebar = document.getElementById('sidebar');
  document.getElementById('menu-toggle')?.addEventListener('click', () => sidebar?.classList.toggle('open'));
  document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm || 'Are you sure?')) event.preventDefault();
  }));
  document.querySelectorAll('input[type="file"][data-preview]').forEach(input => input.addEventListener('change', () => {
    const preview = document.getElementById(input.dataset.preview);
    if (!preview || !input.files?.[0]) return;
    const url = URL.createObjectURL(input.files[0]); preview.src = url; preview.hidden = false;
  }));
})();

