<?php
/**
 * Lite eCommerce Cart Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lite_eCommerce_Cart {

	/**
	 * Cart session key.
	 */
	const SESSION_KEY = 'lite_ecommerce_cart';

	/**
	 * Initialize the cart system.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'handle_cart_actions' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_floating_cart_button' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_floating_cart_script' ) );
		add_action( 'wp_ajax_lite_add_to_cart', array( __CLASS__, 'ajax_add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_lite_add_to_cart', array( __CLASS__, 'ajax_add_to_cart' ) );
		add_action( 'wp_ajax_lite_update_cart_quantity', array( __CLASS__, 'ajax_update_cart_quantity' ) );
		add_action( 'wp_ajax_nopriv_lite_update_cart_quantity', array( __CLASS__, 'ajax_update_cart_quantity' ) );
		add_action( 'wp_ajax_lite_remove_from_cart', array( __CLASS__, 'ajax_remove_from_cart' ) );
		add_action( 'wp_ajax_nopriv_lite_remove_from_cart', array( __CLASS__, 'ajax_remove_from_cart' ) );
	}

	/**
	 * Resolve the URL the floating cart button (and AJAX responses) should link to.
	 *
	 * @return string Cart URL.
	 */
	public static function get_cart_url() {
		$cart_page_id = get_option( 'lite_cart_page' );
		if ( $cart_page_id ) {
			return get_permalink( $cart_page_id );
		}

		$checkout_page_id = get_option( 'lite_checkout_page' );
		return $checkout_page_id ? get_permalink( $checkout_page_id ) : home_url( '/cart/' );
	}

	/**
	 * Enqueue the script that powers the real-time floating cart button.
	 */
	public static function enqueue_floating_cart_script() {
		if ( is_admin() ) {
			return;
		}

		wp_register_script( 'lite-floating-cart', false, array(), '1.0.2', true );
		wp_enqueue_script( 'lite-floating-cart' );

		wp_localize_script(
			'lite-floating-cart',
			'liteCartData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			)
		);

		wp_add_inline_script( 'lite-floating-cart', self::get_floating_cart_script() );
	}

	/**
	 * JS that intercepts add-to-cart form submissions and updates the
	 * floating cart button instantly, without a full page reload.
	 */
	private static function get_floating_cart_script() {
		return <<<'JS'
(function() {
	function updateFloatingCart(count, total, cartUrl) {
		var btn = document.getElementById('lite-floating-cart');
		if (!btn || count === null || count === undefined || total === null || total === undefined) return;

		if (cartUrl) {
			btn.setAttribute('href', cartUrl);
		}

		var countEl = document.getElementById('lite-floating-cart-count');
		var totalEl = document.getElementById('lite-floating-cart-total');
		if (countEl) countEl.textContent = count;
		if (totalEl) totalEl.textContent = '£' + Number(total).toFixed(2);

		if (count > 0) {
			btn.classList.remove('lite-floating-cart-hidden');
		} else {
			btn.classList.add('lite-floating-cart-hidden');
		}

		btn.classList.remove('lite-floating-cart-bump');
		// Force reflow so the animation can restart on rapid successive adds.
		void btn.offsetWidth;
		btn.classList.add('lite-floating-cart-bump');
	}

	// Exposed so other inline scripts on the page (e.g. the checkout page's
	// own quantity-update AJAX) can keep the floating cart live too.
	window.liteUpdateFloatingCart = updateFloatingCart;

	function submitViaAjax(form, ajaxAction, onSuccess) {
		var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
		if (submitBtn) {
			submitBtn.disabled = true;
		}

		var formData = new FormData(form);
		formData.set('action', ajaxAction);

		fetch(liteCartData.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		})
		.then(function(response) { return response.json(); })
		.then(function(json) {
			if (json && json.success && json.data) {
				onSuccess(json.data);
			} else {
				form.submit();
			}
		})
		.catch(function() {
			form.submit();
		})
		.finally(function() {
			if (submitBtn) {
				submitBtn.disabled = false;
			}
		});
	}

	function replaceCartCard(html) {
		if (!html) return;
		var current = document.getElementById('lite-cart-items-card');
		if (!current) return;

		var wrapper = document.createElement('div');
		wrapper.innerHTML = html;
		var updated = wrapper.querySelector('#lite-cart-items-card');
		if (updated) {
			current.replaceWith(updated);
		}
	}

	document.addEventListener('submit', function(e) {
		var form = e.target;
		if (!form.querySelector) return;

		if (form.querySelector('input[name="lite_action"][value="add_to_cart"]')) {
			e.preventDefault();
			submitViaAjax(form, 'lite_add_to_cart', function(data) {
				updateFloatingCart(data.count, data.total, data.cart_url);
				form.dispatchEvent(new CustomEvent('lite:added-to-cart', { bubbles: true, detail: data }));
			});
			return;
		}

		if (form.querySelector('input[name="lite_action"][value="update_cart_quantity"]')) {
			e.preventDefault();
			submitViaAjax(form, 'lite_update_cart_quantity', function(data) {
				replaceCartCard(data.html);
				updateFloatingCart(data.count, data.total, data.cart_url);
			});
			return;
		}

		if (form.querySelector('input[name="lite_action"][value="remove_from_cart"]')) {
			e.preventDefault();
			submitViaAjax(form, 'lite_remove_from_cart', function(data) {
				replaceCartCard(data.html);
				updateFloatingCart(data.count, data.total, data.cart_url);
			});
			return;
		}
	});
})();
JS;
	}

	/**
	 * AJAX handler: add a product to the cart and return updated cart totals.
	 */
	public static function ajax_add_to_cart() {
		$product_id = isset( $_POST['lite_product_id'] ) ? intval( $_POST['lite_product_id'] ) : 0;
		$quantity   = isset( $_POST['lite_quantity'] ) ? intval( $_POST['lite_quantity'] ) : 1;

		if ( ! $product_id || ! isset( $_POST['lite_add_to_cart_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lite_add_to_cart_nonce'] ) ), 'lite_add_to_cart_' . $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'bluu-lite-ecommerce' ) ), 400 );
		}

		$options = array();
		if ( isset( $_POST['lite_options'] ) && is_array( $_POST['lite_options'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_options = wp_unslash( $_POST['lite_options'] );
			foreach ( $raw_options as $key => $value ) {
				$options[ sanitize_key( $key ) ] = sanitize_text_field( $value );
			}
		}

		self::add_to_cart( $product_id, max( 1, $quantity ), $options );

		wp_send_json_success(
			array(
				'count'    => self::get_item_count(),
				'total'    => number_format( self::get_total(), 2, '.', '' ),
				'cart_url' => self::get_cart_url(),
			)
		);
	}

	/**
	 * AJAX handler: update a cart item's quantity (or remove it if set to 0).
	 */
	public static function ajax_update_cart_quantity() {
		$index    = isset( $_POST['cart_index'] ) ? intval( $_POST['cart_index'] ) : -1;
		$quantity = isset( $_POST['lite_quantity'] ) ? intval( $_POST['lite_quantity'] ) : 0;

		if ( $index < 0 || ! isset( $_POST['lite_update_cart_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lite_update_cart_nonce'] ) ), 'lite_update_' . $index ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'bluu-lite-ecommerce' ) ), 400 );
		}

		if ( $quantity > 0 ) {
			$cart = self::get_cart();
			if ( isset( $cart[ $index ] ) ) {
				$cart[ $index ]['quantity'] = $quantity;
				self::set_cart( $cart );
			}
		} else {
			self::remove_from_cart( $index );
		}

		self::send_cart_card_response();
	}

	/**
	 * AJAX handler: remove an item from the cart.
	 */
	public static function ajax_remove_from_cart() {
		$index = isset( $_POST['cart_index'] ) ? intval( $_POST['cart_index'] ) : -1;

		if ( $index < 0 || ! isset( $_POST['lite_remove_from_cart_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lite_remove_from_cart_nonce'] ) ), 'lite_remove_' . $index ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'bluu-lite-ecommerce' ) ), 400 );
		}

		self::remove_from_cart( $index );

		self::send_cart_card_response();
	}

	/**
	 * Send the standard JSON response for a cart mutation: the re-rendered
	 * cart items card plus fresh totals for the floating cart button.
	 */
	private static function send_cart_card_response() {
		wp_send_json_success(
			array(
				'html'     => Lite_eCommerce_Checkout::render_cart_card(),
				'count'    => self::get_item_count(),
				'total'    => number_format( self::get_total(), 2, '.', '' ),
				'cart_url' => self::get_cart_url(),
			)
		);
	}

	/**
	 * Get total quantity of items in the cart.
	 *
	 * @return int Total item count.
	 */
	public static function get_item_count() {
		$cart  = self::get_cart();
		$count = 0;

		foreach ( $cart as $item ) {
			$count += intval( $item['quantity'] );
		}

		return $count;
	}

	/**
	 * Render a site-wide floating cart button.
	 *
	 * Shows on every front-end page whenever the cart contains at least
	 * one item, so it "activates" immediately after an add-to-cart
	 * redirect completes.
	 */
	public static function render_floating_cart_button() {
		if ( is_admin() ) {
			return;
		}

		$count    = self::get_item_count();
		$total    = self::get_total();
		$cart_url = self::get_cart_url();
		$hidden   = $count < 1 ? ' lite-floating-cart-hidden' : '';
		?>
		<style>
			.lite-floating-cart {
				position: fixed;
				bottom: 24px;
				right: 24px;
				z-index: 999999;
				display: inline-flex;
				align-items: center;
				gap: 10px;
				background: #6c5ce7;
				color: #fff;
				padding: 14px 20px;
				border-radius: 999px;
				box-shadow: 0 6px 20px rgba(0,0,0,.2);
				text-decoration: none;
				font-family: 'Inter', system-ui, sans-serif;
				font-weight: 600;
				font-size: 15px;
				line-height: 1;
				transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
			}
			.lite-floating-cart:hover {
				transform: translateY(-2px);
				box-shadow: 0 8px 24px rgba(0,0,0,.28);
				color: #fff;
			}
			.lite-floating-cart-hidden {
				display: none !important;
			}
			.lite-floating-cart svg {
				display: block;
			}
			.lite-floating-cart-count {
				background: #fff;
				color: #6c5ce7;
				border-radius: 999px;
				min-width: 20px;
				height: 20px;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				font-size: 12px;
				font-weight: 700;
				padding: 0 5px;
			}
			.lite-floating-cart-total {
				opacity: 0.9;
			}
			@keyframes lite-floating-cart-bump {
				0%   { transform: scale(1); }
				35%  { transform: scale(1.15); }
				60%  { transform: scale(0.96); }
				100% { transform: scale(1); }
			}
			.lite-floating-cart-bump {
				animation: lite-floating-cart-bump 0.4s ease;
			}
			@media (max-width: 480px) {
				.lite-floating-cart {
					bottom: 16px;
					right: 16px;
					padding: 12px 16px;
					font-size: 14px;
				}
			}
		</style>
		<a href="<?php echo esc_url( $cart_url ); ?>" id="lite-floating-cart" class="lite-floating-cart<?php echo esc_attr( $hidden ); ?>" aria-label="<?php esc_attr_e( 'View cart', 'bluu-lite-ecommerce' ); ?>">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<circle cx="9" cy="21" r="1"></circle>
				<circle cx="20" cy="21" r="1"></circle>
				<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
			</svg>
			<span class="lite-floating-cart-count" id="lite-floating-cart-count"><?php echo intval( $count ); ?></span>
			<span class="lite-floating-cart-total" id="lite-floating-cart-total">&pound;<?php echo esc_html( number_format( $total, 2 ) ); ?></span>
		</a>
		<?php
	}

	/**
	 * Handle add to cart and remove from cart actions.
	 */
	public static function handle_cart_actions() {
		if ( ! isset( $_REQUEST['lite_action'] ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_REQUEST['lite_action'] ) );

		if ( 'add_to_cart' === $action && isset( $_REQUEST['lite_product_id'] ) ) {
			$product_id = intval( $_REQUEST['lite_product_id'] );
			$quantity   = isset( $_REQUEST['lite_quantity'] ) ? intval( $_REQUEST['lite_quantity'] ) : 1;
			
			if ( ! isset( $_REQUEST['lite_add_to_cart_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['lite_add_to_cart_nonce'] ) ), 'lite_add_to_cart_' . $product_id ) ) {
				return; // Invalid nonce
			}

			// Gather selected options
			$options = array();
			if ( isset( $_REQUEST['lite_options'] ) && is_array( $_REQUEST['lite_options'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$raw_options = wp_unslash( $_REQUEST['lite_options'] );
				foreach ( $raw_options as $key => $value ) {
					$options[ sanitize_key( $key ) ] = sanitize_text_field( $value );
				}
			}

			self::add_to_cart( $product_id, max( 1, $quantity ), $options );

			// Redirect to avoid form resubmission
			wp_safe_redirect( remove_query_arg( array( 'lite_action', 'lite_product_id', 'lite_quantity', 'lite_add_to_cart_nonce' ) ) );
			exit;
		}

		if ( 'direct_add_to_cart' === $action && isset( $_REQUEST['product_id'] ) ) {
			$product_id = intval( $_REQUEST['product_id'] );
			$quantity   = isset( $_REQUEST['qty'] ) ? intval( $_REQUEST['qty'] ) : 1;
			
			// Clear existing cart for direct checkout links to ensure a clean start
			self::empty_cart();
			
			self::add_to_cart( $product_id, $quantity );

			// Get configured checkout page
			$checkout_page_id = get_option( 'lite_checkout_page' );
			$checkout_url     = '';

			if ( $checkout_page_id ) {
				$checkout_url = get_permalink( $checkout_page_id );
			} else {
				// Fallback: Try to find the page with the lite_checkout shortcode (cached)
				$page_id = wp_cache_get( 'lite_checkout_page_detected' );
				if ( false === $page_id ) {
					global $wpdb;
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$page_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_content LIKE %s AND post_status = 'publish' LIMIT 1", '%[lite_checkout]%' ) );
					wp_cache_set( 'lite_checkout_page_detected', $page_id, '', 3600 );
				}

				if ( $page_id ) {
					$checkout_url = get_permalink( $page_id );
				} else {
					$checkout_url = home_url( '/checkout/' );
				}
			}
			
			wp_safe_redirect( $checkout_url );
			exit;
		}

		if ( 'update_cart_quantity' === $action && isset( $_REQUEST['cart_index'] ) ) {
			$index    = intval( $_REQUEST['cart_index'] );
			$quantity = isset( $_REQUEST['lite_quantity'] ) ? intval( $_REQUEST['lite_quantity'] ) : 0;
			
			if ( ! isset( $_REQUEST['lite_update_cart_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['lite_update_cart_nonce'] ) ), 'lite_update_' . $index ) ) {
				return;
			}

			if ( $quantity > 0 ) {
				$cart = self::get_cart();
				if ( isset( $cart[ $index ] ) ) {
					$cart[ $index ]['quantity'] = $quantity;
					self::set_cart( $cart );
				}
			} else {
				self::remove_from_cart( $index );
			}

			// Redirect
			wp_safe_redirect( remove_query_arg( array( 'lite_action', 'cart_index', 'lite_quantity', 'lite_update_cart_nonce' ) ) );
			exit;
		}

		if ( 'remove_from_cart' === $action && isset( $_REQUEST['cart_index'] ) ) {
			$index = intval( $_REQUEST['cart_index'] );
			
			if ( ! isset( $_REQUEST['lite_remove_from_cart_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['lite_remove_from_cart_nonce'] ) ), 'lite_remove_' . $index ) ) {
				return;
			}

			self::remove_from_cart( $index );

			// Redirect
			wp_safe_redirect( remove_query_arg( array( 'lite_action', 'cart_index', 'lite_remove_from_cart_nonce' ) ) );
			exit;
		}
	}

	/**
	 * Add a product to the cart.
	 *
	 * @param int   $product_id Product ID.
	 * @param int   $quantity   Quantity to add.
	 * @param array $options    Selected product options (key => value).
	 */
	public static function add_to_cart( $product_id, $quantity = 1, $options = array() ) {
		$cart = self::get_cart();

		// Check if product with same options exists in cart
		$found = false;
		foreach ( $cart as &$item ) {
			$item_options = isset( $item['options'] ) ? $item['options'] : array();
			if ( $item['product_id'] === $product_id && $item_options == $options ) {
				$item['quantity'] += $quantity;
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			$cart[] = array(
				'product_id' => $product_id,
				'quantity'   => $quantity,
				'options'    => $options,
			);
		}

		self::set_cart( $cart );
	}

	/**
	 * Remove a product from the cart by index.
	 *
	 * @param int $index Cart item index.
	 */
	public static function remove_from_cart( $index ) {
		$cart = self::get_cart();

		if ( isset( $cart[ $index ] ) ) {
			unset( $cart[ $index ] );
			$cart = array_values( $cart ); // Re-index array
			self::set_cart( $cart );
		}
	}

	/**
	 * Get cart contents.
	 *
	 * @return array Cart items.
	 */
	public static function get_cart() {
		// 1. Try Session
		if ( isset( $_SESSION[ self::SESSION_KEY ] ) && is_array( $_SESSION[ self::SESSION_KEY ] ) && ! empty( $_SESSION[ self::SESSION_KEY ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return $_SESSION[ self::SESSION_KEY ];
		}
		
		// 2. Try Cookie if session is empty or lost
		if ( isset( $_COOKIE[ self::SESSION_KEY ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$cart = json_decode( wp_unslash( $_COOKIE[ self::SESSION_KEY ] ), true );
			if ( is_array( $cart ) ) {
				// Re-hydrate session
				if ( session_id() ) {
					$_SESSION[ self::SESSION_KEY ] = $cart;
				}
				return $cart;
			}
		}
		
		return array();
	}

	/**
	 * Set cart contents.
	 *
	 * @param array $cart Cart items.
	 */
	public static function set_cart( $cart ) {
		// 1. Set Session
		if ( session_id() ) {
			$_SESSION[ self::SESSION_KEY ] = $cart;
		}

		// 2. Set Cookie (7 days)
		if ( ! headers_sent() ) {
			setcookie( self::SESSION_KEY, wp_json_encode( $cart ), time() + ( 7 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	/**
	 * Empty the cart.
	 */
	public static function empty_cart() {
		if ( session_id() ) {
			unset( $_SESSION[ self::SESSION_KEY ] );
		}
		
		if ( ! headers_sent() ) {
			setcookie( self::SESSION_KEY, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	/**
	 * Calculate cart total.
	 *
	 * @return float Total price.
	 */
	public static function get_total() {
		$cart = self::get_cart();
		$total = 0;

		foreach ( $cart as $item ) {
			$price = get_post_meta( $item['product_id'], '_lite_price', true );
			if ( $price ) {
				$total += floatval( $price ) * intval( $item['quantity'] );
			}
		}

		return max( 0, $total );
	}
}
