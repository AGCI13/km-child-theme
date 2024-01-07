<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_filter( 'wp_ajax_filter_archive_products', 'km_filter_archive_products' );
add_filter( 'wp_ajax_nopriv_filter_archive_products', 'km_filter_archive_products' );

function km_filter_archive_products() {

	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	$nonce_value = isset( $_POST['km_product_filters_nonce'] ) && ! empty( $_POST['km_product_filters_nonce'] ) ? wp_unslash( $_POST['km_product_filters_nonce'] ) : '';
	$nonce_value = sanitize_text_field( $nonce_value );

	if ( ! wp_verify_nonce( $nonce_value, 'filter_archive_products' ) ) {
		wp_send_json_error( array( 'message' => __( 'La vérification du nonce a échoué.' ) ) );
	}

	if ( isset( $_POST['product_filter_category'] ) && ! empty( $_POST['product_filter_category'] ) ) {
		$term_slug = sanitize_text_field( $_POST['product_filter_category'] );
	} else {
		wp_send_json_error( array( 'message' => __( 'Terme de catégorie invalide ou manquant.' ) ) );
	}

	if ( isset( $_POST['product_filter_uses'] ) && ! empty( $_POST['product_filter_uses'] ) ) {
		$raw_product_filter_uses = wp_unslash( $_POST['product_filter_uses'] );
		$sanitized_uses_array    = array();

		if ( is_array( $raw_product_filter_uses ) ) {

			foreach ( $raw_product_filter_uses  as $use ) {
				$sanitized_uses_array[] = sanitize_text_field( $use );
			}
		} else {
			$sanitized_uses_array[] = sanitize_text_field( $raw_product_filter_uses );
		}
	}

	if ( isset( $_POST['product_filter_colors'] ) && ! empty( $_POST['product_filter_colors'] ) ) {
		$raw_product_filter_colors = wp_unslash( $_POST['product_filter_colors'] );
		$sanitized_colors_array    = array();

		if ( is_array( $raw_product_filter_colors ) ) {

			foreach ( $raw_product_filter_colors  as $color ) {
				$sanitized_colors_array[] = sanitize_text_field( $color );
			}
		} else {
			$sanitized_colors_array[] = sanitize_text_field( $raw_product_filter_colors );
		}
	}
	if ( isset( $_POST['filter_price_range_max'] ) && ! empty( $_POST['filter_price_range_max'] ) ) {

		if ( ! is_numeric( $_POST['filter_price_range_max'] ) ) {
			wp_send_json_error( array( 'message' => __( 'La valeur du prix maximum est invalide.' ) ) );
		}
		$max_price = intval( $_POST['filter_price_range_max'] );
	}

	if ( isset( $_POST['filter_price_range_min'] ) && ! empty( $_POST['filter_price_range_min'] ) ) {

		if ( ! is_numeric( $_POST['filter_price_range_min'] ) ) {
			wp_send_json_error( array( 'message' => __( 'La valeur du prix minimum est invalide.' ) ) );
		}
		$min_price = intval( $_POST['filter_price_range_min'] );
	}

	$tax_query = array( 'relation' => 'AND' );

	if ( ! empty( $sanitized_uses_array ) ) {
		$tax_query[] = array(
			'taxonomy' => 'uses',
			'field'    => 'slug',
			'terms'    => $sanitized_uses_array,
		);
	}

	if ( ! empty( $sanitized_colors_array ) ) {
		$tax_query[] = array(
			'taxonomy' => 'colors',
			'field'    => 'slug',
			'terms'    => $sanitized_colors_array,
		);
	}

	$products = wc_get_products(
		array(
			'status'    => 'publish',
			'limit'     => -1,
			'category'  => array( $term_slug ),
			'return'    => 'ids',
			'tax_query' => $tax_query,
		)
	);

	$matching_product_ids = array();

	foreach ( $products as $product_id ) {

		$product = wc_get_product( $product_id );

		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_available_variations() as $variation ) {
				$variation_id      = $variation['variation_id'];
				$variation_product = wc_get_product( $variation_id );

				$variation_price = $variation_product->get_price();

				if ( $variation_price >= $min_price && $variation_price <= $max_price ) {
					$matching_product_ids[] = $product_id;
					break;
				}
			}
		} else {
			$product_price = $product->get_price();
			if ( $product_price >= $min_price && $product_price <= $max_price ) {
				$matching_product_ids[] = $product_id;
			}
		}
	}

	$found_results_count = count( array_unique( $matching_product_ids ) );

	if ( ! $matching_product_ids ) {
		wp_send_json_success(
			array(
				'found_results_count' => $found_results_count,
				'found_results_html'  => esc_html__( 'Aucun produit ne correspond à votre filtrage.', 'kingmateriaux' ),
				'content_html'        => '',
			)
		);
	}

	$html              = '';
	$filtered_products = wc_get_products( array( 'include' => $matching_product_ids ) );

	foreach ( $filtered_products as $product ) {
		$product_id    = $product->get_id();
		$image_id      = $product->get_image_id();
		$product_title = $product->get_name();

		if ( $image_id ) {
			$srcset        = wp_get_attachment_image_srcset( $image_id, 'medium' ); // 'full' peut être remplacé par une autre taille d'image si nécessaire
			$src           = wp_get_attachment_image_url( $image_id, 'medium' );
			$product_image = '<img src="' . esc_url( $src ) . '" srcset="' . esc_attr( $srcset ) . '" alt="' . esc_attr( $product_title ) . '">';
		}

		$html .= '<li class="product type-product status-publish instock product_cat-agregats product_cat-' . esc_html( $term_slug ) .
		'has-post-thumbnail taxable shipping-taxable purchasable product-type-variable">';
		$html .= '<a href="' . esc_html( $product->get_permalink() ) . '" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">';
		$html .= $product_image;
		$html .= '<h2 class="woocommerce-loop-product__title">' . esc_html( $product_title ) . '</h2>';
		$html .= '<span class="price">À partir de <span class="woocommerce-Price-amount amount"><bdi>' . esc_html( $product->get_price() ) . '&nbsp;<span class="woocommerce-Price-currencySymbol">€</span></bdi></span></span>';
		$html .= '</a>';
		$html .= '</li>';
	}
	wp_send_json_success(
		array(
			'found_results_count' => $found_results_count,
			'found_results_html'  => sprintf(
				_n(
					'%d produit correspond à votre filtrage',
					'%d produits correspondent à votre filtrage',
					$found_results_count
				),
				$found_results_count
			),
			'content_html'        => $html,
		)
	);
}

/**
 * Enqueue assets for product archive
 *
 * @return void
 */
function km_archive_product_assets() {
	$category = get_queried_object();

	// Get the category's parent, if it exists, otherwise use the current category.
	$category_name = get_term_by( 'id', $category->parent ?: $category->term_id, 'product_cat' )->name;

	// Set the category name to lowercase.
	$category_name = strtolower( $category_name );

	// Get the first letter of the category name.
	$first_letter = substr( $category_name, 0, 1 );

	// Check if the first letter is a vowel.
	$cat_vowel = in_array( $first_letter, array( 'a', 'e', 'i', 'o', 'u' ) );

	// Enqueue the styles and scripts.
	wp_enqueue_script( 'archive-product-script' );
	wp_enqueue_style( 'archive-product-style' );

	// Output the category variables in hidden inputs.
	echo '<input type="hidden" value="' . esc_html( $category_name ) . '" id="cat_parent_name">';
	echo '<input type="hidden" value="' . esc_html( $category->name ) . '" id="cat_name">';
	echo '<input type="hidden" value="' . esc_html( $cat_vowel ) . '" id="cat_vowel">';
}
add_shortcode( 'archive_product_assets', 'km_archive_product_assets' );

/**
 * display Out of stock if product simple is OOS or if every variations of a variable product are OOS
 *
 * @return void
 */
function km_woocommerce_template_loop_price() {
	global $product;

	if ( ! $product->is_purchasable() ) {
		return;
	}
	// Retirer l'action qui affiche le prix par défaut et ajouter la vôtre.
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );

	// Pour les produits variables.
	if ( $product->is_type( 'variable' ) ) {
		$available = false;

		foreach ( $product->get_available_variations() as $variation ) {
			$variation_obj = new WC_Product_Variation( $variation['variation_id'] );
			if ( $variation_obj->is_in_stock() ) {
				$available = true;
				break;
			}
		}

		if ( ! $available ) {
			echo '<span class="price out-of-stock">' . __( 'Rupture de stock', 'woocommerce' ) . '</span>';
			return;
		}
	}

	// Pour les produits simples.
	if ( $product->is_type( 'simple' ) && ! $product->is_in_stock() ) {
		echo '<span class="price out-of-stock">' . __( 'Rupture de stock', 'woocommerce' ) . '</span>';
		return;
	}

	// Affiche le prix si le produit est en stock.
	wc_get_template( 'loop/price.php' );
}
add_action( 'woocommerce_after_shop_loop_item_title', 'km_woocommerce_template_loop_price', 10 );
