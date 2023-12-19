<?php
class Product_filters_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'km_product_filters';
	}

	public function get_title() {
		return esc_html__( 'KM Filtres à facettes produits', 'elementor-addon' );
	}

	public function get_icon() {
		return 'eicon-taxonomy-filter';
	}

	public function get_categories() {
		return array( 'kingmateriaux', 'woocommerce' );
	}

	protected function render() {
		wp_enqueue_style( 'km-product-filters-style' );
		wp_enqueue_script( 'km-product-filters-script' );

		global $wpdb;

		$term = get_queried_object();

		if ( $term && isset( $term->taxonomy ) && 'product_cat' === $term->taxonomy ) {
			$sql = "
					SELECT MIN(meta_value + 0) as min_price, MAX(meta_value + 0) as max_price
					FROM {$wpdb->postmeta}
					JOIN {$wpdb->posts} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
					JOIN {$wpdb->term_relationships} ON ({$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id)
					JOIN {$wpdb->term_taxonomy} ON ({$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id)
					JOIN {$wpdb->terms} ON ({$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id)
					WHERE meta_key = '_price'
					AND meta_value != ''
					AND {$wpdb->term_taxonomy}.taxonomy = 'product_cat'
					AND {$wpdb->terms}.slug = %s
				";

			$query  = $wpdb->prepare( $sql, $term->slug );
			$result = $wpdb->get_row( $query );

			$min_price = $result->min_price ? (int) $result->min_price : 0;
			$max_price = $result->max_price ? (int) $result->max_price : null;
		}

		$category_term_id   = $term->term_id ?? 0;
		$category_term_slug = $term->slug ?? '';

		$terms_uses = get_terms(
			array(
				'taxonomy'   => 'uses',
				'hide_empty' => true,
				'object_ids' => get_objects_in_term( $category_term_id, 'product_cat' ),
			)
		);

		$terms_colors = get_terms(
			array(
				'taxonomy'   => 'colors',
				'hide_empty' => true,
				'object_ids' => get_objects_in_term( $category_term_id, 'product_cat' ),
			)
		);
		?>
		<div class="sliding-bar-off-canvas"></div>
		<div class="km-product-filters_sliding-bar sliding-bar-right">
		<div class="km-product-filters_close">
    		<span class="close-icon">X</span>
		</div>
		<h4 class="sliding-bar_title"><?php esc_html_e( 'Filtrer par :', 'kingmateriaux' ); ?></h4>
			<form class="km-product-filters__form">
				<?php if ( $terms_uses ) : ?>
					<h4><?php esc_html_e( 'Utilisation', 'kingmateriaux' ); ?></h4>

					<div class="km-product-filters__wrapper product-filters-use">
						<?php foreach ( $terms_uses as $term ) : ?>
							<?php $term_image = wp_get_attachment_image_url( get_term_meta( $term->term_id, 'image_id', true ) ); ?>
							<div class="km-product-filters__item">
								<input type="checkbox" name="product_filter_uses[]" id="km-product-filters__utilisation-<?php echo esc_attr( $term->slug ); ?>" value="<?php echo esc_attr( $term->slug ); ?>">
								<label style="background-image:url('<?php echo esc_url( $term_image ); ?>')" for="km-product-filters__utilisation-<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?><span class="fa fa-check"></span></label>
							</div>
						<?php endforeach; ?>	
					</div>
				<?php endif; ?>

				<?php if ( $terms_colors ) : ?>
					<h4><?php esc_html_e( 'Couleurs', 'kingmateriaux' ); ?></h4>

					<div class="km-product-filters__wrapper product-filters-color">
						<?php foreach ( $terms_colors as $term ) : ?>
							<?php $term_color = get_term_meta( $term->term_id, 'color', true ); ?>
							<div class="km-product-filters__item">
							<label for="km-product-filters__color-<?php echo esc_attr( $term->slug ); ?>"><span class="checkmark" style="background-color:<?php echo esc_html( $term_color ); ?>"></span>
							<input type="checkbox" name="product_filter_colors[]" id="km-product-filters__color-<?php echo esc_attr( $term->slug ); ?>" value="<?php echo esc_attr( $term->slug ); ?>">
							<?php echo esc_html( $term->name ); ?></label>
							</div>
						<?php endforeach; ?>	
					</div>
				<?php endif; ?>
				
				<?php if ( $max_price ) : ?>
					<div class="km-product-filters__wrapper product-filters-price-range">
						<h5><?php esc_html_e( 'Prix', 'kingmateriaux' ); ?></h5>
						
						<div class="sliders_control">
							<input id="fromSlider" type="range" value="<?php echo esc_html( $min_price ); ?>" min="<?php echo esc_html( $min_price ); ?>" max="<?php echo esc_html( $max_price ); ?>"/>
							<input id="toSlider" type="range" value="<?php echo esc_html( $max_price ); ?>" min="<?php echo esc_html( $min_price ); ?>" max="<?php echo esc_html( $max_price ); ?>"/>
						</div>

						<div class="form_control">
							<div class="form_control_container">
								<?php printf( __( 'De %1s €', 'kingmateriaux' ), '<span class="min-price" id="fromDisplay">' . esc_html( $min_price ) . '</span>' ); ?>
								<input class="form_control_container__time__input" name="filter_price_range_min" type="hidden" id="fromInput" value="<?php echo esc_html( $min_price ); ?>" min="<?php echo esc_html( $min_price ); ?>" max="<?php echo esc_html( $max_price ); ?>"/>
							</div>
							<div class="form_control_container">
							<?php printf( __( 'à %1s €', 'kingmateriaux' ), '<span class="max-price" id="toDisplay">' . esc_html( $max_price ) . '</span>' ); ?>
								<input class="form_control_container__time__input" name="filter_price_range_max" type="hidden" id="toInput" value="<?php echo esc_html( $max_price ); ?>" min="<?php echo esc_html( $min_price ); ?>" max="<?php echo esc_html( $max_price ); ?>"/>
							</div>
						</div>
					</div>
				<?php endif; ?>
				
				<input type="hidden" name="product_filter_category" value="<?php echo esc_html( $category_term_slug ); ?>">
				<?php wp_nonce_field( 'filter_archive_products', 'km_product_filters_nonce' ); ?>
							<button type="submit" class="btn-confirm btn btn-primary btn-span">
								<span class="btn-confirm-label"><?php esc_html_e( 'Filtrer', 'kingmateriaux' ); ?></span>
								<span class="btn-confirm-loader"></span>
							</button>
				<input type="reset" class="btn btn-link btn-span" value="<?php esc_html_e( 'Réinitialiser', 'kingmateriaux' ); ?>">
			</form>	
		</div>
		<?php
	}
}
