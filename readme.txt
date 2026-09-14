=== Bluu Lite eCommerce ===
Contributors: bluuinteractive
Donate link: https://bluuhq.com
Tags: ecommerce, shop, stripe, paypal, checkout
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, standalone ecommerce plugin with Stripe and PayPal support.

== Description ==

Bluu Lite eCommerce is designed for those who want a simple, clean, and modern shopping experience without the bloat of larger plugins. It includes built-in support for Stripe and PayPal, a sleek AJAX-powered checkout, and a modern product page design.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/bluu-lite-ecommerce` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure your settings in Bluu Lite eCommerce > Settings.
4. Add the `[lite_cart]` and `[lite_checkout]` shortcodes to your respective pages.

== Screenshots ==

1. Modern product page.
2. AJAX-powered checkout sidebar.

== Changelog ==

= 1.0.2 =
* Security hardening: wp_unslash, sanitization, and escaping across all files.
* Replaced wp_redirect with wp_safe_redirect throughout.
* Added register_setting sanitization callbacks.
* Country fields upgraded to select dropdowns.
* Prefixed global template variables.
* Deleted hidden and temporary files.

= 1.0.1 =
* Rebranded to Bluu Interactive.
* Switched currency to GBP (£).
* Redesigned checkout and cart pages.

= 1.0.0 =
* Initial release.
