=== Frame for WooCommerce ===
Contributors: framepayments
Tags: payments, credit card, checkout, gateway, frame, fintech
Requires at least: 6.3
Tested up to: 9.0
Requires PHP: 8.2
Stable tag: 1.0.0
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

== Screenshots ==

1. Frame checkout field on the WooCommerce checkout page  
2. Payment settings screen with API keys  
3. Order details showing Frame transaction ID

== Changelog ==

= 1.0.0 =
* Initial release — adds full Frame payments integration with Charge Intents, Refunds, and webhooks.

== Upgrade Notice ==

= 1.0.0 =
First stable release of Frame for WooCommerce.