<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles palletization management based on product added in WooCommerce cart.
 */

class KM_Big_Bag_Manager {

	use SingletonTrait;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'woocommerce_after_single_product', array( $this, 'render_big_bag_confirmation_popup' ) );
		add_action( 'wp_ajax_big_bag_user_accept', array( $this, 'big_bag_user_accept' ) );
		add_action( 'wp_ajax_nopriv_big_bag_user_accept', array( $this, 'big_bag_user_accept' ) );
	}

	/**
	 * Vérifie si le produit est un big bag.
	 *
	 * @param int $product_id L'ID du produit.
	 * @return bool Vrai si le produit est un big bag, faux sinon.
	 */
	public function is_big_bag( $product_id ) {
		$product      = wc_get_product( $product_id );
		$product_name = $product->get_name();

			// Vérifier si le produit appartient à la catégorie 'location-big-bag'.
		if ( has_term( 'location-big-bag', 'product_cat', $product_id ) ) {
			return 'collection';
		}

		if ( strpos( $product_name, 'Big Bag' ) !== false ) {
			return 'delivery-collection';
		} elseif ( $product->is_type( 'variable' ) ) {
			$variations = $product->get_available_variations();

			foreach ( $variations as $variation ) {
				$variation_name = $variation['attributes']['attribute_pa_variation-conditionnement'];

				if ( strpos( $variation_name, 'big-bag' ) !== false ) {
					return 'delivery-collection';
				}
			}
		}

		return false;
	}

	/**
	 * Affiche la popup de confirmation d'ajout de palette.
	 */
	public function render_big_bag_confirmation_popup() {
		$product_id   = get_the_ID();
		$big_bag_type = $this->is_big_bag( $product_id );

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
