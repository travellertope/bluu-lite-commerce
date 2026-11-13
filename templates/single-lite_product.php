<?php
/**
 * Single Product Template – Bluu Lite eCommerce
 * Override by copying to yourtheme/lite-ecommerce/single-lite_product.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$lite_product_id    = get_the_ID();
	$lite_price         = floatval( get_post_meta( $lite_product_id, '_lite_price', true ) );
	$lite_has_image     = has_post_thumbnail();
	$lite_cart_page_id  = get_option( 'lite_cart_page' );
	$lite_cart_url      = $lite_cart_page_id ? get_permalink( $lite_cart_page_id ) : home_url( '/cart/' );
	$lite_product_options = Lite_eCommerce_Product::get_product_options( $lite_product_id );
	?>

	<style>
		/* ── Reset & base ─────────────────────────────── */
		*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

		.lite-product-page {
			--color-bg:        #f8f8fb;
			--color-surface:   #ffffff;
			--color-card:      #f2f2f7;
			--color-border:    rgba(0,0,0,.08);
			--color-text:      #1a1a2e;
			--color-muted:     #6b7280;
			--color-accent:    #6c5ce7;
			--color-accent-h:  #5a4bd1;
			--color-green:     #16a34a;
			--radius:          16px;
			--radius-sm:       10px;
			font-family: 'Inter', system-ui, sans-serif;
			background: var(--color-bg);
			color: var(--color-text);
			min-height: 100vh;
			padding: 0 0 80px;
		}

		/* Google Font */
		@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

		/* ── Breadcrumb ───────────────────────────────── */
		.lite-breadcrumb {
			padding: 24px 40px 0;
			font-size: 13px;
			color: var(--color-muted);
		}
		.lite-breadcrumb a { color: var(--color-muted); text-decoration: none; }
		.lite-breadcrumb a:hover { color: var(--color-accent); }
		.lite-breadcrumb span { padding: 0 6px; }

		/* ── Main layout ──────────────────────────────── */
		.lite-product-layout {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 48px;
			max-width: 1100px;
			margin: 40px auto 0;
			padding: 0 40px;
		}
		@media (max-width: 768px) {
			.lite-product-layout { grid-template-columns: 1fr; padding: 0 20px; }
			.lite-breadcrumb { padding: 20px 20px 0; }
		}

		/* ── Image panel ──────────────────────────────── */
		.lite-product-gallery {
			position: relative;
			border-radius: var(--radius);
			overflow: hidden;
			background: var(--color-surface);
			aspect-ratio: 1 / 1;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		.lite-product-gallery img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
			transition: transform .45s ease;
		}
		.lite-product-gallery:hover img { transform: scale(1.03); }
		.lite-product-gallery-placeholder {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 12px;
			color: var(--color-muted);
			font-size: 14px;
		}
		.lite-product-gallery-placeholder svg {
			width: 64px;
			height: 64px;
			opacity: .3;
		}

		/* ── Info panel ───────────────────────────────── */
		.lite-product-info {
			display: flex;
			flex-direction: column;
			justify-content: center;
			gap: 0;
		}

		/* Badge */
		.lite-product-badge {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: rgba(108,92,231,.1);
			color: var(--color-accent);
			border: 1px solid rgba(108,92,231,.25);
			font-size: 11px;
			font-weight: 600;
			letter-spacing: .08em;
			text-transform: uppercase;
			padding: 4px 12px;
			border-radius: 99px;
			width: fit-content;
			margin-bottom: 20px;
		}

		/* Title */
		.lite-product-title {
			font-size: clamp(24px, 3.5vw, 36px);
			font-weight: 700;
			line-height: 1.15;
			letter-spacing: -.02em;
			margin-bottom: 16px;
			color: var(--color-text);
		}

		/* Excerpt / description */
		.lite-product-excerpt {
			font-size: 15px;
			line-height: 1.7;
			color: var(--color-muted);
			margin-bottom: 32px;
		}

		/* Divider */
		.lite-product-divider {
			height: 1px;
			background: var(--color-border);
			margin-bottom: 28px;
		}

		/* Price */
		.lite-product-price-row {
			display: flex;
			align-items: baseline;
			gap: 10px;
			margin-bottom: 30px;
		}
		.lite-product-price {
			font-size: 38px;
			font-weight: 700;
			color: var(--color-text);
			letter-spacing: -.03em;
		}
		.lite-product-price-label {
			font-size: 13px;
			color: var(--color-muted);
		}

		/* Quantity + CTA */
		.lite-product-actions {
			display: flex;
			flex-direction: column;
			gap: 14px;
		}

		.lite-qty-row {
			display: flex;
			align-items: center;
			gap: 0;
			background: var(--color-card);
			border: 1px solid var(--color-border);
			border-radius: var(--radius-sm);
			width: fit-content;
			overflow: hidden;
		}
		.lite-qty-btn {
			width: 44px;
			height: 44px;
			background: none;
			border: none;
			color: var(--color-text);
			font-size: 20px;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			transition: background .2s;
		}
		.lite-qty-btn:hover { background: rgba(255,255,255,.08); }
		.lite-qty-input {
			width: 56px;
			height: 44px;
			background: none;
			border: none;
			border-left: 1px solid var(--color-border);
			border-right: 1px solid var(--color-border);
			color: var(--color-text);
			font-size: 16px;
			font-weight: 600;
			text-align: center;
			-moz-appearance: textfield;
			outline: none;
		}
		.lite-qty-input::-webkit-outer-spin-button,
		.lite-qty-input::-webkit-inner-spin-button { -webkit-appearance: none; }

		/* Buttons */
		.lite-btn-primary {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 10px;
			background: var(--color-accent);
			color: #fff;
			border: none;
			border-radius: var(--radius-sm);
			padding: 16px 32px;
			font-size: 16px;
			font-weight: 600;
			cursor: pointer;
			transition: background .2s, transform .15s, box-shadow .2s;
			width: 100%;
		}
		.lite-btn-primary:hover {
			background: var(--color-accent-h);
			transform: translateY(-1px);
			box-shadow: 0 8px 24px rgba(124,110,245,.35);
		}
		.lite-btn-primary:active { transform: translateY(0); }

		.lite-btn-secondary {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 10px;
			background: var(--color-surface);
			color: var(--color-text);
			border: 1px solid var(--color-border);
			border-radius: var(--radius-sm);
			padding: 14px 32px;
			font-size: 15px;
			font-weight: 500;
			cursor: pointer;
			transition: all .2s;
			width: 100%;
			text-decoration: none;
		}
		.lite-btn-secondary:hover {
			background: rgba(0,0,0,.03);
			border-color: rgba(0,0,0,.15);
			color: var(--color-accent);
		}

		/* Trust badges */
		.lite-trust-badges {
			display: flex;
			gap: 20px;
			margin-top: 28px;
			flex-wrap: wrap;
		}
		.lite-trust-badge {
			display: flex;
			align-items: center;
			gap: 7px;
			font-size: 12px;
			color: var(--color-muted);
		}
		.lite-trust-badge svg { width: 16px; height: 16px; flex-shrink: 0; color: var(--color-green); }

		/* ── Description section ──────────────────────── */
		.lite-product-description-section {
			max-width: 1100px;
			margin: 60px auto 0;
			padding: 0 40px;
		}
		@media (max-width: 768px) {
			.lite-product-description-section { padding: 0 20px; }
		}
		.lite-product-description-section h2 {
			font-size: 22px;
			font-weight: 600;
			margin-bottom: 24px;
			color: var(--color-text);
			padding-bottom: 12px;
			border-bottom: 1px solid var(--color-border);
		}
		.lite-product-description-section .lite-desc-body {
			font-size: 15px;
			line-height: 1.8;
			color: var(--color-muted);
		}

		/* ── Product options ──────────────────────────── */
		.lite-option-group {
			margin-bottom: 18px;
		}
		.lite-option-group label {
			display: block;
			font-size: 13px;
			color: var(--color-muted);
			margin-bottom: 8px;
			font-weight: 500;
		}
		.lite-option-select,
		.lite-option-text {
			width: 100%;
			padding: 12px 14px;
			border: 1px solid var(--color-border);
			border-radius: var(--radius-sm);
			background: var(--color-card);
			color: var(--color-text);
			font-size: 15px;
			font-family: 'Inter', system-ui, sans-serif;
			transition: border-color .2s, box-shadow .2s;
			outline: none;
			-webkit-appearance: none;
			appearance: none;
		}
		.lite-option-select {
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
			background-repeat: no-repeat;
			background-position: right 14px center;
			padding-right: 40px;
		}
		.lite-option-select:focus,
		.lite-option-text:focus {
			border-color: var(--color-accent);
			box-shadow: 0 0 0 3px rgba(108,92,231,.12);
		}

		/* ── Toast notification ───────────────────────── */
		#lite-toast {
			position: fixed;
			bottom: 30px;
			right: 30px;
			background: #ffffff;
			border: 1px solid var(--color-accent);
			color: var(--color-text);
			padding: 12px 18px;
			border-radius: var(--radius-sm);
			font-size: 14px;
			font-weight: 500;
			display: flex;
			align-items: center;
			gap: 12px;
			z-index: 99999;
			transform: translateY(80px);
			opacity: 0;
			transition: all .4s cubic-bezier(.34,1.56,.64,1);
			box-shadow: 0 12px 40px rgba(0,0,0,.15);
		}
		.lite-toast-content {
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.lite-toast-link {
			color: var(--color-accent);
			text-decoration: underline;
			font-weight: 600;
			margin-left: 4px;
		}
		.lite-toast-link:hover {
			color: var(--color-accent-h);
		}
		#lite-toast.show { transform: translateY(0); opacity: 1; }
		#lite-toast svg { width: 18px; height: 18px; color: var(--color-green); flex-shrink: 0; }
	</style>

	<div class="lite-product-page">

		<!-- Breadcrumb -->
		<nav class="lite-breadcrumb">
			<a href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Home', 'bluu-lite-ecommerce' ); ?></a>
			<span>/</span>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'lite_product' ) ); ?>"><?php esc_html_e( 'Products', 'bluu-lite-ecommerce' ); ?></a>
			<span>/</span>
			<?php the_title(); ?>
		</nav>

		<!-- 2-column layout -->
		<div class="lite-product-layout">

			<!-- Left: Image -->
			<div class="lite-product-gallery">
				<?php if ( $lite_has_image ) : ?>
					<?php the_post_thumbnail( 'large' ); ?>
				<?php else : ?>
					<div class="lite-product-gallery-placeholder">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
							<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
							<polyline points="21 15 16 10 5 21"/>
						</svg>
						<span><?php esc_html_e( 'No image available', 'bluu-lite-ecommerce' ); ?></span>
					</div>
				<?php endif; ?>
			</div>

			<!-- Right: Info -->
			<div class="lite-product-info">

				<div class="lite-product-badge">
					<svg width="8" height="8" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4" fill="currentColor"/></svg>
					<?php esc_html_e( 'In Stock', 'bluu-lite-ecommerce' ); ?>
				</div>

				<h1 class="lite-product-title"><?php the_title(); ?></h1>

				<?php if ( has_excerpt() ) : ?>
					<p class="lite-product-excerpt"><?php echo wp_kses_post( get_the_excerpt() ); ?></p>
				<?php endif; ?>

				<div class="lite-product-divider"></div>

				<div class="lite-product-price-row">
					<span class="lite-product-price">£<?php echo number_format( $lite_price, 2 ); ?></span>
					<?php if ( floatval( get_option( 'lite_vat_rate', 0 ) ) > 0 ) : ?>
						<span class="lite-product-price-label"><?php esc_html_e( 'excl. VAT', 'bluu-lite-ecommerce' ); ?></span>
					<?php endif; ?>
				</div>

				<!-- Form: Quantity + Add to Cart -->
				<form class="lite-product-actions" method="post" action="" id="lite-atc-form">
					<input type="hidden" name="lite_action" value="add_to_cart">
					<input type="hidden" name="lite_product_id" value="<?php echo esc_attr( $lite_product_id ); ?>">
					<?php wp_nonce_field( 'lite_add_to_cart_' . $lite_product_id, 'lite_add_to_cart_nonce' ); ?>

					<?php if ( ! empty( $lite_product_options ) ) : ?>
						<?php foreach ( $lite_product_options as $opt ) : ?>
							<div class="lite-option-group">
								<label for="lite-opt-<?php echo esc_attr( $opt['id'] ); ?>">
									<?php echo esc_html( $opt['label'] ); ?>
								</label>
								<?php if ( 'select' === $opt['type'] && ! empty( $opt['values'] ) ) : ?>
									<select
										name="lite_options[<?php echo esc_attr( $opt['id'] ); ?>]"
										id="lite-opt-<?php echo esc_attr( $opt['id'] ); ?>"
										class="lite-option-select"
										required
									>
										<option value=""><?php
											/* translators: %s: option label */
											printf( esc_html__( 'Select %s...', 'bluu-lite-ecommerce' ), esc_html( $opt['label'] ) );
										?></option>
										<?php foreach ( $opt['values'] as $val ) : ?>
											<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $val ); ?></option>
										<?php endforeach; ?>
									</select>
								<?php else : ?>
									<input
										type="text"
										name="lite_options[<?php echo esc_attr( $opt['id'] ); ?>]"
										id="lite-opt-<?php echo esc_attr( $opt['id'] ); ?>"
										class="lite-option-text"
										placeholder="<?php
											/* translators: %s: option label */
											printf( esc_attr__( 'Enter %s...', 'bluu-lite-ecommerce' ), esc_attr( $opt['label'] ) );
										?>"
										required
									>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>

					<div>
						<label style="font-size:13px; color: var(--color-muted); display:block; margin-bottom:8px;"><?php esc_html_e( 'Quantity', 'bluu-lite-ecommerce' ); ?></label>
						<div class="lite-qty-row">
							<button type="button" class="lite-qty-btn" id="lite-qty-minus">−</button>
							<input class="lite-qty-input" type="number" name="lite_quantity" id="lite-qty-value" value="1" min="1" max="999">
							<button type="button" class="lite-qty-btn" id="lite-qty-plus">+</button>
						</div>
					</div>

					<button type="submit" class="lite-btn-primary" id="lite-atc-btn">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
							<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
						</svg>
						<?php esc_html_e( 'Add to Cart', 'bluu-lite-ecommerce' ); ?>
					</button>

					<a href="<?php echo esc_url( home_url( '/?lite_action=direct_add_to_cart&product_id=' . $lite_product_id ) ); ?>" class="lite-btn-secondary">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/>
						</svg>
						<?php esc_html_e( 'Buy Now', 'bluu-lite-ecommerce' ); ?>
					</a>
				</form>

				<!-- Trust badges -->
				<div class="lite-trust-badges">
					<div class="lite-trust-badge">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
						</svg>
						<?php esc_html_e( 'Secure checkout', 'bluu-lite-ecommerce' ); ?>
					</div>
				</div>

			</div><!-- /.lite-product-info -->
		</div><!-- /.lite-product-layout -->

		<!-- Full description -->
		<?php if ( get_the_content() ) : ?>
		<div class="lite-product-description-section">
			<h2><?php esc_html_e( 'Product Details', 'bluu-lite-ecommerce' ); ?></h2>
			<div class="lite-desc-body">
				<?php the_content(); ?>
			</div>
		</div>
		<?php endif; ?>

	</div>

	<!-- Toast notification -->
	<div id="lite-toast">
		<div class="lite-toast-content">
			<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-green);">
				<polyline points="20 6 9 17 4 12"/>
			</svg>
			<span id="lite-toast-msg"><?php esc_html_e( 'Added to cart!', 'bluu-lite-ecommerce' ); ?></span>
		</div>
		<a href="<?php echo esc_url( $lite_cart_url ); ?>" class="lite-toast-link"><?php esc_html_e( 'View Cart', 'bluu-lite-ecommerce' ); ?> &rarr;</a>
	</div>

	<script>
	(function() {
		// Quantity +/- buttons
		var qtyInput = document.getElementById('lite-qty-value');
		document.getElementById('lite-qty-minus').addEventListener('click', function() {
			var v = parseInt(qtyInput.value) || 1;
			if (v > 1) qtyInput.value = v - 1;
		});
		document.getElementById('lite-qty-plus').addEventListener('click', function() {
			var v = parseInt(qtyInput.value) || 1;
			qtyInput.value = v + 1;
		});

		// Add-to-cart feedback toast
		// Actual cart submission (and floating cart update) is handled by the
		// single, site-wide AJAX handler registered in class-lite-cart.php.
		// This just reacts to its result so we don't double-submit the form.
		var form = document.getElementById('lite-atc-form');
		var toast = document.getElementById('lite-toast');
		var toastMsg = document.getElementById('lite-toast-msg');
		var toastTimer;

		function showToast(msg) {
			toast.classList.add('show');
			clearTimeout(toastTimer);
			toastTimer = setTimeout(function() { toast.classList.remove('show'); }, 5000); // Increased to 5s to give time to click
		}

		form.addEventListener('lite:added-to-cart', function() {
			showToast('<?php echo esc_js( __( 'Added to cart!', 'bluu-lite-ecommerce' ) ); ?>');
		});
	})();
	</script>

<?php endwhile; ?>

<?php get_footer(); ?>
