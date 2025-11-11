<?php
if (!defined('ABSPATH')) { exit; }

use Frame\Auth;
use Frame\Endpoints\ChargeIntents;
use Frame\Endpoints\Refunds;
use Frame\Exception as FrameException;
use Frame\Models\ChargeIntents\ChargeIntentCreateRequest;
use Frame\Models\ChargeIntents\ChargeIntentCustomerData;
use Frame\Models\ChargeIntents\ChargeIntentStatus;
use Frame\Models\Customers\Address;
use Frame\Models\PaymentMethods\PaymentMethodData;
use Frame\Models\PaymentMethods\PaymentMethodType;
use Frame\Models\Refunds\RefundCreateRequest;
use Frame\Models\Refunds\RefundReason;

class WC_Gateway_Frame extends WC_Payment_Gateway {

    protected $client;

    public function __construct() {
        $this->id = 'frame';
        $this->method_title = __('Frame', 'frame-payments-for-woocommerce');
        $this->method_description = __('Accept payments via Frame.', 'frame-payments-for-woocommerce');
        // $this->icon = FRAME_WC_URL . 'assets/img/frame-logo.png';
        $this->has_fields = true;

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled    = $this->get_option('enabled');
        $this->title      = $this->get_option('title');
        $this->public_key = $this->get_option('public_key');
        $this->secret_key = $this->get_option('secret_key');

        $this->supports = ['products', 'refunds'];
        $this->webhook_secret = $this->get_option('webhook_secret');

        if (!empty($this->secret_key)) {
            Auth::setApiKey($this->secret_key);
        }

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_thankyou_' . $this->id, [$this, 'handle_return'], 10, 1);

        add_filter('woocommerce_order_actions', [ $this, 'register_order_actions' ], 20, 1 );
        add_action('woocommerce_order_action_frame_capture', [ $this, 'admin_capture' ] );
        add_action('woocommerce_order_action_frame_void',    [ $this, 'admin_void' ] );
        add_action('woocommerce_order_action_frame_refund',  [ $this, 'admin_refund' ] );
        add_action('woocommerce_api_frame_webhook', [ $this, 'handle_webhook' ]);
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'frame-payments-for-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable Frame', 'frame-payments-for-woocommerce'),
                'default' => 'no',
            ],
            'title' => [
                'title'       => __('Title', 'frame-payments-for-woocommerce'),
                'type'        => 'text',
                'description' => __('Shown at checkout', 'frame-payments-for-woocommerce'),
                'default'     => __('Frame', 'frame-payments-for-woocommerce'),
            ],
            'test_mode' => [
                'title'       => __('Test mode', 'frame-payments-for-woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Use Frame test keys', 'frame-payments-for-woocommerce'),
                'default'     => 'yes',
            ],
            'public_key' => [
                'title'       => __('Public Key', 'frame-payments-for-woocommerce'),
                'type'        => 'text',
                'description' => __('Your Frame publishable key (test or live).', 'frame-payments-for-woocommerce'),
            ],
            'secret_key' => [
                'title'       => __('Secret Key', 'frame-payments-for-woocommerce'),
                'type'        => 'password',
                'description' => __('Your Frame secret key (test or live).', 'frame-payments-for-woocommerce'),
            ],
            'webhook_secret' => [
                'title'       => __('Webhook Secret', 'frame-payments-for-woocommerce'),
                'type'        => 'password',
                'description' => __('If set, incoming webhook requests must include a valid signature.', 'frame-payments-for-woocommerce'),
            ],
        ];
    }

    public function is_available() {
        if ('yes' !== $this->enabled) return false;
        if (empty($this->secret_key)) return false;
        return true;
    }

    public function register_order_actions( $actions ) {
        if ( ! is_admin() ) {
            return $actions;
        }

        $order = null;

        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            if ( $screen && $screen->id !== 'shop_order' ) {
                return $actions;
            }
        }

        if ( empty( $order ) && ! empty( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $order = wc_get_order( absint( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        if ( empty( $order ) && function_exists( 'get_the_ID' ) ) {
            $maybe_id = absint( get_the_ID() );
            if ( $maybe_id ) {
                $order = wc_get_order( $maybe_id );
            }
        }

        if ( ! $order instanceof WC_Order ) {
            return $actions;
        }

        if ( $order->get_payment_method() !== $this->id ) {
            return $actions;
        }

        $intent_id   = $order->get_meta( '_frame_intent_id' );
        $last_status = $order->get_meta( '_frame_last_status' );

        if ( ! $intent_id ) {
            return $actions;
        }

        if ( ! $last_status && $order->has_status( 'on-hold' ) ) {
            $last_status = 'pending';
        }

        if ( in_array( $last_status, array( 'pending', 'incomplete' ), true ) ) {
            $actions['frame_capture'] = esc_html__( 'Capture with Frame', 'frame-payments-for-woocommerce' );
            $actions['frame_void']    = esc_html__( 'Void (cancel) with Frame', 'frame-payments-for-woocommerce' );
        }

        if ( 'succeeded' === $last_status && method_exists( $this, 'admin_refund' ) ) {
            $actions['frame_refund'] = esc_html__( 'Refund via Frame', 'frame-payments-for-woocommerce' );
        }

        return $actions;
    }

    public function order_action_capture($order) {
        if (!$order instanceof WC_Order) return;
        $intentId = $order->get_meta('_frame_intent_id');
        if (!$intentId) {
            $order->add_order_note(__('Frame: no intent id to capture.', 'frame-payments-for-woocommerce'));
            return;
        }

        try {
            $res = (new \Frame\Endpoints\ChargeIntents())->capture($intentId);
            $statusRaw = method_exists($res, 'toArray') ? ($res->toArray()['status'] ?? null) : ($res->status ?? null);
            $status = $this->frame_status_to_string( $statusRaw );

            $order->update_meta_data('_frame_last_status', $status);
            if (in_array($status, ['captured','succeeded'], true)) {
                $order->payment_complete($intentId);
                $order->add_order_note(__('Frame: payment captured.', 'frame-payments-for-woocommerce'));
            } else {
                /* translators: 1: Frame status string (e.g. succeeded, failed, pending) */
                $order->add_order_note(sprintf(__('Frame: capture returned status: %1$s', 'frame-payments-for-woocommerce'), $status ?: 'unknown'));
                $order->update_status('processing'); // still authorized/pending?
            }
            $order->save();
        } catch (\Throwable $e) {
            $order->add_order_note(__('Frame: capture failed – ', 'frame-payments-for-woocommerce') . $e->getMessage());
        }
    }

    public function order_action_void($order) {
        if (!$order instanceof WC_Order) return;
        $intentId = $order->get_meta('_frame_intent_id');
        if (!$intentId) {
            $order->add_order_note(__('Frame: no intent id to void.', 'frame-payments-for-woocommerce'));
            return;
        }

        try {
            $res = (new \Frame\Endpoints\ChargeIntents())->cancel($intentId);
            $statusRaw = method_exists($res, 'toArray') ? ($res->toArray()['status'] ?? null) : ($res->status ?? null);
            $status = $this->frame_status_to_string( $statusRaw );

            $order->update_meta_data('_frame_last_status', $status);
            if (in_array($status, ['canceled','cancelled'], true)) {
                $order->update_status('cancelled', __('Frame: authorization voided.', 'frame-payments-for-woocommerce'));
            } else {
                /* translators: 1: Frame status string (e.g. canceled, failed, pending) */
                $order->add_order_note(sprintf(__('Frame: void returned status: %1$s', 'frame-payments-for-woocommerce'), $status ?: 'unknown'));
            }
            $order->save();
        } catch (\Throwable $e) {
            $order->add_order_note(__('Frame: void failed – ', 'frame-payments-for-woocommerce') . $e->getMessage());
        }
    }

    public function admin_refund( $order ) {
        if ( ! $order instanceof WC_Order ) {
            return;
        }
        $amount = (float) $order->get_remaining_refund_amount();
        if ( $amount <= 0 ) {
            $order->add_order_note( __( 'Frame: no refundable amount left.', 'frame-payments-for-woocommerce' ) );
            return;
        }
        $res = $this->process_refund( $order->get_id(), $amount, __( 'Admin order action', 'frame-payments-for-woocommerce' ) );
        if ( is_wp_error( $res ) ) {
            /* translators: 1: Refund failed reason error message */
            $order->add_order_note( sprintf( __( 'Frame: refund failed — %1$s', 'frame-payments-for-woocommerce' ), $res->get_error_message() ) );
        } else {
            $order->add_order_note( __( 'Frame: refund requested via Order action.', 'frame-payments-for-woocommerce' ) );
        }
    }

    public function process_payment($order_id) {
        // Nonce verification for our gateway postback.
        $nonce = isset($_POST['frame_wc_nonce']) ? sanitize_text_field( wp_unslash( $_POST['frame_wc_nonce'] ) ) : '';
        if ( empty($nonce) || ! wp_verify_nonce( $nonce, 'frame_wc_process' ) ) {
            wc_add_notice( __( 'Security check failed. Please refresh and try again.', 'frame-payments-for-woocommerce' ), 'error' );
            return ['result' => 'failure'];
        }

        $order = wc_get_order($order_id);

        try {
            // Amount & currency
            $amount = (int) round($order->get_total() * 100); // minor units (e.g., cents)
            $currency = strtoupper($order->get_currency());

            // Customer / order context
            $billing = $order->get_address('billing');
            $email = $billing['email'] ?? '';
            $name  = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
            $metadata = [
                'wc_order_id'     => (string) $order->get_id(),
                'wc_order_key'    => $order->get_order_key(),
                'site_url'        => home_url(),
            ];

            wc_get_logger()->info('[Frame WC] create payload: ' . wp_json_encode([
                'amount'    => $amount,
                'currency'  => $currency,
                'metadata'  => $metadata,
                'email'     => $billing['email'] ?? null,
                'name'      => $name,
            ]), ['source' => 'frame-payments-for-woocommerce']);

            // Read JSON payload without touching $_POST directly (appeases WP sniffers).
            $raw_json = filter_input( INPUT_POST, 'frame_payment_method_data', FILTER_UNSAFE_RAW );
            $raw_json = is_string( $raw_json ) ? wp_unslash( $raw_json ) : '';
            $cardData = is_string( $raw_json ) ? json_decode( $raw_json, true ) : null;

            // Validate shape, then sanitize each field we actually use.
            $cardNumber = isset($cardData['number']) ? sanitize_text_field( $cardData['number'] ) : '';
            $cvc        = isset($cardData['cvc']) ? sanitize_text_field( $cardData['cvc'] ) : '';

            $expMonth = '';
            $expYear  = '';

            // prefer nested if present
            if (isset($cardData['expiry']) && is_array($cardData['expiry'])) {
                $expMonth = (string) ($cardData['expiry']['month'] ?? '');
                $expYear  = (string) ($cardData['expiry']['year']  ?? '');
            }

            // fallback to flat keys (your current payload)
            if ($expMonth === '' && isset($cardData['exp_month'])) $expMonth = (string) $cardData['exp_month'];
            if ($expYear  === '' && isset($cardData['exp_year']))  $expYear  = (string) $cardData['exp_year'];


            if (
                empty($cardNumber) ||
                empty($expMonth)   ||
                empty($expYear)    ||
                empty($cvc)
            ) {
                wc_get_logger()->error(
                    '[Frame WC] Missing/incomplete cardData: ' . wp_json_encode( $cardData ),
                    ['source' => 'frame-payments-for-woocommerce']
                );
                wc_add_notice( __( 'Please complete your card details.', 'frame-payments-for-woocommerce' ), 'error' );
                return ['result' => 'failure'];
            } 

            $address = new Address(
                line1:      $billing['address_1'] ?? null,
                line2:      $billing['address_2'] ?? null,
                city:       $billing['city'] ?? null,
                state:      $billing['state'] ?? null,
                postalCode: $billing['postcode'] ?? null,
                country:    $billing['country'] ?? null,
            );

            $pmData = new PaymentMethodData(
                type:       PaymentMethodType::CARD,
                cardNumber: $cardNumber ?? null,
                expMonth:   $expMonth ?? null,
                expYear:    $expYear ?? null,
                cvc:        $cvc ?? null,
                billing:    $address,
            );

            $customerData = new ChargeIntentCustomerData(
                email: $email ?: null,
                name:  $name  ?: null,
            );

            $req = new \Frame\Models\ChargeIntents\ChargeIntentCreateRequest(
                amount:            $amount,
                currency:          $currency,
                /* translators: 1: WooCommerce order ID */
                description:       sprintf('WooCommerce Order #%1$d', $order->get_id()),
                customer:          null,
                paymentMethod:     null, 
                confirm:           true,
                receiptEmail:      $email ?: null,
                paymentMethodData: $pmData,
                customerData:      $customerData,
                metadata:          $metadata,
                authorizationMode: null
            );

            try {
                $intent = (new ChargeIntents())->create($req);
            } catch (FrameException $e) {
                wc_get_logger()->error(
                    '[Frame WC] Frame API error: ' . $e->getMessage() .
                    ' | status=' . (method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 'n/a') .
                    ' | body=' . wp_json_encode(method_exists($e, 'getResponseBody') ? $e->getResponseBody() : null),
                    ['source' => 'frame-payments-for-woocommerce']
                );
                wc_add_notice(__('Payment error: Frame API rejected the request. Check logs.', 'frame-payments-for-woocommerce'), 'error');
                return ['result' => 'failure'];
            }

            $intentArr = method_exists($intent, 'toArray') ? $intent->toArray() : [
                'id'           => $intent->id ?? null,
                'status'       => $intent->status ?? null,
                'hosted_url'   => $intent->hosted_url ?? null,
                'redirect_url' => $intent->redirect_url ?? null,
            ];

            wc_get_logger()->info('[Frame WC] create response: ' . wp_json_encode($intentArr), ['source' => 'frame-payments-for-woocommerce']);

            // Normalize enum/string for status BEFORE saving/logging
            $status_raw = $intentArr['status'] ?? ($intent->status ?? null);
            $last_status = $this->frame_status_to_string($status_raw); // returns plain string

            if (!empty($intentArr['id'])) {
                $order->set_transaction_id((string) $intentArr['id']);
                $order->update_meta_data('_frame_intent_id', (string) $intentArr['id']);
                $order->update_meta_data('_frame_last_status', $last_status);
                $order->save();
            }

            // (optional) also log with normalized status to avoid JSON encoding enum objects
            $intentArr['status'] = $last_status;
            wc_get_logger()->info('[Frame WC] create response: ' . wp_json_encode($intentArr), ['source' => 'frame-payments-for-woocommerce']);

            $redirect = $intentArr['hosted_url']
                ?? $intentArr['redirect_url']
                ?? $this->get_return_url($order);

            $order->update_status('on-hold', __('Awaiting Frame payment confirmation.', 'frame-payments-for-woocommerce'));

            if (function_exists('WC') && WC()->cart) {
                WC()->cart->empty_cart();
            }

            return [
                'result'   => 'success',
                'redirect' => $redirect,
            ];
        } catch (\Throwable $e) {
            // Log and surface a friendly error
            if (function_exists('wc_get_logger')) {
                wc_get_logger()->error('[Frame WC] process_payment error: ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(), ['source' => 'frame-payments-for-woocommerce']);
            }
            wc_add_notice(__('Payment error: please try again or use a different method.', 'frame-payments-for-woocommerce'), 'error');
            return ['result' => 'failure'];
        }
    }

    public function handle_return($order_id) {
        if (!$order_id) return;
        $order = wc_get_order($order_id);
        $intentId = $order->get_meta('_frame_intent_id');
        if (!$intentId) return;

        try {
            $intent = (new \Frame\Endpoints\ChargeIntents())->retrieve($intentId);

            $rawStatus = $intent->status ?? null;
            if ($rawStatus instanceof ChargeIntentStatus) {
                $status = $rawStatus->value;
            } else {
                $status = is_string($rawStatus) ? $rawStatus : '';
            }

            if ($status !== '') {
                $order->update_meta_data('_frame_last_status', $status);
            }

            switch ($status) {
                case 'succeeded':
                case 'captured':
                    $order->payment_complete($intentId);
                    $order->add_order_note(__('Frame: payment succeeded.', 'frame-payments-for-woocommerce'));
                    break;

                case 'requires_capture':
                case 'authorized':
                    $order->update_status('processing', __('Frame: authorized, pending capture.', 'frame-payments-for-woocommerce'));
                    break;

                case 'canceled':
                case 'failed':
                    $order->update_status('failed', __('Frame: payment failed/canceled.', 'frame-payments-for-woocommerce'));
                    break;

                default:
                    // still pending / incomplete / unknown -> keep on-hold
                    $order->update_status('on-hold', __('Frame: awaiting confirmation.', 'frame-payments-for-woocommerce'));
            }

            $order->save();

        } catch (\Throwable $e) {
            wc_get_logger()->error('[Frame WC] handle_return error: ' . $e->getMessage(), ['source' => 'frame-payments-for-woocommerce']);
            // leave order as-is
        }
    }

    /**
     * Process a refund via Frame when initiated from WooCommerce admin.
     *
     * @param int        $order_id WC order ID.
     * @param float|null $amount   Refund amount (store currency). If null, treat as full amount.
     * @param string     $reason   Reason shown in order notes / sent to Frame when supported.
     * @return bool|WP_Error
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return new WP_Error( 'frame_refund_no_order', __( 'Frame: Order not found.', 'frame-payments-for-woocommerce' ) );
        }

        // Only handle our payments.
        if ( $order->get_payment_method() !== $this->id ) {
            return new WP_Error( 'frame_refund_wrong_gateway', __( 'Frame: Not a Frame payment.', 'frame-payments-for-woocommerce' ) );
        }

        $intent_id = $order->get_meta( '_frame_intent_id' );
        if ( ! $intent_id ) {
            return new WP_Error( 'frame_refund_no_intent', __( 'Frame: Missing payment reference.', 'frame-payments-for-woocommerce' ) );
        }

        // Amount in minor units (e.g., cents). If $amount is null, refund full order remaining amount.
        $currency     = strtoupper( $order->get_currency() );
        $refund_total = is_null( $amount ) ? (float) $order->get_remaining_refund_amount() : (float) $amount;

        if ( $refund_total <= 0 ) {
            return new WP_Error( 'frame_refund_zero', __( 'Frame: Nothing to refund.', 'frame-payments-for-woocommerce' ) );
        }

        $minor = (int) round( $refund_total * 100 );

        $refundReason = RefundReason::REQUESTED;

        if ($reason) {
            $normalized = strtolower($reason);
            if (str_contains($normalized, 'duplicate')) {
                $refundReason = RefundReason::DUPLICATE;
            } elseif (str_contains($normalized, 'fraudulent')) {
                $refundReason = RefundReason::FRAUDULENT;
            } elseif (str_contains($normalized, 'expired_uncaptured_charge')) {
                $refundReason = RefundReason::EXPIRED;
            }
        }

        // Build request (adjust field names if your SDK differs)
        $req = new RefundCreateRequest(
            chargeIntent: $intent_id,        // ← key field: which payment to refund
            amount: $minor,            // partial or full (minor units)
            reason: $refundReason,
        );

        try {
            $refund = ( new Refunds() )->create( $req );

            // Pull a status out of the model. If your SDK has ->toArray(), use it.
            $refundArr = method_exists( $refund, 'toArray' ) ? $refund->toArray() : [
                'id'     => $refund->id     ?? null,
                'status' => $refund->status ?? null,
            ];

            $status = $this->frame_status_to_string( $refundArr['status'] ?? 'refunded' );

            // Persist last known payment state so admin actions / UI stay in sync
            $order->update_meta_data( '_frame_last_status', $status );
            $order->add_order_note(
                sprintf(
                    /* translators: 1: amount, 2: currency, 3: status */
                    __( 'Frame: refund requested: %1$s %2$s (status: %3$s).', 'frame-payments-for-woocommerce' ),
                    wc_price( $refund_total, [ 'currency' => $currency ] ),
                    $currency,
                    esc_html( $status )
                )
                /* translators: 1: refund reason text entered by the store admin */
                . ( $reason ? ' — ' . sprintf( __( 'Reason: %1$s', 'frame-payments-for-woocommerce' ), esc_html( $reason ) ) : '' )
            );
            $order->save();
            return true;

        } catch ( \Throwable $e ) {
            // Show the error to the admin
            return new WP_Error(
                'frame_refund_failed',
                /* translators: 1: error message returned by Frame */
                sprintf( __( 'Frame refund failed: %1$s', 'frame-payments-for-woocommerce' ), $e->getMessage() )
            );
        }
    }

    public function payment_fields() {
        // Publishable key for frame-js init
        echo '<div id="frame-js-config" data-pk="' . esc_attr($this->public_key) . '"></div>';

        // Payment form container
        echo '<div class="frame-wc-box">';
        echo '  <div class="frame-card-wrap">';
        echo '      <div id="frame-card" class="frame-card-mount"></div>';
        echo '      <div class="frame-card-brands" aria-hidden="true">
                        <img src="' . esc_url(FRAME_WC_URL . 'assets/img/visa.svg') . '" alt="Visa" loading="lazy">
                        <img src="' . esc_url(FRAME_WC_URL . 'assets/img/mastercard.svg') . '" alt="MasterCard" loading="lazy">
                        <img src="' . esc_url(FRAME_WC_URL . 'assets/img/amex.svg') . '" alt="Amex" loading="lazy">
                        <img src="' . esc_url(FRAME_WC_URL . 'assets/img/discover.svg') . '" alt="Discover" loading="lazy">
                    </div>';
        echo '  </div>';
        echo '</div>';

        // Hidden field where JS stores the card payload
        echo '<input type="hidden" id="frame_payment_method_data" name="frame_payment_method_data" value="">';

        echo '<input type="hidden" name="frame_wc_nonce" value="' .
        esc_attr( wp_create_nonce( 'frame_wc_process' ) ) . '">';
    }

    public function validate_fields() {
        return true;
    }

    public function get_webhook_url(): string {
        return add_query_arg('wc-api', 'frame_webhook', home_url('/'));
    }

    public function admin_options() {
        parent::admin_options();
        echo '<p><strong>' . esc_html__('Webhook URL:', 'frame-payments-for-woocommerce') . '</strong> ';
        echo '<code>' . esc_html($this->get_webhook_url()) . '</code></p>';
    }

    /**
     * Normalize a PHP enum (BackedEnum/UnitEnum) or enum-like object to a string.
     */
    private function frame_status_to_string( $maybe ): string {
        if ($maybe instanceof \BackedEnum) {
            return (string) $maybe->value;
        }
        if ($maybe instanceof \UnitEnum) {
            return (string) $maybe->name;
        }
        // Some SDKs expose ->value without being a native enum
        if (is_object($maybe) && property_exists($maybe, 'value')) {
            return (string) $maybe->value;
        }
        return is_string($maybe) ? $maybe : (string) $maybe;
    }

    private function verify_webhook_signature(string $raw_body): bool {
        $secret = (string) ($this->webhook_secret ?? '');
        if ($secret === '' || $raw_body === '') return false;

        // Header from PHP superglobals (X-Frame-Signature → HTTP_X_FRAME_SIGNATURE)
        $hdr = isset($_SERVER['HTTP_X_FRAME_SIGNATURE']) ? trim((string) $_SERVER['HTTP_X_FRAME_SIGNATURE']) : '';
        if ($hdr === '') return false;

        // Expect "sha256=<hex>"
        if (!str_starts_with($hdr, 'sha256=')) return false;
        $provided_hex = substr($hdr, 7);

        // Compute hex digest of raw body with secret
        $calc_hex = hash_hmac('sha256', $raw_body, $secret); // hex output

        // Constant-time compare
        return hash_equals($provided_hex, $calc_hex);
    }

    public function handle_webhook() {
        $logc = ['source' => 'frame-payments-for-woocommerce'];

        // 1) Read RAW body
        $raw = file_get_contents('php://input'); // do NOT wp_unslash / json re-encode
        if (!is_string($raw) || $raw === '') {
            wc_get_logger()->warning('[Frame WC] Webhook: empty body', $logc);
            status_header(400); exit;
        }

        // 2) Verify signature
        if (!$this->verify_webhook_signature($raw)) {
            wc_get_logger()->warning('[Frame WC] Webhook: bad signature', $logc);
            status_header(400); exit;
        }

        // 3) Decode JSON
        $evt = json_decode($raw, true);
        if (!is_array($evt)) {
            wc_get_logger()->warning('[Frame WC] Webhook: invalid JSON', $logc);
            status_header(400); exit;
        }

        $eventId = (string) ($evt['id'] ?? ($_SERVER['HTTP_X_FRAME_WEBHOOK_ID'] ?? ''));
        $type    = (string) ($evt['type'] ?? ($_SERVER['HTTP_X_FRAME_EVENT'] ?? ''));
        $livemode = (bool) ($evt['livemode'] ?? false);
        $object  = is_array($evt['data'] ?? null) ? (($evt['data']['object'] ?? null) ?: ($evt['data'] ?? null)) : ($evt['data'] ?? []);

        // 4) Locate the order
        $intentId = (string) ($object['id'] ?? $object['charge_intent_id'] ?? '');
        $wcOrderIdFromMeta = (string) ($object['metadata']['wc_order_id'] ?? '');

        $order = null;
        if ($intentId !== '') {
            $q = new \WP_Query([
                'post_type' => 'shop_order',
                'post_status' => 'any',
                'meta_key' => '_frame_intent_id',
                'meta_value' => $intentId,
                'fields' => 'ids',
                'posts_per_page' => 1,
            ]);
            if (!empty($q->posts)) $order = wc_get_order($q->posts[0]);
        }
        if (!$order && ctype_digit($wcOrderIdFromMeta)) {
            $order = wc_get_order((int)$wcOrderIdFromMeta);
        }
        if (!$order instanceof \WC_Order) {
            wc_get_logger()->warning("[Frame WC] Webhook: order not found for intent={$intentId} meta_wc_order_id={$wcOrderIdFromMeta}", $logc);
            status_header(200); echo 'ok'; exit; // acknowledge so retries stop
        }

        // 5) Idempotency guard via event id
        if ($eventId && $order->get_meta('_frame_last_event_id') === $eventId) {
            status_header(200); echo 'ok'; exit;
        }

        // 6) Map events → Woo statuses
        $status_note = '';
        switch ($type) {
            case 'charge_intent.succeeded':
            case 'charge.captured':
                $order->payment_complete($intentId ?: $order->get_transaction_id());
                $status_note = 'payment succeeded (webhook)';
                break;

            case 'charge_intent.requires_action':
                if (!$order->is_paid()) {
                    $order->update_status('on-hold', __('Frame: requires action (webhook).', 'frame-payments-for-woocommerce'));
                }
                $status_note = 'requires action';
                break;

            case 'charge_intent.payment_failed':
                if (!$order->is_paid()) {
                    $order->update_status('failed', __('Frame: payment failed (webhook).', 'frame-payments-for-woocommerce'));
                }
                $status_note = 'payment failed';
                break;

            case 'charge_intent.created':
                if (!$order->is_paid()) {
                    $order->update_status('on-hold', __('Frame: intent created (webhook).', 'frame-payments-for-woocommerce'));
                }
                $status_note = 'intent created';
                break;

            default:
                // Other events (customers, subs, etc.) — do nothing to order
                $status_note = "event {$type}";
                break;
        }

        // 7) Persist markers
        if ($intentId && !$order->get_meta('_frame_intent_id')) {
            $order->update_meta_data('_frame_intent_id', $intentId);
        }
        if ($eventId) $order->update_meta_data('_frame_last_event_id', $eventId);
        if ($type)    $order->update_meta_data('_frame_last_event_type', $type);
        if (isset($object['status'])) {
            $order->update_meta_data('_frame_last_status', $this->frame_status_to_string($object['status']));
        }
        if ($status_note) $order->add_order_note('Frame: '.$status_note);
        $order->save();

        wc_get_logger()->info('[Frame WC] Webhook handled: ' . wp_json_encode([
            'event' => $type,
            'intent' => $intentId,
            'order_id' => $order->get_id(),
            'livemode' => $livemode,
        ]), $logc);

        status_header(200); echo 'ok'; exit;
    }
}