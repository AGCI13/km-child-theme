<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles Big bag management based on product added in WooCommerce cart.
 */

class KM_Big_Bag_Manager {

	use SingletonTrait;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'adjust_cart_item_prices' ), 20, 1 );

		// Client demand to hide BB confirmation popup. Uncomment these line to enable it.
		// add_action( 'woocommerce_after_single_product', array( $this, 'render_big_bag_confirmation_popup' ) );
		// add_action( 'wp_ajax_big_bag_user_accept', array( $this, 'big_bag_user_accept' ) );
		// add_action( 'wp_ajax_nopriv_big_bag_user_accept', array( $this, 'big_bag_user_accept' ) );
	}

	/***
	 * Adjust cart item prices based on big bags shipping price.
	 *
	 * @param WC_Cart $cart The cart object.
	 */
	public function adjust_cart_item_prices( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		$km_shipping_zone = KM_Shipping_Zone::get_instance();

		if ( $km_shipping_zone->is_in_thirteen || in_array( $km_shipping_zone->shipping_zone_id, array( 4, 5 ), true ) ) {
			return;
		}

		$cart = $cart->get_cart();

		$bigbag_quantity = $this->get_big_bag_quantity_in_cart( $cart );

		if ( 0 === $bigbag_quantity ) {
			return;
		}

		$shipping_product_for_one_bb = get_page_by_path( '1-big-bag-degressif', OBJECT, 'product' );

		if ( ! $shipping_product_for_one_bb ) {
			return;
		}

		$shipping_product_id_for_one_bb = $shipping_product_for_one_bb->ID;

		$one_big_bag_shipping_price = wc_get_product( $shipping_product_id_for_one_bb )->get_price();
		$big_bags_shipping_price    = $this->calculate_big_bags_shipping_price( $bigbag_quantity, $one_big_bag_shipping_price );

		foreach ( $cart as $cart_item ) {

			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

			if ( ! is_int( $product_id ) || ! $this->is_big_bag( $product_id ) ) {
				continue;
			}

			$new_price = $cart_item['data']->get_price( 'edit' ) + $big_bags_shipping_price / $bigbag_quantity - $one_big_bag_shipping_price;
			$cart_item['data']->set_price( $new_price );
		}
	}

	/**
	 * Calcule le prix de livraison des big bags.
	 *
	 * @param int $bigbag_quantity La quantité de big bags.
	 * @return float Le prix de livraison des big bags.
	 */
	public function calculate_big_bags_shipping_price( $bigbag_quantity, $one_big_bag_shipping_price ) {

		// Get big bag quantity modulo 8.
		$bigbag_quantity_modulo = intval( round( $bigbag_quantity % 8 ) );

		// Get integer division of big bag quantity by 8.
		$bigbag_quantity_division = intval( floor( $bigbag_quantity / 8 ) );

		$shipping_price = 0;

		if ( $bigbag_quantity_division > 0 ) {

			for ( $i = 0; $i < $bigbag_quantity_division; $i++ ) {
				$shipping_product_id = get_page_by_path( '8-big-bag-degressif', OBJECT, 'product' )->ID;
				$shipping_price     += wc_get_product( $shipping_product_id )->get_price();
			}
		}

		if ( $bigbag_quantity_modulo > 0 ) {
			$shipping_price += ( 1 === $bigbag_quantity_modulo ) ? $one_big_bag_shipping_price : wc_get_product( get_page_by_path( $bigbag_quantity_modulo . '-big-bag-degressif', OBJECT, 'product' )->ID )->get_price();
		}

		return $shipping_price;
	}

	/**
	 * Récupère la quantité de big bag dans le panier.
	 *
	 * @param array $cart Le panier.
	 * @return int La quantité de big bag dans le panier.
	 */
	public function get_big_bag_quantity_in_cart( $cart ) {
		$bigbag_quantity = 0;

		foreach ( $cart as $cart_item_key => $cart_item ) {
			if ( $this->is_big_bag( $cart_item['product_id'] ) || $this->is_big_bag( $cart_item['variation_id'] ) ) {
				$bigbag_quantity += $cart_item['quantity'];
			}
		}

		return $bigbag_quantity;
	}

	/**
	 * Vérifie si le produit est un big bag.
	 *
	 * @param int|WC_Product $product L'ID du produit ou l'objet produit.
	 * @return bool Vrai si le produit est un big bag, faux sinon.
	 */
	public function is_big_bag( $product ) {

		if ( ! $product ) {
			return false;
		}

		$product_id = $product instanceof WC_Product ? $product->get_id() : $product;
		$product    = wc_get_product( $product_id );

		// Vérifier si le produit appartient à la catégorie 'location-big-bag'.
		if ( has_term( 'location-big-bag', 'product_cat', $product_id ) ) {
			return true; // Si le produit appartient à la catégorie, retourner vrai.
		}

		// Vérifier si l'ID du produit n'est pas l'un des IDs exclus.
		if ( in_array( $product_id, array( 96815, 96800 ), true ) ) {
			return false;
		}

		if ( stripos( $product->get_name(), 'dalles stabilisatrices' ) !== false ) {
			return false;
		}

		// Vérifier si le nom du produit contient 'Big Bag'.
		if ( stripos( $product->get_name(), 'big bag' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Affiche la popup de confirmation d'ajout de palette.
	 */
	public function render_big_bag_confirmation_popup() {
		$product_id = get_the_ID();

		if ( false === $this->is_big_bag( $product_id ) ) {
			return;
		}

		if ( isset( $_COOKIE['big_bag_user_accept'] ) || WC()->session->get( 'big_bag_user_accept' ) ) {
			return;
		}

		// Enqueue scripts.
		wp_enqueue_script( 'add-to-cart-confirmation' );

		// requiert le template.
		require_once get_stylesheet_directory() . '/templates/modals/big-bag.php';
	}

	/**
	 * Gère la confirmation d'ajout de palette.
	 */
	public function big_bag_user_accept() {

		if ( is_user_logged_in() ) {
			WC()->cart->set_session( 'big_bag_user_accept', true );
		}

		if ( ! isset( $_COOKIE['big_bag_user_accept'] ) ) {
			setcookie( 'big_bag_user_accept', true, time() + 3600 * 24 * 30, '/' );
		}

		wp_send_json_success();
	}
}
