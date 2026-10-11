const menuButton = document.querySelector('.menu-toggle');
const navigation = document.getElementById('site-nav');
const accountMenus = document.querySelectorAll('.account-menu');
function closeAccounts(restoreFocus = false) {
  accountMenus.forEach((menu) => {
    if (menu.open) {
      menu.open = false;
      if (restoreFocus) menu.querySelector('summary').focus();
    }
  });
}

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
  const input = document.getElementById(button.getAttribute('aria-controls'));
  if (!input) return;
  button.hidden = false;
  button.addEventListener('click', () => {
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.textContent = visible ? 'Hide' : 'Show';
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
  });
  button.setAttribute('aria-label', 'Show password');
});
const errorSummary = document.querySelector('[data-error-summary]');
if (errorSummary) {
  errorSummary.focus();
  errorSummary.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener('click', (event) => {
      const field = document.getElementById(link.hash.slice(1));
      if (!field || field.disabled) return;
      event.preventDefault();
      field.focus();
    });
  });
}
document.querySelectorAll('form[method="post"]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;
    if (form.getAttribute('aria-busy') === 'true') { event.preventDefault(); return; }
    form.setAttribute('aria-busy', 'true');
    form.querySelectorAll('button[type="submit"]').forEach((button) => {
      button.dataset.originalContent = button.innerHTML;
      button.dataset.originalDisabled = String(button.disabled);
      button.disabled = true;
      button.textContent = form.dataset.submittingLabel || 'Please wait...';
    });
  });
});
window.addEventListener('pageshow', () => {
  document.querySelectorAll('form[aria-busy="true"]').forEach((form) => {
    form.removeAttribute('aria-busy');
    form.querySelectorAll('button[data-original-content]').forEach((button) => {
      button.innerHTML = button.dataset.originalContent;
      button.disabled = button.dataset.originalDisabled === 'true';
      delete button.dataset.originalContent;
      delete button.dataset.originalDisabled;
    });
  });
});
document.addEventListener('click', (event) => {
  accountMenus.forEach((menu) => { if (!menu.contains(event.target)) menu.open = false; });
});
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && [...accountMenus].some((menu) => menu.open)) {
    closeAccounts(true);
    event.stopImmediatePropagation();
  }
});
if (menuButton && navigation) {
  menuButton.hidden = false;
  navigation.classList.add('enhanced');
  menuButton.addEventListener('click', () => {
    const expanded = menuButton.getAttribute('aria-expanded') !== 'true';
    menuButton.setAttribute('aria-expanded', String(expanded));
    navigation.classList.toggle('is-open', expanded);
    if (!expanded) closeAccounts();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && navigation.classList.contains('is-open')) {
      navigation.classList.remove('is-open');
      menuButton.setAttribute('aria-expanded', 'false');
      menuButton.focus();
    }
  });
}
