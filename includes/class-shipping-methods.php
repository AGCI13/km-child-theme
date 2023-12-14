<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles dynamic pricing based on shipping zones and classes in WooCommerce.
 */
class KM_Shipping_Methods {

	use SingletonTrait;


	/**
	 * The shipping zone instance.
	 *
	 * @var KM_Shipping_zone|null
	 */
	public $km_shipping_zone;

	/**
	 * KM_Shipping_Methods constructor.
	 */
	public function __construct() {
		$this->km_shipping_zone = KM_Shipping_zone::get_instance();
		$this->register();
	}

	/**
	 * Register hooks.
	 */
	public function register() {
		add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_methods' ), 10, 1 );
	}

	/**
	 * Ajoute les options de livraison
	 *
	 * @param array $methods
	 * @return array
	 */
	public function add_shipping_methods( $methods ) {
		$methods['option1']        = 'Shipping_method_1';
		$methods['option1express'] = 'Shipping_method_1_express';
		$methods['option2']        = 'Shipping_method_2';
		$methods['option2express'] = 'Shipping_method_2_express';
		$methods['drive']          = 'Shipping_method_drive';
		$methods['out13']          = 'Shipping_method_out_13';
		return $methods;
	}

	/**
	 * Calcule le poids total du panier.
	 *
	 * @param WC_Cart $cart Le panier.
	 * @return float Le poids total du panier.
	 */
	private function calculate_cart_weight( $cart ) {
		$total_weight = 0;
		foreach ( $cart->get_cart() as $cart_item ) {
			$total_weight += $cart_item['data']->get_weight() * $cart_item['quantity'];
		}
		return $total_weight;
	}

	/**
	 * Calcule le prix de la livraison en fonction du poids du panier.
	 *
	 * @param string $weight_class La classe de poids.
	 * @param string $express Si la livraison est express ou non.
	 * @return float Le prix de la livraison.
	 */
	private function get_shipping_price( $delivery_option_full_name ) {

		if ( ! $delivery_option_full_name ) {
			return;
		}

		$args = array(
			'fields'         => 'ids',
			'post_type'      => 'product',
			'posts_per_page' => 1,
			'post_status'    => array( 'private' ),
			'name'           => $delivery_option_full_name,
		);

		$shipping_products_posts = get_posts( $args );

		if ( ! $shipping_products_posts ) {
			return;
		}

		return wc_get_product( $shipping_products_posts[0] )->get_price( 'edit' );
	}

	/**
	 * Calculate shipping cost based on weight for 'VRAC A LA TONNE' products.
	 *
	 * @param string $shipping_method_name The name of the shipping method.
	 * @return float Total shipping cost.
	 */
	public function calculate_shipping_method_price( $shipping_method_name ) {
		$cart_items            = WC()->cart->get_cart();
		$total_shipping_cost   = 0.0;
		$vrac_weight           = 0.0;
		$isolation_weight      = 0.0;
		$other_products_weight = 0.0;

		if ( ! $cart_items ) {
			return 0;
		}

		$cart_has_plasterboard = $this->cart_has_plasterboard( $cart_items );

		foreach ( $cart_items as $cart_item ) {
			$product        = $cart_item['data'];
			$product_weight = $product->get_weight() * $cart_item['quantity'];

			if ( strpos( $product->get_name(), 'VRAC A LA TONNE' ) !== false ) {
				$vrac_weight += $product_weight;
			} elseif ( $cart_has_plasterboard && $this->is_isolation_product( $product ) ) {
				$isolation_weight += $product_weight;
			} else {
				$other_products_weight += $product_weight;
			}
		}

		if ( $vrac_weight > 0 ) {
			$vrac_shipping_cost   = $this->calculate_shipping_for_product( $vrac_weight, $shipping_method_name );
			$total_shipping_cost += $vrac_shipping_cost;
		}

		if ( $isolation_weight > 0 ) {
			$isolation_shipping_cost = $this->calculate_shipping_for_product( $isolation_weight, $shipping_method_name );
			$total_shipping_cost    += $isolation_shipping_cost;
		}

		if ( $other_products_weight > 0 ) {
			$other_products_shipping_cost = $this->calculate_shipping_for_product( $other_products_weight, $shipping_method_name );
			$total_shipping_cost         += $other_products_shipping_cost;
		}

		/**
		 * For degugging purposes only.
		 */
		$detailed_shipping_cost = array(
			'placo_present'         => $cart_has_plasterboard ? 'Oui' : 'Non',
			'vrac_poids'            => $vrac_weight ?: 0,
			'vrac_prix'             => $vrac_shipping_cost ?: 0,
			'isolation_poids'       => $isolation_weight ?: 0,
			'isolation_prix'        => $isolation_shipping_cost ?: 0,
			'autres_produits_poids' => $other_products_weight ?: 0,
			'autres_produits_prix'  => $other_products_shipping_cost ?: 0,
			'total_ht'              => $total_shipping_cost ?: 0,
			'total_ttc'             => $total_shipping_cost * 1.2 ?: 0,
		);

		// Convertir le tableau en chaîne JSON pour le stockage dans le cookie.
		$cookie_value = wp_json_encode( $detailed_shipping_cost );

		// Enregistrer le cookie avec la durée de vie correcte (24 heures à partir de maintenant).
		setcookie( sanitize_title( 'km_shipping_cost_' . $shipping_method_name ), $cookie_value, time() + 60 * 60 * 24 * 30, '/' );

		return $total_shipping_cost;
	}

	/**
	 *
	 * Calcule le prix de la livraison en fonction du poids du panier.
	 *
	 * @param float  $weight Le poids total du panier.
	 * @param string $shipping_method_name Le nom de la méthode de livraison.
	 * @return float Le prix de la livraison.
	 */
	private function calculate_shipping_for_product( $weight, $shipping_method_name ) {
		$remaining_weight = $weight / 1000; // Convertir en tonnes.

		// Utiliser les mêmes classes de poids que dans calculate_shipping_cost_based_on_weight.
		$weight_classes = array(
			'45 A 60 T' => array( 45, 60 ),
			'38 A 45 T' => array( 38, 45 ),
			'32 A 38 T' => array( 32, 38 ),
			'15 A 30 T' => array( 15, 30 ),
			'8 A 15 T'  => array( 8, 15 ),
			'2 A 8 T'   => array( 2, 8 ),
			'0 A 2 T'   => array( 0, 2 ),
		);

		$total_price = 0;

		foreach ( $weight_classes as $weight_class => $range ) {
			if ( $remaining_weight > $range[1] ) {
				$times                     = ceil( $remaining_weight / $range[1] );
				$delivery_option_full_name = $this->km_shipping_zone->shipping_zone_name . ' ' . $shipping_method_name . ' - ' . $weight_class;
				$total_price              += $times * $this->get_shipping_price( $delivery_option_full_name );
				$remaining_weight         %= $range[1];
			} elseif ( $remaining_weight >= $range[0] && $remaining_weight <= $range[1] ) {
				$delivery_option_full_name = $this->km_shipping_zone->shipping_zone_name . ' ' . $shipping_method_name . ' - ' . $weight_class;
				$total_price              += $this->get_shipping_price( $delivery_option_full_name );
				break;
			}
		}

		return $total_price;
	}

	/**
	 * Vérifie si un produit appartient à la catégorie 'isolation'.
	 *
	 * @param WC_Product $product Le produit à vérifier.
	 * @return bool Vrai si le produit est dans la catégorie 'isolation', faux sinon.
	 */
	private function is_isolation_product( $product ) {
		return has_term( 'isolation', 'product_cat', $product->get_id() );
	}

	/**
	 * Vérifie si le panier contient une plaque de platre
	 *
	 * @param array $cart_items Les articles du panier.
	 * @return bool Vrai si le panier contient une plaque de platre, faux sinon.
	 */
	private function cart_has_plasterboard( $cart_items ) {
		// Find if cart has plasterboard.
		foreach ( $cart_items as $cart_item ) {
			$product = $cart_item['data'];

			if ( strpos( $product->get_name(), 'Plaque de plâtre' ) !== false ) {
				return true;
			}
		}
		return false;
	}
}
