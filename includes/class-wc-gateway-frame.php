<?php
if (!defined('ABSPATH')) { exit; }

use Frame\Auth;
use Frame\Endpoints\ChargeIntents;
use Frame\Endpoints\Refunds;
use Frame\Exception as FrameException;
use Frame\Models\ChargeIntents\ChargeIntentCreateRequest;
use Frame\Models\ChargeIntents\ChargeIntentCustomerData;
use Frame\Models\Customers\Address;
use Frame\Models\PaymentMethods\PaymentMethodData;
use Frame\Models\PaymentMethods\PaymentMethodType;
use Frame\Models\Refunds\RefundCreateRequest;

class WC_Gateway_Frame extends WC_Payment_Gateway {

    protected $client;

    public function __construct() {
        $this->id = 'frame';
        $this->method_title = __('Frame', 'frame-wc');
        $this->method_description = __('Accept payments via Frame.', 'frame-wc');
        $this->icon = ''; // set later
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
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'frame-wc'),
                'type'    => 'checkbox',
                'label'   => __('Enable Frame', 'frame-wc'),
                'default' => 'no',
            ],
            'title' => [
                'title'       => __('Title', 'frame-wc'),
                'type'        => 'text',
                'description' => __('Shown at checkout', 'frame-wc'),
                'default'     => __('Frame', 'frame-wc'),
            ],
            'test_mode' => [
                'title'       => __('Test mode', 'frame-wc'),
                'type'        => 'checkbox',
                'label'       => __('Use Frame test keys', 'frame-wc'),
                'default'     => 'yes',
            ],
            'public_key' => [
                'title'       => __('Public Key', 'frame-wc'),
                'type'        => 'text',
                'description' => __('Your Frame publishable key (test or live).', 'frame-wc'),
            ],
            'secret_key' => [
                'title'       => __('Secret Key', 'frame-wc'),
                'type'        => 'password',
                'description' => __('Your Frame secret key (test or live).', 'frame-wc'),
            ],
            'webhook_secret' => [
                'title'       => __('Webhook Secret', 'frame-wc'),
                'type'        => 'password',
                'description' => __('If set, incoming webhook requests must include a valid signature.', 'frame-wc'),
            ],
        ];
    }

    public function is_available() {
        if ('yes' !== $this->enabled) return false;
        if (empty($this->secret_key)) return false;
        return true;
    }

    public function register_order_actions( $actions ) {
        // Only run in admin, and only when we can resolve an order
        if ( ! is_admin() ) {
            return $actions;
        }

        // Try to resolve the order across classic editor / HPOS screens
        $order = null;

        // A) Classic screen
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            if ( $screen && $screen->id !== 'shop_order' ) {
                return $actions;
            }
        }

        // B) From ?post=ID (classic)
        if ( empty( $order ) && ! empty( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $order = wc_get_order( absint( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        // C) Fallback to global
        if ( empty( $order ) && function_exists( 'get_the_ID' ) ) {
            $maybe_id = absint( get_the_ID() );
            if ( $maybe_id ) {
                $order = wc_get_order( $maybe_id );
            }
        }

        if ( ! $order instanceof WC_Order ) {
            return $actions;
        }

        // Only for this gateway
        if ( $order->get_payment_method() !== $this->id ) {
            return $actions;
        }

        $intent_id   = $order->get_meta( '_frame_intent_id' );
        $last_status = $order->get_meta( '_frame_last_status' );

        if ( ! $intent_id ) {
            return $actions;
        }

        // If we haven't recorded a last status yet, infer for UI:
        if ( ! $last_status && $order->has_status( 'on-hold' ) ) {
            $last_status = 'pending';
        }

        // Capture / Void when the intent is not settled yet
        if ( in_array( $last_status, array( 'pending', 'incomplete' ), true ) ) {
            $actions['frame_capture'] = esc_html__( 'Capture with Frame', 'frame-wc' );
            $actions['frame_void']    = esc_html__( 'Void (cancel) with Frame', 'frame-wc' );
        }

        // Optional: Refund when settled
        if ( 'succeeded' === $last_status && method_exists( $this, 'admin_refund' ) ) {
            $actions['frame_refund'] = esc_html__( 'Refund via Frame', 'frame-wc' );
        }

        return $actions;
    }

    public function order_action_capture($order) {
        if (!$order instanceof WC_Order) return;
        $intentId = $order->get_meta('_frame_intent_id');
        if (!$intentId) {
            $order->add_order_note(__('Frame: no intent id to capture.', 'frame-wc'));
            return;
        }

        try {
            $res = (new \Frame\Endpoints\ChargeIntents())->capture($intentId);
            $status = method_exists($res, 'toArray') ? ($res->toArray()['status'] ?? null) : ($res->status ?? null);

            $order->update_meta_data('_frame_last_status', $status);
            if (in_array($status, ['captured','succeeded'], true)) {
                $order->payment_complete($intentId);
                $order->add_order_note(__('Frame: payment captured.', 'frame-wc'));
            } else {
                $order->add_order_note(sprintf(__('Frame: capture returned status: %s', 'frame-wc'), $status ?: 'unknown'));
                $order->update_status('processing'); // still authorized/pending?
            }
            $order->save();
        } catch (\Throwable $e) {
            $order->add_order_note(__('Frame: capture failed – ', 'frame-wc') . $e->getMessage());
        }
    }

    public function order_action_void($order) {
        if (!$order instanceof WC_Order) return;
        $intentId = $order->get_meta('_frame_intent_id');
        if (!$intentId) {
            $order->add_order_note(__('Frame: no intent id to void.', 'frame-wc'));
            return;
        }

        try {
            $res = (new \Frame\Endpoints\ChargeIntents())->cancel($intentId);
            $status = method_exists($res, 'toArray') ? ($res->toArray()['status'] ?? null) : ($res->status ?? null);

            $order->update_meta_data('_frame_last_status', $status);
            if (in_array($status, ['canceled','cancelled'], true)) {
                $order->update_status('cancelled', __('Frame: authorization voided.', 'frame-wc'));
            } else {
                $order->add_order_note(sprintf(__('Frame: void returned status: %s', 'frame-wc'), $status ?: 'unknown'));
            }
            $order->save();
        } catch (\Throwable $e) {
            $order->add_order_note(__('Frame: void failed – ', 'frame-wc') . $e->getMessage());
        }
    }

    public function admin_refund( $order ) {
        if ( ! $order instanceof WC_Order ) {
            return;
        }
        $amount = (float) $order->get_remaining_refund_amount();
        if ( $amount <= 0 ) {
            $order->add_order_note( __( 'Frame: no refundable amount left.', 'frame-wc' ) );
            return;
        }
        $res = $this->process_refund( $order->get_id(), $amount, __( 'Admin order action', 'frame-wc' ) );
        if ( is_wp_error( $res ) ) {
            $order->add_order_note( sprintf( __( 'Frame: refund failed — %s', 'frame-wc' ), $res->get_error_message() ) );
        } else {
            $order->add_order_note( __( 'Frame: refund requested via Order action.', 'frame-wc' ) );
        }
    }

    public function process_payment($order_id) {
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
                'name'      => trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')),
            ]), ['source' => 'frame-payments-for-woocommerce']);

            // Read JSON posted by frame-wc.js (WP adds slashes to POST data)
            $raw_json = isset($_POST['frame_payment_method_data'])
                ? wp_unslash($_POST['frame_payment_method_data'])
                : '';

            $cardData = $raw_json ? json_decode($raw_json, true) : null;

            // Debug once to confirm it’s parsed
            wc_get_logger()->info('[Frame WC] raw_json len=' . strlen((string)$raw_json) . ' parsed=' . (is_array($cardData) ? 'yes' : 'no'), ['source' => 'frame-payments-for-woocommerce']);

            // Validate we actually have the fields from Frame.js
            if (
                !is_array($cardData) ||
                empty($cardData['number']) ||
                empty($cardData['exp_month']) ||
                empty($cardData['exp_year']) ||
                empty($cardData['cvc'])
            ) {
                // Log for debugging
                if (function_exists('wc_get_logger')) {
                    wc_get_logger()->error('[Frame WC] Missing/incomplete cardData: ' . wp_json_encode($cardData), ['source' => 'frame-payments-for-woocommerce']);
                }
                wc_add_notice(__('Please complete your card details.', 'frame-wc'), 'error');
                return ['result' => 'failure'];
            }

            // Normalize types (SDK constructor expects strings)
            $cardNumber = (string) $cardData['number'];
            $expMonth   = (string) $cardData['exp_month'];
            $expYear    = (string) $cardData['exp_year'];
            $cvc        = (string) $cardData['cvc'];

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
                description:       sprintf('WooCommerce Order #%d', $order->get_id()),
                customer:          null,
                paymentMethod:     null, 
                confirm:           true,
                receiptEmail:      $email ?: null,
                paymentMethodData: $pmData,
                customerData:      $customerData,
                metadata:          $metadata,
                authorizationMode: null
            );

            // Create the intent
            try {
                $intent = (new ChargeIntents())->create($req);
            } catch (FrameException $e) {
                wc_get_logger()->error(
                    '[Frame WC] Frame API error: ' . $e->getMessage() .
                    ' | status=' . (method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 'n/a') .
                    ' | body=' . wp_json_encode(method_exists($e, 'getResponseBody') ? $e->getResponseBody() : null),
                    ['source' => 'frame-payments-for-woocommerce']
                );
                wc_add_notice(__('Payment error: Frame API rejected the request. Check logs.', 'frame-wc'), 'error');
                return ['result' => 'failure'];
            }

            // Convert to array if available (rest of your function can stay as-is)
            $intentArr = method_exists($intent, 'toArray') ? $intent->toArray() : [
                'id'           => $intent->id ?? null,
                'status'       => $intent->status ?? null,
                'hosted_url'   => $intent->hosted_url ?? null,
                'redirect_url' => $intent->redirect_url ?? null,
            ];

            wc_get_logger()->info('[Frame WC] create response: ' . wp_json_encode($intentArr), ['source' => 'frame-payments-for-woocommerce']);

            // Persist Frame intent id on the order
            if (!empty($intentArr['id'])) {
                $order->set_transaction_id($intentArr['id']);
                $order->update_meta_data('_frame_intent_id', $intentArr['id']);
                $order->update_meta_data('_frame_last_status', $intentArr['status'] ?? 'pending');
                $order->save();
            }

            // Decide where to send the customer
            $redirect = $intentArr['hosted_url']
                ?? $intentArr['redirect_url']
                ?? $this->get_return_url($order);

            // Put order on-hold while waiting for confirmation from Frame
            $order->update_status('on-hold', __('Awaiting Frame payment confirmation.', 'frame-wc'));

            // Empty the cart and return success
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
            wc_add_notice(__('Payment error: please try again or use a different method.', 'frame-wc'), 'error');
            return ['result' => 'failure'];
        }
    }

    public function handle_return($order_id) {
        if (!$order_id) return;
        $order = wc_get_order($order_id);
        $intentId = $order->get_meta('_frame_intent_id');
        if (!$intentId) return;

        try {
            // Get latest status from Frame
            $intent = (new \Frame\Endpoints\ChargeIntents())->retrieve($intentId);
            $arr = method_exists($intent, 'toArray') ? $intent->toArray() : ['status' => $intent->status ?? null];

            switch ($arr['status'] ?? null) {
                case 'succeeded':
                case 'captured':
                    $order->payment_complete($intentId);
                    $order->add_order_note(__('Frame: payment succeeded.', 'frame-wc'));
                    break;

                case 'requires_capture':
                case 'authorized':
                    $order->update_status('processing', __('Frame: authorized, pending capture.', 'frame-wc'));
                    break;
                case 'refunded':
                case 'reversed':
                    $order->update_status('refunded', __('Frame: payment refunded.', 'frame-wc'));
                    break;
                case 'canceled':
                case 'failed':
                    $order->update_status('failed', __('Frame: payment failed/canceled.', 'frame-wc'));
                    break;

                default:
                    // still pending/requires_action
                    $order->update_status('on-hold', __('Frame: awaiting confirmation.', 'frame-wc'));
            }
        } catch (\Throwable $e) {
            wc_get_logger()->error('[Frame WC] handle_return error: ' . $e->getMessage());
            // leave order as-is; customer already sees the thank-you page
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
            return new WP_Error( 'frame_refund_no_order', __( 'Frame: Order not found.', 'frame-wc' ) );
        }

        // Only handle our payments.
        if ( $order->get_payment_method() !== $this->id ) {
            return new WP_Error( 'frame_refund_wrong_gateway', __( 'Frame: Not a Frame payment.', 'frame-wc' ) );
        }

        $intent_id = $order->get_meta( '_frame_intent_id' );
        if ( ! $intent_id ) {
            return new WP_Error( 'frame_refund_no_intent', __( 'Frame: Missing payment reference.', 'frame-wc' ) );
        }

        // Amount in minor units (e.g., cents). If $amount is null, refund full order remaining amount.
        $currency     = strtoupper( $order->get_currency() );
        $refund_total = is_null( $amount ) ? (float) $order->get_remaining_refund_amount() : (float) $amount;

        if ( $refund_total <= 0 ) {
            return new WP_Error( 'frame_refund_zero', __( 'Frame: Nothing to refund.', 'frame-wc' ) );
        }

        $minor = (int) round( $refund_total * 100 );

        // Build request (adjust field names if your SDK differs)
        $req = new RefundCreateRequest(
            chargeIntentId: $intent_id,        // ← key field: which payment to refund
            amount:         $minor,            // partial or full (minor units)
            reason:         $reason ?: null,   // optional
        );

        try {
            $refund = ( new Refunds() )->create( $req );

            // Pull a status out of the model. If your SDK has ->toArray(), use it.
            $refundArr = method_exists( $refund, 'toArray' ) ? $refund->toArray() : [
                'id'     => $refund->id     ?? null,
                'status' => $refund->status ?? null,
            ];

            $status = $refundArr['status'] ?? 'refunded';

            // Persist last known payment state so admin actions / UI stay in sync
            $order->update_meta_data( '_frame_last_status', $status );
            $order->add_order_note(
                sprintf(
                    /* translators: 1: amount, 2: currency, 3: status */
                    __( 'Frame: refund requested: %1$s %2$s (status: %3$s).', 'frame-wc' ),
                    wc_price( $refund_total, [ 'currency' => $currency ] ),
                    $currency,
                    esc_html( $status )
                )
                . ( $reason ? ' — ' . sprintf( __( 'Reason: %s', 'frame-wc' ), esc_html( $reason ) ) : '' )
            );
            $order->save();
            return true;

        } catch ( \Throwable $e ) {
            // Show the error to the admin
            return new WP_Error(
                'frame_refund_failed',
                sprintf( __( 'Frame refund failed: %s', 'frame-wc' ), $e->getMessage() )
            );
        }
    }

    public function payment_fields() {
        // Publishable key for frame-js init
        echo '<div id="frame-js-config" data-pk="' . esc_attr($this->public_key) . '"></div>';

        // Container where frame-js will mount its single Card element
        echo '<div id="frame-card-fields"></div>';

        // Hidden field where JS will place the payment method token
        echo '<input type="hidden" id="frame_payment_method_data" name="frame_payment_method_data" value="">';
    }

    public function validate_fields() {
        return true;
    }

    public function get_webhook_url(): string {
        return add_query_arg('wc-api', 'frame_webhook', home_url('/'));
    }

    public function admin_options() {
        parent::admin_options();
        echo '<p><strong>' . esc_html__('Webhook URL:', 'frame-wc') . '</strong> ';
        echo '<code>' . esc_html($this->get_webhook_url()) . '</code></p>';
    }
}