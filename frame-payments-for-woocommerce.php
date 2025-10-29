<?php
/**
 * Plugin Name: Frame for WooCommerce
 * Plugin URI:  https://framepayments.com/
 * Description: Accept payments through Frame — secure, modern payment infrastructure for WooCommerce.
 * Version:     1.0.0
 * Author:      Frame
 * Author URI:  https://framepayments.com/
 * License:     GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: frame-wc
 * Domain Path: /languages
 * Requires PHP: 8.2
 * Requires at least: 6.3
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 */

if (!defined('ABSPATH')) { exit; } // no direct access

/** -------------------------------------------------------
 * Core constants (define at top level, not inside a hook)
 * ------------------------------------------------------ */
define('FRAME_WC_VERSION', '1.0.0');
define('FRAME_WC_FILE', __FILE__);
define('FRAME_WC_DIR', plugin_dir_path(__FILE__));
define('FRAME_WC_URL', plugin_dir_url(__FILE__));

/** --------------------------------------
 * Composer autoload (SDK + deps)
 * ------------------------------------- */
if (file_exists(FRAME_WC_DIR . 'vendor/autoload.php')) {
    require FRAME_WC_DIR . 'vendor/autoload.php';
} else {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>Frame Payments for WooCommerce:</strong> Composer autoload not found. Run <code>composer install</code> in the plugin directory.</p></div>';
    });
    // Don’t proceed without deps
    return;
}

/** ---------------------------------------------------------------------
 * Optional admin notice if WooCommerce is not active (dev convenience)
 * -------------------------------------------------------------------- */
add_action('admin_init', function () {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>Frame Payments for WooCommerce</strong> requires WooCommerce to be installed and active.</p></div>';
        });
    }
});

/** -------------------------------------------------------
 * Declare compatibility with modern WC features
 * ------------------------------------------------------ */
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        // HPOS (High Performance Order Storage)
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', FRAME_WC_FILE, true);
        // Checkout Blocks (we’ll add full Blocks integration later)
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', FRAME_WC_FILE, true);
    }
});

add_action('plugins_loaded', function () {
    load_plugin_textdomain('frame-wc', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});

/** -------------------------------------------------------
 * Frontend assets (loads only on Checkout)
 * ------------------------------------------------------ */
add_action('wp_enqueue_scripts', function () {
    if (function_exists('is_checkout') && is_checkout()) {
        // Load Frame.js directly from Frame’s CDN (required)
        wp_enqueue_script(
            'frame-js',
            'https://js.framepayments.com/v1/index.js',
            [],
            null,
            true
        );

        // Load our glue script after frame-js
        wp_enqueue_script(
            'frame-wc',
            FRAME_WC_URL . 'assets/js/frame-wc.js',
            ['jquery', 'frame-js'],
            FRAME_WC_VERSION,
            true
        );
    }
});

/** -------------------------------------------------------
 * Register the payment gateway after WooCommerce loads
 * ------------------------------------------------------ */
add_action('woocommerce_loaded', function () {
    // Load gateway class
    require_once FRAME_WC_DIR . 'includes/class-wc-gateway-frame.php';

    // Register with WooCommerce
    add_filter('woocommerce_payment_gateways', function ($methods) {
        $methods[] = 'WC_Gateway_Frame';
        return $methods;
    });
});

/** -------------------------------------------------------
* Frame Webhook endpoint: https://your-site/?wc-api=frame_webhook
* ------------------------------------------------------ */
add_action('woocommerce_api_frame_webhook', function () {
    $logger = function_exists('wc_get_logger') ? wc_get_logger() : null;

    // Read raw body
    $body = file_get_contents('php://input') ?: '';
    $data = json_decode($body, true);

    if (!is_array($data)) {
        $logger?->warning('[Frame WC] Webhook: invalid JSON', ['source' => 'frame-payments-for-woocommerce']);
        status_header(400); exit;
    }

    // Optional signature verification
    // Header name may vary; adjust to Frame docs when available.
    $signature = $_SERVER['HTTP_FRAME_SIGNATURE'] ?? '';
    $secret    = get_option('woocommerce_frame_settings')['webhook_secret'] ?? '';
    if ($secret) {
        $expected = hash_hmac('sha256', $body, $secret);
        if (!hash_equals($expected, $signature)) {
            $logger?->warning('[Frame WC] Webhook: bad signature', ['source' => 'frame-payments-for-woocommerce']);
            status_header(401); exit;
        }
    }

    $type   = $data['type'] ?? '';
    $intent = $data['data'] ?? [];
    $cid    = $intent['id'] ?? '';
    $status = $intent['status'] ?? '';
    $meta   = $intent['metadata'] ?? [];

    $logger?->info(
        sprintf('[Frame WC] Webhook received: type=%s id=%s status=%s', (string)$type, (string)$cid, (string)$status),
        ['source' => 'frame-payments-for-woocommerce']
    );

    // Detect refund-related webhooks
    if (isset($data['type']) && str_starts_with($data['type'], 'refund.')) {
        $refundId   = $intent['id'] ?? null;
        $parentCid  = $intent['charge_intent_id'] ?? ($intent['chargeIntentId'] ?? null);
        $refundStat = $intent['status'] ?? 'refunded';

        // Try to locate order by refund's metadata or parent charge intent
        $order_id = isset($meta['wc_order_id']) ? (int) $meta['wc_order_id'] : 0;
        if (!$order_id && $parentCid) {
            $found = wc_get_orders([
                'limit'      => 1,
                'meta_key'   => '_frame_intent_id',
                'meta_value' => $parentCid,
                'return'     => 'ids',
            ]);
            if (!empty($found)) {
                $order_id = (int)$found[0];
            }
        }

        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_meta_data('_frame_last_status', $refundStat);
                $order->add_order_note(
                    sprintf(
                        __('Frame: refund %s (status: %s).', 'frame-wc'),
                        esc_html($refundId ?? ''),
                        esc_html($refundStat)
                    )
                );
                $order->update_status('refunded', __('Frame: refund confirmed via webhook.', 'frame-wc'));
                $order->save();
            }
        }

        status_header(200);
        exit;
    }

    // Find the Woo order
    $order_id = isset($meta['wc_order_id']) ? (int)$meta['wc_order_id'] : 0;
    if (!$order_id && $cid) {
        $found = wc_get_orders([
            'limit'      => 1,
            'meta_key'   => '_frame_intent_id',
            'meta_value' => $cid,
            'return'     => 'ids',
        ]);
        if (!empty($found)) {
            $order_id = (int)$found[0];
        }
    }

    if (!$order_id) { status_header(200); exit; } // Nothing to do

    $order = wc_get_order($order_id);
    if (!$order) { status_header(200); exit; }

    // If the event doesn't include a status (some informational events), acknowledge and exit.
    if ($status === '' || $status === null) {
        $logger?->info('[Frame WC] Webhook: no status in payload; acknowledged', ['source' => 'frame-payments-for-woocommerce']);
        status_header(200); exit;
    }

    // Ensure the order stores the Frame transaction id (helps admin UI & later ops)
    if ($cid && ! $order->get_transaction_id()) {
        $order->set_transaction_id($cid);
        $order->update_meta_data('_frame_intent_id', $cid);
        $order->save();
    }

    // Idempotency: don’t repeat work
    $last = $order->get_meta('_frame_last_status');
    if ($last === $status) { status_header(200); exit; }
    $order->update_meta_data('_frame_last_status', $status);

    // Map Frame ChargeIntent statuses
    switch ($status) {
        case 'succeeded':
            if (!$order->is_paid()) {
                $order->payment_complete($cid ?: $order->get_meta('_frame_intent_id'));
                $order->add_order_note(__('Frame: payment succeeded (webhook).', 'frame-wc'));
            }
            break;

        case 'pending':
        case 'incomplete':
            $order->update_status(
                'on-hold',
                __('Frame: payment pending or incomplete (webhook).', 'frame-wc')
            );
            break;

        case 'refunded':
        case 'reversed':
            $order->update_status(
                'refunded',
                __('Frame: payment refunded or reversed (webhook).', 'frame-wc')
            );
            break;

        case 'canceled':
            $order->update_status(
                'cancelled',
                __('Frame: payment canceled (webhook).', 'frame-wc')
            );
            break;

        case 'failed':
            $order->update_status(
                'failed',
                __('Frame: payment failed (webhook).', 'frame-wc')
            );
            break;

        case 'disputed':
            $order->update_status(
                'on-hold',
                __('Frame: payment disputed (webhook).', 'frame-wc')
            );
            break;

        default:
            // Unknown or new status → keep record but don’t alter order
            $order->add_order_note(sprintf(
                __('Frame: unhandled webhook status "%s".', 'frame-wc'),
                esc_html($status)
            ));
            break;
    }

    $order->save();
    status_header(200); exit;
});