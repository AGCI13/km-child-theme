<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles dynamic pricing based on shipping zones and classes in WooCommerce.
 */

class KM_Dynamic_Pricing {

	/**
	 * The single instance of the class.
	 *
	 * @var KM_Dynamic_Pricing|null
	 */

	use SingletonTrait;

	/**
	 *  The shipping zone instance.
	 *
	 *  @var KM_Shipping_zone|null
	 */
	public $km_shipping_zone;

	/**
	 * The ecotaxe rate.
	 *
	 * @var float
	 */
	public $ecotaxe_rate = 0.50;

	/**
	 * The message to display when the product is not available in the shipping zone.
	 *
	 * @var string
	 */
	public $unavailable_message;

	/**
	 * Constructor.
	 *
	 * The constructor is protected to prevent creating a new instance from outside
	 * and to prevent creating multiple instances through the `new` keyword.
	 */
	private function __construct() {
		$this->km_shipping_zone    = KM_Shipping_zone::get_instance();
		$this->unavailable_message = __( 'Ce produit n\'est pas disponible dans votre zone de livraison', 'kingmateriaux' );
		$this->register();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	private function register() {

		if ( ! $this->km_shipping_zone->is_in_thirteen() ) {

			// Hook pour le produit simple.
			add_filter( 'woocommerce_product_get_price', array( $this, 'change_product_price_based_on_shipping_zone' ), 10, 2 );

			// Hook pour les variations de produit.
			add_filter( 'woocommerce_product_variation_get_price', array( $this, 'change_product_price_based_on_shipping_zone' ), 10, 2 );

			add_filter( 'woocommerce_variation_prices', array( $this, 'change_variation_prices_based_on_shipping_zone' ), 80, 3 );

			add_filter( 'woocommerce_is_purchasable', array( $this, 'no_shipping_class_is_purchasable' ), 10, 2 );

			add_filter( 'woocommerce_get_price_html', array( $this, 'change_price_html' ), 10, 2 );

			// Décommenter la ligne ci dessous afin de masquer les produits lorsqu'ils n'ont pas de classe de livraison
			// add_action( 'pre_get_posts', array( $this, 'hide_products_out_thirteen' ), 10, 1 );

			add_filter( 'woocommerce_available_variation', array( $this, 'disable_variation_if_no_shipping_product' ), 10, 3 );

		}
		add_action( 'wp', array( $this, 'set_prices_on_zip_or_zone_missing' ) );
	}

	/**
	 * Désactive la variation si aucun produit de livraison n'est disponible.
	 *
	 * @param array                $variation_data Les données de la variation.
	 * @param WC_Product           $product Le produit.
	 * @param WC_Product_Variation $variation La variation.
	 * @return array Les données de la variation.
	 */
	public function disable_variation_if_no_shipping_product( $variation_data, $product, $variation ) {

		// Vérifier le prix du produit de livraison.
		if ( ! $this->check_shipping_product_price( $variation ) ) {
			// Désactiver la variation si aucun produit de livraison n'est disponible ou si le prix est 0.
			$variation_data['is_sold_individually'] = 'yes';
			$variation_data['is_purchasable']       = false;
			$variation_data['variation_is_active']  = false;
		}

		return $variation_data;
	}

	/**
	 * Change le prix du produit en fonction de la zone de livraison.
	 *
	 * @return void
	 */
	public function set_prices_on_zip_or_zone_missing() {
		if ( $this->km_shipping_zone->zip_code && $this->km_shipping_zone->shipping_zone_id ) {
			return;
		}
		add_filter( 'woocommerce_is_purchasable', '__return_false' );
		add_filter( 'woocommerce_get_price_html', array( $this, 'display_required_postcode_message' ), 99, 2 );
	}

	/**
	 * Change le prix du produit en fonction de la zone de livraison.
	 *
	 * @param float      $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @return float Le prix du produit.
	 */
	public function change_product_price_based_on_shipping_zone( $price, $product ) {

		if ( empty( $price ) || ! $this->has_shipping_class( $product ) ) {
			error_log( 'Method change_product_price_based_on_shipping_zone ( $price, $product )  : Price is empty or product has no shipping class' );
			return $price;
		}

		$shipping_product = $this->km_shipping_zone->get_related_shipping_product( $product );

		if ( ! $shipping_product instanceof WC_Product ) {
			return $price;
		}

		$shipping_price = $shipping_product->get_price( 'edit' );

		if ( $shipping_price && is_numeric( $shipping_price ) ) {
			$price += $shipping_price;
		}

		return $price;
	}


	/**
	 * Change les prix des variations de produit en fonction de la zone de livraison.
	 *
	 * @param array $prices Les prix des variations de produit.
	 */
	public function change_variation_prices_based_on_shipping_zone( $prices, $product, $for_display ) {
		foreach ( $prices as $price_type => $variation_prices ) {
			foreach ( $variation_prices as $variation_id => $price ) {
				$variation_prices[ $variation_id ] = $this->change_product_price_based_on_shipping_zone( $price, wc_get_product( $variation_id ) );
			}
			$prices[ $price_type ] = $variation_prices;
		}
		return $prices;
	}

	/**
	 * Affiche un message au lieu du prix du produit.
	 *
	 * @param string $price Le prix du produit.
	 */
	public function change_price_html( $price, $product ) {
		if ( ! $product->is_type( 'variable' ) ) {
			// Pour les produits simple, continuez avec la logique existante.
			if ( ! $product->get_shipping_class_id() ) {
				return $this->unavailable_message;
			}
		}
		return $price;
	}

	/**
	 * Rend le produit non achetable si il n'a pas de classe de livraison.
	 *
	 * @param bool $is_purchasable Si le produit est achetable ou non.
	 */
	public function no_shipping_class_is_purchasable( $is_purchasable, $product ) {
		if ( ! $this->has_shipping_class( $product ) ) {
			return false;
		}
		return $is_purchasable;
	}

	/**
	 * @param WC_Product $product Le produit.
	 *
	 * @return bool Si le produit a une classe de livraison ou non.
	 */
	private function has_shipping_class( $product ) {
		$shipping_class = $product->get_shipping_class();
		return ! empty( $shipping_class );
	}

		/**
		 * Vérifie si une variation de produit a un produit de livraison associé avec un prix supérieur à 0€.
		 *
		 * @param WC_Product $variation Le produit (variation) à vérifier.
		 * @return bool Retourne true si un produit de livraison existe et que son prix est supérieur à 0€, false sinon.
		 */
	public function check_shipping_product_price( $variation ) {
		// Obtient le produit de livraison associé.
		$shipping_product = $this->km_shipping_zone->get_related_shipping_product( $variation );

		// Vérifie si le produit de livraison existe et si son prix est supérieur à 0€.
		if ( $shipping_product && $shipping_product->get_price() > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Masque les produits dont la classe de livraison n'est pas dans le département du 13
	 *
	 * @param WP_Query $query La requête principale.
	 */
	public function hide_products_out_thirteen( $query ) {

		// Ne pas modifier les requêtes dans l'administration ou qui ne sont pas la requête principale.
		if ( is_admin() || $query->get( 'post_type' ) !== 'product' ) {
			return;
		}

		// Obtenir tous les termes de la classe d'expédition.
		$shipping_class_ids = get_terms(
			array(
				'taxonomy'   => 'product_shipping_class',
				'fields'     => 'ids',
				'hide_empty' => false,
			)
		);

		// S'il n'y a pas de classes d'expédition, ne rien faire.
		if ( empty( $shipping_class_ids ) ) {
			return;
		}

		// Modifier la requête pour exclure les produits sans classe d'expédition.
		$tax_query = (array) $query->get( 'tax_query' );

		$tax_query[] = array(
			'taxonomy' => 'product_shipping_class',
			'field'    => 'term_id',
			'terms'    => $shipping_class_ids,
			'operator' => 'IN',
		);

		$query->set( 'tax_query', $tax_query );
	}

	/**
	 * Affiche un message au lieu du prix du produit.
	 *
	 * @param string     $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @return string Le prix du produit.
	 */
	public function display_required_postcode_message( $price, $product ) {

		// Vous pouvez ajouter une condition pour vérifier si un code postal a été entré ou non.
		// Si aucun code postal n'est entré, affichez le message.
		if ( ! isset( $_COOKIE['zip_code'] ) || empty( $_COOKIE['zip_code'] ) ) {
			return __( 'L\'affichage du prix requiert un code postal', 'kingmateriaux' );
		}
		// Sinon, retournez le prix habituel.
		return $price;
	}

	/**
	 * Vérifie si le produit est un big bag ou un vrac à la tonne.
	 *
	 * @param string $item_name Le nom du produit.
	 * @return bool Si le produit est un big bag ou un vrac à la tonne.
	 */
	public function product_is_bulk_or_bigbag( $item_name ) {

		if ( false !== stripos( $item_name, 'big bag' ) || false !== stripos( $item_name, 'vrac a la tonne' ) ) {
			return true;
		}

		return false;
	}
}
