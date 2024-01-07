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
	 * The ecotaxe rate.
	 *
	 * @var float
	 */
	public $ecotaxe_rate_incl_taxes = 0.60;
	/**
	 * The ecotaxe message to display.
	 *
	 * @var string
	 */
	public $ecotaxe_info_html;

	/*
	 * The list of products that are not purchasable.
	 *
	 * @var array
	 */
	private $unpurchasable_products = array();

	/**
	 * Constructor.
	 *
	 * The constructor is protected to prevent creating a new instance from outside
	 * and to prevent creating multiple instances through the `new` keyword.
	 */
	private function __construct() {
		$this->km_shipping_zone  = KM_Shipping_zone::get_instance();
		$this->ecotaxe_info_html = '<div class="km-product-ecotaxe">' . sprintf( __( 'Dont %s d\'écotaxe', 'kingmateriaux' ), wc_price( $this->ecotaxe_rate_incl_taxes ) ) . '</div>';

		$this->register();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	private function register() {
		if ( is_admin() ) {
			return;
		}

		add_filter( 'woocommerce_is_purchasable', array( $this, 'maybe_set_product_unpurchasable' ), 10, 2 );

		add_filter( 'woocommerce_product_get_price', array( $this, 'change_product_price_based_on_shipping_zone' ), 10, 2 );
		// add_filter( 'woocommerce_product_get_regular_price', array( $this, 'change_product_price_based_on_shipping_zone' ), 50, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'change_product_price_based_on_shipping_zone' ), 10, 2 );
		// add_filter( 'woocommerce_product_variation_get_regular_price', array( $this, 'change_product_price_based_on_shipping_zone' ), 10, 2 );
		add_filter( 'woocommerce_variation_prices_price', array( $this, 'change_variation_prices_based_on_shipping_zone' ), 80, 3 );
		// add_filter( 'woocommerce_variation_prices_regular_price', array( $this, 'change_variation_prices_based_on_shipping_zone' ), 80, 3 );

		add_filter( 'woocommerce_get_price_html', array( $this, 'adjust_simple_product_price_html' ), 99, 2 );
		add_filter( 'woocommerce_variable_price_html', array( $this, 'adjust_variable_product_price_html' ), 99, 2 );
		// Décommenter la ligne ci dessous afin de masquer les produits lorsqu'ils n'ont pas de classe de livraison.
		// add_action( 'pre_get_posts', array( $this, 'hide_products_out_thirteen' ), 10, 1 );
		add_filter( 'woocommerce_available_variation', array( $this, 'disable_variation_if_no_shipping_product' ), 10, 3 );

		add_action( 'wp', array( $this, 'set_prices_on_zip_or_zone_missing' ) );
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
	 * Désactive la variation si aucun produit de livraison n'est disponible.
	 *
	 * @param array                $variation_data Les données de la variation.
	 * @param WC_Product           $product Le produit.
	 * @param WC_Product_Variation $variation La variation.
	 * @return array Les données de la variation.
	 */
	public function disable_variation_if_no_shipping_product( $variation_data, $product, $variation ) {

		if ( ( ! $this->check_shipping_product_price( $variation ) && ! $this->km_shipping_zone->is_in_thirteen() )
		|| ( $this->km_shipping_zone->is_in_thirteen() && 'yes' === get_post_meta( $variation->get_id(), '_disable_variation_in_13', true ) )
		|| ( $this->km_shipping_zone->is_in_thirteen() && false !== stripos( $product->get_name(), 'benne' ) && false === stripos( sanitize_title( $variation->get_name() ), str_replace( ' ', '-', $this->km_shipping_zone->shipping_zone_name ) ) ) ) {

			// Désactiver la variation si aucun produit de livraison n'est disponible ou si le prix est 0.
			$variation_data['is_purchasable']      = false;
			$variation_data['variation_is_active'] = false;
		}

		return $variation_data;
	}

	/**
	 * Change le prix du produit en fonction de la zone de livraison.
	 *
	 * @param float      $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @return float Le prix du produit.
	 */
	public function change_product_price_based_on_shipping_zone( $price, $product ) {

		if ( ! $this->km_shipping_zone->is_in_thirteen() ) {
			if ( empty( $price ) || ! $this->has_shipping_class( $product ) ) {
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
		}

		if ( $this->product_is_bulk_or_bigbag( $product->get_name() ) ) {
			$price += $this->ecotaxe_rate;
		}

		return $price;
	}

	/**
	 * Change les prix des variations de produit en fonction de la zone de livraison.
	 *
	 * @param array      $prices Les prix des variations de produit.
	 * @param WC_Product $product Le produit.
	 * @return array Les prix des variations de produit.
	 */
	public function change_variation_prices_based_on_shipping_zone( $price, $variation, $product ) {
		return $this->change_product_price_based_on_shipping_zone( $price, $product );
	}

	/**
	 * Ajuste le prix du produit simple.
	 *
	 * @param string     $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @return string Le prix du produit.
	 */
	public function adjust_simple_product_price_html( $price, $product ) {

		if ( $this->product_is_bulk_or_bigbag( $product->get_name() ) && is_product() ) {
			$price .= $this->ecotaxe_info_html;
		}
		return $price;
	}

	/**
	 * Ajuste le range de prix pour les produits variables.
	 *
	 * @param string     $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @return string La fourchette de prix du produit.
	 */
	public function adjust_variable_product_price_html( $price, $product ) {

		// Vérifiez si le produit est un produit variable.
		if ( ! is_product() || ! $product->is_type( 'variable' ) ) {
			return $price;
		}

		$prices      = array();
		$has_ecotaxe = false;

		// Parcourez les variations disponibles.
		foreach ( $product->get_available_variations() as $variation ) {
			$variation_obj = wc_get_product( $variation['variation_id'] );

			// Vérifiez si la variation est achetable.
			if ( $variation_obj->is_purchasable() ) {
				$prices[] = wc_get_price_including_tax( $variation_obj );

				if ( $this->product_is_bulk_or_bigbag( $variation_obj->get_name() ) ) {
					$has_ecotaxe = true;
				}
			}
		}

		// Calculez le prix minimum et maximum.
		if ( ! empty( $prices ) ) {
			$min_price = min( $prices );
			$max_price = max( $prices );

			// Si le prix minimum et maximum sont identiques, affichez un prix unique.
			if ( $min_price === $max_price ) {
				$price = wc_price( $min_price );

			} else {
				// Sinon, affichez le range de prix.
				$price = wc_format_price_range( $min_price, $max_price );
			}

			if ( true === $has_ecotaxe ) {
				$price .= $this->ecotaxe_info_html;
			}
		}
		return $price;
	}

	/**
	 * Rend le produit non achetable si il n'a pas de classe de livraison.
	 *
	 * @param bool       $is_purchasable Si le produit est achetable ou non.
	 * @param WC_Product $product Le produit.
	 * @return bool Si le produit est achetable ou non.
	 */

	public function maybe_set_product_unpurchasable( $is_purchasable, $product ) {
		$product_id = $product->get_id();

		if ( ! $product_id ) {
			return $is_purchasable;
		}

		if ( $this->km_shipping_zone->is_in_thirteen() ) {
			$dontsell = get_field( 'dont_sell_in_thirteen', $product_id );
			if ( true === $dontsell ) {
				$this->add_price_html_filter( $product_id );
				return false;
			}
		} elseif ( $product->is_type( 'simple' ) ) {
			if ( ! $product->get_shipping_class_id() ) {
				$this->add_price_html_filter( $product_id );
				return false; // Aucune variation n'est achetable.
			}
		} elseif ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( $this->check_shipping_product_price( $variation ) ) {
					return $is_purchasable; // Au moins une variation est achetable.
				}
			}
			$this->add_price_html_filter( $product_id );
			return false; // Aucune variation n'est achetable.
		}

		return $is_purchasable;
	}

	/**
	 * Ajoute un filtre pour afficher un message au lieu du prix du produit.
	 *
	 * @param int $product_id L'ID du produit.
	 * @return void
	 */
	private function add_price_html_filter( $product_id ) {
		if ( ! in_array( $product_id, $this->unpurchasable_products ) ) {
			$this->unpurchasable_products[] = $product_id;
			add_filter( 'woocommerce_get_price_html', array( $this, 'display_unavailable_message' ), 99, 2 );
		}
	}

	/**
	 * Affiche un message au lieu du prix du produit.
	 *
	 * @param string     $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @return string Le prix du produit.
	 */
	public function display_unavailable_message( $price, $product ) {
		if ( in_array( $product->get_id(), $this->unpurchasable_products ) ) {
			return __( 'Ce produit n\'est pas disponible dans votre zone de livraison', 'kingmateriaux' );
		}
		return $price;
	}

	/**
	 * Check if a product has a shipping class.
	 *
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
		// Si aucun code postal n'est entré, affichez le message.
		if ( $this->km_shipping_zone->get_zip_and_country_from_cookie() === false ) {
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

		if ( false !== stripos( $item_name, 'big bag' ) || false !== stripos( $item_name, 'vrac' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Ajoute l'écotaxe au prix du produit si c'est un big bag ou un vrac à la tonne.
	 *
	 * @param float      $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @return float Le prix du produit.
	 */
	public function maybe_add_eco_tax( $price, $product ) {
		if ( $this->product_is_bulk_or_bigbag( $product->get_name() ) ) {
			$price += $this->ecotaxe_rate;
		}
		return $price;
	}

	/**
	 * Retourne le montant total de l'écotaxe dans le panier.
	 *
	 * @return float Le montant total de l'écotaxe dans le panier.
	 */
	public function get_total_ecotaxe() {
		// Calculate total ecotaxe in cart.
		$total_ecotaxe = 0;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			// Add condition to check if product is big bag or bulk.
			if ( $this->product_is_bulk_or_bigbag( $cart_item['data']->get_name() ) ) {
				$total_ecotaxe += $this->ecotaxe_rate_incl_taxes * $cart_item['quantity'];
			}
		}
		return $total_ecotaxe;
	}
}
