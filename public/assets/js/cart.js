function updateQuantityButtons(control) {
  const input = control.querySelector('input');
  control.querySelectorAll('[data-quantity-step]').forEach((button) => {
    const bound = Number(button.dataset.quantityStep) < 0 ? Number(input.min || 1) : Number(input.max || 999);
    button.disabled = Number(button.dataset.quantityStep) < 0 ? Number(input.value) <= bound : Number(input.value) >= bound;
  });
}
document.querySelectorAll('.quantity-control').forEach((control) => {
  updateQuantityButtons(control);
  control.querySelector('input').addEventListener('input', () => updateQuantityButtons(control));
});
document.querySelectorAll('[data-quantity-step]').forEach((button) => {
  button.addEventListener('click', () => {
    const input = button.closest('.quantity-control').querySelector('input');
    const next = Number(input.value) + Number(button.dataset.quantityStep);
    input.value = String(Math.max(Number(input.min || 1), Math.min(Number(input.max || 99), next)));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    updateQuantityButtons(button.closest('.quantity-control'));
  });
});
const cartForms = [...document.querySelectorAll('form[data-cart-quantity]')];
if (cartForms.length) {
  const pending = new Map();
  const approvedForms = new WeakSet();
  const removingRows = new WeakSet();
  let mutationTail = Promise.resolve();
  let mutationCount = 0;
  function runMutation(task) {
    mutationCount++;
    const operation = mutationTail.then(task).finally(() => { mutationCount--; });
    mutationTail = operation.catch(() => {});
    return operation;
  }
  let debounce;
  let saving;
  const failedForms = new Set();
  function statusFor(form, message) {
    const status = form.closest('.cart-item').querySelector('[data-cart-status]');
    status.hidden = false;
    status.textContent = message;
  }
  function queue(form) {
    if (removingRows.has(form)) return;
    pending.set(form, new FormData(form));
    statusFor(form, 'Saving your changes...');
    clearTimeout(debounce);
    debounce = setTimeout(flush, 350);
  }
  async function savePending() {
    while (pending.size) {
      const [form, data] = pending.entries().next().value;
      pending.delete(form);
      if (!form.checkValidity()) {
        statusFor(form, 'Choose an available quantity, then select Update.');
        failedForms.add(form);
        continue;
      }
      const button = form.querySelector('button[type="submit"]');
      form.setAttribute('aria-busy', 'true');
      button.disabled = true;
      try {
        await runMutation(async () => {
        const response = await fetch(form.getAttribute('action'), {method: 'POST', body: data, credentials: 'same-origin'});
        const documentCopy = new DOMParser().parseFromString(await response.text(), 'text/html');
        const summary = documentCopy.querySelector('.summary-box');
        if (!response.ok || !summary || !documentCopy.querySelector('form[data-cart-quantity]')) throw new Error('Save failed');
        const messages = documentCopy.querySelector('.flash-wrap');
        const rejected = Boolean(messages?.querySelector('.error'));
        document.querySelector('.summary-box').replaceWith(summary);
        documentCopy.querySelectorAll('.cart-item').forEach((savedItem) => {
          const id = savedItem.querySelector('[name="product_id"]').value;
          const currentForm = cartForms.find((row) => row.querySelector('[name="product_id"]').value === id);
          if (!currentForm) return;
          const currentItem = currentForm.closest('.cart-item');
          currentItem.querySelector('.item-total').textContent = savedItem.querySelector('.item-total').textContent;
          if (!rejected && currentForm === form && !pending.has(form) && currentForm.elements.quantity.value === data.get('quantity')) {
            currentForm.elements.quantity.value = savedItem.querySelector('[name="quantity"]').value;
            currentForm.elements.quantity.max = savedItem.querySelector('[name="quantity"]').max;
            updateQuantityButtons(currentForm.querySelector('.quantity-control'));
          }
        });
        const bag = documentCopy.querySelector('.cart-link');
        document.querySelector('.cart-link').setAttribute('aria-label', bag.getAttribute('aria-label'));
        document.querySelector('.cart-count').textContent = bag.querySelector('.cart-count').textContent;
        let flash = document.querySelector('.flash-wrap');
        if (messages) {
          if (flash) flash.replaceWith(messages);
          else document.getElementById('main').before(messages);
        } else if (flash) flash.remove();
        if (rejected) {
          failedForms.add(form);
          statusFor(form, 'This change could not be saved. Check the message above, then try Update.');
        } else {
          failedForms.delete(form);
          if (!pending.has(form)) statusFor(form, 'Bag updated. Your total is up to date.');
        }
        });
      } catch (error) {
        failedForms.add(form);
        statusFor(form, 'Your change could not be saved. Check your connection, then select Update to retry.');
      } finally {
        form.removeAttribute('aria-busy');
        button.disabled = false;
      }
    }
    return failedForms.size === 0;
  }
  function flush() {
    clearTimeout(debounce);
    if (!saving) saving = savePending().finally(() => { saving = null; });
    return saving;
  }
  async function finishChanges() {
    do { await flush(); await mutationTail; } while (pending.size || saving || mutationCount);
    return failedForms.size === 0;
  }
  async function removeArrangement(form, row, button) {
    button.disabled = true;
    statusFor(row, 'Removing arrangement...');
    try {
      await runMutation(async () => {
      const response = await fetch(form.getAttribute('action'), {method: 'POST', body: new FormData(form), credentials: 'same-origin'});
      const documentCopy = new DOMParser().parseFromString(await response.text(), 'text/html');
      const summary = documentCopy.querySelector('.summary-box');
      const id = row.elements.product_id.value;
      const stillPresent = [...documentCopy.querySelectorAll('form[data-cart-quantity]')].some((saved) => saved.elements.product_id.value === id);
      if (!response.ok || stillPresent || (!summary && !documentCopy.querySelector('.empty-state'))) throw new Error('Remove failed');
      row.closest('.cart-item').remove();
      failedForms.delete(row);
      pending.delete(row);
      if (!summary) { window.location.assign(response.url); return; }
      document.querySelector('.summary-box').replaceWith(summary);
      const bag = documentCopy.querySelector('.cart-link');
      document.querySelector('.cart-link').setAttribute('aria-label', bag.getAttribute('aria-label'));
      document.querySelector('.cart-count').textContent = bag.querySelector('.cart-count').textContent;
      const messages = documentCopy.querySelector('.flash-wrap');
      if (messages) {
        const previous = document.querySelector('.flash-wrap');
        if (previous) previous.replaceWith(messages);
        else document.getElementById('main').before(messages);
      }
      const nextRow = cartForms.find((candidate) => candidate.isConnected);
      nextRow?.elements.quantity.focus();
      });
    } catch (error) {
      failedForms.add(row);
      statusFor(row, 'This arrangement could not be removed. Check your connection, then try Remove again.');
    } finally {
      button.disabled = false;
      removingRows.delete(row);
      row.querySelectorAll('input[name="quantity"], button').forEach((control) => { control.disabled = false; });
      updateQuantityButtons(row.querySelector('.quantity-control'));
    }
  }
  cartForms.forEach((form) => {
    form.addEventListener('change', () => queue(form));
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      queue(form);
      flush();
    }, true);
  });
  document.addEventListener('click', async (event) => {
    const link = event.target.closest('a[href]');
    if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || link.hash || (!pending.size && !saving && !failedForms.size && !mutationCount)) return;
    event.preventDefault();
    const saved = await finishChanges();
    const needsSavedBag = /\/(checkout|payment)\.php$/.test(new URL(link.href).pathname);
    if (saved || !needsSavedBag) window.location.assign(link.href);
    else [...failedForms][0]?.elements.quantity.focus();
  });
  document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (approvedForms.delete(form) || form.matches('[data-cart-quantity]') || (!pending.size && !saving && !failedForms.size && !mutationCount)) return;
    event.preventDefault();
    const removing = form.elements.namedItem('action')?.value === 'remove';
    const removedRow = removing ? cartForms.find((row) => row.elements.product_id.value === form.elements.product_id.value) : null;
    if (removedRow) {
      pending.delete(removedRow); failedForms.delete(removedRow); removingRows.add(removedRow);
      removedRow.querySelectorAll('input[name="quantity"], button').forEach((control) => { control.disabled = true; });
    }
    await finishChanges();
    if (removedRow) failedForms.delete(removedRow);
    const loggingOut = /\/logout\.php$/.test(new URL(form.getAttribute('action'), location.href).pathname);
    if (removedRow) {
      await removeArrangement(form, removedRow, event.submitter);
    } else if (loggingOut || !failedForms.size) {
      approvedForms.add(form);
      // Leave the native submission event before dispatching its replacement.
      const submitter = event.submitter;
      setTimeout(() => form.requestSubmit(submitter), 0);
    } else [...failedForms][0]?.elements.quantity.focus();
  }, true);
}
