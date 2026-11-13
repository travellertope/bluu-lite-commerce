<?php
/**
 * Lite eCommerce Stripe Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lite_eCommerce_Stripe {

	/**
	 * Process payment with Stripe Checkout.
	 */
	public static function process_payment( $order_id, $total, $customer_email ) {
		$secret_key = get_option( 'lite_stripe_secret_key' );

		if ( empty( $secret_key ) ) {
			return new WP_Error( 'stripe_error', __( 'Stripe secret key is not configured.', 'bluu-lite-ecommerce' ) );
		}

		$line_items = array();
		$cart = Lite_eCommerce_Cart::get_cart();

		// Add Products
		foreach ( $cart as $item ) {
			$product = get_post( $item['product_id'] );
			$price   = get_post_meta( $item['product_id'], '_lite_price', true );

			$name = $product->post_title;
			if ( class_exists( 'Lite_eCommerce_Checkout' ) ) {
				$options_display = html_entity_decode( wp_strip_all_tags( Lite_eCommerce_Checkout::format_item_options( $item ) ), ENT_QUOTES, 'UTF-8' );
				if ( $options_display ) {
					$name .= ' (' . $options_display . ')';
				}
			}

			$line_items[] = array(
				'price_data' => array(
					'currency'     => 'gbp',
					'unit_amount'  => intval( floatval( $price ) * 100 ),
					'product_data' => array(
						'name' => $name,
					),
				),
				'quantity' => intval( $item['quantity'] ),
			);
		}

		// Add VAT
		$vat_rate   = floatval( get_option( 'lite_vat_rate', 0 ) );
		$subtotal   = Lite_eCommerce_Cart::get_total();
		$vat_amount = $subtotal * ( $vat_rate / 100 );

		if ( $vat_amount > 0 ) {
			$line_items[] = array(
				'price_data' => array(
					'currency'     => 'gbp',
					'unit_amount'  => intval( $vat_amount * 100 ),
					'product_data' => array(
						'name' => 'VAT (' . $vat_rate . '%)',
					),
				),
				'quantity' => 1,
			);
		}

		// Add Shipping
		$shipping_cost = floatval( get_option( 'lite_shipping_cost', 0 ) );
		if ( $shipping_cost > 0 ) {
			$line_items[] = array(
				'price_data' => array(
					'currency'     => 'gbp',
					'unit_amount'  => intval( $shipping_cost * 100 ),
					'product_data' => array(
						'name' => 'Flat Shipping',
					),
				),
				'quantity' => 1,
			);
		}

		// Get checkout URL
		$checkout_page_id = get_option( 'lite_checkout_page' );
		if ( $checkout_page_id ) {
			$checkout_url = get_permalink( $checkout_page_id );
		} else {
			$checkout_url = home_url( '/checkout/' );
		}

		$response = wp_remote_post( 'https://api.stripe.com/v1/checkout/sessions', array(
			'headers' => array(
				'Authorization'  => 'Bearer ' . $secret_key,
				'Content-Type'   => 'application/x-www-form-urlencoded',
				'Stripe-Version' => '2024-04-10',
			),
			'body' => http_build_query( array(
				'payment_method_types' => array( 'card', 'link' ),
				'line_items'           => $line_items,
				'mode'                 => 'payment',
				'customer_email'       => $customer_email,
				'success_url'          => add_query_arg( array( 'lite_order' => $order_id, 'lite_status' => 'success' ), $checkout_url ),
				'cancel_url'           => add_query_arg( array( 'lite_error' => 'payment_cancelled' ), $checkout_url ),
				'client_reference_id'  => $order_id,
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( isset( $body->error ) ) {
			return new WP_Error( 'stripe_error', $body->error->message );
		}

		return $body->url; // Redirect to this URL
	}
}
