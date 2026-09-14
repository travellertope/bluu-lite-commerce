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
