<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
class KM_Shipping_Methods {

	use SingletonTrait;

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
	 * KM_Shipping_Methods constructor.
	 */
	public function __construct() {
		$this->register();
	}

	/**
	 * Register hooks.
	 */
	public function register() {
		add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_methods' ), 10, 1 );
		add_filter( 'woocommerce_package_rates', array( $this, 'filter_shipping_methods' ), 99, 2 );
	}

	/**
	 * Ajoute les méthodes de livraison.
	 *
	 * @param array $methods Les méthodes de livraison.
	 * @return array
	 */
	public function add_shipping_methods( $methods ) {
		$methods['option1']        = 'Shipping_method_1';
		$methods['option1express'] = 'Shipping_method_1_express';
		$methods['option2']        = 'Shipping_method_2';
		$methods['option2express'] = 'Shipping_method_2_express';
		$methods['drive']          = 'Shipping_method_drive';
		$methods['out13']          = 'Shipping_method_out_13';
		$methods['dumpster']       = 'Shipping_method_dumpster';
		$methods['included']       = 'Shipping_method_included';
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
	 * @param string $shipping_method_id The ID of the shipping method.
	 * @param string $shipping_method_name The name of the shipping method.
	 *
	 * @return float Total shipping cost.
	 */
	public function calculate_shipping_method_price( $shipping_method_id, $shipping_method_name ) {
		$cart_items = WC()->cart->get_cart();

		if ( ! $cart_items ) {
			return array( 'price_excl_tax' => 0 );
		}

		$total_weight          = 0;
		$vrac_count            = 0;
		$other_product_count   = 0;
		$cart_has_plasterboard = false;

		foreach ( $cart_items as $cart_item ) {
			$product        = $cart_item['data'];
			$product_name   = $product->get_name();
			$product_weight = (int) $product->get_weight() * $cart_item['quantity'];

			// Vérifiez si le produit n'est pas une 'benne'.
			if ( false === stripos( $product_name, 'benne' ) ) {
				$total_weight += $product_weight;
			}

			if ( false !== stripos( $product_name, 'vrac' ) ) {
				++$vrac_count;
			} elseif ( false !== stripos( $product_name, 'Plaque de plâtre' ) ) {
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
		$delivery_option_full_name = km_get_shipping_zone_name() . ' ' . $shipping_method_name . ' - ' . $weight_class_name;

		$shipping_product = km_get_product_from_title( $delivery_option_full_name );

		if ( ! $shipping_product ) {
			return array( 'price_excl_tax' => 0 );
		}

		$shipping_price_excluding_taxes = wc_get_price_excluding_tax( $shipping_product );
		$shipping_price_including_taxes = wc_get_price_including_tax( $shipping_product );

		// Traitement spécifique selon la méthode de livraison et le besoin de plusieurs camions.
		if ( in_array( $shipping_method_id, array( 'option2', 'option2express' ), true ) && ! $multiple_trucks && ! $multiple_trucks_only ) {
			return array( 'price_excl_tax' => 0 );
		}

		if ( in_array( $shipping_method_id, array( 'option1', 'option1express' ), true ) && $multiple_trucks_only ) {
			return array( 'price_excl_tax' => 0 );
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
	 * @param float $total_weight Le poids total du panier.
	 * @return string Description de la méthode de livraison.
	 */
	private function get_weight_class_index( $total_weight ) {

		$total_tons = $total_weight / 1000; // Convertir en tonnes.

		// if total weight is > 60 tonnes then return.
		if ( 60 < $total_tons ) {
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

	/**
	 * Récupère les méthodes de livraison autorisées pour un produit ou une variation.
	 *
	 * @param WC_Product $product Le produit ou la variation.
	 * @return array Les méthodes de livraison autorisées.
	 */
	private function get_allowed_shipping_methods( $product ) {
		// Check if the product is a variation
		if ( $product->is_type( 'variation' ) ) {
			$variation_id               = $product->get_id();
			$shipping_methods_variation = get_post_meta( $variation_id, '_product_shipping_methods', true );
			if ( ! empty( $shipping_methods_variation ) && is_array( $shipping_methods_variation ) ) {
				return $shipping_methods_variation;
			}
		}

		$product_id               = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		$shipping_methods_product = get_post_meta( $product_id, '_product_shipping_methods', true );
		if ( ! empty( $shipping_methods_product ) && is_array( $shipping_methods_product ) ) {
			return $shipping_methods_product;
		}

		return array();
	}


	/**
	 * Filtre les méthodes d'expédition en fonction des produits dans le panier.
	 *
	 * @param array $rates Les méthodes d'expédition.
	 * @param array $package Les informations sur le panier.
	 * @return array
	 */
	public function filter_shipping_methods( $rates, $package ) {
		$allowed_methods = array();

		foreach ( $package['contents'] as $item ) {
			$product                  = $item['data'];
			$product_shipping_methods = $this->get_allowed_shipping_methods( $product );

			if ( ! empty( $product_shipping_methods ) && is_array( $product_shipping_methods ) ) {
				$allowed_methods = array_merge( $allowed_methods, $product_shipping_methods );
			}
		}

		// Si aucune méthode autorisée n'est spécifiée, ne filtre pas les méthodes.
		if ( empty( $allowed_methods ) ) {
			return $rates;
		}

		$allowed_methods = array_unique( $allowed_methods );

		foreach ( $rates as $rate_id => $rate ) {
			if ( ! in_array( $rate->method_id, $allowed_methods, true ) ) {
				unset( $rates[ $rate_id ] );
			}
		}

		return $rates;
	}
}
