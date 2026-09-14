<?php
/**
 * Lite eCommerce PayPal Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lite_eCommerce_PayPal {

	/**
	 * Get PayPal API URL.
	 */
	private static function get_api_url() {
		return get_option( 'lite_paypal_sandbox' ) ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
	}

	/**
	 * Get Access Token.
	 */
	private static function get_access_token() {
		$client_id = get_option( 'lite_paypal_client_id' );
		$secret    = get_option( 'lite_paypal_secret' );

		if ( ! $client_id || ! $secret ) {
			return false;
		}

		$response = wp_remote_post( self::get_api_url() . '/v1/oauth2/token', array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $secret ),
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
			'body' => 'grant_type=client_credentials',
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		return $body->access_token ?? false;
	}

	/**
	 * Create PayPal Order and Return Approval Link.
	 */
	public static function process_payment( $order_id, $total ) {
		$token = self::get_access_token();
		if ( ! $token ) {
			return new WP_Error( 'paypal_error', __( 'PayPal authentication failed. Check your API keys.', 'bluu-lite-ecommerce' ) );
		}

		$subtotal      = Lite_eCommerce_Cart::get_total();
		$vat_rate      = floatval( get_option( 'lite_vat_rate', 0 ) );
		$shipping_cost = floatval( get_option( 'lite_shipping_cost', 0 ) );
		$vat_amount    = $subtotal * ( $vat_rate / 100 );

		// Get checkout URL
		$checkout_page_id = get_option( 'lite_checkout_page' );
		if ( $checkout_page_id ) {
			$checkout_url = get_permalink( $checkout_page_id );
		} else {
			$checkout_url = home_url( '/checkout/' );
		}

		$response = wp_remote_post( self::get_api_url() . '/v2/checkout/orders', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body' => json_encode( array(
				'intent'         => 'CAPTURE',
				'purchase_units' => array( array(
					'reference_id' => $order_id,
					'amount'       => array(
						'currency_code' => 'GBP',
						'value'         => number_format( $total, 2, '.', '' ),
						'breakdown'     => array(
							'item_total' => array(
								'currency_code' => 'GBP',
								'value'         => number_format( $subtotal, 2, '.', '' ),
							),
							'tax_total' => array(
								'currency_code' => 'GBP',
								'value'         => number_format( $vat_amount, 2, '.', '' ),
							),
							'shipping' => array(
								'currency_code' => 'GBP',
								'value'         => number_format( $shipping_cost, 2, '.', '' ),
							),
						),
					),
				) ),
				'application_context' => array(
					'return_url' => add_query_arg( array( 'lite_order' => $order_id, 'lite_status' => 'success_paypal' ), $checkout_url ),
					'cancel_url' => add_query_arg( array( 'lite_error' => 'payment_cancelled' ), $checkout_url ),
				),
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( isset( $body->links ) ) {
			foreach ( $body->links as $link ) {
				if ( 'approve' === $link->rel ) {
					return $link->href;
				}
			}
		}

		return new WP_Error( 'paypal_error', __( 'Could not generate PayPal link.', 'bluu-lite-ecommerce' ) );
	}

	/**
	 * Capture PayPal Order.
	 */
	public static function capture_payment( $paypal_order_id ) {
		$token = self::get_access_token();
		if ( ! $token ) return false;

		$response = wp_remote_post( self::get_api_url() . "/v2/checkout/orders/{$paypal_order_id}/capture", array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
		) );

		if ( is_wp_error( $response ) ) return false;

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		return ( 'COMPLETED' === ( $body->status ?? '' ) );
	}
}
