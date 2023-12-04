<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Hide Price Range for WooCommerce Variable Products.
add_filter( 'woocommerce_variable_sale_price_html', 'km_variable_product_price', 10, 2 );
add_filter( 'woocommerce_variable_price_html', 'km_variable_product_price', 10, 2 );
/**
 * Change the price display for variable products.
 *
 * @param  string $v_price The variable product price.
 * @param  object $v_product The variable product object.
 * @return string The updated variable product price.
 */
function km_variable_product_price( $v_price, $v_product ) {
	// Product Price.
	$prod_prices = array(
		$v_product->get_variation_price( 'min', true ),
		$v_product->get_variation_price( 'max', true ),
	);
	$prod_price  = $prod_prices[0] !== $prod_prices[1] ? sprintf( __( 'À partir de %1$s', 'woocommerce' ), wc_price( $prod_prices[0] ) ) : wc_price( $prod_prices[0] );

	// Regular Price.
	$regular_prices = array(
		$v_product->get_variation_regular_price( 'min', true ),
		$v_product->get_variation_regular_price( 'max', true ),
	);
	sort( $regular_prices );
	$regular_price = $regular_prices[0] !== $regular_prices[1] ? sprintf(
		__( 'À partir de %1$s', 'woocommerce' ),
		wc_price( $regular_prices[0] )
	) : wc_price( $regular_prices[0] );

	if ( $prod_price !== $regular_price ) {
		$prod_price = '<del>' . $regular_price . $v_product->get_price_suffix() . '</del> <ins>' .
						$prod_price . $v_product->get_price_suffix() . '</ins>';
	}
	return $prod_price;
}

add_action( 'wp_ajax_tonnage_calculation', 'km_tonnage_calculation' );
add_action( 'wp_ajax_nopriv_tonnage_calculation', 'km_tonnage_calculation' );
/**
 * Calcul du tonnage
 *
 * @return void
 */
function km_tonnage_calculation() {

	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	$nonce_value = isset( $_POST['nonce_tonnage_calculator'] ) && ! empty( $_POST['nonce_tonnage_calculator'] ) ? wp_unslash( $_POST['nonce_tonnage_calculator'] ) : '';
	$nonce_value = sanitize_text_field( $nonce_value );

	if ( ! wp_verify_nonce( $nonce_value, 'tonnage_calculation' ) ) {
		wp_send_json_error( array( 'message' => __( 'La vérification du nonce a échoué.' ) ) );
	}

	if ( ! isset( $_POST['lon'] ) || ! is_numeric( $_POST['lon'] )
	|| ! isset( $_POST['lar'] ) || ! is_numeric( $_POST['lar'] )
	|| ! isset( $_POST['epa'] ) || ! is_numeric( $_POST['epa'] )
	|| ! isset( $_POST['den'] ) || ! is_numeric( $_POST['den'] ) ) {
		wp_send_json_error();
	}

	$conditionnement = array(
		'BIG BAG 1.5T'  => 1500,
		'BIG BAG 1T'    => 1000,
		'BIG BAG 800KG' => 800,
		'BIG BAG 600KG' => 600,
		'BIG BAG 400KG' => 400,
		'BIG BAG 200KG' => 200,
	);

	$longueur  = (float) $_POST['lon'];
	$largeur   = (float) $_POST['lar'];
	$epaisseur = (float) $_POST['epa'];
	$densite   = (float) $_POST['den'];

	$result    = $longueur * $largeur * $epaisseur * $densite;
	$kg_result = $result / 1000;

	$meilleur_conditionnement = '';
	$capacite_conditionnement = 60000;

	foreach ( $conditionnement as $nom_conditionnement => $capacite ) {
		if ( $kg_result <= $capacite && ( $capacite - $kg_result ) < ( $capacite_conditionnement - $kg_result ) ) {
			$meilleur_conditionnement = $nom_conditionnement;
			$capacite_conditionnement = $capacite;
		}
	}

	if ( $result > 1000000 ) {
		$unit   = 'T';
		$result = round( $result / 1000000, 2 );
	} else {
		$unit   = 'Kg';
		$result = round( $result / 1000, 2 );
	}

	$json = array(
		'lon'             => $longueur,
		'lar'             => $largeur,
		'epa'             => $epaisseur,
		'den'             => $densite,
		'res'             => $result,
		'unit'            => $unit,
		'conditionnement' => $meilleur_conditionnement,
	);
	wp_send_json_success( $json );
}

/**
 * Ajoute une classe au body pour les produits non achetables
 *
 * @param array $classes Les classes du body.
 * @return array Les classes du body.
 */

function add_custom_body_class_for_unpurchasable_products( $classes ) {
	$product = wc_get_product( get_the_ID() );

	if ( is_product() && ! $product->is_purchasable() ) {
		$classes[] = 'unpurchasable-product';
	}

	return $classes;
}

add_filter( 'body_class', 'add_custom_body_class_for_unpurchasable_products' );
