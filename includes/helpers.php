<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Vérifie si un le nom d'un produit contient une des chaînes de caractères données.
 *
 * @param string $product_name Le nom du produit.
 * @param array  $strings Les chaînes de caractères à vérifier.
 * @param string $operation L'opération à effectuer. 'or' ou 'and'.
 * @return bool Vrai si le nom du produit contient une des chaînes de caractères données, faux sinon.
 */
function km_check_product_name( $product_name, $strings, $operation = 'or' ) {
	$product_name = mb_strtolower( $product_name, 'UTF-8' );
	$match_count  = 0;

	if ( ! is_array( $strings ) ) {
		$strings = array( $strings );
	}

	foreach ( $strings as $string ) {
		if ( mb_stripos( $product_name, mb_strtolower( $string, 'UTF-8' ), 0, 'UTF-8' ) !== false ) {
			if ( 'or' === $operation ) {
				return true;
			}
			++$match_count;
		}
	}

	return ( 'and' === $operation && count( $strings ) === $match_count );
}

/**
 * Vérifie si un produit appartient à une ou plusieurs catégories.
 *
 * @param WC_Product $product Le produit à vérifier.
 * @param array      $categories Les catégories à vérifier.
 *
 * @return bool Vrai si le produit appartient à une ou plusieurs catégories, faux sinon.
 */
function km_product_has_category( $product, $categories ) {
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( $product );
	}

	if ( $product->is_type( 'variation' ) ) {
		$product = wc_get_product( $product->get_parent_id() );
	}

	$product_id = $product->get_id();
	$categories = (array) $categories;

	foreach ( $categories as $category ) {
		if ( has_term( $category, 'product_cat', $product_id ) ) {
			return true;
		}
	}

	return false;
}


/**
 * Vérifie si la zone de livraison se trouve dans le département du 13.
 *
 * @return bool
 */
function km_is_shipping_zone_in_thirteen( $zone_id = null ) {

	if ( ! class_exists( 'KM_Shipping_Zone' ) ) {
		exit( 'KM_Shipping_Zone class does not exist' );
	}

	return KM_Shipping_Zone::get_instance()->is_zone_in_thirteen( $zone_id );
}

/**
 * Récupère l'ID de la zone de livraison.
 *
 * @return int
 */
function km_get_current_shipping_zone_id() {

	if ( ! class_exists( 'KM_Shipping_Zone' ) ) {
		exit( 'KM_Shipping_Zone class does not exist' );
	}
	return KM_Shipping_Zone::get_instance()->shipping_zone_id;
}

/**
 * Récupère le code postal de livraison.
 *
 * @return string
 */
function km_get_current_shipping_postcode() {

	if ( ! class_exists( 'KM_Shipping_Zone' ) ) {
		exit( 'KM_Shipping_Zone class does not exist' );
	}
	return KM_Shipping_Zone::get_instance()->shipping_postcode;
}

/**
 * Récupère le nom de la zone de livraison.
 *
 * @return string
 */
function km_get_shipping_zone_name( $shipping_zone_id = null ) {

	if ( ! class_exists( 'KM_Shipping_Zone' ) ) {
		exit( 'KM_Shipping_Zone class does not exist' );
	}
	return KM_Shipping_Zone::get_instance()->get_shipping_zone_name( $shipping_zone_id );
}

/**
 * Affiche les délais de livraison en fonction du contexte.
 *
 * @param string $context Le contexte dans lequel les délais de livraison sont affichés.
 * @param int    $min     Le délai de livraison minimum.
 * @param int    $max     Le délai de livraison maximum.
 *
 * @return string
 */
function km_get_shipping_dates( $context = 'cart', $min = 0, $max = 0 ) {
	if ( ! class_exists( 'KM_Shipping_Delays' ) ) {
		exit( 'KM_Shipping_Delays class does not exist' );
	}
	$km_shipping_delays = new KM_Shipping_Delays( km_get_current_shipping_zone_id(), $context, $min, $max );
	return $km_shipping_delays->display_shipping_dates();
}


/**
 * Récupère l'ID de la zone de livraison.
 *
 * @return int
 */
function km_get_ecotaxe_rate( $with_tax = false ) {

	if ( ! class_exists( 'KM_Dynamic_Pricing' ) ) {
		exit( 'KM_Dynamic_Pricing class does not exist' );
	}

	if ( $with_tax ) {
		return KM_Dynamic_Pricing::get_instance()->ecotaxe_rate_incl_taxes;
	}
	return KM_Dynamic_Pricing::get_instance()->ecotaxe_rate;
}

/**
 * Récupère prix du produit de livraison associé à un produit.
 *
 * @param WC_Product|int $product Le produit ou $product_id pour lequel récupérer le prix du produit de livraison.
 * @param int            $zone_id L'ID de la zone de livraison.
 * @return float
 */
function km_get_shipping_product_price( $product, $zone_id = null ) {
	if ( ! class_exists( 'KM_Dynamic_Pricing' ) ) {
		exit( 'KM_Dynamic_Pricing class does not exist' );
	}
	return KM_Dynamic_Pricing::get_instance()->get_shipping_product_price( $product, $zone_id );
}

/**
 * Modifie le prix d'un produit en fonction de la zone de livraison.
 *
 * @param float      $price Le prix du produit.
 * @param WC_Product $product Le produit.
 * @param int        $zone_id L'ID de la zone de livraison.
 *
 * @return float
 */
function km_get_product_price_based_on_shipping_zone( $price, $product, $zone_id, $force_recal = false ) {
	if ( ! class_exists( 'KM_Dynamic_Pricing' ) ) {
		exit( 'KM_Dynamic_Pricing class does not exist' );
	}
	return KM_Dynamic_Pricing::get_instance()->get_product_price_based_on_shipping_zone( $price, $product, $zone_id, $force_recal );
}

/**
 * Vérifie si un produit est expédiable dans un département hors 13.
 *
 * @param WC_Product|int $product Le produit ou $product_id à vérifier.
 *
 * @return bool
 */
function km_is_product_shippable_out_13( $product, $zone_id = null ) {
	if ( ! class_exists( 'KM_Shipping_Zone' ) ) {
		exit( 'KM_Shipping_Zone class does not exist' );
	}
	return KM_Shipping_Zone::get_instance()->is_product_shippable_out_13( $product, $zone_id );
}

/**
 * Vérifie si un produit est achetable dans une zone de livraison donnée.
 *
 * @param WC_Product|int $product Le produit ou $product_id à vérifier.
 * @param int            $zone_id L'ID de la zone de livraison.
 *
 * @return bool
 */
function km_is_purchasable_in_zone( $product, $zone_id ) {
	return km_is_shipping_zone_in_thirteen( $zone_id ) ? true : km_is_product_shippable_out_13( $product, $zone_id );
}

/**
 * Récupère le produit de livraison associé à un produit.
 *
 * @param WC_Product $product Le produit pour lequel récupérer le produit de livraison.
 *
 * @return WC_Product
 */
function km_get_related_shipping_product( $product, $zone_id = null ) {
	if ( ! class_exists( 'KM_Shipping_Zone' ) ) {
		exit( 'KM_Shipping_Zone class does not exist' );
	}
	return KM_Shipping_Zone::get_instance()->get_related_shipping_product( $product, $zone_id );
}

/**
 * Vérifie si un produit à un tarif dégressif de livraison
 *
 * @param WC_Product|int $product Le produit ou $product_id à vérifier.
 *
 * @return bool
 */
function km_product_has_decreasing_shipping_price( $product ) {
	if ( ! class_exists( 'KM_Big_Bag_Manager' ) ) {
		exit( 'KM_Big_Bag_Manager class does not exist' );
	}
	return KM_Big_Bag_Manager::get_instance()->product_has_decreasing_shipping_price( $product );
}

/**
 * Vérifie si un produit est un big bag
 *
 * @param WC_Product|int $product Le produit ou $product_id à vérifier.
 *
 * @return bool
 */
function km_is_big_bag( $product ) {
	if ( ! class_exists( 'KM_Big_Bag_Manager' ) ) {
		exit( 'KM_Big_Bag_Manager class does not exist' );
	}
	return KM_Big_Bag_Manager::get_instance()->is_big_bag( $product );
}

/**
 * Vérifie si un produit est big bag avec des dalles
 *
 * @param WC_Product|int $product Le produit ou $product_id à vérifier.
 *
 * @return bool
 */
function km_is_big_bag_and_slab( $product ) {
	if ( ! class_exists( 'KM_Big_Bag_Manager' ) ) {
		exit( 'KM_Big_Bag_Manager class does not exist' );
	}
	return KM_Big_Bag_Manager::get_instance()->is_big_bag_and_slab( $product );
}

/**
 * Vérifie si un big bag est dans le panier
 *
 * @return bool
 */
function km_is_big_bag_price_decreasing_zone( $zone_id = null ) {
	if ( ! class_exists( 'KM_Big_Bag_Manager' ) ) {
		exit( 'KM_Big_Bag_Manager class does not exist' );
	}
	return KM_Big_Bag_Manager::get_instance()->is_big_bag_price_decreasing_zone( $zone_id );
}

/**
 * Vérifie si un big bag est dans le panier
 *
 * @return bool
 */
function km_product_has_decreasing_shipping_price_in_cart() {
	if ( ! class_exists( 'KM_Big_Bag_Manager' ) ) {
		exit( 'KM_Big_Bag_Manager class does not exist' );
	}
	return KM_Big_Bag_Manager::get_instance()->count_items_with_decreasing_shipping_price_in_cart();
}

/**
 * Récupère le produit de livraison associé à un produit Big bag.
 *
 * @param WC_Product $product Le produit pour lequel récupérer le produit de livraison.
 *
 * @return WC_Product
 */
function km_get_big_bag_shipping_product( $product ) {

	if ( ! class_exists( 'KM_Big_Bag_Manager' ) ) {
		exit( 'KM_Big_Bag_Manager class does not exist' );
	}
	return KM_Big_Bag_Manager::get_instance()->get_big_bag_shipping_product( $product );
}

/**
 * Vérifie si un produit avec un tarif dégressif de livraison est dans le panier.

 * @return bool
 */
function km_has_item_with_decreasing_shipping_price_in_cart() {

	if ( ! class_exists( 'KM_Big_Bag_Manager' ) ) {
		exit( 'KM_Big_Bag_Manager class does not exist' );
	}
	return KM_Big_Bag_Manager::get_instance()->count_items_with_decreasing_shipping_price_in_cart();
}

/**
 * Récupère la quantité de big bag dans le panier.
 *
 * @return int
 */
function km_get_big_bag_quantity_in_cart( $cart = '' ) {
	if ( ! class_exists( 'KM_Big_Bag_Manager' ) ) {
		exit( 'KM_Big_Bag_Manager class does not exist' );
	}
	return KM_Big_Bag_Manager::get_instance()->get_big_bag_quantity_in_cart( $cart );
}



/**
 * Calcule le prix de livraison d'un produit.
 *
 * @param string $method L'identifiant de la méthode de livraison.
 * @param array  $package Le package de livraison.
 *
 * @return float
 */
function km_calculate_shipping_method_price( $method, $package ) {
	if ( ! class_exists( 'KM_Shipping_Methods' ) ) {
		exit( 'KM_Shipping_Methods class does not exist' );
	}
	return KM_Shipping_Methods::get_instance()->calculate_shipping_method_price( $method, $package );
}

/**
 * Calcule le montant total de l'EcoTaxe.
 *
 * @param string $context 'cart'|'order' Le contexte dans lequel le montant total de l'EcoTaxe est calculé.
 *
 * @return float
 */
function km_get_total_ecotaxe( $context = 'cart' ) {
	if ( ! class_exists( 'KM_Dynamic_Pricing' ) ) {
		exit( 'KM_Dynamic_Pricing class does not exist' );
	}
	return KM_Dynamic_Pricing::get_instance()->get_total_ecotaxe( $context );
}
