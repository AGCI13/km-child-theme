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
		$methods['shipping_method_1']         = 'Shipping_method_1';
		$methods['shipping_method_1_express'] = 'Shipping_method_1_express';
		$methods['shipping_method_2']         = 'Shipping_method_2';
		$methods['shipping_method_2_express'] = 'Shipping_method_2_express';
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
		$cart_items                   = WC()->cart->get_cart();
		$total_shipping_cost          = 0.0;
		$vrac_weight                  = 0.0;
		$isolation_weight             = 0.0;
		$other_products_weight        = 0.0;
		$isolation_plasterboard_found = false;

		foreach ( $cart_items as $cart_item ) {
			$product        = $cart_item['data'];
			$product_weight = $product->get_weight() * $cart_item['quantity'];

			if ( strpos( $product->get_name(), 'VRAC A LA TONNE' ) !== false ) {
				$vrac_weight += $product_weight;
			} elseif ( $this->is_isolation_product( $product ) ) {
				$isolation_weight += $product_weight;
				if ( $this->is_plasterboard( $product ) ) {
					$isolation_plasterboard_found = true;
				}
			} else {
				$other_products_weight += $product_weight;
			}
		}

		// Debug.
		error_log( '======================== NEW BATCH ===================' );
		error_log( 'VRAC weight: ' . $vrac_weight );
		error_log( 'Isolation weight: ' . $isolation_weight );
		error_log( 'Other products weight: ' . $other_products_weight );

		if ( $vrac_weight > 0 ) {
			$vrac_shipping_cost = $this->calculate_shipping_for_product( $vrac_weight, $shipping_method_name );
			error_log( 'VRAC shipping cost: ' . $vrac_shipping_cost );
			$total_shipping_cost += $vrac_shipping_cost;
		}

		if ( $isolation_weight > 0 ) {
			$isolation_shipping_cost = $this->calculate_shipping_for_product( $isolation_weight, $shipping_method_name );
			error_log( 'Isolation shipping cost: ' . $isolation_shipping_cost );
			$total_shipping_cost += $isolation_shipping_cost;
		}

			// Calculer et ajouter les coûts de livraison pour les produits d'isolation, si nécessaire.
		if ( $isolation_plasterboard_found && $isolation_weight > 0 ) {
			$isolation_cost       = $this->calculate_shipping_for_product( $isolation_weight, $shipping_method_name );
			$total_shipping_cost += $isolation_cost;
			error_log( 'Other products shipping cost: ' . $isolation_cost );
		}

		error_log( 'Total shipping cost: ' . $total_shipping_cost );
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
		$remaining_weight = ceil( $weight / 1000 ); // Convertir en tonnes.

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
				$times                     = floor( $remaining_weight / $range[1] );
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
	 * Ajoute le prix de la livraison en tant que frais.
	 *
	 * @param WC_Cart $cart Le panier.
	 */
	public function add_shipping_cost_as_fee( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// S'assurer que cette action n'est exécutée qu'une seule fois.
		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		if ( 'local_pickup_plus' === WC()->session->get( 'chosen_shipping_methods' )[0]
		|| ! $this->km_shipping_zone->is_in_thirteen()
		|| $cart->get_shipping_total() <= 0 ) {
			return;
		}
		// Frais de port hors TVA.
		$shipping_total = $cart->get_shipping_total();

		$cart->add_fee( __( 'Frais de livraison', 'kingmateriaux' ), $shipping_total, true, 'standard' );
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
	 * Vérifie si un produit est une plaque de plâtre.
	 *
	 * @param WC_Product $product Le produit à vérifier.
	 * @return bool Vrai si le produit est une plaque de plâtre, faux sinon.
	 */
	private function is_plasterboard( $product ) {
		return strpos( $product->get_name(), 'Plaque de plâtre' ) !== false;
	}
}
