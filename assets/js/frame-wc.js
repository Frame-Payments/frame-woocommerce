(function ($) {
  const FRAME_GATEWAY_ID = 'frame';
  const ACTIVE_BODY_CLASS = 'frame-method-active';

  let frameInstance = null;
  let cardElement = null;
  let lastFramePayload = null;

  // C2: mount mutex. mountPromise is set whenever a mount is in flight so
  // subsequent invocations can await the same operation instead of racing.
  let mountPromise = null;

  // C1: in-flight syncing flag so syncBillingToWoo doesn't recurse via
  // updated_checkout when our own .trigger('change') causes Woo to refetch.
  let isSyncing = false;
  let syncDebounceTimer = null;

  function readConfig() {
    const node = document.querySelector('#frame-js-config');
    if (!node) return null;
    try {
      return JSON.parse(node.textContent || '{}');
    } catch (e) {
      if (window.console && console.error) {
        console.error('[Frame] Failed to parse #frame-js-config:', e);
      }
      return null;
    }
  }

  async function initFrame(cfg) {
    if (frameInstance) return frameInstance;
    if (typeof window.Frame === 'undefined') return null;
    if (!cfg || !cfg.publicKey) return null;
    // I1: do NOT clear frame_charge_session_id here. The site-wide
    // Frame.init (enqueued by the plugin's main PHP file) already created
    // the Sonar session; wiping it from the checkout JS would lose context
    // collected since page load.
    frameInstance = await window.Frame.init(cfg.publicKey);
    return frameInstance;
  }

  function captureSonarSessionId() {
    try {
      const sessionId = localStorage.getItem('frame_charge_session_id');
      if (!sessionId) return;
      const form = document.querySelector('form.checkout');
      if (!form) return;

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

  function normalizeCardFields(card) {
    if (!card) return null;
    return {
      number: card.number || null,
      exp_month: card.expiry?.month || null,
      exp_year: card.expiry?.year || null,
      cvc: card.cvc || null,
    };
  }

  function buildPayload(framePayload) {
    if (!framePayload) return null;
    const cardFields = normalizeCardFields(framePayload.card);
    if (!cardFields || !cardFields.number) return null;
    return {
      card: cardFields,
      billing_address: framePayload.billingAddress || null,
      individual: framePayload.individual || null,
    };
  }

  // C5: build an E.164-ish phone string from Frame's split payload.
  // Frame's `phoneCountryCode` shape isn't documented exactly; handle the
  // three plausible cases (ISO alpha-2, raw dial code, dial code with +).
  function buildPhone(individual) {
    if (!individual || !individual.phoneNumber) return null;
    const digits = String(individual.phoneNumber).replace(/[^0-9]/g, '');
    if (!digits) return null;

    const cc = individual.phoneCountryCode;
    if (!cc) return digits;

    const ccStr = String(cc).trim();
    if (/^[A-Za-z]{2}$/.test(ccStr)) {
      // ISO alpha-2 — can't derive dial code locally; submit national-only
      // and let downstream systems combine with billing_country.
      return digits;
    }
    const ccDigits = ccStr.replace(/[^0-9]/g, '');
    if (!ccDigits) return digits;
    return `+${ccDigits}${digits}`;
  }

  // C1+I7: write Frame-collected values into the corresponding Woo billing_*
  // inputs. Gates per-field on cfg.identityShownFields so we don't clobber a
  // value the merchant still expects the customer to enter in Woo's UI.
  function performSync(framePayload, cfg) {
    if (!framePayload) return;
    if (isSyncing) return;
    isSyncing = true;
    try {
      const setWoo = (selector, value, triggerChange) => {
        const $el = $(selector);
        if (!$el.length) return;
        if (value === undefined || value === null || value === '') return;
        if ($el.val() === String(value)) return;
        $el.val(value);
        if (triggerChange) $el.trigger('change');
      };

      if (cfg.collectBilling && framePayload.billingAddress) {
        const ba = framePayload.billingAddress;
        setWoo('#billing_address_1', ba.line1);
        setWoo('#billing_address_2', ba.line2);
        setWoo('#billing_city',       ba.city);
        setWoo('#billing_postcode',   ba.postalCode);
        // country/state changes drive tax recalculation — trigger change so
        // Woo refetches shipping/tax. The .val() === current guard above
        // suppresses redundant triggers; the isSyncing flag prevents recursion
        // from the updated_checkout re-sync path.
        setWoo('#billing_country',    ba.country, true);
        setWoo('#billing_state',      ba.state, true);
      }

      if (cfg.collectIdentity && framePayload.individual) {
        const shown = Array.isArray(cfg.identityShownFields) ? cfg.identityShownFields : [];
        const ind = framePayload.individual;
        if (shown.includes('firstName')) setWoo('#billing_first_name', ind.firstName);
        if (shown.includes('lastName'))  setWoo('#billing_last_name',  ind.lastName);
        if (shown.includes('email'))     setWoo('#billing_email',      ind.email);
        if (shown.includes('phone')) {
          const phone = buildPhone(ind);
          if (phone) setWoo('#billing_phone', phone);
        }
      }
    } finally {
      isSyncing = false;
    }
  }

  // C1: debounce syncs from the rapid-fire change event so we don't spam
  // Woo's update_checkout AJAX on every keystroke. The trailing call also
  // means the latest payload always wins.
  function syncBillingToWoo(framePayload, cfg) {
    if (!framePayload) return;
    clearTimeout(syncDebounceTimer);
    syncDebounceTimer = setTimeout(() => performSync(framePayload, cfg), 300);
  }

  function buildCreateElementOptions(frame, cfg) {
    const opts = {};
    const persisted = cfg.cardOptions || {};

    if (persisted.cardTheme && persisted.cardTheme.preset) {
      const themeArgs = persisted.cardTheme.styles
        ? [persisted.cardTheme.preset, { styles: persisted.cardTheme.styles }]
        : [persisted.cardTheme.preset];
      opts.cardTheme = frame.cardTheme(...themeArgs);
    }

    if (Array.isArray(persisted.fields) && persisted.fields.length) {
      opts.fields = persisted.fields;
    }
    if (persisted.autoFocus) opts.autoFocus = true;
    if (persisted.billing)   opts.billing = true;
    if (persisted.identityFields) opts.identityFields = persisted.identityFields;
    if (persisted.translations)   opts.translations = persisted.translations;

    return opts;
  }

  // C2: mountCard now returns/uses a shared promise so concurrent callers
  // (boot + updated_checkout firing before the first mount resolves) all
  // await the same operation rather than racing to createElement+mount.
  function mountCard(cfg) {
    if (mountPromise) return mountPromise;

    mountPromise = (async () => {
      const mountSelector = (cfg && cfg.mountSelector) || '#frame-card';
      const container = document.querySelector(mountSelector);
      if (!container) return;

      if (container.dataset.mounted === '1' && cardElement) return;

      const frame = await initFrame(cfg);
      if (!frame) return;

      // Clean up any old element before creating a new one.
      try { cardElement?.unmount?.(); } catch (e) { /* noop */ }
      cardElement = null;

      const elementOptions = buildCreateElementOptions(frame, cfg);
      cardElement = await frame.createElement('card', elementOptions);

      await cardElement.mount(mountSelector);
      container.dataset.mounted = '1';

      cardElement.on('complete', (payload) => {
        lastFramePayload = payload;
        setHidden(buildPayload(payload));
        syncBillingToWoo(payload, cfg);
      });

      cardElement.on('change', (payload) => {
        lastFramePayload = payload;
        if (!payload?.isComplete) {
          setHidden(null);
          // C1: don't fan out to Woo from intermediate keystrokes; only
          // sync once we have a complete (and therefore settled) payload.
          return;
        }
        setHidden(buildPayload(payload));
        syncBillingToWoo(payload, cfg);
      });
    })().finally(() => { mountPromise = null; });

    return mountPromise;
  }

  function bindSubmitGuard() {
    const $form = $('form.checkout');
    if (!$form.length || $form.data('frame-guard-bound')) return;
    $form.data('frame-guard-bound', true);

    $form.on('checkout_place_order_frame', function () {
      const val = $('#frame_payment_method_data').val();
      let parsed = null;
      try { parsed = val ? JSON.parse(val) : null; } catch (e) { parsed = null; }
      if (!parsed || !parsed.card || !parsed.card.number) {
        window.wc_checkout_form?.submit_error?.(
          '<ul class="woocommerce-error"><li>Please complete your card details.</li></ul>'
        );
        return false;
      }
      return true;
    });
  }

  // C3: toggle the body class that gates the "hide Woo billing fields"
  // CSS rules. Only hide when Frame is the *selected* payment method, so
  // switching to another gateway restores Woo's native billing fields.
  function applyActiveBodyClass() {
    const selected = $('input[name="payment_method"]:checked').val();
    document.body.classList.toggle(ACTIVE_BODY_CLASS, selected === FRAME_GATEWAY_ID);
  }

  function bindPaymentMethodToggle() {
    if ($(document.body).data('frame-pm-toggle-bound')) return;
    $(document.body).data('frame-pm-toggle-bound', true);

    // Cover both possible Woo events depending on theme/version.
    $(document.body).on('payment_method_selected updated_checkout', applyActiveBodyClass);
    // And direct user clicks, for themes that don't fire the events promptly.
    $(document).on('change', 'input[name="payment_method"]', applyActiveBodyClass);
  }

  function boot() {
    const cfg = readConfig();
    if (!cfg) return;
    captureSonarSessionId();
    bindPaymentMethodToggle();
    applyActiveBodyClass();
    mountCard(cfg);
    bindSubmitGuard();
  }

  $(boot);

  // Remount after Woo refreshes checkout fragments. The legacy
  // `wc-credit-card-form-init` event is for Woo's built-in CC form and is
  // not relevant to Frame's element, so it's not listened to here.
  $(document.body).on('updated_checkout', () => {
    const cfg = readConfig();
    if (!cfg) return;
    captureSonarSessionId();
    applyActiveBodyClass();

    const container = document.querySelector(cfg.mountSelector || '#frame-card');
    if (container && container.dataset.mounted !== '1') {
      mountCard(cfg);
    }
    bindSubmitGuard();

    // Re-sync the last known Frame values into the freshly-rendered Woo
    // inputs. The isSyncing flag + debounce inside syncBillingToWoo prevent
    // this from triggering another update_checkout loop.
    if (lastFramePayload) {
      syncBillingToWoo(lastFramePayload, cfg);
    }
  });
})(jQuery);
