const methodInputs = document.querySelectorAll('input[name="fulfillment_method"]');
const deliveryFields = document.querySelector('[data-delivery-fields]');
function updateFulfillment() {
  const method = document.querySelector('input[name="fulfillment_method"]:checked')?.value;
  if (deliveryFields) {
    deliveryFields.hidden = method !== 'delivery';
    deliveryFields.querySelectorAll('input,textarea').forEach((input) => {
      input.disabled = method !== 'delivery';
      input.required = method === 'delivery';
    });
  }
  document.querySelectorAll('[data-method-fee]').forEach((element) => {
    element.hidden = element.dataset.methodFee !== method;
  });
}
methodInputs.forEach((input) => input.addEventListener('change', updateFulfillment));
updateFulfillment();
const giftMessage = document.getElementById('gift_message');
const characterCount = document.querySelector('[data-character-count]');
if (giftMessage && characterCount) {
  characterCount.hidden = false;
  const updateCount = () => { characterCount.textContent = `${giftMessage.value.length} / ${giftMessage.maxLength}`; };
  giftMessage.addEventListener('input', updateCount);
  updateCount();
}
