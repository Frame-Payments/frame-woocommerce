=== Frame for WooCommerce ===
Contributors: framepayments
Tags: payments, checkout, gateway, frame, fintech
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 1.0.12
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Accept fast, secure payments powered by Frame — a modern payment infrastructure for WooCommerce.

== Description ==

Frame for WooCommerce allows merchants to accept payments using [Frame](https://framepayments.com/), a next-generation payment platform built for security, speed, and flexibility.

With this extension you can:
* Accept card payments directly at checkout
* Automatically confirm and capture payments through Frame
* View transaction IDs and statuses in the WooCommerce order screen
* Process refunds and voids directly from the admin
* Receive asynchronous payment updates via webhooks
* Use Frame.js for PCI-compliant, secure card entry

== Card element customization ==

The Frame.js card element exposes several options that can be configured from **WooCommerce → Settings → Payments → Frame**:

* **Theme** — choose between the `clean`, `minimal`, and `material` presets shipped with Frame.js.
* **Auto-focus** — focus the card element as soon as the checkout page loads.
* **Input text color / Input font size** — optional CSS overrides applied to the input text inside the element.
* **Identity fields** — each of First name, Last name, Email, and Phone can be set to `Hidden`, `Optional`, or `Required`. Fields set to anything other than `Hidden` are collected by Frame.js and the matching WooCommerce billing fields are hidden.

Billing addresses are always collected via the standard WooCommerce checkout fields and forwarded to Frame on charge creation.

== For developers ==

Use the `frame_wc_card_element_options` filter to override the card-element options passed to `frame.createElement('card', ...)` — including options the admin UI doesn't expose (for example `translations`):

`
add_filter( 'frame_wc_card_element_options', function ( $options, $gateway ) {
    $options['autoFocus'] = true;
    $options['translations'] = [ /* ... */ ];
    return $options;
}, 10, 2 );
`

The filter runs after the required-fields safeguard, so removing `number`, `expiry`, or `cvc` here will break tokenization. Don't.

== Installation ==

1. Upload the plugin folder **frame-payments-for-woocommerce** to the `/wp-content/plugins/` directory, or install it via the Plugins → Add New screen.
2. Run `composer install` inside the plugin directory to install the Frame PHP SDK.
3. Activate **Frame for WooCommerce** through the “Plugins” menu in WordPress.
4. Go to **WooCommerce → Settings → Payments → Frame** and enter your Frame **Public** and **Secret** keys.
5. (Optional) Set your **Webhook Secret** and point your Frame dashboard webhook to:

https://your-site.com/?wc-api=frame_webhook

== Frequently Asked Questions ==

= Do I need an SSL certificate? =
Yes. Frame requires a secure (HTTPS) connection for all API requests and checkout operations.

= Does this plugin support test and live keys? =
Yes — you can switch between test and live keys in the gateway settings.

= Where can I get help? =
Visit [https://docs.framepayments.com/](https://docs.framepayments.com/) or email support@framepayments.com.

== Changelog ==

= 1.0.12 =
* Add admin settings for Frame.js card-element theme, auto-focus, and input style overrides.
* Add per-field controls for collecting first name, last name, email, and phone via Frame.js identity fields.
* Add the `frame_wc_card_element_options` filter for developer-level customization.
* Switch the PHP → JS config bridge to a JSON `<script>` tag (was a single `data-pk` attribute).