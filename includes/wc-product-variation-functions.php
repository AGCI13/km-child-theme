<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Add custom fields to product variations
add_action( 'woocommerce_variation_options', 'add_custom_fields_to_variations', 10, 3 );

// Save custom fields for product variations
add_action( 'woocommerce_save_product_variation', 'save_custom_fields_variations', 10, 2 );

/**
 * Adds custom fields to product variations.
 */
function add_custom_fields_to_variations( $loop, $variation_data, $variation ) {
	echo '<div class="km-variation-custom-fields">';

	// Eco-taxe.
	$has_ecotax = get_post_meta( $variation->ID, '_has_ecotax', true );
	woocommerce_wp_radio(
		array(
			'id'      => '_has_ecotax[' . $variation->ID . ']',
			'label'   => __( 'La variation de produit a une éco-taxe', 'woocommerce' ),
			'options' => array(
				'undefined' => __( 'Utiliser option produit', 'woocommerce' ),
				'yes'       => __( 'Oui', 'woocommerce' ),
				'no'        => __( 'Non', 'woocommerce' ),
			),
			'value'   => $has_ecotax ? $has_ecotax : 'undefined',
		)
	);

	// Product type.
	$product_type = get_post_meta( $variation->ID, '_product_type', true );
	woocommerce_wp_radio(
		array(
			'id'      => '_product_type[' . $variation->ID . ']',
			'label'   => __( 'Type du produit', 'woocommerce' ),
			'options' => array(
				'undefined'        => __( 'Utiliser option produit', 'woocommerce' ),
				'other'            => __( 'Autre', 'woocommerce' ),
				'big_bag'          => __( 'Big Bag', 'woocommerce' ),
				'big_bag_and_slab' => __( 'Big Bag + dalles', 'woocommerce' ),
				'geotextile'       => __( 'Géotextile', 'woocommerce' ),
			),
			'value'   => $product_type ? $product_type : 'undefined',
		)
	);

	$sales_areas = get_post_meta( $variation->ID, '_product_sales_area', true );
	woocommerce_wp_radio(
		array(
			'id'      => '_product_sales_area[' . $variation->ID . ']',
			'label'   => __( 'Zone de vente du produit', 'woocommerce' ),
			'options' => array(
				'undefined'         => __( 'Utiliser option produit', 'woocommerce' ),
				'all'               => __( 'Partout', 'woocommerce' ),
				'in_thirteen_only'  => __( 'Uniquement dans le 13', 'woocommerce' ),
				'out_thirteen_only' => __( 'Uniquement hors 13', 'woocommerce' ),
				'custom_zones'      => __( 'Choix des zones', 'woocommerce' ),
			),
			'value'   => $sales_areas ? $sales_areas : 'undefined',
		)
	);

	// Custom sales area.
	echo '<fieldset class="form-field _custom_product_shipping_zones[' . $variation->ID . ']"><legend>' . esc_html__( 'Zone de vente personnalisée(s)', 'woocommerce' ) . '</legend>'
	. '<p class="info">' . esc_html__( 'Si une case est cochée, prendra le pas sur les options générales du produit.', 'woocommerce' ) . '</p>'
	. '<div class="options_group">';
	$custom_product_shipping_zones = get_post_meta( $variation->ID, '_custom_product_shipping_zones', true );
	$custom_product_shipping_zones = is_array( $custom_product_shipping_zones ) ? $custom_product_shipping_zones : array();
	$custom_shipping_zones         = array(
		'4'  => __( 'PACA PROCHE', 'woocommerce' ),
		'5'  => __( 'NEW PACA', 'woocommerce' ),
		'6'  => __( 'France', 'woocommerce' ),
		'7'  => __( 'PARIS 1', 'woocommerce' ),
		'8'  => __( 'CORSE', 'woocommerce' ),
		'9'  => __( 'POUTRES 1', 'woocommerce' ),
		'10' => __( 'POUTRES 2', 'woocommerce' ),
		'11' => __( 'ZONE 1', 'woocommerce' ),
		'12' => __( 'ZONE 2', 'woocommerce' ),
		'13' => __( 'ZONE 3', 'woocommerce' ),
		'14' => __( 'ZONE 3', 'woocommerce' ),
		'15' => __( 'ZONE 4', 'woocommerce' ),
		'16' => __( 'ZONE 5', 'woocommerce' ),
		'17' => __( 'ZONE 6', 'woocommerce' ),
		'18' => __( 'ZONE 7', 'woocommerce' ),
	);
	foreach ( $custom_shipping_zones as $key => $label ) {
		woocommerce_wp_checkbox(
			array(
				'id'      => '_custom_product_shipping_zones_' . $key . '[' . $variation->ID . ']',
				'label'   => $label,
				'cbvalue' => $key,
				'value'   => in_array( $key, $custom_product_shipping_zones, true ) ? $key : false,
			)
		);
	}
	echo '</div></fieldset>';

	// Shipping methods.
		echo '<fieldset class="form-field"><legend>' . esc_html__( 'Méthodes d\'expédition', 'woocommerce' ) . '</legend>'
		. '<p class="info">' . esc_html__( 'Si une case est cochée, prendra le pas sur les options générales du produit.', 'woocommerce' ) . '</p>'
		. '<div class="options_group">';
		$shipping_methods_values = get_post_meta( $variation->ID, '_product_shipping_methods', true );
		$shipping_methods_values = is_array( $shipping_methods_values ) ? $shipping_methods_values : array();
		$shipping_methods        = array(
			'drive'          => __( 'Drive', 'woocommerce' ),
			'option1'        => __( 'Option 1', 'woocommerce' ),
			'option2'        => __( 'Option 2', 'woocommerce' ),
			'option1express' => __( 'Option 1 Express', 'woocommerce' ),
			'option2express' => __( 'Option 2 Express', 'woocommerce' ),
			'included'       => __( 'Incluse', 'woocommerce' ),
			'dumpster'       => __( 'Benne', 'woocommerce' ),
		);
		foreach ( $shipping_methods as $key => $label ) {
			woocommerce_wp_checkbox(
				array(
					'id'      => '_product_shipping_methods_' . $key . '[' . $variation->ID . ']',
					'label'   => $label,
					'cbvalue' => $key,
					'value'   => in_array( $key, $shipping_methods_values, true ) ? $key : false,
				)
			);
		}
		echo '</div></fieldset>';

		echo '<fieldset class="form-field"><legend>' . esc_html__( 'Délais de livraison de Mars à Août', 'woocommerce' ) . '</legend>'
		. '<div class="options_group">';
		// Custom fields for shipping delays min and max
		$shipping_delay_min_hs = get_post_meta( $variation->ID, '_min_shipping_days_hs', true );
		woocommerce_wp_text_input(
			array(
				'id'    => '_min_shipping_days_hs[' . $variation->ID . ']',
				'label' => __( 'Délai de livraison minimum en jours', 'woocommerce' ),
				'value' => $shipping_delay_min_hs,
			)
		);

		$shipping_delay_max_hs = get_post_meta( $variation->ID, '_max_shipping_days_hs', true );
		woocommerce_wp_text_input(
			array(
				'id'    => '_max_shipping_days_hs[' . $variation->ID . ']',
				'label' => __( 'Délai de livraison maximum en jours', 'woocommerce' ),
				'value' => $shipping_delay_max_hs,
			)
		);

		echo '</div></fieldset>';

		echo '<fieldset class="form-field"><legend>' . esc_html__( 'Délais de livraison de Septembre à Février', 'woocommerce' ) . '</legend>'
		. '<div class="options_group">';

		// Custom fields for shipping delays min and max
		$shipping_delay_min_ls = get_post_meta( $variation->ID, '_min_shipping_days_ls', true );
		woocommerce_wp_text_input(
			array(
				'id'    => '_min_shipping_days_ls[' . $variation->ID . ']',
				'label' => __( 'Délai de livraison minimum en jours', 'woocommerce' ),
				'value' => $shipping_delay_min_ls,
				'',
			)
		);

		$shipping_delay_max_ls = get_post_meta( $variation->ID, '_max_shipping_days_ls', true );
		woocommerce_wp_text_input(
			array(
				'id'    => '_max_shipping_days_ls[' . $variation->ID . ']',
				'label' => __( 'Délai de livraison maximum en jours', 'woocommerce' ),
				'value' => $shipping_delay_max_ls,
			)
		);

		echo '</div></fieldset>';
		echo '</div>';
}

/**
 * Saves custom fields for product variations.
 *
 * @param int $post_id Post ID.
 * @param int $i Variation ID.
 * @return void
 */
function save_custom_fields_variations( $post_id, $i ) {

	$ecotax_checkbox_value = sanitize_text_field( $_POST['_has_ecotax'][ $post_id ] );
	update_post_meta( $post_id, '_has_ecotax', $ecotax_checkbox_value );

	$product_type = isset( $_POST['_product_type'][ $post_id ] ) ? sanitize_text_field( $_POST['_product_type'][ $post_id ] ) : 'other';
	update_post_meta( $post_id, '_product_type', $product_type );

	$sales_area = isset( $_POST['_product_sales_area'][ $post_id ] ) ? sanitize_text_field( $_POST['_product_sales_area'][ $post_id ] ) : 'all';
	update_post_meta( $post_id, '_product_sales_area', $sales_area );

	$custom_product_shipping_zones     = array();
	$all_custom_product_shipping_zones = array( 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18 );
	foreach ( $all_custom_product_shipping_zones as $zone ) {
		if ( isset( $_POST[ '_custom_product_shipping_zones_' . $zone ][ $post_id ] ) ) {
			$custom_product_shipping_zones[] = $zone;
		}
	}
	update_post_meta( $post_id, '_custom_product_shipping_zones', $custom_product_shipping_zones );

	$shipping_methods     = array();
	$all_shipping_methods = array(
		'drive',
		'option1',
		'option2',
		'option1express',
		'option2express',
		'included',
		'dumpster',
	);
	foreach ( $all_shipping_methods as $method ) {
		if ( isset( $_POST[ '_product_shipping_methods_' . $method ][ $post_id ] ) ) {
			$shipping_methods[] = $method;
		}
	}
	update_post_meta( $post_id, '_product_shipping_methods', $shipping_methods );

	$shipping_delay_min_hs = isset( $_POST['_min_shipping_days_hs'][ $post_id ] ) ? sanitize_text_field( $_POST['_min_shipping_days_hs'][ $post_id ] ) : '';
	update_post_meta( $post_id, '_min_shipping_days_hs', $shipping_delay_min_hs );

	$shipping_delay_max_hs = isset( $_POST['_max_shipping_days_hs'][ $post_id ] ) ? sanitize_text_field( $_POST['_max_shipping_days_hs'][ $post_id ] ) : '';
	update_post_meta( $post_id, '_max_shipping_days_hs', $shipping_delay_max_hs );

	$shipping_delay_min_ls = isset( $_POST['_min_shipping_days_ls'][ $post_id ] ) ? sanitize_text_field( $_POST['_min_shipping_days_ls'][ $post_id ] ) : '';
	update_post_meta( $post_id, '_min_shipping_days_ls', $shipping_delay_min_ls );

	$shipping_delay_max_ls = isset( $_POST['_max_shipping_days_ls'][ $post_id ] ) ? sanitize_text_field( $_POST['_max_shipping_days_ls'][ $post_id ] ) : '';
	update_post_meta( $post_id, '_max_shipping_days_ls', $shipping_delay_max_ls );
}
