(function ($) {
  async function boot() {
    // Wait for Frame.js to load
    if (typeof window.Frame === 'undefined') {
      setTimeout(boot, 50);
      return;
    }

    const cfg = document.querySelector('#frame-js-config');
    if (!cfg) return;

    const pk = cfg.getAttribute('data-pk');
    if (!pk) return;

    // Prevent double-initialization (checkout reloads trigger multiple times)
    if (window.__frame_wc_inited) return;
    window.__frame_wc_inited = true;

    // Initialize Frame.js
    const frame = await window.Frame.init(pk);

    // Mount a single Card element
    const mountEl = document.getElementById('frame-card-fields');
    if (!mountEl || mountEl.dataset.mounted === '1') return;

    const card = await frame.createElement('card', {
      theme: frame.themes('clean'),
    });
    card.mount('#frame-card-fields');
    mountEl.dataset.mounted = '1';

    // Listen for card completion
    card.on('complete', (payload) => {
      console.log('[Frame WC] card complete:', payload);

      const card = payload?.card || {};
      const cardData = {
        number: card.number,
        exp_month: card.expiry?.month,
        exp_year: card.expiry?.year,
        cvc: card.cvc,
      };

      const json = JSON.stringify(cardData);

      // Always ensure hidden input exists
      let hidden =
        document.getElementById('frame_payment_method_data') ||
        document.querySelector('input[name="frame_payment_method_data"]');
      if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id = 'frame_payment_method_data';
        hidden.name = 'frame_payment_method_data';
        (document.querySelector('form.checkout') || document.body).appendChild(hidden);
      }

      hidden.value = json;
      console.log('[Frame WC] wrote hidden JSON:', hidden.value);
    });

    // When checkout updates (AJAX refresh), clear stale card data
    $(document.body).on('updated_checkout', () => {
      const hidden = document.getElementById('frame_payment_method_data');
      if (hidden) hidden.value = '';
    });

    // Bind WooCommerce form submission for this gateway
    const $form = $('form.checkout');
    if ($form.length && !$form.data('frame-bound')) {
      $form.data('frame-bound', true);

      $form.on('checkout_place_order_frame', function () {
        console.log('[Frame WC] submit hook fired');

        const val = $('#frame_payment_method_data').val();
        if (!val || val === '{}') {
          window.wc_checkout_form?.submit_error?.(
            '<div class="woocommerce-error">Please complete your card details.</div>'
          );
          return false;
        }

        return true;
      });
    }
  }

  $(boot);
  $(document.body).on('updated_checkout wc-credit-card-form-init', boot);
})(jQuery);