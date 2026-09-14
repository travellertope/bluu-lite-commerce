<?php
/**
 * Lite eCommerce Orders Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lite_eCommerce_Orders {

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
	}

	/**
	 * Register Order CPT.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => _x( 'Orders', 'post type general name', 'bluu-lite-ecommerce' ),
			'singular_name'      => _x( 'Order', 'post type singular name', 'bluu-lite-ecommerce' ),
			'menu_name'          => _x( 'Orders', 'admin menu', 'bluu-lite-ecommerce' ),
			'name_admin_bar'     => _x( 'Order', 'add new on admin bar', 'bluu-lite-ecommerce' ),
			'add_new'            => _x( 'Add New', 'order', 'bluu-lite-ecommerce' ),
			'add_new_item'       => __( 'Add New Order', 'bluu-lite-ecommerce' ),
			'new_item'           => __( 'New Order', 'bluu-lite-ecommerce' ),
			'edit_item'          => __( 'Edit Order', 'bluu-lite-ecommerce' ),
			'view_item'          => __( 'View Order', 'bluu-lite-ecommerce' ),
			'all_items'          => __( 'Orders', 'bluu-lite-ecommerce' ),
			'search_items'       => __( 'Search Orders', 'bluu-lite-ecommerce' ),
			'not_found'          => __( 'No orders found.', 'bluu-lite-ecommerce' ),
			'not_found_in_trash' => __( 'No orders found in Trash.', 'bluu-lite-ecommerce' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => 'bluu-lite-ecommerce',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'lite_order' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' ),
		);

		register_post_type( 'lite_order', $args );
	}

	/**
	 * Add meta boxes for order details.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'lite_order_details',
			__( 'Order Details', 'bluu-lite-ecommerce' ),
			array( __CLASS__, 'render_order_meta_box' ),
			'lite_order',
			'normal',
			'high'
		);
	}

	/**
	 * Render the order meta box.
	 */
	public static function render_order_meta_box( $post ) {
		$customer = get_post_meta( $post->ID, '_lite_customer_details', true );
		$items    = get_post_meta( $post->ID, '_lite_items', true );
		$subtotal = get_post_meta( $post->ID, '_lite_subtotal', true );
		$vat      = get_post_meta( $post->ID, '_lite_vat', true );
		$shipping = get_post_meta( $post->ID, '_lite_shipping', true );
		$total    = get_post_meta( $post->ID, '_lite_total', true );
		$gateway  = get_post_meta( $post->ID, '_lite_gateway', true );
		$status   = get_post_meta( $post->ID, '_lite_payment_status', true );
		$shipping_details = get_post_meta( $post->ID, '_lite_shipping_details', true );

		?>
		<div class="lite-order-details-admin">
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px;">
				<div>
					<h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;"><?php esc_html_e( 'Customer Billing Info', 'bluu-lite-ecommerce' ); ?></h3>
					<p>
						<strong><?php esc_html_e( 'Name:', 'bluu-lite-ecommerce' ); ?></strong> <?php echo esc_html( $customer['first_name'] . ' ' . $customer['last_name'] ); ?><br>
						<strong><?php esc_html_e( 'Email:', 'bluu-lite-ecommerce' ); ?></strong> <?php echo esc_html( $customer['email'] ); ?><br>
						<strong><?php esc_html_e( 'Phone:', 'bluu-lite-ecommerce' ); ?></strong> <?php echo esc_html( $customer['phone'] ); ?><br>
						<strong><?php esc_html_e( 'Address:', 'bluu-lite-ecommerce' ); ?></strong> <?php echo esc_html( $customer['address'] ); ?>, <?php echo esc_html( $customer['city'] ); ?>, <?php echo esc_html( $customer['country'] ); ?>
					</p>
				</div>
				<div>
					<h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;"><?php esc_html_e( 'Shipping Details', 'bluu-lite-ecommerce' ); ?></h3>
					<?php if ( ! empty( $shipping_details ) ) : ?>
						<p>
							<strong><?php esc_html_e( 'Name:', 'bluu-lite-ecommerce' ); ?></strong> <?php echo esc_html( $shipping_details['first_name'] . ' ' . $shipping_details['last_name'] ); ?><br>
							<strong><?php esc_html_e( 'Address:', 'bluu-lite-ecommerce' ); ?></strong> <?php echo esc_html( $shipping_details['address'] ); ?>, <?php echo esc_html( $shipping_details['city'] ); ?>, <?php echo esc_html( $shipping_details['country'] ); ?>
						</p>
					<?php else : ?>
						<p><em><?php esc_html_e( 'Same as billing address.', 'bluu-lite-ecommerce' ); ?></em></p>
					<?php endif; ?>
					<p>
						<strong><?php esc_html_e( 'Payment Status:', 'bluu-lite-ecommerce' ); ?></strong> 
						<span style="display:inline-block; padding: 4px 10px; border-radius: 4px; background: <?php echo 'completed' === $status ? '#dcfce7' : '#fef9c3'; ?>; color: <?php echo 'completed' === $status ? '#166534' : '#854d0e'; ?>; font-weight: 600; text-transform: uppercase; font-size: 11px;">
							<?php echo esc_html( $status ); ?>
						</span>
						<br>
						<strong><?php esc_html_e( 'Gateway:', 'bluu-lite-ecommerce' ); ?></strong> <?php echo esc_html( ucfirst( $gateway ) ); ?>
					</p>
				</div>
			</div>

			<h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;"><?php esc_html_e( 'Order Items', 'bluu-lite-ecommerce' ); ?></h3>
			<table class="wp-list-table widefat fixed striped" style="border: none; box-shadow: none;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'bluu-lite-ecommerce' ); ?></th>
						<th><?php esc_html_e( 'Price', 'bluu-lite-ecommerce' ); ?></th>
						<th><?php esc_html_e( 'Quantity', 'bluu-lite-ecommerce' ); ?></th>
						<th style="text-align: right;"><?php esc_html_e( 'Total', 'bluu-lite-ecommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $items as $item ) : 
						$product = get_post( $item['product_id'] );
						$price   = floatval( get_post_meta( $item['product_id'], '_lite_price', true ) );
					?>
					<tr>
						<td>
							<strong><?php echo $product ? esc_html( $product->post_title ) : 'Deleted Product'; ?></strong>
							<?php
							// Borrow formatter from checkout class if available
							if ( class_exists( 'Lite_eCommerce_Checkout' ) ) {
								$options_display = Lite_eCommerce_Checkout::format_item_options( $item );
								if ( $options_display ) {
									echo '<div style="font-size: 11px; color: #646970; margin-top: 4px;">' . $options_display . '</div>'; // phpcs:ignore
								}
							}
							?>
						</td>
						<td><?php echo '£' . number_format( $price, 2 ); ?></td>
						<td><?php echo intval( $item['quantity'] ); ?></td>
						<td style="text-align: right;"><?php echo '£' . number_format( $price * $item['quantity'], 2 ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div style="margin-top: 20px; text-align: right; padding-right: 12px; font-size: 16px; line-height: 1.6;">
				<p>
					<?php esc_html_e( 'Subtotal:', 'bluu-lite-ecommerce' ); ?> £<?php echo number_format( $subtotal, 2 ); ?><br>
					<?php if ( $vat > 0 ) : ?>
						<?php esc_html_e( 'VAT:', 'bluu-lite-ecommerce' ); ?> £<?php echo number_format( $vat, 2 ); ?><br>
					<?php endif; ?>
					<?php if ( $shipping > 0 ) : ?>
						<?php esc_html_e( 'Shipping:', 'bluu-lite-ecommerce' ); ?> £<?php echo number_format( $shipping, 2 ); ?><br>
					<?php endif; ?>
					<strong style="font-size: 20px;"><?php esc_html_e( 'Grand Total:', 'bluu-lite-ecommerce' ); ?> £<?php echo number_format( $total, 2 ); ?></strong>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Create a new order.
	 *
	 * @param array $data Order data.
	 * @return int Order ID.
	 */
	public static function create_order( $data ) {
		$order_id = wp_insert_post( array(
			'post_title'  => 'Order #' . time(),
			'post_type'   => 'lite_order',
			'post_status' => 'publish',
		) );

		if ( $order_id ) {
			update_post_meta( $order_id, '_lite_customer_details', $data['customer'] );
			update_post_meta( $order_id, '_lite_items', $data['items'] );
			update_post_meta( $order_id, '_lite_subtotal', $data['subtotal'] );
			update_post_meta( $order_id, '_lite_vat', $data['vat'] );
			update_post_meta( $order_id, '_lite_shipping', $data['shipping'] );
			update_post_meta( $order_id, '_lite_total', $data['total'] );
			update_post_meta( $order_id, '_lite_gateway', $data['gateway'] );
			update_post_meta( $order_id, '_lite_shipping_details', $data['shipping_details'] );
			update_post_meta( $order_id, '_lite_payment_status', 'pending' );
			
			// Update title with proper ID
			wp_update_post( array(
				'ID'         => $order_id,
				'post_title' => 'Order #' . $order_id,
			) );
		}

		return $order_id;
	}

	/**
	 * Update order status.
	 */
	public static function update_status( $order_id, $status ) {
		update_post_meta( $order_id, '_lite_payment_status', $status );
		do_action( 'lite_ecommerce_order_status_' . $status, $order_id );
	}
}
