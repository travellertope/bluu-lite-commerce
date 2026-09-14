<?php
/**
 * Lite eCommerce Checkout Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lite_eCommerce_Checkout {

	/**
	 * Initialize.
	 */
	public static function init() {
		add_shortcode( 'lite_cart', array( __CLASS__, 'render_cart' ) );
		add_shortcode( 'lite_checkout', array( __CLASS__, 'render_checkout' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_checkout_submission' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_payment_callback' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
	}

	/**
	 * Format cart item options for display.
	 *
	 * @param array $item Cart item with 'product_id' and 'options'.
	 * @return string Formatted string like 'Size: Large · Colour: Red', or empty.
	 */
	public static function format_item_options( $item ) {
		if ( empty( $item['options'] ) || ! is_array( $item['options'] ) ) {
			return '';
		}

		$product_options = Lite_eCommerce_Product::get_product_options( $item['product_id'] );
		$labels_map = array();
		foreach ( $product_options as $opt ) {
			$labels_map[ $opt['id'] ] = $opt['label'];
		}

		$parts = array();
		foreach ( $item['options'] as $key => $value ) {
			if ( '' === $value ) {
				continue;
			}
			$label = isset( $labels_map[ $key ] ) ? $labels_map[ $key ] : $key;
			$parts[] = esc_html( $label ) . ': ' . esc_html( $value );
		}

		return implode( ' &middot; ', $parts );
	}

	/**
	 * Enqueue Google Fonts.
	 */
	public static function enqueue_styles() {
		wp_enqueue_style( 'lite-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', array(), '1.0.2' );
	}

	/**
	 * List of countries for the select field.
	 */
	public static function get_countries() {
		return array(
			'GB' => 'United Kingdom',
			'US' => 'United States',
			'CA' => 'Canada',
			'AU' => 'Australia',
			'IE' => 'Ireland',
			'FR' => 'France',
			'DE' => 'Germany',
			'IT' => 'Italy',
			'ES' => 'Spain',
			'NL' => 'Netherlands',
			'BE' => 'Belgium',
			'SE' => 'Sweden',
			'NO' => 'Norway',
			'DK' => 'Denmark',
			'ZA' => 'South Africa',
			'NG' => 'Nigeria',
			'GH' => 'Ghana',
			'KE' => 'Kenya',
			'AE' => 'United Arab Emirates',
			'IN' => 'India',
			'CN' => 'China',
			'JP' => 'Japan',
			'BR' => 'Brazil',
			'MX' => 'Mexico',
		);
	}

	/**
	 * Shared CSS for eCommerce pages.
	 */
	public static function get_shared_css() {
		ob_start();
		?>
		<style>
			:root {
				--lite-primary: #6c5ce7;
				--lite-primary-h: #5a4bd1;
				--lite-bg: #f8f8fb;
				--lite-surface: #ffffff;
				--lite-text: #1a1a2e;
				--lite-muted: #6b7280;
				--lite-border: rgba(0,0,0,.08);
				--lite-radius: 12px;
				--lite-radius-sm: 8px;
				--lite-shadow: 0 4px 20px rgba(0,0,0,.05);
			}
			.lite-ecommerce-page {
				font-family: 'Inter', system-ui, sans-serif;
				color: var(--lite-text);
				max-width: 1000px;
				margin: 40px auto;
				padding: 0 20px;
			}
			.lite-ecommerce-card {
				background: var(--lite-surface);
				border-radius: var(--lite-radius);
				border: 1px solid var(--lite-border);
				box-shadow: var(--lite-shadow);
				padding: 32px;
				margin-bottom: 30px;
			}
			.lite-ecommerce-title {
				font-size: 24px;
				font-weight: 700;
				margin-bottom: 24px;
				letter-spacing: -0.02em;
			}
			.lite-table {
				width: 100%;
				border-collapse: collapse;
			}
			.lite-table th {
				text-align: left;
				padding: 12px;
				font-size: 13px;
				text-transform: uppercase;
				letter-spacing: 0.05em;
				color: var(--lite-muted);
				border-bottom: 1px solid var(--lite-border);
			}
			.lite-table td {
				padding: 16px 12px;
				border-bottom: 1px solid var(--lite-border);
				vertical-align: middle;
			}
			.lite-product-meta {
				display: flex;
				align-items: center;
				gap: 12px;
			}
			.lite-qty-input {
				width: 60px;
				padding: 6px;
				border-radius: var(--lite-radius-sm);
				border: 1px solid var(--lite-border);
				text-align: center;
				font-weight: 600;
			}
			.lite-remove-btn {
				background: none;
				border: none;
				color: #ff4d4f;
				cursor: pointer;
				font-size: 18px;
				opacity: 0.6;
				transition: opacity 0.2s;
			}
			.lite-remove-btn:hover { opacity: 1; }
			.lite-btn {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				padding: 14px 28px;
				background: var(--lite-primary);
				color: #fff;
				border-radius: var(--lite-radius-sm);
				border: none;
				font-weight: 600;
				cursor: pointer;
				transition: all 0.2s;
				text-decoration: none;
				font-size: 15px;
			}
			.lite-btn:hover {
				background: var(--lite-primary-h);
				transform: translateY(-1px);
				box-shadow: 0 4px 12px rgba(108,92,231,.3);
			}
			.lite-btn-secondary {
				background: #f3f4f6;
				color: var(--lite-text);
			}
			.lite-btn-secondary:hover {
				background: #e5e7eb;
			}
			.lite-summary-row {
				display: flex;
				justify-content: space-between;
				padding: 8px 0;
				font-size: 15px;
			}
			.lite-summary-total {
				margin-top: 16px;
				padding-top: 16px;
				border-top: 2px solid var(--lite-border);
				font-weight: 700;
				font-size: 18px;
			}
			.lite-form-grid {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 20px;
			}
			@media (max-width: 600px) {
				.lite-form-grid { grid-template-columns: 1fr; }
			}
			.lite-form-group {
				margin-bottom: 20px;
			}
			.lite-form-group label {
				display: block;
				font-size: 13px;
				font-weight: 600;
				margin-bottom: 6px;
				color: var(--lite-muted);
			}
			.lite-form-control {
				width: 100%;
				padding: 12px;
				border-radius: var(--lite-radius-sm);
				border: 1px solid var(--lite-border);
				background: #fafafa;
				font-size: 15px;
				transition: border-color 0.2s;
			}
			.lite-form-control:focus {
				border-color: var(--lite-primary);
				outline: none;
				background: #fff;
			}
			.lite-payment-option {
				display: flex;
				align-items: center;
				padding: 16px;
				border: 1px solid var(--lite-border);
				border-radius: var(--lite-radius-sm);
				margin-bottom: 12px;
				cursor: pointer;
				transition: all 0.2s;
			}
			.lite-payment-option:hover { background: #f9fafb; }
			.lite-payment-option input { margin-right: 12px; }
			.lite-checkout-layout {
				display: grid;
				grid-template-columns: 1.5fr 1fr;
				gap: 30px;
			}
			@media (max-width: 900px) {
				.lite-checkout-layout { grid-template-columns: 1fr; }
			}
			.lite-success-badge {
				width: 64px;
				height: 64px;
				background: #dcfce7;
				color: #16a34a;
				border-radius: 50%;
				display: flex;
				align-items: center;
				justify-content: center;
				margin: 0 auto 20px;
			}
		</style>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render cart shortcode.
	 */
	public static function render_cart() {
		$cart = Lite_eCommerce_Cart::get_cart();

		if ( empty( $cart ) ) {
			return '<div class="lite-ecommerce-page"><p>' . esc_html__( 'Your cart is currently empty.', 'bluu-lite-ecommerce' ) . '</p></div>';
		}

		ob_start();
		echo self::get_shared_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		<div class="lite-ecommerce-page">
			<h1 class="lite-ecommerce-title"><?php esc_html_e( 'Shopping Cart', 'bluu-lite-ecommerce' ); ?></h1>
			<div class="lite-ecommerce-card">
				<table class="lite-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Product', 'bluu-lite-ecommerce' ); ?></th>
							<th><?php esc_html_e( 'Price', 'bluu-lite-ecommerce' ); ?></th>
							<th><?php esc_html_e( 'Quantity', 'bluu-lite-ecommerce' ); ?></th>
							<th><?php esc_html_e( 'Total', 'bluu-lite-ecommerce' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $cart as $index => $item ) : 
							$product = get_post( $item['product_id'] );
							if ( ! $product ) continue;
							$price = floatval( get_post_meta( $item['product_id'], '_lite_price', true ) );
							$subtotal = $price * intval( $item['quantity'] );
						?>
						<tr>
							<td>
								<div class="lite-product-meta">
									<div>
										<strong><?php echo esc_html( $product->post_title ); ?></strong>
										<?php
										$options_display = self::format_item_options( $item );
										if ( $options_display ) :
										?>
											<div style="font-size: 12px; color: var(--lite-muted); margin-top: 4px;"><?php echo $options_display; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in format_item_options ?></div>
										<?php endif; ?>
									</div>
								</div>
							</td>
							<td><?php echo '£' . number_format( $price, 2 ); ?></td>
							<td>
								<form method="post" action="" style="display:flex; align-items:center; gap:8px;">
									<input type="hidden" name="lite_action" value="update_cart_quantity">
									<input type="hidden" name="cart_index" value="<?php echo esc_attr( $index ); ?>">
									<?php wp_nonce_field( 'lite_update_' . $index, 'lite_update_cart_nonce' ); ?>
									<input type="number" name="lite_quantity" value="<?php echo intval( $item['quantity'] ); ?>" min="1" class="lite-qty-input">
									<button type="submit" class="lite-btn lite-btn-secondary" style="padding: 6px 12px; font-size: 12px;"><?php esc_html_e( 'Update', 'bluu-lite-ecommerce' ); ?></button>
								</form>
							</td>
							<td><?php echo '£' . number_format( $subtotal, 2 ); ?></td>
							<td>
								<form method="post" action="">
									<input type="hidden" name="lite_action" value="remove_from_cart">
									<input type="hidden" name="cart_index" value="<?php echo esc_attr( $index ); ?>">
									<?php wp_nonce_field( 'lite_remove_' . $index, 'lite_remove_from_cart_nonce' ); ?>
									<button type="submit" class="lite-remove-btn" title="<?php esc_attr_e( 'Remove item', 'bluu-lite-ecommerce' ); ?>">&times;</button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<div style="margin-top: 30px; display: flex; justify-content: flex-end; align-items: center; gap: 40px;">
					<div style="text-align: right;">
						<span style="color: var(--lite-muted); font-size: 14px;"><?php esc_html_e( 'Grand Total', 'bluu-lite-ecommerce' ); ?></span>
						<div style="font-size: 24px; font-weight: 700;">£<?php echo number_format( Lite_eCommerce_Cart::get_total(), 2 ); ?></div>
					</div>
					<?php
					$checkout_page_id = get_option( 'lite_checkout_page' );
					$checkout_url = $checkout_page_id ? get_permalink( $checkout_page_id ) : home_url( '/checkout/' );
					?>
					<a href="<?php echo esc_url( $checkout_url ); ?>" class="lite-btn"><?php esc_html_e( 'Proceed to Checkout', 'bluu-lite-ecommerce' ); ?> &rarr;</a>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Process native checkout submission.
	 */
	public static function handle_checkout_submission() {
		if ( ! isset( $_POST['lite_checkout_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lite_checkout_nonce'] ) ), 'lite_checkout_action' ) ) {
			return;
		}

		$cart = Lite_eCommerce_Cart::get_cart();
		if ( empty( $cart ) ) return;

		// Allow AJAX pure cart update
		if ( isset( $_POST['lite_ajax_update'] ) && $_POST['lite_ajax_update'] == '1' ) {
			if ( isset( $_POST['lite_quantities'] ) && is_array( $_POST['lite_quantities'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$posted_quantities = wp_unslash( $_POST['lite_quantities'] );
				foreach ( $posted_quantities as $index => $qty ) {
					$index = intval( $index );
					if ( isset( $cart[ $index ] ) ) {
						$qty = intval( $qty );
						if ( $qty > 0 ) {
							$cart[ $index ]['quantity'] = $qty;
						} else {
							unset( $cart[ $index ] ); // Remove if quantity is 0
						}
					}
				}
				$cart = array_values( $cart );
				Lite_eCommerce_Cart::set_cart( $cart );
			}
			
			// Render just the table and exit
			echo self::render_order_summary_table(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		// 1. Sync cart quantities first
		if ( isset( $_POST['lite_quantities'] ) && is_array( $_POST['lite_quantities'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$posted_quantities = wp_unslash( $_POST['lite_quantities'] );
			foreach ( $posted_quantities as $index => $qty ) {
				$index = intval( $index );
				if ( isset( $cart[ $index ] ) ) {
					$qty = intval( $qty );
					if ( $qty > 0 ) {
						$cart[ $index ]['quantity'] = $qty;
					} else {
						unset( $cart[ $index ] ); // Remove if quantity is 0
					}
				}
			}
			$cart = array_values( $cart );
			Lite_eCommerce_Cart::set_cart( $cart );
		}

		if ( empty( $cart ) ) {
			wp_safe_redirect( add_query_arg( 'lite_error', 'Your cart is empty.', get_permalink() ) );
			exit;
		}

		$first_name = isset( $_POST['lite_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lite_first_name'] ) ) : '';
		$last_name  = isset( $_POST['lite_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lite_last_name'] ) ) : '';
		$email      = isset( $_POST['lite_email'] ) ? sanitize_email( wp_unslash( $_POST['lite_email'] ) ) : '';
		$phone      = isset( $_POST['lite_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['lite_phone'] ) ) : '';
		$gateway    = isset( $_POST['lite_payment_gateway'] ) ? sanitize_text_field( wp_unslash( $_POST['lite_payment_gateway'] ) ) : 'stripe';
		$subtotal   = Lite_eCommerce_Cart::get_total();

		$vat_rate      = floatval( get_option( 'lite_vat_rate', 0 ) );
		$shipping_cost = floatval( get_option( 'lite_shipping_cost', 0 ) );
		$vat_amount    = $subtotal * ( $vat_rate / 100 );
		$grand_total   = $subtotal + $vat_amount + $shipping_cost;

		// Gather main postage address
		$billing_address = array(
			'address' => isset( $_POST['lite_address'] ) ? sanitize_text_field( wp_unslash( $_POST['lite_address'] ) ) : '',
			'city'    => isset( $_POST['lite_city'] ) ? sanitize_text_field( wp_unslash( $_POST['lite_city'] ) ) : '',
			'country' => isset( $_POST['lite_country'] ) ? sanitize_text_field( wp_unslash( $_POST['lite_country'] ) ) : '',
		);

		$shipping_details = array();
		if ( isset( $_POST['lite_ship_to_different'] ) ) {
			$shipping_details = array(
				'first_name' => isset( $_POST['lite_shipping_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lite_shipping_first_name'] ) ) : '',
				'last_name'  => isset( $_POST['lite_shipping_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lite_shipping_last_name'] ) ) : '',
				'address'    => isset( $_POST['lite_shipping_address'] ) ? sanitize_text_field( wp_unslash( $_POST['lite_shipping_address'] ) ) : '',
				'city'       => isset( $_POST['lite_shipping_city'] ) ? sanitize_text_field( wp_unslash( $_POST['lite_shipping_city'] ) ) : '',
				'country'    => isset( $_POST['lite_shipping_country'] ) ? sanitize_text_field( wp_unslash( $_POST['lite_shipping_country'] ) ) : '',
			);
		}

		$order_id = Lite_eCommerce_Orders::create_order( array(
			'customer' => array( 
				'first_name' => $first_name, 
				'last_name'  => $last_name, 
				'email'      => $email,
				'phone'      => $phone,
				'address'    => $billing_address['address'],
				'city'       => $billing_address['city'],
				'country'    => $billing_address['country'],
			),
			'items'    => $cart,
			'subtotal' => $subtotal,
			'vat'      => $vat_amount,
			'shipping' => $shipping_cost,
			'total'    => $grand_total,
			'gateway'  => $gateway,
			'shipping_details' => $shipping_details,
		) );

		if ( ! $order_id ) {
			wp_safe_redirect( add_query_arg( 'lite_error', 'order_failed', get_permalink() ) );
			exit;
		}

		if ( 'stripe' === $gateway ) {
			$redirect_url = Lite_eCommerce_Stripe::process_payment( $order_id, $grand_total, $email );
		} else {
			$redirect_url = Lite_eCommerce_PayPal::process_payment( $order_id, $grand_total );
		}

		if ( is_wp_error( $redirect_url ) ) {
			wp_safe_redirect( add_query_arg( 'lite_error', urlencode( $redirect_url->get_error_message() ), get_permalink() ) );
			exit;
		}

		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle Payment Callback.
	 */
	public static function handle_payment_callback() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['lite_order'] ) || ! isset( $_GET['lite_status'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_id = intval( $_GET['lite_order'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status   = sanitize_text_field( wp_unslash( $_GET['lite_status'] ) );

		if ( 'success' === $status ) {
			// Stripe direct return or general success
			Lite_eCommerce_Orders::update_status( $order_id, 'completed' );
			Lite_eCommerce_Cart::empty_cart();
			wp_safe_redirect( add_query_arg( array( 'lite_success' => 1 ), get_permalink() ) );
			exit;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'success_paypal' === $status && isset( $_GET['token'] ) ) {
			// PayPal return - need to capture
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( Lite_eCommerce_PayPal::capture_payment( sanitize_text_field( wp_unslash( $_GET['token'] ) ) ) ) {
				Lite_eCommerce_Orders::update_status( $order_id, 'completed' );
				Lite_eCommerce_Cart::empty_cart();
				wp_safe_redirect( add_query_arg( array( 'lite_success' => 1 ), get_permalink() ) );
				exit;
			}
		}
	}

	/**
	 * Render checkout form shortcode.
	 */
	public static function render_checkout() {
		$cart = Lite_eCommerce_Cart::get_cart();

		echo self::get_shared_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['lite_success'] ) ) {
			return '
			<div class="lite-ecommerce-page">
				<div class="lite-ecommerce-card" style="text-align:center; padding: 60px 40px;">
					<div class="lite-success-badge">
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
					</div>
					<h1 class="lite-ecommerce-title" style="margin-bottom:12px;">' . esc_html__( 'Order Placed!', 'bluu-lite-ecommerce' ) . '</h1>
					<p style="color:var(--lite-muted); margin-bottom:32px;">' . esc_html__( 'Thank you for your purchase. We have received your order successfully.', 'bluu-lite-ecommerce' ) . '</p>
					<a href="' . esc_url( home_url() ) . '" class="lite-btn">' . esc_html__( 'Return to Home', 'bluu-lite-ecommerce' ) . '</a>
				</div>
			</div>';
		}

		if ( empty( $cart ) ) {
			return '<div class="lite-ecommerce-page"><p>' . esc_html__( 'Add items to your cart to checkout.', 'bluu-lite-ecommerce' ) . '</p></div>';
		}

		ob_start();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['lite_error'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="lite-ecommerce-page"><div class="lite-error" style="color:#ef4444; margin-bottom: 24px; padding: 16px; background: #fef2f2; border: 1px solid #fee2e2; border-radius:8px; font-weight:500;">' . esc_html( sanitize_text_field( wp_unslash( $_GET['lite_error'] ) ) ) . '</div></div>';
		}
		?>
		<div class="lite-ecommerce-page">
			<h1 class="lite-ecommerce-title"><?php esc_html_e( 'Checkout', 'bluu-lite-ecommerce' ); ?></h1>
			<form method="post" action="" id="lite-checkout-form">
				<?php wp_nonce_field( 'lite_checkout_action', 'lite_checkout_nonce' ); ?>

				<div class="lite-checkout-layout">
					<!-- Sticky container for form -->
					<div class="lite-checkout-main">
						<div class="lite-ecommerce-card">
							<h3 class="lite-ecommerce-title" style="font-size: 18px;"><?php esc_html_e( 'Billing & Postage Details', 'bluu-lite-ecommerce' ); ?></h3>
							
							<div class="lite-form-grid">
								<div class="lite-form-group">
									<label><?php esc_html_e( 'First Name', 'bluu-lite-ecommerce' ); ?></label>
									<input type="text" name="lite_first_name" required class="lite-form-control" placeholder="John">
								</div>
								<div class="lite-form-group">
									<label><?php esc_html_e( 'Last Name', 'bluu-lite-ecommerce' ); ?></label>
									<input type="text" name="lite_last_name" required class="lite-form-control" placeholder="Doe">
								</div>
							</div>

							<div class="lite-form-grid">
								<div class="lite-form-group">
									<label><?php esc_html_e( 'Email Address', 'bluu-lite-ecommerce' ); ?></label>
									<input type="email" name="lite_email" required class="lite-form-control" placeholder="john@example.com">
								</div>
								<div class="lite-form-group">
									<label><?php esc_html_e( 'Phone Number', 'bluu-lite-ecommerce' ); ?></label>
									<input type="tel" name="lite_phone" required class="lite-form-control" placeholder="+44 123 456 7890">
								</div>
							</div>

							<div class="lite-form-group">
								<label><?php esc_html_e( 'Street Address', 'bluu-lite-ecommerce' ); ?></label>
								<input type="text" name="lite_address" required class="lite-form-control" placeholder="123 Main St">
							</div>

							<div class="lite-form-grid">
								<div class="lite-form-group">
									<label><?php esc_html_e( 'City', 'bluu-lite-ecommerce' ); ?></label>
									<input type="text" name="lite_city" required class="lite-form-control" placeholder="London">
								</div>
								<div class="lite-form-group">
									<label><?php esc_html_e( 'Country', 'bluu-lite-ecommerce' ); ?></label>
									<select name="lite_country" required class="lite-form-control">
										<option value=""><?php esc_html_e( 'Select a country...', 'bluu-lite-ecommerce' ); ?></option>
										<?php foreach ( self::get_countries() as $code => $name ) : ?>
											<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $name, 'United Kingdom' ); ?>><?php echo esc_html( $name ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>

							<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--lite-border);">
								<label style="display: flex; align-items: center; cursor: pointer; font-size: 15px; font-weight: 500;">
									<input type="checkbox" name="lite_ship_to_different" id="lite_ship_to_different" style="margin-right: 12px; width:18px; height:18px;">
									<?php esc_html_e( 'Ship to a different address?', 'bluu-lite-ecommerce' ); ?>
								</label>

								<div id="lite_shipping_fields" style="display: none; margin-top: 24px; background: #f9fafb; padding: 24px; border-radius: var(--lite-radius-sm); border: 1px solid var(--lite-border);">
									<div class="lite-form-grid">
										<div class="lite-form-group">
											<label><?php esc_html_e( 'Shipping First Name', 'bluu-lite-ecommerce' ); ?></label>
											<input type="text" name="lite_shipping_first_name" class="lite-form-control">
										</div>
										<div class="lite-form-group">
											<label><?php esc_html_e( 'Shipping Last Name', 'bluu-lite-ecommerce' ); ?></label>
											<input type="text" name="lite_shipping_last_name" class="lite-form-control">
										</div>
									</div>
									<div class="lite-form-group">
										<label><?php esc_html_e( 'Street Address', 'bluu-lite-ecommerce' ); ?></label>
										<input type="text" name="lite_shipping_address" class="lite-form-control">
									</div>
									<div class="lite-form-grid">
										<div class="lite-form-group">
											<label><?php esc_html_e( 'City', 'bluu-lite-ecommerce' ); ?></label>
											<input type="text" name="lite_shipping_city" class="lite-form-control">
										</div>
										<div class="lite-form-group">
											<label><?php esc_html_e( 'Country', 'bluu-lite-ecommerce' ); ?></label>
											<select name="lite_shipping_country" class="lite-form-control">
												<option value=""><?php esc_html_e( 'Select a country...', 'bluu-lite-ecommerce' ); ?></option>
												<?php foreach ( self::get_countries() as $code => $name ) : ?>
													<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $name, 'United Kingdom' ); ?>><?php echo esc_html( $name ); ?></option>
												<?php endforeach; ?>
											</select>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="lite-ecommerce-card">
							<h3 class="lite-ecommerce-title" style="font-size: 18px;"><?php esc_html_e( 'Payment Method', 'bluu-lite-ecommerce' ); ?></h3>
							<?php
							$stripe_enabled = (bool) get_option( 'lite_stripe_secret_key' );
							$paypal_enabled = (bool) get_option( 'lite_paypal_client_id' );
							$checked_already = false;

							if ( $stripe_enabled ) : ?>
								<label class="lite-payment-option" style="position: relative; overflow: hidden;">
									<input type="radio" name="lite_payment_gateway" value="stripe" <?php echo ! $checked_already ? 'checked' : ''; ?>>
									<span style="flex: 1;">
										<strong style="display: flex; align-items: center; gap: 8px;">
											<?php esc_html_e( 'Secure Card Payment', 'bluu-lite-ecommerce' ); ?>
											<span style="display: inline-flex; gap: 4px;">
												<svg width="24" height="15" viewBox="0 0 24 15" fill="none" style="opacity: 0.8;"><rect width="24" height="15" rx="2" fill="#6772E5"/><path d="M5 5h2v5H5zM8 5h2v5H8zM11 5h2v5h-2z" fill="#fff"/></svg>
											</span>
										</strong>
										<small style="color:var(--lite-muted); display: block; margin-top: 2px;">
											<?php esc_html_e( 'Supports Apple Pay, Google Pay, Link & Cards', 'bluu-lite-ecommerce' ); ?>
										</small>
									</span>
								</label>
							<?php $checked_already = true; endif; ?>

							<?php if ( $paypal_enabled ) : ?>
								<label class="lite-payment-option">
									<input type="radio" name="lite_payment_gateway" value="paypal" <?php echo ! $checked_already ? 'checked' : ''; ?>>
									<span>
										<strong><?php esc_html_e( 'PayPal', 'bluu-lite-ecommerce' ); ?></strong><br>
										<small style="color:var(--lite-muted);"><?php esc_html_e( 'Pay via your PayPal account or credit card.', 'bluu-lite-ecommerce' ); ?></small>
									</span>
								</label>
							<?php $checked_already = true; endif; ?>

							<?php if ( ! $stripe_enabled && ! $paypal_enabled ) : ?>
								<p style="color: #ef4444; font-weight: 500;"><?php esc_html_e( 'No payment methods are configured yet.', 'bluu-lite-ecommerce' ); ?></p>
							<?php endif; ?>
						</div>
					</div>

					<!-- Sidebar summary -->
					<div class="lite-checkout-sidebar">
						<div class="lite-ecommerce-card" style="position: sticky; top: 40px;">
							<h3 class="lite-ecommerce-title" style="font-size: 18px; margin-bottom: 20px;"><?php esc_html_e( 'Order Summary', 'bluu-lite-ecommerce' ); ?></h3>
							
							<div id="lite-order-summary-container">
								<?php echo self::render_order_summary_table(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>

							<button type="submit" class="lite-btn" style="width: 100%; margin-top: 20px; padding: 18px;">
								<?php esc_html_e( 'Continue to Payment', 'bluu-lite-ecommerce' ); ?>
							</button>
							
							<p style="text-align: center; font-size: 12px; color: var(--lite-muted); margin-top: 16px;">
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: -2px; margin-right: 4px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
								<?php esc_html_e( 'Secure encrypted checkout', 'bluu-lite-ecommerce' ); ?>
							</p>
						</div>
					</div>
				</div>

				<script>
				document.getElementById('lite_ship_to_different').addEventListener('change', function() {
					document.getElementById('lite_shipping_fields').style.display = this.checked ? 'block' : 'none';
				});

				// Auto-reload totals on quantity change (AJAX)
				document.addEventListener('change', function(e) {
					if (e.target.name && e.target.name.startsWith('lite_quantities')) {
						var form = e.target.closest('form');
						var formData = new FormData(form);
						formData.append('lite_ajax_update', '1');

						var container = document.getElementById('lite-order-summary-container');
						container.style.opacity = '0.5';

						fetch(window.location.href, {
							method: 'POST',
							body: formData
						})
						.then(response => response.text())
						.then(html => {
							container.innerHTML = html;
							container.style.opacity = '1';
						});
					}
				});
				</script>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Helper to render the order summary table for AJAX and normal rendering.
	 */
	public static function render_order_summary_table() {
		$cart = Lite_eCommerce_Cart::get_cart();
		if ( empty( $cart ) ) {
			return '<p>' . esc_html__( 'Your cart is empty.', 'bluu-lite-ecommerce' ) . '</p>';
		}

		$subtotal      = Lite_eCommerce_Cart::get_total();
		$vat_rate      = floatval( get_option( 'lite_vat_rate', 0 ) );
		$shipping_cost = floatval( get_option( 'lite_shipping_cost', 0 ) );
		$vat_amount    = $subtotal * ( $vat_rate / 100 );
		$grand_total   = $subtotal + $vat_amount + $shipping_cost;

		ob_start();
		?>
		<div style="padding: 10px 0;">
			<?php foreach ( $cart as $index => $item ) : 
				$product = get_post( $item['product_id'] );
				$price   = floatval( get_post_meta( $item['product_id'], '_lite_price', true ) );
			?>
			<div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px;">
				<div style="flex: 1;">
					<strong><?php echo esc_html( $product->post_title ); ?></strong>
					<?php
					$options_display = self::format_item_options( $item );
					if ( $options_display ) :
					?>
						<div style="font-size: 11px; color: var(--lite-muted); margin-top: 2px;"><?php echo $options_display; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in format_item_options ?></div>
					<?php endif; ?>
					<div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
						<span style="color: var(--lite-muted);">Qty:</span>
						<input type="number" name="lite_quantities[<?php echo esc_attr( $index ); ?>]" value="<?php echo intval( $item['quantity'] ); ?>" min="0" class="lite-qty-input" style="width: 50px; padding: 2px 4px; font-size: 13px;">
					</div>
				</div>
				<div style="font-weight: 600;"><?php echo '£' . number_format( $price * $item['quantity'], 2 ); ?></div>
			</div>
			<?php endforeach; ?>
			
			<div style="margin-top: 20px; border-top: 1px solid var(--lite-border); padding-top: 16px;">
				<div class="lite-summary-row">
					<span><?php esc_html_e( 'Subtotal', 'bluu-lite-ecommerce' ); ?></span>
					<span><?php echo '£' . number_format( $subtotal, 2 ); ?></span>
				</div>
				<?php if ( $vat_amount > 0 ) : ?>
				<div class="lite-summary-row">
					<span><?php esc_html_e( 'VAT', 'bluu-lite-ecommerce' ); ?></span>
					<span><?php echo '£' . number_format( $vat_amount, 2 ); ?></span>
				</div>
				<?php endif; ?>
				<?php if ( $shipping_cost > 0 ) : ?>
				<div class="lite-summary-row">
					<span><?php esc_html_e( 'Shipping', 'bluu-lite-ecommerce' ); ?></span>
					<span><?php echo '£' . number_format( $shipping_cost, 2 ); ?></span>
				</div>
				<?php endif; ?>
				<div class="lite-summary-row lite-summary-total">
					<span><?php esc_html_e( 'Total', 'bluu-lite-ecommerce' ); ?></span>
					<span><?php echo '£' . number_format( $grand_total, 2 ); ?></span>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
