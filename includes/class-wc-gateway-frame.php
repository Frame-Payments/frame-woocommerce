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

class Frame_WC_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'frame';
        $this->method_title = __('Frame', 'frame-payments-for-woocommerce');
        $this->method_description = __('Accept payments via Frame.', 'frame-payments-for-woocommerce');
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

        add_filter('body_class', [$this, 'add_body_classes']);

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_thankyou_' . $this->id, [$this, 'handle_return'], 10, 1);

        add_filter('woocommerce_order_actions', [ $this, 'register_order_actions' ], 20, 1 );
        add_action('woocommerce_order_action_frame_capture', [ $this, 'order_action_capture' ] );
        add_action('woocommerce_order_action_frame_void',    [ $this, 'order_action_void' ] );
        add_action('woocommerce_order_action_frame_refund',  [ $this, 'admin_refund' ] );
        add_action('woocommerce_api_frame_webhook', [ $this, 'handle_webhook' ]);
    }

    public function init_form_fields() {
        $identity_choices = [
            'hidden'   => __('Hidden', 'frame-payments-for-woocommerce'),
            'optional' => __('Optional', 'frame-payments-for-woocommerce'),
            'required' => __('Required', 'frame-payments-for-woocommerce'),
        ];

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

            // --- Card element appearance ---
            'card_element_section' => [
                'title'       => __('Card element', 'frame-payments-for-woocommerce'),
                'type'        => 'title',
                'description' => __('Controls how the Frame.js card element is rendered at checkout.', 'frame-payments-for-woocommerce'),
            ],
            'card_theme' => [
                'title'   => __('Theme', 'frame-payments-for-woocommerce'),
                'type'    => 'select',
                'default' => 'clean',
                'options' => [
                    'clean'    => __('Clean', 'frame-payments-for-woocommerce'),
                    'minimal'  => __('Minimal', 'frame-payments-for-woocommerce'),
                    'material' => __('Material', 'frame-payments-for-woocommerce'),
                ],
            ],
            'auto_focus' => [
                'title'   => __('Auto-focus', 'frame-payments-for-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Focus the card element on page load', 'frame-payments-for-woocommerce'),
                'default' => 'no',
            ],
            'style_input_color' => [
                'title'       => __('Input text color', 'frame-payments-for-woocommerce'),
                'type'        => 'text',
                'description' => __('Optional. CSS color (e.g. #333333) applied to input text inside the card element.', 'frame-payments-for-woocommerce'),
                'default'     => '',
            ],
            'style_input_font_size' => [
                'title'       => __('Input font size', 'frame-payments-for-woocommerce'),
                'type'        => 'text',
                'description' => __('Optional. CSS font size (e.g. 16px) applied to input text inside the card element.', 'frame-payments-for-woocommerce'),
                'default'     => '',
            ],

            // --- Billing & identity capture ---
            'collect_section' => [
                'title'       => __('Collect customer details via Frame', 'frame-payments-for-woocommerce'),
                'type'        => 'title',
                'description' => __('When enabled, WooCommerce\'s native fields for these values are hidden at checkout and Frame\'s element collects them instead.', 'frame-payments-for-woocommerce'),
            ],
            'identity_first_name' => [
                'title'   => __('First name', 'frame-payments-for-woocommerce'),
                'type'    => 'select',
                'default' => 'hidden',
                'options' => $identity_choices,
            ],
            'identity_last_name' => [
                'title'   => __('Last name', 'frame-payments-for-woocommerce'),
                'type'    => 'select',
                'default' => 'hidden',
                'options' => $identity_choices,
            ],
            'identity_email' => [
                'title'   => __('Email', 'frame-payments-for-woocommerce'),
                'type'    => 'select',
                'default' => 'hidden',
                'options' => $identity_choices,
            ],
            'identity_phone' => [
                'title'   => __('Phone', 'frame-payments-for-woocommerce'),
                'type'    => 'select',
                'default' => 'hidden',
                'options' => $identity_choices,
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
            $phone = $billing['phone'] ?? '';
            $metadata = [
                'wc_order_id'     => (string) $order->get_id(),
                'wc_order_key'    => $order->get_order_key(),
                'site_url'        => home_url(),
            ];

            // Cart context. Frame's metadata is <string,string> with a 100-char
            // per-value cap. Order totals live on the WC order itself and can be
            // joined back via wc_order_id; we just send identifiers + line items.
            $cart = $this->build_cart_metadata($order);
            $metadata['cart_item_count']    = (string) $cart['item_count'];
            if ( ! empty($cart['coupon_codes'])) {
                $metadata['coupon_codes']   = $this->fit_metadata_value(implode(',', $cart['coupon_codes']));
            }
            if ( ! empty($cart['line_items'])) {
                $metadata['line_items']     = $cart['line_items'];
            }

            // Read JSON payload without touching $_POST directly (appeases WP sniffers).
            $raw_json = filter_input( INPUT_POST, 'frame_payment_method_data', FILTER_UNSAFE_RAW );
            $raw_json = is_string( $raw_json ) ? wp_unslash( $raw_json ) : '';
            $payload  = is_string( $raw_json ) ? json_decode( $raw_json, true ) : null;
            if ( ! is_array($payload) ) {
                $payload = [];
            }

            $cardData        = isset($payload['card'])       && is_array($payload['card'])       ? $payload['card']       : [];
            $frameIndividual = isset($payload['individual']) && is_array($payload['individual']) ? $payload['individual'] : null;

            // Retrieve Sonar session ID from hidden input
            $sonar_session_id = filter_input( INPUT_POST, 'frame_sonar_session_id', FILTER_UNSAFE_RAW );
            $sonar_session_id = is_string( $sonar_session_id ) ? sanitize_text_field( wp_unslash( $sonar_session_id ) ) : '';

            // Prefer Frame-collected identity values if the gateway is set to collect them via Frame.
            // Frame.js emits the individual object in Accounts-API shape:
            //   { email, name: { first_name, last_name }, phone: { number, country_code } }
            if ($this->is_collecting_identity() && $frameIndividual) {
                $indName  = isset($frameIndividual['name'])  && is_array($frameIndividual['name'])  ? $frameIndividual['name']  : [];
                $indPhone = isset($frameIndividual['phone']) && is_array($frameIndividual['phone']) ? $frameIndividual['phone'] : [];

                $first = isset($indName['first_name']) ? sanitize_text_field((string) $indName['first_name']) : '';
                $last  = isset($indName['last_name'])  ? sanitize_text_field((string) $indName['last_name'])  : '';
                if ($first !== '' || $last !== '') {
                    $name = trim($first . ' ' . $last);
                }
                if ( ! empty($frameIndividual['email']) ) {
                    $email = sanitize_email((string) $frameIndividual['email']);
                }
                $frame_phone = $this->build_phone_from_individual($indPhone);
                if ($frame_phone !== '') {
                    $phone = $frame_phone;
                }
            }

            wc_get_logger()->info('[Frame WC] create payload: ' . wp_json_encode([
                'amount'            => $amount,
                'currency'          => $currency,
                'metadata'          => $metadata,
                'email'             => $email ?: ($billing['email'] ?? null),
                'name'              => $name,
                'has_phone'         => $phone !== '',
                'sonar_session_id'  => $sonar_session_id,
            ]), ['source' => 'frame-payments-for-woocommerce']);

            // Validate shape, then sanitize each field we actually use.
            $cardNumber = isset($cardData['number']) ? sanitize_text_field( $cardData['number'] ) : '';
            $cvc        = isset($cardData['cvc']) ? sanitize_text_field( $cardData['cvc'] ) : '';

            $expMonth = isset($cardData['exp_month']) ? (string) $cardData['exp_month'] : '';
            $expYear  = isset($cardData['exp_year'])  ? (string) $cardData['exp_year']  : '';


            if (
                empty($cardNumber) ||
                empty($expMonth)   ||
                empty($expYear)    ||
                empty($cvc)
            ) {
                // Redact PAN/CVC; only log which fields were present and the shape.
                $redacted = [
                    'has_number'    => $cardNumber !== '',
                    'has_cvc'       => $cvc !== '',
                    'has_exp_month' => $expMonth !== '',
                    'has_exp_year'  => $expYear !== '',
                    'keys'          => is_array($cardData) ? array_keys($cardData) : [],
                ];
                wc_get_logger()->error(
                    '[Frame WC] Missing/incomplete cardData: ' . wp_json_encode( $redacted ),
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

            $shipping_data = $order->get_address('shipping');
            $shipping_address = null;
            if (!empty(array_filter([
                $shipping_data['address_1'] ?? '',
                $shipping_data['city'] ?? '',
                $shipping_data['postcode'] ?? '',
                $shipping_data['country'] ?? '',
            ]))) {
                $shipping_address = new Address(
                    line1:      $shipping_data['address_1'] ?? null,
                    line2:      $shipping_data['address_2'] ?? null,
                    city:       $shipping_data['city'] ?? null,
                    state:      $shipping_data['state'] ?? null,
                    postalCode: $shipping_data['postcode'] ?? null,
                    country:    $shipping_data['country'] ?? null,
                );
            }

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
                phone: $phone ?: null,
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
                authorizationMode: null,
                sonarSessionId:    $sonar_session_id ?: null,
                shipping:          $shipping_address,
            );

            try {
                $intent = (new ChargeIntents())->create($req);
            } catch (FrameException $e) {
                wc_get_logger()->error(
                    '[Frame WC] Frame API error: ' . FrameException::getErrorMessage($e) .
                    ' | code=' . $e->getCode() .
                    ' | response=' . wp_json_encode($e->getResponse()),
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
            $status = $this->frame_status_to_string($intent->status ?? null);

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
        $config = [
            'publicKey'         => (string) $this->public_key,
            'mountSelector'     => '#frame-card',
            'cardOptions'       => $this->build_card_options(),
            'collectIdentity'   => $this->is_collecting_identity(),
            'identityShownFields' => $this->identity_shown_fields(),
        ];

        // Typed JSON config is safer than data-* attributes for nested options.
        echo '<script type="application/json" id="frame-js-config">'
            . wp_json_encode( $config )
            . '</script>';

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

    /**
     * Build the options array passed to frame.createElement('card', ...).
     * Runs through the `frame_wc_card_element_options` filter so site
     * developers can override anything the admin UI doesn't expose
     * (e.g. translations).
     *
     * The cardTheme is persisted as plain data ({ preset, styles }) and
     * reconstructed on the JS side via frame.cardTheme(preset, { styles }).
     */
    public function build_card_options(): array {
        $theme_preset = $this->get_option('card_theme', 'clean');

        // The cardholder name is collected via the identity fields (First/Last
        // name) rather than the in-iframe `name` field, so the card element
        // only renders number/expiry/cvc.
        $fields = ['number', 'expiry', 'cvc'];

        $card_theme = [
            'preset' => $theme_preset,
        ];

        $input_styles = [];
        $color = trim((string) $this->get_option('style_input_color', ''));
        $size  = trim((string) $this->get_option('style_input_font_size', ''));
        if ($color !== '') {
            $input_styles['color'] = $color;
        }
        if ($size !== '') {
            $input_styles['fontSize'] = $size;
        }
        if ( ! empty($input_styles) ) {
            $card_theme['styles'] = ['input' => $input_styles];
        }

        $options = [
            'cardTheme' => $card_theme,
            'fields'    => $fields,
            'autoFocus' => $this->get_option('auto_focus') === 'yes',
        ];

        $identity_fields = [];
        foreach ($this->identity_field_map() as $option_key => $frame_key) {
            $state = (string) $this->get_option($option_key, 'hidden');
            if ($state === 'hidden') {
                continue;
            }
            // identity_phone covers both phoneCountryCode and phoneNumber in Frame.
            if ($frame_key === 'phone') {
                $identity_fields['phoneCountryCode'] = ['show' => true, 'required' => $state === 'required'];
                $identity_fields['phoneNumber']      = ['show' => true, 'required' => $state === 'required'];
            } else {
                $identity_fields[$frame_key] = ['show' => true, 'required' => $state === 'required'];
            }
        }
        if ( ! empty($identity_fields) ) {
            $options['identityFields'] = $identity_fields;
        }

        /**
         * Filter the Frame.js card-element options before they are emitted to JS.
         *
         * @param array              $options  Card element options.
         * @param Frame_WC_Gateway   $gateway  The gateway instance.
         */
        return (array) apply_filters('frame_wc_card_element_options', $options, $this);
    }

    /** Map admin setting keys to Frame identityField keys. */
    private function identity_field_map(): array {
        return [
            'identity_first_name' => 'firstName',
            'identity_last_name'  => 'lastName',
            'identity_email'      => 'email',
            'identity_phone'      => 'phone',
        ];
    }

    /** Which identity fields have a non-hidden setting (used to drive CSS hiding of Woo fields). */
    private function identity_shown_fields(): array {
        $shown = [];
        foreach ($this->identity_field_map() as $option_key => $frame_key) {
            if ($this->get_option($option_key, 'hidden') !== 'hidden') {
                $shown[] = $frame_key;
            }
        }
        return $shown;
    }

    public function is_collecting_identity(): bool {
        return ! empty($this->identity_shown_fields());
    }

    /**
     * Build an E.164-ish phone string from Frame's {number, country_code} shape.
     * Mirrors the JS buildPhone() in frame-wc.js. Returns '' when nothing usable
     * is present.
     *
     *  - ISO alpha-2 country code (e.g. "US"): we can't derive the dial code
     *    server-side without a lookup table, so the digits alone are returned
     *    and downstream systems can combine with the billing country.
     *  - Bare dial code ("1") or "+"-prefixed dial code ("+1"): prepended to
     *    the national digits.
     */
    private function build_phone_from_individual(array $phone): string {
        $raw = isset($phone['number']) ? (string) $phone['number'] : '';
        $digits = preg_replace('/[^0-9]/', '', $raw);
        if ($digits === '' || $digits === null) {
            return '';
        }

        $cc = isset($phone['country_code']) ? trim((string) $phone['country_code']) : '';
        if ($cc === '') {
            return $digits;
        }
        if (preg_match('/^[A-Za-z]{2}$/', $cc)) {
            return $digits;
        }
        $cc_digits = preg_replace('/[^0-9]/', '', $cc);
        if ($cc_digits === '' || $cc_digits === null) {
            return $digits;
        }
        return '+' . $cc_digits . $digits;
    }

    /**
     * Build cart metadata for the ChargeIntent. Frame's metadata is
     * `array<string,string>` with a 100-char per-value cap; full line-item
     * detail won't fit, so we emit a compact "<pid>x<qty>,..." string
     * (truncated at the last comma to fit) plus aggregate totals.
     * Merchants can join back to the WC order via wc_order_id for full detail.
     */
    private function build_cart_metadata(WC_Order $order): array {
        // Compact per-item encoding: "<product_id>x<qty>", or
        // "<product_id>:<variation_id>x<qty>" for variations. Items are joined
        // with commas and the whole string is truncated to fit Frame's 100-char
        // metadata-value limit (lowest-index items are kept).
        $parts = [];
        foreach ($order->get_items() as $item) {
            if ( ! ($item instanceof WC_Order_Item_Product)) continue;
            $pid = (int) $item->get_product_id();
            $vid = (int) $item->get_variation_id();
            $qty = (int) $item->get_quantity();
            $parts[] = $vid > 0 ? "{$pid}:{$vid}x{$qty}" : "{$pid}x{$qty}";
        }
        $line_items = $this->fit_metadata_value(implode(',', $parts));

        $coupons = $order->get_coupon_codes();
        return [
            'line_items'     => $line_items,
            'item_count'     => (int) $order->get_item_count(),
            'coupon_codes'   => is_array($coupons) ? array_values($coupons) : [],
        ];
    }

    /**
     * Truncate a metadata value to fit Frame's 100-char per-value limit.
     * Cuts at the last comma boundary so we don't leave a half-encoded item.
     */
    private function fit_metadata_value(string $value): string {
        $limit = 100;
        if (strlen($value) <= $limit) {
            return $value;
        }
        $truncated = substr($value, 0, $limit);
        $last_comma = strrpos($truncated, ',');
        return $last_comma !== false ? substr($truncated, 0, $last_comma) : $truncated;
    }

    /**
     * Toggle body classes on checkout when Frame is collecting billing/identity.
     *
     * The actual CSS rules also require `frame-method-active` so we don't hide
     * native Woo billing fields when the customer has selected a different
     * payment method. The active class is kept in sync from JS via Woo's
     * `payment_method_selected` event; here we set the initial paint state
     * based on the session's chosen_payment_method.
     */
    public function add_body_classes(array $classes): array {
        if ( ! function_exists('is_checkout') || ! is_checkout() ) {
            return $classes;
        }
        if ('yes' !== $this->enabled) {
            return $classes;
        }
        foreach ($this->identity_shown_fields() as $frame_key) {
            $classes[] = 'frame-wc-collecting-' . sanitize_html_class($frame_key);
        }

        // Initial-paint hint: is Frame the currently-chosen gateway?
        if ( function_exists('WC') && WC() && WC()->session ) {
            $chosen = WC()->session->get('chosen_payment_method');
            if ( $chosen === $this->id ) {
                $classes[] = 'frame-method-active';
            }
        }
        return $classes;
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

        // Read from INPUT_SERVER to satisfy PHPCS (no direct $_SERVER access)
        $sig_input = filter_input(INPUT_SERVER, 'HTTP_X_FRAME_SIGNATURE', FILTER_UNSAFE_RAW);
        $sig_input = is_string($sig_input) ? wp_unslash($sig_input) : '';

        // Signature must remain raw; do not sanitize, only trim.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- HMAC must use raw header bytes
        $hdr = is_string($sig_input) ? trim($sig_input) : '';
        if ($hdr === '' || strpos($hdr, 'sha256=') !== 0) {
            return false;
        }

        $provided_hex = substr($hdr, 7);
        // Compute hex digest of RAW body with secret (Frame spec)
        $calc_hex = hash_hmac('sha256', $raw_body, $secret); // hex output

        return hash_equals($provided_hex, $calc_hex);
    }

    public function handle_webhook() {
        $logc = ['source' => 'frame-payments-for-woocommerce'];

        // 1) RAW body
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') {
            wc_get_logger()->warning('[Frame WC] Webhook: empty body', $logc);
            status_header(400); exit;
        }

        // 2) Verify signature first (uses raw body)
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

        // ---- Header reads using filter_input (silences PHPCS) ----
        $id_input    = filter_input(INPUT_SERVER, 'HTTP_X_FRAME_WEBHOOK_ID', FILTER_UNSAFE_RAW);
        $event_input = filter_input(INPUT_SERVER, 'HTTP_X_FRAME_EVENT',      FILTER_UNSAFE_RAW);

        $hdr_id    = is_string($id_input)    ? sanitize_text_field( wp_unslash($id_input) )    : '';
        $hdr_event = is_string($event_input) ? sanitize_text_field( wp_unslash($event_input) ) : '';

        $eventId  = (string) ($evt['id']   ?? $hdr_id);
        $type     = (string) ($evt['type'] ?? $hdr_event);
        $livemode = (bool)   ($evt['livemode'] ?? false);

        $object = is_array($evt['data'] ?? null)
            ? ( ($evt['data']['object'] ?? null) ?: ($evt['data'] ?? null) )
            : ($evt['data'] ?? []);

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

// Backwards compatibility for Woo filters or external references
class_alias('Frame_WC_Gateway', 'WC_Gateway_Frame');