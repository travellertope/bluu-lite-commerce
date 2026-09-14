<?php
/**
 * Lite eCommerce Settings Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lite_eCommerce_Settings {

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Add Settings Page.
	 */
	public static function add_settings_page() {
		add_submenu_page(
			'bluu-lite-ecommerce',
			__( 'Settings', 'bluu-lite-ecommerce' ),
			__( 'Settings', 'bluu-lite-ecommerce' ),
			'manage_options',
			'lite-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting( 'lite_settings_group', 'lite_stripe_secret_key', 'sanitize_text_field' );
		register_setting( 'lite_settings_group', 'lite_stripe_publishable_key', 'sanitize_text_field' );
		register_setting( 'lite_settings_group', 'lite_paypal_client_id', 'sanitize_text_field' );
		register_setting( 'lite_settings_group', 'lite_paypal_secret', 'sanitize_text_field' );
		register_setting( 'lite_settings_group', 'lite_paypal_sandbox', 'intval' );
		register_setting( 'lite_settings_group', 'lite_vat_rate', 'floatval' );
		register_setting( 'lite_settings_group', 'lite_shipping_cost', 'floatval' );
		register_setting( 'lite_settings_group', 'lite_checkout_page', 'intval' );
		register_setting( 'lite_settings_group', 'lite_cart_page', 'intval' );
		
		// Email Settings
		register_setting( 'lite_settings_group', 'lite_email_from_name', 'sanitize_text_field' );
		register_setting( 'lite_settings_group', 'lite_email_from_email', 'sanitize_email' );
		register_setting( 'lite_settings_group', 'lite_email_subject', 'sanitize_text_field' );
		register_setting( 'lite_settings_group', 'lite_email_body', 'wp_kses_post' );
		register_setting( 'lite_settings_group', 'lite_admin_email_enabled', 'intval' );
		register_setting( 'lite_settings_group', 'lite_admin_email_recipient', 'sanitize_email' );
		register_setting( 'lite_settings_group', 'lite_admin_email_subject', 'sanitize_text_field' );
		register_setting( 'lite_settings_group', 'lite_admin_email_body', 'wp_kses_post' );
	}

	/**
	 * Render the settings page.
	 */
	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bluu Lite eCommerce Settings', 'bluu-lite-ecommerce' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'lite_settings_group' ); ?>
				<?php do_settings_sections( 'lite_settings_group' ); ?>

				<h2 class="title"><?php esc_html_e( 'Stripe Configuration', 'bluu-lite-ecommerce' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="lite_stripe_publishable_key"><?php esc_html_e( 'Publishable Key', 'bluu-lite-ecommerce' ); ?></label></th>
						<td><input type="text" id="lite_stripe_publishable_key" name="lite_stripe_publishable_key" value="<?php echo esc_attr( get_option( 'lite_stripe_publishable_key' ) ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><label for="lite_stripe_secret_key"><?php esc_html_e( 'Secret Key', 'bluu-lite-ecommerce' ); ?></label></th>
						<td><input type="password" id="lite_stripe_secret_key" name="lite_stripe_secret_key" value="<?php echo esc_attr( get_option( 'lite_stripe_secret_key' ) ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"></th>
						<td>
							<div style="background: #f0f7ff; border-left: 4px solid #007cba; padding: 16px; margin-top: 10px; border-radius: 4px;">
								<strong style="display: block; margin-bottom: 8px; color: #1d2327;">🚀 <?php esc_html_e( 'Enable Digital Wallets & Link', 'bluu-lite-ecommerce' ); ?></strong>
								<p style="margin: 0 0 10px; font-size: 13px; color: #50575e;">
									<?php esc_html_e( 'Apple Pay, Google Pay, and Link are now enabled! To ensure they work correctly:', 'bluu-lite-ecommerce' ); ?>
								</p>
								<ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #50575e;">
									<li><strong><?php esc_html_e( 'Apple Pay:', 'bluu-lite-ecommerce' ); ?></strong> <?php esc_html_e( 'You MUST verify your domain in the', 'bluu-lite-ecommerce' ); ?> <a href="https://dashboard.stripe.com/settings/payments/apple_pay" target="_blank"><?php esc_html_e( 'Stripe Dashboard', 'bluu-lite-ecommerce' ); ?></a>.</li>
									<li><strong><?php esc_html_e( 'Manage Methods:', 'bluu-lite-ecommerce' ); ?></strong> <?php esc_html_e( 'Enable or disable specific methods in your', 'bluu-lite-ecommerce' ); ?> <a href="https://dashboard.stripe.com/settings/payment_methods" target="_blank"><?php esc_html_e( 'Stripe Payment Method Settings', 'bluu-lite-ecommerce' ); ?></a>.</li>
								</ul>
							</div>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'PayPal Configuration', 'bluu-lite-ecommerce' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="lite_paypal_client_id"><?php esc_html_e( 'Client ID', 'bluu-lite-ecommerce' ); ?></label></th>
						<td><input type="text" id="lite_paypal_client_id" name="lite_paypal_client_id" value="<?php echo esc_attr( get_option( 'lite_paypal_client_id' ) ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><label for="lite_paypal_secret"><?php esc_html_e( 'Client Secret', 'bluu-lite-ecommerce' ); ?></label></th>
						<td><input type="password" id="lite_paypal_secret" name="lite_paypal_secret" value="<?php echo esc_attr( get_option( 'lite_paypal_secret' ) ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Mode', 'bluu-lite-ecommerce' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="lite_paypal_sandbox" value="1" <?php checked( get_option( 'lite_paypal_sandbox' ), 1 ); ?>>
								<?php esc_html_e( 'Sandbox Mode', 'bluu-lite-ecommerce' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Tax & Shipping', 'bluu-lite-ecommerce' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="lite_vat_rate"><?php esc_html_e( 'VAT Rate (%)', 'bluu-lite-ecommerce' ); ?></label></th>
						<td><input type="number" id="lite_vat_rate" name="lite_vat_rate" value="<?php echo esc_attr( get_option( 'lite_vat_rate', 0 ) ); ?>" class="small-text" step="0.01"> %</td>
					</tr>
					<tr>
						<th scope="row"><label for="lite_shipping_cost"><?php esc_html_e( 'Flat Shipping Cost', 'bluu-lite-ecommerce' ); ?></label></th>
						<td><input type="number" id="lite_shipping_cost" name="lite_shipping_cost" value="<?php echo esc_attr( get_option( 'lite_shipping_cost', 0 ) ); ?>" class="small-text" step="0.01"></td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Page Setup', 'bluu-lite-ecommerce' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="lite_checkout_page"><?php esc_html_e( 'Checkout Page', 'bluu-lite-ecommerce' ); ?></label></th>
						<td>
							<?php
							wp_dropdown_pages( array(
								'name'             => 'lite_checkout_page',
								'show_option_none' => esc_html__( '&mdash; Select &mdash;', 'bluu-lite-ecommerce' ),
								'option_none_value'=> '0',
								'selected'         => intval( get_option( 'lite_checkout_page' ) ),
							) );
							?>
							<p class="description"><?php esc_html_e( 'Select the page where you pasted the [lite_checkout] shortcode.', 'bluu-lite-ecommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="lite_cart_page"><?php esc_html_e( 'Cart Page (Optional)', 'bluu-lite-ecommerce' ); ?></label></th>
						<td>
							<?php
							wp_dropdown_pages( array(
								'name'             => 'lite_cart_page',
								'show_option_none' => esc_html__( '&mdash; Select &mdash;', 'bluu-lite-ecommerce' ),
								'option_none_value'=> '0',
								'selected'         => intval( get_option( 'lite_cart_page' ) ),
							) );
							?>
							<p class="description"><?php esc_html_e( 'Select the page where you pasted the [lite_cart] shortcode, if separate from Checkout.', 'bluu-lite-ecommerce' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Email Settings', 'bluu-lite-ecommerce' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Customize the emails sent to customers and admins.', 'bluu-lite-ecommerce' ); ?></p>
				
				<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px; border-radius: 4px;">
					<h3 style="margin-top:0;"><?php esc_html_e( 'Global Email Config', 'bluu-lite-ecommerce' ); ?></h3>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="lite_email_from_name"><?php esc_html_e( 'From Name', 'bluu-lite-ecommerce' ); ?></label></th>
							<td><input type="text" id="lite_email_from_name" name="lite_email_from_name" value="<?php echo esc_attr( get_option( 'lite_email_from_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text"></td>
						</tr>
						<tr>
							<th scope="row"><label for="lite_email_from_email"><?php esc_html_e( 'From Email', 'bluu-lite-ecommerce' ); ?></label></th>
							<td><input type="email" id="lite_email_from_email" name="lite_email_from_email" value="<?php echo esc_attr( get_option( 'lite_email_from_email', get_bloginfo( 'admin_email' ) ) ); ?>" class="regular-text"></td>
						</tr>
					</table>

					<hr>

					<h3><?php esc_html_e( 'Customer Order Confirmation', 'bluu-lite-ecommerce' ); ?></h3>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="lite_email_subject"><?php esc_html_e( 'Subject', 'bluu-lite-ecommerce' ); ?></label></th>
							<td><input type="text" id="lite_email_subject" name="lite_email_subject" value="<?php echo esc_attr( get_option( 'lite_email_subject', 'Order Confirmation - {order_id}' ) ); ?>" class="large-text"></td>
						</tr>
						<tr>
							<th scope="row"><label for="lite_email_body"><?php esc_html_e( 'Message Body', 'bluu-lite-ecommerce' ); ?></label></th>
							<td>
								<?php 
								$content = get_option( 'lite_email_body', "Hi {customer_name},\n\nThank you for your order! We've received your payment and are now processing your order {order_id}.\n\nTotal: {total_amount}\n\nThanks for shopping with us!" );
								wp_editor( $content, 'lite_email_body', array( 'textarea_rows' => 10 ) ); 
								?>
								<p class="description"><?php esc_html_e( 'Available tags: {order_id}, {customer_name}, {total_amount}, {order_date}', 'bluu-lite-ecommerce' ); ?></p>
							</td>
						</tr>
					</table>

					<hr>

					<h3><?php esc_html_e( 'Admin Notifications', 'bluu-lite-ecommerce' ); ?></h3>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Notification', 'bluu-lite-ecommerce' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="lite_admin_email_enabled" value="1" <?php checked( get_option( 'lite_admin_email_enabled', 1 ), 1 ); ?>>
									<?php esc_html_e( 'Send an email to admin on new orders', 'bluu-lite-ecommerce' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="lite_admin_email_recipient"><?php esc_html_e( 'Recipient Email', 'bluu-lite-ecommerce' ); ?></label></th>
							<td><input type="email" id="lite_admin_email_recipient" name="lite_admin_email_recipient" value="<?php echo esc_attr( get_option( 'lite_admin_email_recipient', get_bloginfo( 'admin_email' ) ) ); ?>" class="regular-text"></td>
						</tr>
						<tr>
							<th scope="row"><label for="lite_admin_email_subject"><?php esc_html_e( 'Admin Subject', 'bluu-lite-ecommerce' ); ?></label></th>
							<td><input type="text" id="lite_admin_email_subject" name="lite_admin_email_subject" value="<?php echo esc_attr( get_option( 'lite_admin_email_subject', 'New Order Received: {order_id}' ) ); ?>" class="large-text"></td>
						</tr>
						<tr>
							<th scope="row"><label for="lite_admin_email_body"><?php esc_html_e( 'Admin Message', 'bluu-lite-ecommerce' ); ?></label></th>
							<td>
								<?php 
								$admin_content = get_option( 'lite_admin_email_body', "A new order has been placed on the site.\n\nOrder ID: {order_id}\nCustomer: {customer_name} ({customer_email})\nTotal: {total_amount}\n\nView order: {admin_order_url}" );
								wp_editor( $admin_content, 'lite_admin_email_body', array( 'textarea_rows' => 8 ) ); 
								?>
							</td>
						</tr>
					</table>
				</div>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
