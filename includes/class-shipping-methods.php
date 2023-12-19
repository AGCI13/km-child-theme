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
		add_filter( 'woocommerce_package_rates', array( $this, 'save_option1_shipping_cost' ), 10, 2 );
	}

	/**
	 * Weight classes.
	 *
	 * @var array
	 */
	public $weight_classes = array(
		'45 A 60 T' => array( 45, 60 ),
		'38 A 45 T' => array( 38, 45 ),
		'32 A 38 T' => array( 32, 38 ),
		'15 A 30 T' => array( 15, 30 ),
		'8 A 15 T'  => array( 8, 15 ),
		'2 A 8 T'   => array( 2, 8 ),
		'0 A 2 T'   => array( 0, 2 ),
	);


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
	public function calculate_shipping_method_price( $shipping_method_id, $shipping_method_name ) {
		$cart_items = WC()->cart->get_cart();

		if ( ! $cart_items ) {
			return 0;
		}

		$total_shipping_cost   = 0;
		$vrac_weight           = 0.0;
		$isolation_weight      = 0.0;
		$other_products_weight = 0.0;
		$total_trucks          = 0;
		$total_weight          = 0;

		$calculated_vrac_shipping      = array(
			'cost'    => 0,
			'package' => 0,
		);
		$calculated_isolation_shipping = array(
			'cost'    => 0,
			'package' => 0,
		);
		$calculated_other_shipping     = array(
			'cost'    => 0,
			'package' => 0,
		);

		$cart_has_plasterboard = $this->cart_has_plasterboard( $cart_items );

		foreach ( $cart_items as $cart_item ) {
			$product        = $cart_item['data'];
			$product_weight = (int) $product->get_weight() * $cart_item['quantity'];
			$total_weight  += (int) $product_weight;
			if ( strpos( $product->get_name(), 'VRAC A LA TONNE' ) !== false ) {
				$vrac_weight += $product_weight;
			} elseif ( $cart_has_plasterboard && $this->is_isolation_product( $product ) ) {
				$isolation_weight += $product_weight;
			} else {
				$other_products_weight += $product_weight;
			}
		}

		if ( $vrac_weight > 0 ) {
			$calculated_vrac_shipping = $this->calculate_shipping_info( $vrac_weight, $shipping_method_name );
			$total_trucks            += $calculated_vrac_shipping['package'];
			$total_shipping_cost     += $calculated_vrac_shipping['cost'];
		}

		if ( $isolation_weight > 0 ) {
			$calculated_isolation_shipping = $this->calculate_shipping_info( $isolation_weight, $shipping_method_name );
			$total_trucks                 += $calculated_isolation_shipping['package'];
			$total_shipping_cost          += $calculated_isolation_shipping['cost'];
		}

		if ( $other_products_weight > 0 ) {
			$calculated_other_shipping = $this->calculate_shipping_info( $other_products_weight, $shipping_method_name );
			$total_trucks             += $calculated_other_shipping['package'];
			$total_shipping_cost      += $calculated_other_shipping['cost'];
		}

		error_log( '------------------------------' );
		error_log( 'total_shipping_cost avant :' . $total_shipping_cost );

		/**
		* For degugging purposes only.
		*/
		$detailed_shipping_cost = array(
			'nbr_camion'            => $total_trucks ?: 0,
			'poids_total'           => $total_weight ?: 0,
			'placo_present'         => $cart_has_plasterboard ? 'Oui' : 'Non',
			'vrac_poids'            => $vrac_weight ?: 0,
			'vrac_prix'             => $calculated_vrac_shipping['cost'] ?: 0,
			'isolation_poids'       => $isolation_weight ?: 0,
			'isolation_prix'        => $calculated_isolation_shipping['cost'] ?: 0,
			'autres_produits_poids' => $other_products_weight ?: 0,
			'autres_produits_prix'  => $calculated_other_shipping['cost'] ?: 0,
			'total_livraison_ht'    => $total_shipping_cost ?: 0,
			'total_livraison_ttc'   => $total_shipping_cost * 1.2 ?: 0,
		);

		// Convertir le tableau en chaîne JSON pour le stockage dans le cookie.
		$cookie_value = wp_json_encode( $detailed_shipping_cost );

		// Enregistrer le cookie avec la durée de vie correcte (24 heures à partir de maintenant).
		setcookie( sanitize_title( 'km_shipping_cost_' . $shipping_method_name ), $cookie_value, time() + 60 * 60 * 24 * 30, '/' );

		error_log( 'shipping_method_id : ' . $shipping_method_id );
		error_log( 'total_weight : ' . $total_weight );
		error_log( 'total_trucks : ' . $total_trucks );

		// Simplifier les conditions pour définir $total_shipping_cost.
		if ( ( in_array( $shipping_method_id, array( 'option2', 'option2express' ) ) && $total_weight <= 2000 && 1 === $total_trucks ) ) {
			$total_shipping_cost = 0;
		}

		if ( in_array( $shipping_method_id, array( 'option1', 'option1express' ) ) && $total_trucks > 1 ) {
			$total_shipping_cost = 0;
		}

		error_log( 'total_shipping_cost : ' . $total_shipping_cost );

		if ( 'option1' === $shipping_method_id || 'option1express' === $shipping_method_id ) {
			$shipping_method_info['weight_class'] = $this->get_shipping_description( $total_weight );
		}

		$shipping_method_info['cost'] = $total_shipping_cost;

		return $shipping_method_info;
	}

	/**
	 * Calcule le poids total du panier.
	 *
	 * @param array $cart_items Les articles du panier.
	 * @return float Le poids total du panier.
	 */
	private function calculate_total_weight( $cart_items ) {
		$total_weight = 0;
		foreach ( $cart_items as $cart_item ) {
			$product       = $cart_item['data'];
			$total_weight += (int) $product->get_weight() * $cart_item['quantity'];
		}
		return $total_weight;
	}

	/**
	 * Calcule les informations de livraison en fonction du poids total.
	 *
	 * @param float  $weight Le poids total du panier.
	 * @param string $shipping_method_name Nom de la méthode de livraison.
	 * @return array Informations sur le coût de livraison.
	 */
	private function calculate_shipping_info( $weight, $shipping_method_name ) {
		$remaining_weight = $weight / 1000; // Convertir en tonnes.

		$total_price  = 0;
		$total_trucks = 0;

		foreach ( $this->weight_classes as $weight_class => $range ) {

			if ( $remaining_weight > $range[1] ) {
				$times                     = ceil( $remaining_weight / $range[1] );
				$delivery_option_full_name = $this->km_shipping_zone->shipping_zone_name . ' ' . $shipping_method_name . ' - ' . $weight_class;
				$shipping_price            = $this->get_shipping_price( $delivery_option_full_name );
				$total_price              += $times * $shipping_price;
				$remaining_weight         %= $range[1];
				$total_trucks             += $times;

				// error_log( "Weight is greater than {$range[1]}. Adding {$times} packages. Each package costs {$shipping_price}. New total is {$total_price}. Remaining weight is {$remaining_weight}." );

			} elseif ( $remaining_weight > $range[0] && $remaining_weight <= $range[1] ) {
				$delivery_option_full_name = $this->km_shipping_zone->shipping_zone_name . ' ' . $shipping_method_name . ' - ' . $weight_class;
				$shipping_price            = $this->get_shipping_price( $delivery_option_full_name );
				$total_price              += $shipping_price;
				$total_trucks             += 1;
				// error_log( "Weight is between {$range[0]} and {$range[1]}. Adding {$shipping_price}." );

				break;
			}
		}

		return array(
			'cost'    => $total_price,
			'package' => $total_trucks,
		);
	}

	/**
	 * Récupère la description de livraison basée sur l'ID de la méthode et le poids total.
	 *
	 * @param string $shipping_method_id ID de la méthode de livraison.
	 * @param float  $total_weight Le poids total du panier.
	 * @return string Description de la méthode de livraison.
	 */
	private function get_shipping_description( $total_weight ) {

		$total_tons = $total_weight / 1000; // Convertir en tonnes.

		// if total weight is > 60 tonnes then return.

		if ( $total_tons > 60 ) {
			$total_tons = 60;
		}

		// Get total weight fit into a range.
		foreach ( $this->weight_classes as $weight_class => $range ) {
			if ( $total_tons > $range[0] && $total_tons <= $range[1] ) {
				// Get the index of the current weight class.
				return array_search( $weight_class, array_keys( $this->weight_classes ) );
			}
		}
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

	public function save_option1_shipping_cost( $rates, $package ) {
		$specific_method_id = 'option1';

		foreach ( $rates as $rate_id => $rate ) {
			if ( strpos( $rate_id, $specific_method_id ) !== false ) {
				// Stocker le coût dans la session de WooCommerce
				WC()->session->set( 'option1_shipping_cost', $rate->get_cost() );
				break;
			}
		}
		return $rates;
	}
}
