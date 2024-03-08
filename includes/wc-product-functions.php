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
 * Ajoute une classe au body pour les produits avec délais de livraison
 *
 * @param array $classes Les classes du body.
 * @return array Les classes du body.
 */
function km_display_shipping_dates_on_product_page( $product_id ) {

	global $product;

	// Vérifier si le produit est achetable.
	if ( ! $product || ! $product->is_purchasable() ) {
		return; // Si le produit n'est pas achetable, ne rien afficher.
	}
	$product_id = $product->get_id();

	// Récupérer l'ID de la zone de livraison.
	$shipping_zone_id = km_get_current_shipping_zone_id();

	// Vérifier si des délais de livraison personnalisés sont définis via ACF.
	$custom_delays_hs = get_field( 'product_shipping_delays_product_shipping_delays_hs', $product_id );
	$custom_delays_ls = get_field( 'product_shipping_delays_product_shipping_delays_ls', $product_id );

	// Déterminer la saison actuelle.
	$current_month  = date( 'n' );
	$is_high_season = $current_month >= 3 && $current_month <= 8; // De Mars à Août.

	// Récupérer les délais de livraison en fonction de la saison et des données personnalisées.
	$min_shipping_days = $is_high_season ? ( empty( $custom_delays_hs['min_shipping_days_hs'] ) ?? get_option( 'min_shipping_days_hs_' . $shipping_zone_id ) ) : ( empty( $custom_delays_ls['min_shipping_days_ls'] ) ? get_option( 'min_shipping_days_ls_' . $shipping_zone_id ) : $custom_delays_ls['min_shipping_days_ls'] );
	$max_shipping_days = $is_high_season ? ( empty( $custom_delays_hs['max_shipping_days_hs'] ) ?? get_option( 'max_shipping_days_hs_' . $shipping_zone_id ) ) : ( empty( $custom_delays_ls['max_shipping_days_ls'] ) ? get_option( 'max_shipping_days_ls_' . $shipping_zone_id ) : $custom_delays_ls['max_shipping_days_ls'] );

	// Vérifier si les informations sont disponibles.
	if ( empty( $min_shipping_days ) && empty( $max_shipping_days ) ) {
		return; // Si les deux sont manquants, ne rien afficher.
	}

	// Construire le message à afficher.
	$delivery_message = 'Délais de livraison de ';
	if ( ! empty( $min_shipping_days ) && ! empty( $max_shipping_days ) ) {
		$delivery_message .= $min_shipping_days . ' à ' . $max_shipping_days . ' jours.';
	} elseif ( ! empty( $min_shipping_days ) ) {
		$delivery_message .= $min_shipping_days . ' jours minimum.';
	} elseif ( ! empty( $max_shipping_days ) ) {
		$delivery_message .= $max_shipping_days . ' jours maximum.';
	}

	echo '<div class="km-product-delivery-delay">' . esc_html( $delivery_message ) . '</div>';
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
 * Ajoute un champ personnalisé pour désactiver une variation dans le 13
 *
 * @param int    $loop
 * @param array  $variation_data
 * @param object $variation
 * @return void
 */
function km_add_custom_field_to_variations( $loop, $variation_data, $variation ) {
		// Eco-taxe.
		woocommerce_wp_checkbox(
			array(
				'id'      => '_has_ecotax[' . $loop . ']',
				'label'   => 'Cette variation de produit a une éco-taxe',
				'value'   => get_post_meta( $variation->ID, '_has_ecotax', true ),
				'cbvalue' => '1',
			)
		);
	// Créer une case à cocher
	woocommerce_wp_checkbox(
		array(
			'id'    => '_disable_variation_in_13[' . $loop . ']',
			'label' => 'Désactiver cette variation dans le 13',
			'value' => get_post_meta( $variation->ID, '_disable_variation_in_13', true ),
		)
	);
}
add_action( 'woocommerce_variation_options', 'km_add_custom_field_to_variations', 10, 3 );
/**
 * Enregistre la valeur du champ personnalisé pour désactiver une variation dans le 13
 *
 * @param int $variation_id
 * @param int $i
 * @return void
 */
function km_save_custom_field_variations( $variation_id, $i ) {
	// Eco-taxe.
	$ecotax_checkbox_value = isset( $_POST['_has_ecotax'][ $i ] );
	update_post_meta( $variation_id, '_has_ecotax', $ecotax_checkbox_value );

	$checkbox_value = isset( $_POST['_disable_variation_in_13'][ $i ] ) ? 'yes' : 'no';
	update_post_meta( $variation_id, '_disable_variation_in_13', $checkbox_value );
}
add_action( 'woocommerce_save_product_variation', 'km_save_custom_field_variations', 10, 2 );



add_action( 'woocommerce_before_single_product', 'km_display_shipping_dates_on_product_page' );
