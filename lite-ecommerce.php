<?php
/**
 * Plugin Name: Bluu Lite eCommerce
 * Description: A lightweight, standalone ecommerce plugin with Stripe and PayPal support.
 * Version: 1.0.2
 * Author: Bluu Interactive
 * Author URI: https://bluuhq.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bluu-lite-ecommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'LITE_ECOMMERCE_PATH', plugin_dir_path( __FILE__ ) );
define( 'LITE_ECOMMERCE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Plugin Class
 */
class Lite_eCommerce {

	/**
	 * Initialize the plugin.
	 */
	public static function init() {
		// Hook session start to run securely during WordPress init
		add_action( 'init', array( __CLASS__, 'start_session' ), 1 );

		// Load core files
		require_once LITE_ECOMMERCE_PATH . 'includes/class-lite-product.php';
		require_once LITE_ECOMMERCE_PATH . 'includes/class-lite-cart.php';
		require_once LITE_ECOMMERCE_PATH . 'includes/class-lite-checkout.php';
		require_once LITE_ECOMMERCE_PATH . 'includes/class-lite-orders.php';
		require_once LITE_ECOMMERCE_PATH . 'includes/class-lite-settings.php';
		require_once LITE_ECOMMERCE_PATH . 'includes/class-lite-admin.php';
		
		// Load gateways
		require_once LITE_ECOMMERCE_PATH . 'includes/class-lite-emails.php';
		require_once LITE_ECOMMERCE_PATH . 'includes/gateways/class-lite-stripe.php';
		require_once LITE_ECOMMERCE_PATH . 'includes/gateways/class-lite-paypal.php';

		// Initialize classes
		Lite_eCommerce_Product::init();
		Lite_eCommerce_Cart::init();
		Lite_eCommerce_Checkout::init();
		Lite_eCommerce_Orders::init();
		Lite_eCommerce_Settings::init();
		Lite_eCommerce_Admin::init();
		Lite_eCommerce_Emails::init();

		register_activation_hook( __FILE__, array( __CLASS__, 'plugin_activation' ) );
	}

	/**
	 * Run on plugin activation.
	 */
	public static function plugin_activation() {
		// Initialize CPTs to ensure rewrite rules can be flushed
		require_once LITE_ECOMMERCE_PATH . 'includes/class-lite-product.php';
		require_once LITE_ECOMMERCE_PATH . 'includes/class-lite-orders.php';
		
		Lite_eCommerce_Product::register_post_type();
		Lite_eCommerce_Orders::register_post_type();

		flush_rewrite_rules();
	}

	/**
	 * Start PHP Session securely.
	 */
	public static function start_session() {
		if ( ! session_id() && ! headers_sent() ) {
			session_start();
		}
	}
}

// Kick off the plugin
Lite_eCommerce::init();
