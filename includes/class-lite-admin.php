<?php
/**
 * Lite eCommerce Admin Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lite_eCommerce_Admin {

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_main_menu' ) );
	}

	/**
	 * Add Main Menu.
	 */
	public static function add_main_menu() {
		add_menu_page(
			__( 'Bluu Lite eCommerce', 'bluu-lite-ecommerce' ),
			__( 'Bluu Lite eCommerce', 'bluu-lite-ecommerce' ),
			'manage_options',
			'bluu-lite-ecommerce',
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-cart',
			30
		);

		add_submenu_page(
			'bluu-lite-ecommerce',
			__( 'Settings', 'bluu-lite-ecommerce' ),
			__( 'Settings', 'bluu-lite-ecommerce' ),
			'manage_options',
			'lite-settings',
			array( 'Lite_eCommerce_Settings', 'render_settings_page' )
		);
	}

	/**
	 * Render Dashboard.
	 */
	public static function render_dashboard() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bluu Lite eCommerce Dashboard', 'bluu-lite-ecommerce' ); ?></h1>
			<p><?php esc_html_e( 'Welcome to your standalone eCommerce system. Manage your products, orders, and settings below.', 'bluu-lite-ecommerce' ); ?></p>
			
			<div class="lite-dashboard-cards" style="display: flex; gap: 20px; margin-top: 20px;">
				<div class="card" style="flex: 1; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h2><?php esc_html_e( 'Products', 'bluu-lite-ecommerce' ); ?></h2>
					<p><?php esc_html_e( 'Create and manage your products.', 'bluu-lite-ecommerce' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lite_product' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Manage Products', 'bluu-lite-ecommerce' ); ?></a>
				</div>
				<div class="card" style="flex: 1; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h2><?php esc_html_e( 'Orders', 'bluu-lite-ecommerce' ); ?></h2>
					<p><?php esc_html_e( 'View and fulfill customer orders.', 'bluu-lite-ecommerce' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=lite_order' ) ); ?>" class="button button-primary"><?php esc_html_e( 'View Orders', 'bluu-lite-ecommerce' ); ?></a>
				</div>
				<div class="card" style="flex: 1; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h2><?php esc_html_e( 'Settings', 'bluu-lite-ecommerce' ); ?></h2>
					<p><?php esc_html_e( 'Configure your Stripe and PayPal keys.', 'bluu-lite-ecommerce' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=lite-settings' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Go to Settings', 'bluu-lite-ecommerce' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}
}
