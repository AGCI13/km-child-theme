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
	 * Ajoute les méthodes de livraison
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
	 * @param string $shipping_method_id The name of the shipping method.
	 * @param string $shipping_method_name The ID of the shipping method.
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
			if ( stripos( $product_name, 'benne' ) === false ) {
				$total_weight += $product_weight;
			}

			if ( stripos( $product_name, 'vrac' ) !== false ) {
				++$vrac_count;
			} elseif ( stripos( $product_name, 'Plaque de plâtre' ) !== false ) {
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

		$shipping_product = $this->get_shipping_product( $delivery_option_full_name );

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
	 * Calcule le prix de la livraison en fonction du poids du panier.
	 *
	 * @param string $shipping_product_name Le nom du produit de livraison.
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
	 * @param float $total_weight Le poids total du panier.
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

	/**
	 * Filtre les méthodes d'expédition en fonction des produits dans le panier.
	 *
	 * @param array $rates Les méthodes d'expédition.
	 * @param array $package Les informations sur le panier.
	 * @return array
	 */
	public function filter_shipping_methods( $rates, $package ) {

		$only_included_shipping_products = true;
		$only_included_big_bag           = true;
		$only_included_geotextile        = true;
		$only_location_bennes            = true;
		$only_included_echantillons      = true;

		$i = 0;
		foreach ( $package['contents'] as $item ) {
			$product = $item['data'];

			$is_location_big_bag = km_product_has_category( $product, 'location-big-bag' );

			if ( ! $is_location_big_bag ) {
				$only_included_big_bag = false;
			}

			$is_location_bennes = km_product_has_category( $product, 'location-bennes' );

			if ( ! $is_location_bennes ) {
				$only_location_bennes = false;
			}

			$is_geotextile = km_check_product_name( $product->get_name(), 'géotextile' );

			if ( ! $is_geotextile ) {
				$only_included_geotextile = false;
			}

			$is_echantillons = km_product_has_category( $product, 'echantillons' );
			if ( ! $is_echantillons ) {
				$only_included_echantillons = false;
			}

			if ( ! $is_location_big_bag && ! $is_location_bennes && ! $is_geotextile && ! $is_echantillons ) {
				$only_included_shipping_products = false;
			}
		}

		foreach ( $rates as $rate_id => $rate ) {

			if ( ( $only_included_shipping_products && ! in_array( $rate_id, array( 'included', 'drive' ), true ) ) ||
			( ! $only_included_shipping_products && ( 'included' === $rate_id ) ) ) {
				unset( $rates[ $rate_id ] );
			}

			if ( ! $only_location_bennes && 'dumpster' === $rate_id ) {
				unset( $rates[ $rate_id ] );
			}

			if ( $only_location_bennes && 'drive' === $rate_id ) {
				unset( $rates[ $rate_id ] );
			}

			if ( $only_included_big_bag && 'included' === $rate_id ) {
				$rate->label = 'Livraison par Colissimo';
			}
		}

		return $rates;
	}
}
