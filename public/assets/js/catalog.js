document.querySelectorAll('[data-catalog-filter]').forEach((select) => {
  select.addEventListener('change', () => {
    select.form.querySelector('button[type=submit]').textContent = 'Apply filters';
  });
});
