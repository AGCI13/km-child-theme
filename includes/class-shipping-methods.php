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
		'30 A 32 T' => array( 30, 32 ),
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
	 * Calculate shipping cost based on weight for 'VRAC A LA TONNE' products.
	 *
	 * @param string $shipping_method_name The name of the shipping method.
	 * @return float Total shipping cost.
	 */
	public function calculate_shipping_method_price( $shipping_method_id, $shipping_method_name ) {
		$cart_items = WC()->cart->get_cart();

		if ( ! $cart_items ) {
			return array( 'cost' => 0 );
		}

		$vrac_count            = 0;
		$other_product_count   = 0;
		$cart_has_plasterboard = false;
		$total_weight          = 0;

		foreach ( $cart_items as $cart_item ) {
			$product        = $cart_item['data'];
			$product_weight = (int) $product->get_weight() * $cart_item['quantity'];
			$total_weight  += $product_weight;

			if ( stripos( $product->get_name(), 'vrac' ) !== false ) {
				++$vrac_count;
			} elseif ( strpos( $product->get_name(), 'Plaque de plâtre' ) !== false ) {
				$cart_has_plasterboard = true;
			} else {
				++$other_product_count;
			}
		}

		// Détermination de la nécessité d'utiliser plusieurs camions.
		$multiple_trucks      = $vrac_count > 1 || $cart_has_plasterboard || $total_weight > 2000;
		$multiple_trucks_only = $cart_has_plasterboard && ( $vrac_count > 0 || $other_product_count > 0 ) || $vrac_count > 1;

		$weight_index              = $this->get_weight_class_index( $total_weight );
		$weight_class_name         = array_keys( $this->weight_classes )[ $weight_index ];
		$delivery_option_full_name = $this->km_shipping_zone->shipping_zone_name . ' ' . $shipping_method_name . ' - ' . $weight_class_name;

		$shipping_product = $this->get_shipping_product( $delivery_option_full_name );

		if ( ! $shipping_product ) {
			$this->debug_shipping_vars( $total_weight, $multiple_trucks, $cart_has_plasterboard, 0, 0, $shipping_method_name );
			return array( 'price_incl_tax' => 0 );
		}

		$shipping_price_excluding_taxes = wc_get_price_excluding_tax( $shipping_product );
		$shipping_price_including_taxes = wc_get_price_including_tax( $shipping_product );

		$this->debug_shipping_vars( $total_weight, $multiple_trucks, $cart_has_plasterboard, $shipping_price_excluding_taxes, $shipping_price_including_taxes, $shipping_method_name );

		// Traitement spécifique selon la méthode de livraison et le besoin de plusieurs camions.
		if ( in_array( $shipping_method_id, array( 'option2', 'option2express' ), true ) && ! $multiple_trucks && ! $multiple_trucks_only ) {
			return array( 'price_incl_tax' => 0 );
		}

		if ( in_array( $shipping_method_id, array( 'option1', 'option1express' ), true ) && $multiple_trucks_only ) {
			return array( 'price_incl_tax' => 0 );
		}

		return array(
			'price_excl_tax' => $shipping_price_excluding_taxes,
			'price_incl_tax' => $shipping_price_including_taxes,
			'tax_amount'     => $shipping_price_including_taxes - $shipping_price_excluding_taxes,
			'ugs'            => $shipping_product->get_sku(),
			'weight_class'   => $weight_index,
		);
	}

	/**
	 * Debug shipping variables.
	 */
	private function debug_shipping_vars( $total_weight, $multiple_trucks, $cart_has_plasterboard, $shipping_price_excluding_taxes, $shipping_price_including_taxes, $shipping_method_name ) {

		/**
		* For degugging purposes only.
		*/
		$detailed_shipping_cost = array(
			'poids_total'         => $total_weight,
			'multiple_camions'    => $multiple_trucks ? 'Oui' : 'Non',
			'placo_present'       => $cart_has_plasterboard ? 'Oui' : 'Non',
			'total_livraison_ht'  => $shipping_price_excluding_taxes,
			'total_livraison_ttc' => $shipping_price_including_taxes,
		);

		// Convertir le tableau en chaîne JSON pour le stockage dans le cookie.
		$cookie_value = wp_json_encode( $detailed_shipping_cost );

		// Enregistrer le cookie avec la durée de vie correcte (24 heures à partir de maintenant).
		setcookie( sanitize_title( 'km_shipping_cost_' . $shipping_method_name ), $cookie_value, time() + 60 * 60 * 24 * 30, '/' );
	}

		/**
		 * Calcule le prix de la livraison en fonction du poids du panier.
		 *
		 * @param string $delivery_option_full_name Le nom du produit de livraison.
		 * @return object le produit de livraison
		 */
	private function get_shipping_product( $shipping_product_name ) {

		if ( ! $shipping_product_name ) {
			return;
		}

		// Récupérer le produit de livraison associé.
		$args = array(
			'fields'         => 'ids', // Ce qu'on demande à recupérer.
			'post_type'      => 'product',
			'post_status'    => array( 'private' ),
			'posts_per_page' => 1,
			'title'          => $shipping_product_name,
			'exact'          => true,
		);

		$shipping_products_posts = get_posts( $args );

		if ( ! $shipping_products_posts ) {
			return;
		}

		return wc_get_product( $shipping_products_posts[0] );
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
		 * @param float $weight Le poids total du panier.
		 * @return array Informations sur le coût de livraison.
		 */
	private function calculate_trucks_number( $weight ) {
		$remaining_weight = $weight / 1000; // Convertir en tonnes.
		$total_trucks     = 0;

		foreach ( $this->weight_classes as  $range ) {

			if ( $remaining_weight > $range[1] ) {
				$times         = ceil( $remaining_weight / $range[1] );
				$total_trucks += $times;
			} elseif ( $remaining_weight > $range[0] && $remaining_weight <= $range[1] ) {
				++$total_trucks;
				break;
			}
		}

		return $total_trucks;
	}

		/**
		 * Récupère la description de livraison basée sur l'ID de la méthode et le poids total.
		 *
		 * @param string $shipping_method_id ID de la méthode de livraison.
		 * @param float  $total_weight Le poids total du panier.
		 * @return string Description de la méthode de livraison.
		 */
	private function get_weight_class_index( $total_weight ) {

		$total_tons = $total_weight / 1000; // Convertir en tonnes.

		// if total weight is > 60 tonnes then return.
		if ( $total_tons > 60 ) {
			$total_tons = 60;
		}

		// Get total weight fit into a range.
		foreach ( $this->weight_classes as $weight_class => $range ) {
			if ( $total_tons > $range[0] && $total_tons <= $range[1] ) {
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

	public function save_option1_shipping_cost( $rates, $package ) {
		$specific_method_id = 'option1';

		foreach ( $rates as $rate_id => $rate ) {
			if ( strpos( $rate_id, $specific_method_id ) !== false ) {
				if ( $rate->get_cost() && $rate->get_taxes() ) {
					$cost_including_taxes = $rate->get_cost() + array_sum( $rate->get_taxes() );
				}

				// Stocker le coût dans la session de WooCommerce.
				WC()->session->set( 'option1_shipping_cost', $cost_including_taxes );
				break;
			}
		}
		return $rates;
	}
}
