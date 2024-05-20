<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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
function km_add_custom_body_class_for_unpurchasable_products( $classes ) {
	$product = wc_get_product( get_the_ID() );

	if ( is_product() && ! $product->is_purchasable() ) {
		$classes[] = 'unpurchasable-product';
	}

	return $classes;
}

add_filter( 'body_class', 'km_add_custom_body_class_for_unpurchasable_products' );

/**
 * Affiche les délais de livraison sur la page produit.
 *
 * @param int $product_id L'ID du produit.
 */
function km_display_shipping_dates_on_product_page( $product_id ) {

	global $product;

	// Vérifier si le produit est achetable.
	if ( ! $product || ! $product->is_purchasable() ) {
		return; // Si le produit n'est pas achetable, ne rien afficher.
	}

	// Récupérer l'ID de la zone de livraison.
	$shipping_zone_id = km_get_current_shipping_zone_id();

	// Déterminer le contexte (product page).
	$context = 'product';

	// Créer une instance de la classe KM_Shipping_Delays.
	$shipping_delays = new KM_Shipping_Delays( $shipping_zone_id, $context );

	// Afficher les délais de livraison.
	echo $shipping_delays->display_shipping_dates();
}

/**
 * Vérifie si le produit doit afficher le calculateur de tonnage
 *
 * @return boolean True si le produit doit afficher le calculateur de tonnage, false sinon.
 */
function km_has_tonnage_calculator() {
	// Vérifier si c'est une page produit.
	if ( is_product() ) {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		// Obtenir les catégories de produits.
		$categories = wp_get_post_terms( $product->get_id(), 'product_cat' );
		if ( empty( $categories ) ) {
			return false;
		}

		// Trouver la catégorie enfant si elle existe, sinon utiliser la catégorie parente.
		$target_category = current(
			array_filter(
				$categories,
				function ( $cat ) {
					return 0 !== $cat->parent;
				}
			)
		);

		if ( ! $target_category ) {
			$target_category = current( $categories );
		}

		if ( ! $target_category ) {
			return false;
		}

		// Vérifier le champ ACF pour la catégorie cible.
		return get_field( 'show_tonnage_calculator', 'product_cat_' . $target_category->term_id ) ? true : false;
	}

	return false;
}
/**
 * Ajoute une classe au body pour les produits avec calculateur de tonnage
 *
 * @param array $classes Les classes du body.
 * @return array Les classes du body.
 */
add_filter( 'body_class', 'km_add_body_class_for_products_with_tonnage_calculator' );
function km_add_body_class_for_products_with_tonnage_calculator( $classes ) {
	if ( is_product() && km_has_tonnage_calculator() ) {
		return array_merge( $classes, array( 'has-tonnage-calculator' ) );
	}
	return $classes;
}


/**
 * Vérifie si le prix d'un produit woocommerce a chang& lors de sa modification et si oui ajoute la meta _atoonext_sync
 *
 * @param int    $product_id
 * @param object $product
 * @return void
 */
function km_check_for_price_change( $product_id, $product ) {

	if ( isset( $_POST['_regular_price'] ) || isset( $_POST['_sale_price'] ) ) {
		update_post_meta( $product_id, '_atoonext_sync', 'yes' );
	}
}
add_action( 'woocommerce_update_product', 'km_check_for_price_change', 10, 2 );

/**
 * Vérifie si le prix d'une variation de produit woocommerce a changée lors de sa modification et si oui ajoute la meta _atoonext_sync
 *
 * @param int $variation_id
 * @param int $i
 */
function km_check_variation_for_price_change( $variation_id, $i ) {
	if ( isset( $_POST['variable_regular_price'][ $i ] ) || isset( $_POST['variable_sale_price'][ $i ] ) ) { // Vérifiez si le prix régulier ou le prix de vente de la variation a été soumis.
		$product_id = wp_get_post_parent_id( $variation_id );
		update_post_meta( $product_id, '_atoonext_sync', 'true' );
	}
}
add_action( 'woocommerce_save_product_variation', 'km_check_variation_for_price_change', 10, 2 );
