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

function add_custom_body_class_for_unpurchasable_products( $classes ) {
	$product = wc_get_product( get_the_ID() );

	if ( is_product() && ! $product->is_purchasable() ) {
		$classes[] = 'unpurchasable-product';
	}

	return $classes;
}

add_filter( 'body_class', 'add_custom_body_class_for_unpurchasable_products' );


/**
 * Ajoute les métadonnées de la palette sur la page produit
 *
 * @return void
 */
function km_debug_single_product() {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) || ! is_product() ) {
		return;
	}
	global $product;

	// Obtenir l'ID du produit.
	$product_id = $product->get_id();

	// Récupérer les valeurs des métadonnées.
	$quantite_par_palette = get_post_meta( $product_id, '_quantite_par_palette', true ) ?: 'Non renseigné';
	$palette_a_partir_de  = get_post_meta( $product_id, '_palette_a_partir_de', true ) ?: 'Non renseigné';
	$product_info         = '';

	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_available_variations() as $variation ) {
			// Get variation name.
			$variation_name = $variation['attributes']['attribute_pa_variation-conditionnement'];
			// Get variation price.
			$variation_id    = $variation['variation_id'];
			$variation_obj   = new WC_Product_Variation( $variation_id );
			$variation_price = $variation_obj->get_price();
			// Get shipping class.
			$shipping_class = $variation_obj->get_shipping_class_id();
			// Get shipping class name.
			if ( $shipping_class ) {
				$shipping_class_name = get_term_by( 'term_id', $shipping_class, 'product_shipping_class' )->name;
			} else {
				$shipping_class_name = 'Aucune';
			}

			$price_including_taxes = wc_get_price_including_tax( $variation_obj );

			$product_info .= '<tr><th colspan="2">Variation : ' . esc_html( $variation_name ) . '</th></tr>';
			$product_info .= '<tr><td>Prix HT</td><td>' . esc_html( $variation_price ) . '</td></tr>';
			$product_info .= '<tr><td>Prix TTC</td><td>' . esc_html( $price_including_taxes ) . '</td></tr>';
			$product_info .= '<tr><td>Classe de livraison</td><td>' . esc_html( $shipping_class_name ) . '</td></tr>';
		}
	} else {
		// Get product shipping classe.
		$shipping_class = $product->get_shipping_class_id();
		// Get including taxes price.
		$price_including_taxes = wc_get_price_including_tax( $product );
		// Get shipping class name.
		if ( $shipping_class ) {
			$shipping_class_name = get_term_by( 'term_id', $shipping_class, 'product_shipping_class' )->name;
		} else {
			$shipping_class_name = 'Aucune';
		}

		$product_info .= '<tr><th colspan="2">Produit : ' . esc_html( $product->get_name() ) . '</th></tr>';
		$product_info .= '<tr><td>Prix HT</td><td>' . esc_html( $product->get_price() ) . ' €</td></tr>';
		$product_info .= '<tr><td>Prix TTC</td><td>' . esc_html( $price_including_taxes ) . ' €</td></tr>';
		$product_info .= '<tr><td>Classe de livraison</td><td>' . esc_html( $shipping_class_name ) . '</td></tr>';
	}

	// Check if product is purchasable.
	$is_purchasable = $product->is_purchasable() ? 'Oui' : 'Non';

	// Afficher les métadonnées sur la page produit.
	echo '<div id="km-shipping-info-debug" class="km-debug-bar">'
	. '<h4>DEBUG INFOS</h4>'
	. '<img class="modal-debug-close km-modal-close" src="' . esc_url( get_stylesheet_directory_uri() . '/assets/img/cross.svg' ) . '" alt="close modal"></span>'
	. '<div class="debug-content"><table>'
	. '<tr><th colspan="2">Est achetable ?</th></tr><tr><td colspan="2">' . $is_purchasable . '</td></tr>'
	. '<tr><th colspan="2">Palettisation</th></tr>'
	. '<tr><td>Quantité par palette</td><td>' . esc_html( $quantite_par_palette ) . '</td></tr>'
	. '<tr><td>Palette à partir de</td><td>' . esc_html( $palette_a_partir_de ) . '</td></tr>'
	. '</table><table>'
	. $product_info
	. '</table></div></div>';
}
// Ajouter l'action au résumé du produit WooCommerce.
add_action( 'woocommerce_after_single_product', 'km_debug_single_product' );

function km_display_shipping_delays_on_product_page( $product_id ) {

	global $product;

	// Vérifier si le produit est achetable.
	if ( ! $product || ! $product->is_purchasable() ) {
		return; // Si le produit n'est pas achetable, ne rien afficher.
	}
	$product_id = $product->get_id();

	// Récupérer l'ID de la zone de livraison.
	$shipping_zone_id = KM_Shipping_Zone::get_instance()->shipping_zone_id;

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

function has_tonnage_calculator() {
	// Si c'est un produit et qu'il n'y a pas l'option "Afficher le calculateur de tonnage" de coché, on ne l'affiche pas.
	if ( is_product() ) {
		global $product;

		// Obtenir les catégories de produits.
		$categories      = wp_get_post_terms( $product->get_id(), 'product_cat' );
		$parent_category = null;
		$child_category  = null;

		foreach ( $categories as $category ) {
			if ( $category->parent == 0 ) {
				// C'est une catégorie parente.
				$parent_category = $category;
			} else {
				// C'est une catégorie enfant.
				$child_category = $category;
			}
		}

		$target_category = $child_category ? $child_category : $parent_category;
		$cat_term_id     = $target_category->term_id;

		if ( $cat_term_id ) {
			// Récupérer la valeur du champ ACF pour cette catégorie.
			$acf_value = get_field( 'show_tonnage_calculator', 'product_cat_' . $cat_term_id );
			if ( $acf_value ) {
				return true;
			}
		}
		return false;
	}
}

add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_product() && has_tonnage_calculator() ) {
			return array_merge( $classes, array( 'has-tonnage-calculator' ) );
		}
		return $classes;
	}
);
