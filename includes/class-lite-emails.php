<?php
/**
 * Lite eCommerce Emails Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lite_eCommerce_Emails {

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'lite_ecommerce_order_status_completed', array( __CLASS__, 'send_order_confirmation' ) );
		add_action( 'lite_ecommerce_order_status_completed', array( __CLASS__, 'send_admin_notification' ) );
		
		// Set HTML content type for emails
		add_filter( 'wp_mail_content_type', array( __CLASS__, 'set_html_content_type' ) );
	}

	/**
	 * Set HTML content type.
	 */
	public static function set_html_content_type() {
		return 'text/html';
	}

	/**
	 * Send Order Confirmation to Customer.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function send_order_confirmation( $order_id ) {
		$customer = get_post_meta( $order_id, '_lite_customer_details', true );
		if ( empty( $customer['email'] ) ) {
			return;
		}

		$subject_template = get_option( 'lite_email_subject', 'Order Confirmation - {order_id}' );
		$body_template    = get_option( 'lite_email_body', "Hi {customer_name},\n\nThank you for your order! We've received your payment and are now processing your order {order_id}.\n\nTotal: {total_amount}\n\nThanks for shopping with us!" );

		$placeholders = self::get_placeholders( $order_id );
		$subject      = self::replace_placeholders( $subject_template, $placeholders );
		$message      = self::replace_placeholders( $body_template, $placeholders );

		$html_message = self::get_html_template( $subject, $message, $order_id );

		$headers = array(
			'From: ' . get_option( 'lite_email_from_name', get_bloginfo( 'name' ) ) . ' <' . get_option( 'lite_email_from_email', get_bloginfo( 'admin_email' ) ) . '>',
		);

		wp_mail( $customer['email'], $subject, $html_message, $headers );
	}

	/**
	 * Send Admin Notification.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function send_admin_notification( $order_id ) {
		if ( ! get_option( 'lite_admin_email_enabled', 1 ) ) {
			return;
		}

		$recipient = get_option( 'lite_admin_email_recipient', get_bloginfo( 'admin_email' ) );
		if ( empty( $recipient ) ) {
			return;
		}

		$subject_template = get_option( 'lite_admin_email_subject', 'New Order Received: {order_id}' );
		$body_template    = get_option( 'lite_admin_email_body', "A new order has been placed on the site.\n\nOrder ID: {order_id}\nCustomer: {customer_name} ({customer_email})\nTotal: {total_amount}\n\nView order: {admin_order_url}" );

		$placeholders = self::get_placeholders( $order_id );
		$subject      = self::replace_placeholders( $subject_template, $placeholders );
		$message      = self::replace_placeholders( $body_template, $placeholders );

		$html_message = self::get_html_template( $subject, $message, $order_id, true );

		$headers = array(
			'From: ' . get_option( 'lite_email_from_name', get_bloginfo( 'name' ) ) . ' <' . get_option( 'lite_email_from_email', get_bloginfo( 'admin_email' ) ) . '>',
		);

		wp_mail( $recipient, $subject, $html_message, $headers );
	}

	/**
	 * Get placeholders for email templates.
	 */
	private static function get_placeholders( $order_id ) {
		$customer = get_post_meta( $order_id, '_lite_customer_details', true );
		$total    = get_post_meta( $order_id, '_lite_total', true );
		$date     = get_the_date( 'F j, Y', $order_id );

		return array(
			'{order_id}'        => '#' . $order_id,
			'{customer_name}'   => $customer['first_name'] . ' ' . $customer['last_name'],
			'{customer_email}'  => $customer['email'],
			'{total_amount}'    => '£' . number_format( $total, 2 ),
			'{order_date}'      => $date,
			'{admin_order_url}' => admin_url( 'post.php?post=' . $order_id . '&action=edit' ),
		);
	}

	/**
	 * Replace placeholders in a string.
	 */
	private static function replace_placeholders( $text, $placeholders ) {
		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $text );
	}

	/**
	 * Wrap message in a beautiful HTML template.
	 */
	private static function get_html_template( $title, $message, $order_id, $is_admin = false ) {
		$items    = get_post_meta( $order_id, '_lite_items', true );
		$total    = get_post_meta( $order_id, '_lite_total', true );
		$shipping = get_post_meta( $order_id, '_lite_shipping', true );
		$vat      = get_post_meta( $order_id, '_lite_vat', true );

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<style>
				body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f7ff; }
				.container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
				.header { background: #6c5ce7; padding: 40px; text-align: center; color: #fff; }
				.header h1 { margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.5px; }
				.content { padding: 40px; }
				.message { font-size: 16px; color: #4b5563; margin-bottom: 30px; white-space: pre-wrap; }
				.order-summary { background: #f9fafb; border-radius: 12px; padding: 24px; margin-top: 30px; }
				.order-summary h3 { margin-top: 0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-bottom: 16px; }
				.item-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e7eb; font-size: 15px; }
				.item-row:last-child { border-bottom: none; }
				.totals { margin-top: 20px; text-align: right; border-top: 2px solid #eee; padding-top: 15px; }
				.total-row { display: flex; justify-content: flex-end; gap: 20px; font-size: 14px; color: #6b7280; margin-bottom: 4px; }
				.grand-total { font-size: 20px; font-weight: 800; color: #1a1a1a; margin-top: 10px; }
				.footer { padding: 30px; text-align: center; font-size: 13px; color: #9ca3af; background: #f9fafb; }
				.btn { display: inline-block; padding: 14px 28px; background: #6c5ce7; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 20px; }
			</style>
		</head>
		<body>
			<div class="container">
				<div class="header">
					<h1><?php echo esc_html( $title ); ?></h1>
				</div>
				<div class="content">
					<div class="message"><?php echo wp_kses_post( nl2br( $message ) ); ?></div>

					<div class="order-summary">
						<h3>Order Details</h3>
						<?php foreach ( $items as $item ) : 
							$product = get_post( $item['product_id'] );
							$price = get_post_meta( $item['product_id'], '_lite_price', true );
						?>
						<div style="display: table; width: 100%; padding: 10px 0; border-bottom: 1px solid #eee;">
							<div style="display: table-cell; text-align: left;">
								<strong><?php echo esc_html( $product->post_title ); ?></strong><br>
								<?php
								if ( class_exists( 'Lite_eCommerce_Checkout' ) ) {
									$options_display = Lite_eCommerce_Checkout::format_item_options( $item );
									if ( $options_display ) {
										echo '<div style="font-size: 11px; color: #6b7280; margin-top: 2px; margin-bottom: 4px;">' . $options_display . '</div>'; // phpcs:ignore
									}
								}
								?>
								<small style="color: #6b7280;">Qty: <?php echo intval( $item['quantity'] ); ?></small>
							</div>
							<div style="display: table-cell; text-align: right; vertical-align: middle; font-weight: 600;">
								£<?php echo number_format( $price * $item['quantity'], 2 ); ?>
							</div>
						</div>
						<?php endforeach; ?>

						<div style="margin-top: 20px; text-align: right;">
							<?php if ( $vat > 0 ) : ?>
								<div style="color: #6b7280; font-size: 13px;">VAT: £<?php echo number_format( $vat, 2 ); ?></div>
							<?php endif; ?>
							<?php if ( $shipping > 0 ) : ?>
								<div style="color: #6b7280; font-size: 13px;">Shipping: £<?php echo number_format( $shipping, 2 ); ?></div>
							<?php endif; ?>
							<div style="font-size: 18px; font-weight: 700; margin-top: 10px; color: #111;">Total: £<?php echo number_format( $total, 2 ); ?></div>
						</div>
					</div>

					<?php if ( $is_admin ) : ?>
						<div style="text-align: center; margin-top: 30px;">
							<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ); ?>" class="btn">Process Order</a>
						</div>
					<?php endif; ?>
				</div>
				<div class="footer">
					&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}
