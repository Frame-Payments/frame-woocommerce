(function ($) {
  let frameInstance = null;
  let cardElement = null;
  let mountedOnce = false;

  async function initFrame() {
    if (frameInstance) return frameInstance;
    if (typeof window.Frame === 'undefined') return null;

    const cfg = document.querySelector('#frame-js-config');
    if (!cfg) return null;

    const pk = cfg.getAttribute('data-pk');
    if (!pk) return null;

    frameInstance = await window.Frame.init(pk);
    return frameInstance;
  }

  async function mountCard() {
    const container = document.getElementById('frame-card');
    if (!container) return;                 // nothing to mount into

    // If Woo refreshed the DOM, mount again (but never duplicate)
    if (container.dataset.mounted === '1' && cardElement) return;

    const frame = await initFrame();
    if (!frame) return;

    // If a previous element exists (after updated_checkout), unmount it
    if (typeof cardElement?.unmount === 'function') {
      try { cardElement.unmount(); } catch (e) {}
      cardElement = null;
    }

    cardElement = await frame.createElement('card', {
      theme: frame.themes('clean'), // neutral theme with placeholders
    });

    await cardElement.mount('#frame-card');
    container.dataset.mounted = '1';
    mountedOnce = true;

    // Keep hidden field updated for server-side processing
    cardElement.on('complete', (payload) => {
      const hidden = document.getElementById('frame_payment_method_data')
                || document.querySelector('input[name="frame_payment_method_data"]');
      if (hidden) hidden.value = JSON.stringify(payload?.card || {});
    });
  }

  // Woo will call this before placing an order with your gateway
  function bindSubmitGuard() {
    const $form = $('form.checkout');
    if (!$form.length || $form.data('frame-guard-bound')) return;

    $form.data('frame-guard-bound', true);
    $form.on('checkout_place_order_frame', async function () {
      // Ensure card is present & complete
      if (!cardElement) {
        await mountCard();
      }

      const state = (typeof cardElement?.getState === 'function')
        ? await cardElement.getState()
        : null;

      if (!state?.isComplete) {
        window.wc_checkout_form?.submit_error?.(
          '<ul class="woocommerce-error"><li>Please complete your card details.</li></ul>'
        );
        return false;
      }

      const hidden = document.getElementById('frame_payment_method_data');
      if (!hidden || !hidden.value) {
        window.wc_checkout_form?.submit_error?.(
          '<ul class="woocommerce-error"><li>Payment details missing. Please try again.</li></ul>'
        );
        return false;
      }

      return true;
    });
  }

  function boot() {
    // initial render
    mountCard();
    bindSubmitGuard();
  }

  $(boot);
  // Remount after Woo refreshes checkout fragments
  $(document.body).on('updated_checkout wc-credit-card-form-init', () => {
    // Reset mounted flag if Woo replaced the mount node
    const container = document.getElementById('frame-card');
    if (container && container.dataset.mounted !== '1') {
      mountCard();
    }
    bindSubmitGuard();
  });
})(jQuery);