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

	/**
	 * The include shipping message to display.
	 *
	 * @var string
	 */
	public $include_shipping_html;

	/**
	 * The include shipping message to display.
	 *
	 * @var string
	 */
	public $quantity_discount_msg_html;

	/**
	 * The list of products that are not purchasable.
	 *
	 * @var array
	 */
	private $unpurchasable_products = array();

	/**
	 * The list of products that are not purchasable.
	 *
	 * @var array
	 */
	private $out_of_stock_products = array();

	/**
	 * Return true if the shipping zone is 13.
	 *
	 * @var bool
	 */
	private $is_in_thirteen;

	/**
	 * Constructor.
	 *
	 * The constructor is protected to prevent creating a new instance from outside
	 * and to prevent creating multiple instances through the `new` keyword.
	 */
	private function __construct() {
		$this->ecotaxe_info_html          = '<div class="km-product-ecotaxe">' . sprintf( __( 'Dont %s d\'écotaxe', 'kingmateriaux' ), wc_price( $this->ecotaxe_rate_incl_taxes ) ) . '</div>';
		$this->include_shipping_html      = '<div class="km-include-shipping">' . esc_html__( 'Livraison incluse', 'kingmateriaux' ) . '</div>';
		$this->quantity_discount_msg_html = '<div class="km-include-shipping">' . esc_html__( 'Tarifs dégressifs en fonction des quantités (visible uniquement dans le panier)', 'kingmateriaux' ) . '</div>';
		$this->is_in_thirteen             = km_is_shipping_zone_in_thirteen();

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

		add_filter( 'woocommerce_is_purchasable', array( $this, 'handle_product_purchasability' ), 10, 2 );
		add_filter( 'woocommerce_product_get_price', array( $this, 'change_product_price_based_on_shipping_zone' ), 10, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'change_product_price_based_on_shipping_zone' ), 10, 2 );
		add_filter( 'woocommerce_variation_prices_price', array( $this, 'change_variation_prices_based_on_shipping_zone' ), 80, 3 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'adjust_simple_product_price_html' ), 98, 2 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'maybe_display_include_shipping_html' ), 99, 2 );
		add_filter( 'woocommerce_variable_price_html', array( $this, 'adjust_variable_product_price_html' ), 99, 2 );
		add_filter( 'woocommerce_available_variation', array( $this, 'disable_variation_if_no_shipping_product' ), 10, 3 );
		add_action( 'wp', array( $this, 'set_prices_on_zip_or_zone_missing' ) );
	}

	/**
	 * Change le prix du produit en fonction de la zone de livraison.
	 *
	 * @return void
	 */
	public function set_prices_on_zip_or_zone_missing() {
		if ( km_get_current_shipping_zone_id() ) {
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

		if ( ( ! $this->is_in_thirteen && ! $this->get_shipping_product_price( $variation ) )
		|| ( $this->is_in_thirteen && 'yes' === get_post_meta( $variation->get_id(), '_disable_variation_in_13', true ) )
		|| ( $this->is_in_thirteen && false !== stripos( $product->get_name(), 'benne' ) && false === stripos( sanitize_title( $variation->get_name() ), str_replace( ' ', '-', km_get_shipping_zone_name() ) ) ) ) {

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
	public function change_product_price_based_on_shipping_zone( $price, $product, $zone_id = null ) {

		if ( $this->product_has_ecotax_meta( $product ) ) {
				$price += $this->ecotaxe_rate;
		}

		if ( km_is_shipping_zone_in_thirteen( $zone_id ) ) {
			return $price;
		}

		$price = $this->calculate_shipping_price( $price, $product, $zone_id );

		return $price;
	}

	/**
	 * Calcule le prix de livraison du produit.
	 * Si le produit est dans une zone de livraison spécifique, le prix de livraison est ajouté au prix du produit.
	 *
	 * @param float      $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @param int        $zone_id L'ID de la zone de livraison.
	 * @return float Le prix du produit.
	 */
	private function calculate_shipping_price( $price, $product, $zone_id ) {

		if ( ( km_is_big_bag( $product ) && km_is_big_bag_price_decreasing_zone( $zone_id ) )
		|| ( km_is_big_bag_and_slab( $product ) && km_is_big_bag_and_slab_price_decreasing_zone( $zone_id ) ) ) {
			$shipping_product = km_get_big_bag_shipping_product( $product );
		} else {
			$shipping_product = km_get_related_shipping_product( $product );
		}

		if ( $shipping_product instanceof WC_Product ) {
			$shipping_price = $shipping_product->get_price( 'edit' );
			if ( is_numeric( $shipping_price ) && $shipping_price >= 1 ) {
				$price += $shipping_price;
			}
		}
		return $price;
	}

	/**
	 * Change les prix des variations de produit en fonction de la zone de livraison.
	 *
	 * @param array      $price Les prix des variations de produit.
	 * @param WC_Product $variation La variation de produit.
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

		if ( is_product() && $this->product_has_ecotax_meta( $product )
		&& ( $product->is_type( 'simple' ) || $product->is_type( 'variation' ) ) ) {
			$price .= $this->ecotaxe_info_html;
		}
		return $price;
	}

	/**
	 * Affiche le message "Livraison incluse" si le produit est dans la zone 13.
	 *
	 * @param string     $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @return string Le prix du produit.
	 */
	public function maybe_display_include_shipping_html( $price, $product ) {
		if ( ! km_is_shipping_zone_in_thirteen() ) {

			if ( ! $product->is_type( 'variation' ) ) {

				$price .= $this->include_shipping_html;

				if ( is_product() && km_is_big_bag( $product ) && km_is_big_bag_price_decreasing_zone() ) {
					$price .= $this->quantity_discount_msg_html;
				}
			} elseif ( km_is_big_bag( $product ) && km_is_big_bag_price_decreasing_zone() ) {
				$price .= $this->quantity_discount_msg_html;
			}
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

		$prices      = array();
		$has_ecotaxe = false;

		// Parcourez les variations disponibles.
		foreach ( $product->get_available_variations() as $variation ) {
			$variation_obj = wc_get_product( $variation['variation_id'] );

			// Vérifiez si la variation est achetable.
			if ( $variation_obj->is_purchasable() && $this->disable_variation_if_no_shipping_product( $variation, $product, $variation_obj )['is_purchasable'] ) {
				$prices[] = wc_get_price_including_tax( $variation_obj );

				if ( $this->product_has_ecotax_meta( $product ) || $this->product_has_ecotax_meta( $variation_obj ) ) {
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
	 * Empêche l'achat du produit et le retire du panier si il n'a pas de classe de livraison.
	 *
	 * @param bool       $is_purchasable Si le produit est achetable ou non.
	 * @param WC_Product $product Le produit.
	 * @return bool Si le produit est achetable ou non.
	 */
	public function handle_product_purchasability( $is_purchasable, $product ) {
		$product_id = $product->get_id();

		if ( ! $product_id ) {
			return $is_purchasable;
		}

		$disable_in_thirteen = get_field( 'dont_sell_in_thirteen', $product_id );

		if ( $this->is_in_thirteen && $disable_in_thirteen ) {
			$this->modify_product_status( $product_id, 'unpurchasable' );
			return false;
		}

		if ( $product->is_type( 'variable' ) ) {
			return $this->handle_variable_product( $product );
		} elseif ( ! $this->is_in_thirteen && ! km_is_product_shippable_out_13( $product ) ) {
			$this->modify_product_status( $product_id, 'unpurchasable' );
			return false;
		}

		return $is_purchasable;
	}

	/**
	 * Vérifie si toutes les variations d'un produit variable sont achetables.
	 * Si ce n'est pas le cas, le produit est rendu non achetable.
	 * Si toutes les variations sont achetables, le produit est rendu achetable.
	 * Si toutes les variations sont en rupture de stock, le produit est rendu en rupture de stock.
	 * Si toutes les variations sont non achetables, le produit est rendu non achetable.
	 *
	 * @param WC_Product $product Le produit.
	 * @return bool Si le produit est achetable ou non.
	 */
	private function handle_variable_product( $product ) {
		$product_id                   = $product->get_id();
		$all_variations_out_of_stock  = true;
		$all_variations_unpurchasable = true;

		foreach ( $product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			// Intégration de la logique de 'disable_variation_if_no_shipping_product'.
			$is_variation_purchasable = $this->is_variation_purchasable( $variation );

			if ( $variation->is_in_stock() ) {
				$all_variations_out_of_stock = false;
			}

			if ( $is_variation_purchasable && ! get_field( 'dont_sell_in_thirteen', $variation_id ) ) {
				$all_variations_unpurchasable = false;
			}
		}

		if ( $all_variations_out_of_stock ) {
			$this->modify_product_status( $product_id, 'out_of_stock' );
		}

		if ( $all_variations_unpurchasable ) {
			$this->modify_product_status( $product_id, 'unpurchasable' );
		}

		return ! ( $all_variations_out_of_stock || $all_variations_unpurchasable );
	}

	/**
	 * Détermine si une variation est achetable.
	 *
	 * @param WC_Product_Variation $variation La variation du produit.
	 * @return bool True si la variation est achetable, false sinon.
	 */
	private function is_variation_purchasable( $variation ) {

		if ( ! $this->is_in_thirteen ) {
			return $this->get_shipping_product_price( $variation ) ? true : false;
		}

		// Obtention de l'objet produit parent.
		$parent_product = wc_get_product( $variation->get_parent_id() );

		if ( $this->is_in_thirteen && 'yes' === get_post_meta( $variation->get_id(), '_disable_variation_in_13', true ) ) {
			return false;
		} elseif ( $this->is_in_thirteen && $parent_product instanceof WC_Product && false !== stripos( $parent_product->get_name(), 'benne' ) && false === stripos( sanitize_title( $variation->get_name() ), str_replace( ' ', '-', km_get_shipping_zone_name() ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Modifie le statut du produit et applique le filtre HTML approprié.
	 *
	 * @param int    $product_id L'ID du produit.
	 * @param string $status Le statut du produit ('unpurchasable' ou 'out_of_stock').
	 * @return void
	 */
	private function modify_product_status( $product_id, $status ) {
		if ( 'unpurchasable' === $status && ! in_array( $product_id, $this->unpurchasable_products ) ) {
			$this->unpurchasable_products[] = $product_id;
		} elseif ( 'out_of_stock' === $status && ! in_array( $product_id, $this->out_of_stock_products ) ) {
			$this->out_of_stock_products[] = $product_id;
		}
		add_filter( 'woocommerce_get_price_html', array( $this, 'display_product_status_message' ), 99, 2 );
	}

	/**
	 * Affiche un message au lieu du prix du produit.
	 *
	 * @param string     $price Le prix du produi
	 *      t.
	 * @param WC_Product $product Le produit.
	 * @return string Le prix du produit.
	 */
	public function display_product_status_message( $price, $product ) {
		$product_id = $product->get_id();
		$messages   = array();

		if ( in_array( $product_id, $this->unpurchasable_products ) ) {
			$messages[] = '<p class="km-price-info">' . __( 'Indisponible dans votre zone de livraison', 'kingmateriaux' ) . '</p>';
		}

		if ( in_array( $product_id, $this->out_of_stock_products ) ) {
			// Si le produit est uniquement en rupture de stock (et pas non achetable), affichez le prix avec le message de rupture de stock.
			$messages[] = '<p class="km-price-info">' . __( 'En rupture de stock', 'kingmateriaux' ) . '</p>';
		}

		if ( ! empty( $messages ) ) {
			return implode( ' ', $messages );
		}

		return $price;
	}

	/**
	 * Check if a product has a shipping class.
	 *
	 * @param WC_Product $product Le produit.
	 * @return bool Si le produit a une classe de livraison ou non.
	 */
	private function has_shipping_class( $product ) {
		$shipping_class = $product->get_shipping_class();
		return ! empty( $shipping_class );
	}

	/**
	 * Vérifie si un produit de produit a un produit de livraison associé avec un prix supérieur à 0€.
	 *
	 * @param WC_Product $product Le produit (variation) à vérifier.
	 * @return bool Retourne true si un produit de livraison existe et que son prix est supérieur à 0€, false sinon.
	 */
	public function get_shipping_product_price( $product, $zone_id = null ) {

		$shipping_product = km_get_related_shipping_product( $product, $zone_id );

		if ( ! $shipping_product ) {
			return false;
		}

		$shipping_product_price = $shipping_product->get_price();

		if ( $shipping_product && $shipping_product_price > 0 ) {
			return $shipping_product_price;
		}

		return false;
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
		if ( ! km_get_current_shipping_zone_id() ) {
			return __( 'L\'affichage du prix requiert un code postal', 'kingmateriaux' );
		}
		// Sinon, retournez le prix habituel.
		return $price;
	}

	/**
	 * Vérifie si le produit à une meta _has_ecotaxe.
	 *
	 * @param WC_Product $product Le produit.
	 * @return bool Si le produit à une meta _has_ecotax.
	 */
	public function product_has_ecotax_meta( $product ) {

		if ( $product->is_type( 'variation' ) ) {
			$parent_ecotax = get_post_meta( $product->get_parent_id(), '_has_ecotax', true );

			if ( isset( $parent_ecotax ) && $parent_ecotax ) {
				return true;
			}
		}

		$ecotax = get_post_meta( $product->get_id(), '_has_ecotax', true );

		if ( isset( $ecotax ) && $ecotax ) {
			return true;
		}

		return false;
	}

	/**
	 * Retourne le montant total de l'écotaxe dans le panier.
	 *
	 * @param string $context Le contexte dans lequel on se trouve (cart ou order).
	 * @return float Le montant total de l'écotaxe dans le panier.
	 */
	public function get_total_ecotaxe( $context = 'cart' ) {
		// Calculate total ecotaxe in cart.
		$total_ecotaxe = 0;

		$items = 'cart' === $context ? WC()->cart->get_cart() : WC()->order->get_items();

		foreach ( $items as $item ) {

			if ( $item['_has_ecotax'] ) {
				$total_ecotaxe += $this->ecotaxe_rate_incl_taxes * $item['quantity'];
			}
		}
		return $total_ecotaxe;
	}
}
