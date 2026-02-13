<?php
/**
 * Plugin Name: Frame for WooCommerce
 * Plugin URI:  https://github.com/Frame-Payments/frame-woocommerce
 * Description: Accept payments through Frame — secure, modern payment infrastructure for WooCommerce.
 * Version:     1.0.10
 * Author:      Frame
 * Author URI:  https://framepayments.com/
 * License:     GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: frame-payments-for-woocommerce
 * Domain Path: /languages
 * Requires PHP: 8.2
 * Requires at least: 6.3
 * WC requires at least: 6.0
 * WC tested up to: 6.8
 */

if (!defined('ABSPATH')) { exit; } // no direct access
if (!defined('FRAME_WC_TD')) {
    define('FRAME_WC_TD', 'frame-payments-for-woocommerce');
}

/** -------------------------------------------------------
 * Core constants (define at top level, not inside a hook)
 * ------------------------------------------------------ */
define('FRAME_WC_VERSION', '1.0.10');
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
        echo '<div class="notice notice-error"><p><strong>' .
        esc_html__( 'Frame Payments for WooCommerce:', 'frame-payments-for-woocommerce' ) .
        '</strong> ' .
        sprintf(
            /* translators: 1: <code>, 2: </code> */
            esc_html__( 'Composer autoload not found. Run %1$scomposer install%2$s in the plugin directory.', 'frame-payments-for-woocommerce' ),
            '<code>',
            '</code>'
        ) .
        '</p></div>';
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
            echo '<div class="notice notice-error"><p><strong>' .
            esc_html__( 'Frame Payments for WooCommerce', 'frame-payments-for-woocommerce' ) .
            '</strong> ' .
            esc_html__( 'requires WooCommerce to be installed and active.', 'frame-payments-for-woocommerce' ) .
            '</p></div>';
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

// add_action('plugins_loaded', function () {
//     load_plugin_textdomain(
//         'frame-payments-for-woocommerce',
//         false,
//         dirname(plugin_basename(__FILE__)) . '/languages'
//     );
// });

/** -------------------------------------------------------
 * Frontend assets (Frame.js loads site-wide for Sonar)
 * ------------------------------------------------------ */
add_action('wp_enqueue_scripts', function () {
    // Only load on frontend (not admin)
    if (is_admin()) {
        return;
    }

    // Load Frame.js site-wide for Sonar session tracking
    wp_enqueue_script(
        'frame-js',
        'https://js.framepayments.com/v1/index.js',
        [],
        FRAME_WC_VERSION,
        true
    );

    // Initialize Frame.js immediately to generate session ID
    // Only on frontend (not admin) and only if gateway is enabled
    if (!is_admin() && class_exists('WC_Gateway_Frame')) {
        // Get gateway settings without instantiating the full class
        $gateway_options = get_option('woocommerce_frame_settings', []);
        $enabled = isset($gateway_options['enabled']) && $gateway_options['enabled'] === 'yes';
        $public_key = $gateway_options['public_key'] ?? '';

        if ($enabled && !empty($public_key)) {
            wp_add_inline_script(
                'frame-js',
                sprintf(
                    '(function(){
                        if (typeof window.Frame !== "undefined") {
                            window.Frame.init("%s").catch(function(err){
                                console.error("[Frame] Init failed:", err);
                            });
                        }
                    })();',
                    esc_js($public_key)
                ),
                'after'
            );
        }
    }

    // Load checkout-specific scripts only on checkout page
    if (function_exists('is_checkout') && is_checkout()) {
        wp_enqueue_script(
            FRAME_WC_TD,
            FRAME_WC_URL . 'assets/js/frame-wc.js',
            ['jquery', 'frame-js'],
            FRAME_WC_VERSION,
            true
        );

        wp_enqueue_style(
            FRAME_WC_TD,
            FRAME_WC_URL . 'assets/css/frame-wc.css',
            [],
            FRAME_WC_VERSION
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