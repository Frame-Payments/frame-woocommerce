(function ($) {
  let frameInstance = null;
  let cardElement = null;

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

  function captureSonarSessionId() {
    try {
      // Frame.js automatically stores session ID here after Frame.init()
      const sessionId = localStorage.getItem('frame_charge_session_id');

      if (!sessionId) {
        return;
      }

      const form = document.querySelector('form.checkout');
      if (!form) {
        return;
      }

      let hidden = document.getElementById('frame_sonar_session_id');

      if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id = 'frame_sonar_session_id';
        hidden.name = 'frame_sonar_session_id';
        form.appendChild(hidden);
      }

      hidden.value = sessionId;
    } catch (e) {
      if (window.console && console.error) {
        console.error('[Frame] Error capturing Sonar session ID:', e);
      }
    }
  }

  function setHidden(valueObj) {
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
    hidden.value = valueObj ? JSON.stringify(valueObj) : '';
  }

  function normalizeCardPayload(card) {
    if (!card) return null;
    return {
      number: card.number || null,
      exp_month: card.expiry?.month || null,
      exp_year: card.expiry?.year || null,
      cvc: card.cvc || null,
    };
  }

  async function mountCard() {
    // CHANGE THIS IF YOUR PHP OUTPUT USES A DIFFERENT ID
    const mountSelector = '#frame-card';
    const container = document.querySelector(mountSelector);
    if (!container) return;

    // If Woo refreshed the DOM, avoid double-mount
    if (container.dataset.mounted === '1' && cardElement) return;

    const frame = await initFrame();
    if (!frame) return;

    // Clean up any old element
    try { cardElement?.unmount?.(); } catch (e) {}
    cardElement = null;

    cardElement = await frame.createElement('card', {
      theme: frame.themes('clean'),
    });

    await cardElement.mount(mountSelector);
    container.dataset.mounted = '1';

    // Keep hidden input synced – write FLAT shape
    cardElement.on('complete', (payload) => {
      const flat = normalizeCardPayload(payload?.card);
      setHidden(flat);
    });

    // Also clear/update on change (in case user edits after completion)
    cardElement.on('change', (payload) => {
      if (!payload?.isComplete) {
        setHidden(null);
      } else {
        const flat = normalizeCardPayload(payload?.card);
        setHidden(flat);
      }
    });
  }

  function bindSubmitGuard() {
    const $form = $('form.checkout');
    if (!$form.length || $form.data('frame-guard-bound')) return;

    $form.data('frame-guard-bound', true);

    $form.on('checkout_place_order_frame', function () {
      const val = $('#frame_payment_method_data').val();
      if (!val || val === '{}' || val === 'null') {
        window.wc_checkout_form?.submit_error?.(
          '<ul class="woocommerce-error"><li>Please complete your card details.</li></ul>'
        );
        return false;
      }
      return true;
    });
  }

  function boot() {
    captureSonarSessionId(); // Capture session ID early
    mountCard();
    bindSubmitGuard();
  }

  $(boot);

  // Remount after Woo refreshes checkout fragments
  $(document.body).on('updated_checkout wc-credit-card-form-init', () => {
    captureSonarSessionId(); // Re-capture after WooCommerce updates DOM

    // If Woo replaced the node, reset mounted flag so we can mount again
    const container = document.querySelector('#frame-card');
    if (container && container.dataset.mounted !== '1') {
      mountCard();
    }
    bindSubmitGuard();
  });
})(jQuery);