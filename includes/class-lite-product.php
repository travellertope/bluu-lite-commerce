<?php
/**
 * Lite eCommerce Product Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lite_eCommerce_Product {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_lite_product', array( __CLASS__, 'save_meta_box_data' ) );
		add_action( 'admin_footer', array( __CLASS__, 'render_options_admin_scripts' ) );
		add_shortcode( 'lite_add_to_cart', array( __CLASS__, 'add_to_cart_shortcode' ) );
		add_filter( 'template_include', array( __CLASS__, 'load_product_template' ), 9999 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_product_styles' ) );
	}

	/**
	 * Load custom single-product template.
	 */
	public static function load_product_template( $template ) {
		if ( is_singular( 'lite_product' ) ) {
			// Allow theme override: yourtheme/lite-ecommerce/single-lite_product.php
			$theme_template = locate_template( 'lite-ecommerce/single-lite_product.php' );
			if ( $theme_template ) {
				return $theme_template;
			}
			// Plugin built-in template (reliable absolute path)
			$plugin_template = dirname( dirname( __FILE__ ) ) . '/templates/single-lite_product.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}
		return $template;
	}

	/**
	 * Enqueue Google Fonts on product page.
	 */
	public static function enqueue_product_styles() {
		if ( is_singular( 'lite_product' ) ) {
			wp_enqueue_style(
				'lite-inter-font',
				'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
				array(),
				'1.0.2'
			);
		}
	}

	/**
	 * Register the lite_product custom post type.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => _x( 'Products', 'post type general name', 'bluu-lite-ecommerce' ),
			'singular_name'      => _x( 'Product', 'post type singular name', 'bluu-lite-ecommerce' ),
			'menu_name'          => _x( 'Products', 'admin menu', 'bluu-lite-ecommerce' ),
			'name_admin_bar'     => _x( 'Product', 'add new on admin bar', 'bluu-lite-ecommerce' ),
			'add_new'            => _x( 'Add New', 'product', 'bluu-lite-ecommerce' ),
			'add_new_item'       => __( 'Add New Product', 'bluu-lite-ecommerce' ),
			'new_item'           => __( 'New Product', 'bluu-lite-ecommerce' ),
			'edit_item'          => __( 'Edit Product', 'bluu-lite-ecommerce' ),
			'view_item'          => __( 'View Product', 'bluu-lite-ecommerce' ),
			'all_items'          => __( 'All Products', 'bluu-lite-ecommerce' ),
			'search_items'       => __( 'Search Products', 'bluu-lite-ecommerce' ),
			'not_found'          => __( 'No products found.', 'bluu-lite-ecommerce' ),
			'not_found_in_trash' => __( 'No products found in Trash.', 'bluu-lite-ecommerce' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'bluu-lite-ecommerce',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'lite-product' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'menu_icon'          => 'dashicons-cart',
		);

		register_post_type( 'lite_product', $args );
	}

	/**
	 * Add meta boxes.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'lite_product_details',
			__( 'Product Details', 'bluu-lite-ecommerce' ),
			array( __CLASS__, 'render_meta_box' ),
			'lite_product',
			'side',
			'default'
		);

		add_meta_box(
			'lite_product_options',
			__( 'Product Options (Size, Colour, etc.)', 'bluu-lite-ecommerce' ),
			array( __CLASS__, 'render_options_meta_box' ),
			'lite_product',
			'normal',
			'default'
		);
	}

	/**
	 * Render the product details meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'lite_product_save_meta_box_data', 'lite_product_meta_box_nonce' );

		$price = get_post_meta( $post->ID, '_lite_price', true );

		echo '<p><label for="lite_product_price"><strong>';
		esc_html_e( 'Price (£):', 'bluu-lite-ecommerce' );
		echo '</strong></label><br>';
		echo '<input type="number" id="lite_product_price" name="lite_product_price" value="' . esc_attr( $price ) . '" step="0.01" style="width: 100%;" /></p>';

		if ( $post->post_status !== 'auto-draft' ) {
			$direct_link = home_url( '/?lite_action=direct_add_to_cart&product_id=' . $post->ID );
			echo '<p style="margin-top: 20px;"><strong>' . esc_html__( 'Direct Checkout Link:', 'bluu-lite-ecommerce' ) . '</strong><br>';
			echo '<input type="text" readonly value="' . esc_url( $direct_link ) . '" style="width: 100%; padding: 5px; background: #f0f0f1; border: 1px solid #ccd0d4;" onclick="this.select();" />';
			echo '<br><span class="description" style="font-size: 12px;">' . esc_html__( 'Copy this link for your buttons/emails to send customers instantly to checkout.', 'bluu-lite-ecommerce' ) . '</span></p>';
		}
	}

	/**
	 * Render the product options meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public static function render_options_meta_box( $post ) {
		$options = self::get_product_options( $post->ID );
		?>
		<style>
			.lite-options-wrap { margin: 0; }
			.lite-option-row {
				background: #f9f9f9;
				border: 1px solid #ddd;
				border-radius: 6px;
				padding: 16px;
				margin-bottom: 12px;
				display: grid;
				grid-template-columns: 1fr 140px 1fr 40px;
				gap: 12px;
				align-items: end;
			}
			.lite-option-row label {
				display: block;
				font-size: 12px;
				font-weight: 600;
				color: #555;
				margin-bottom: 4px;
			}
			.lite-option-row input,
			.lite-option-row select {
				width: 100%;
				padding: 8px 10px;
				border: 1px solid #ccc;
				border-radius: 4px;
			}
			.lite-option-remove {
				background: #fee2e2;
				color: #dc2626;
				border: none;
				border-radius: 4px;
				padding: 8px;
				cursor: pointer;
				font-size: 18px;
				line-height: 1;
				height: 36px;
			}
			.lite-option-remove:hover { background: #fecaca; }
			.lite-option-values-field { display: none; }
			.lite-option-row[data-type="select"] .lite-option-values-field { display: block; }
			.lite-options-empty {
				text-align: center;
				padding: 24px;
				color: #888;
				font-style: italic;
			}
			#lite-add-option {
				margin-top: 8px;
			}
		</style>

		<div class="lite-options-wrap">
			<div id="lite-options-list">
				<?php if ( empty( $options ) ) : ?>
					<div class="lite-options-empty" id="lite-options-empty">
						<?php esc_html_e( 'No custom options yet. Click "Add Option" to create one.', 'bluu-lite-ecommerce' ); ?>
					</div>
				<?php else : ?>
					<?php foreach ( $options as $i => $opt ) : ?>
						<div class="lite-option-row" data-type="<?php echo esc_attr( $opt['type'] ); ?>">
							<div>
								<label><?php esc_html_e( 'Label', 'bluu-lite-ecommerce' ); ?></label>
								<input type="text" name="lite_options[<?php echo intval( $i ); ?>][label]" value="<?php echo esc_attr( $opt['label'] ); ?>" placeholder="e.g. Size" required>
							</div>
							<div>
								<label><?php esc_html_e( 'Type', 'bluu-lite-ecommerce' ); ?></label>
								<select name="lite_options[<?php echo intval( $i ); ?>][type]" class="lite-option-type-select">
									<option value="select" <?php selected( $opt['type'], 'select' ); ?>><?php esc_html_e( 'Dropdown', 'bluu-lite-ecommerce' ); ?></option>
									<option value="text" <?php selected( $opt['type'], 'text' ); ?>><?php esc_html_e( 'Text Input', 'bluu-lite-ecommerce' ); ?></option>
								</select>
							</div>
							<div class="lite-option-values-field">
								<label><?php esc_html_e( 'Values (comma-separated)', 'bluu-lite-ecommerce' ); ?></label>
								<input type="text" name="lite_options[<?php echo intval( $i ); ?>][values]" value="<?php echo esc_attr( implode( ', ', $opt['values'] ) ); ?>" placeholder="Small, Medium, Large, XL">
							</div>
							<button type="button" class="lite-option-remove" title="<?php esc_attr_e( 'Remove', 'bluu-lite-ecommerce' ); ?>">&times;</button>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<button type="button" class="button button-secondary" id="lite-add-option">
				<?php esc_html_e( '+ Add Option', 'bluu-lite-ecommerce' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Render admin scripts for the options repeater.
	 */
	public static function render_options_admin_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || 'lite_product' !== $screen->post_type ) {
			return;
		}
		?>
		<script>
		(function() {
			var list = document.getElementById('lite-options-list');
			var addBtn = document.getElementById('lite-add-option');
			if (!list || !addBtn) return;

			var counter = list.querySelectorAll('.lite-option-row').length;

			addBtn.addEventListener('click', function() {
				var empty = document.getElementById('lite-options-empty');
				if (empty) empty.remove();

				var row = document.createElement('div');
				row.className = 'lite-option-row';
				row.setAttribute('data-type', 'select');
				row.innerHTML = '<div>' +
					'<label><?php echo esc_js( __( 'Label', 'bluu-lite-ecommerce' ) ); ?></label>' +
					'<input type="text" name="lite_options[' + counter + '][label]" placeholder="e.g. Size" required>' +
				'</div>' +
				'<div>' +
					'<label><?php echo esc_js( __( 'Type', 'bluu-lite-ecommerce' ) ); ?></label>' +
					'<select name="lite_options[' + counter + '][type]" class="lite-option-type-select">' +
						'<option value="select"><?php echo esc_js( __( 'Dropdown', 'bluu-lite-ecommerce' ) ); ?></option>' +
						'<option value="text"><?php echo esc_js( __( 'Text Input', 'bluu-lite-ecommerce' ) ); ?></option>' +
					'</select>' +
				'</div>' +
				'<div class="lite-option-values-field">' +
					'<label><?php echo esc_js( __( 'Values (comma-separated)', 'bluu-lite-ecommerce' ) ); ?></label>' +
					'<input type="text" name="lite_options[' + counter + '][values]" placeholder="Small, Medium, Large, XL">' +
				'</div>' +
				'<button type="button" class="lite-option-remove" title="<?php echo esc_js( __( 'Remove', 'bluu-lite-ecommerce' ) ); ?>">&times;</button>';
				list.appendChild(row);
				counter++;
			});

			// Delegate: remove button
			list.addEventListener('click', function(e) {
				if (e.target.classList.contains('lite-option-remove')) {
					e.target.closest('.lite-option-row').remove();
				}
			});

			// Delegate: type change
			list.addEventListener('change', function(e) {
				if (e.target.classList.contains('lite-option-type-select')) {
					e.target.closest('.lite-option-row').setAttribute('data-type', e.target.value);
				}
			});
		})();
		</script>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id The post ID.
	 */
	public static function save_meta_box_data( $post_id ) {
		if ( ! isset( $_POST['lite_product_meta_box_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lite_product_meta_box_nonce'] ) ), 'lite_product_save_meta_box_data' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['lite_product_price'] ) ) {
			$price = sanitize_text_field( wp_unslash( $_POST['lite_product_price'] ) );
			$price = floatval( $price );
			update_post_meta( $post_id, '_lite_price', $price );
		}

		// Save product options
		$options = array();
		if ( isset( $_POST['lite_options'] ) && is_array( $_POST['lite_options'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_options = wp_unslash( $_POST['lite_options'] );
			foreach ( $raw_options as $opt ) {
				$label = isset( $opt['label'] ) ? sanitize_text_field( $opt['label'] ) : '';
				$type  = isset( $opt['type'] ) && in_array( $opt['type'], array( 'select', 'text' ), true ) ? $opt['type'] : 'select';

				if ( empty( $label ) ) {
					continue;
				}

				$values = array();
				if ( 'select' === $type && ! empty( $opt['values'] ) ) {
					$values = array_map( 'trim', explode( ',', sanitize_text_field( $opt['values'] ) ) );
					$values = array_filter( $values );
				}

				$options[] = array(
					'id'     => 'opt_' . sanitize_title( $label ),
					'label'  => $label,
					'type'   => $type,
					'values' => array_values( $values ),
				);
			}
		}
		update_post_meta( $post_id, '_lite_product_options', $options );
	}

	/**
	 * Get product options for a given product.
	 *
	 * @param int $product_id Product ID.
	 * @return array Array of option definitions.
	 */
	public static function get_product_options( $product_id ) {
		$options = get_post_meta( $product_id, '_lite_product_options', true );
		if ( ! is_array( $options ) ) {
			return array();
		}
		return $options;
	}

	/**
	 * Render the add to cart shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function add_to_cart_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'lite_add_to_cart'
		);

		$product_id = intval( $atts['id'] );

		// If no ID is provided, try to get the current post ID
		if ( ! $product_id ) {
			$product_id = get_the_ID();
		}

		if ( ! $product_id || get_post_type( $product_id ) !== 'lite_product' ) {
			return '';
		}

		$price = get_post_meta( $product_id, '_lite_price', true );
		if ( ! $price ) {
			$price = 0;
		}

		ob_start();
		?>
		<form method="post" action="">
			<input type="hidden" name="lite_action" value="add_to_cart">
			<input type="hidden" name="lite_product_id" value="<?php echo esc_attr( $product_id ); ?>">
			<?php wp_nonce_field( 'lite_add_to_cart_' . $product_id, 'lite_add_to_cart_nonce' ); ?>
			<button type="submit" class="lite-add-to-cart-button">
				<?php 
				/* translators: %s: product price */
				printf( esc_html__( 'Add to Cart - £%s', 'bluu-lite-ecommerce' ), number_format( $price, 2 ) ); 
				?>
			</button>
		</form>
		<?php
		return ob_get_clean();
	}
}
