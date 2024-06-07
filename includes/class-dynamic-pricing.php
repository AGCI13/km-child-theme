<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Classe KM_Dynamic_Pricing pour gérer la tarification dynamique des produits.
 */
class KM_Dynamic_Pricing {

	use SingletonTrait;

	// Constantes pour les taux d'écotaxe et les messages HTML.
	const ECOTAXE_RATE               = 0.50;
	const ECOTAXE_RATE_INCL_TAXES    = 0.60;
	const ECOTAXE_HTML               = '<div class="km-product-ecotaxe">Dont %s d\'écotaxe</div>';
	const INCLUDE_SHIPPING_HTML      = '<div class="km-include-shipping">Livraison incluse</div>';
	const QUANTITY_DISCOUNT_MSG_HTML = '<div class="km-include-shipping">Tarifs dégressifs en fonction des quantités (visible uniquement dans le panier)</div>';

	/**
	 * @var string $ecotaxe_info_html HTML pour afficher l'information d'écotaxe.
	 */
	private $ecotaxe_info_html;

	/**
	 * @var string $include_shipping_html HTML pour afficher l'information de livraison incluse.
	 */
	private $include_shipping_html;

	/**
	 * @var string $quantity_discount_msg_html HTML pour afficher le message de réduction de quantité.
	 */
	private $quantity_discount_msg_html;

	/**
	 * @var array $unpurchasable_products Tableau des produits non achetables.
	 */
	private $unpurchasable_products = array();

	/**
	 * @var array $out_of_stock_products Tableau des produits en rupture de stock.
	 */
	private $out_of_stock_products = array();

	/**
	 * @var bool $is_in_thirteen Indique si la zone de livraison est dans la zone treize.
	 */
	private $is_in_thirteen;

	/**
	 * @var int $current_shipping_zone_id ID de la zone de livraison actuelle.
	 */
	private $current_shipping_zone_id;

	/**
	 * @var bool $is_big_bag_decreasing_zone Indique si la zone de livraison actuelle est une zone de prix décroissant pour les big bags.
	 */
	private $is_big_bag_decreasing_zone;

	/**
	 * @var array $calculated_prices Tableau des prix calculés pour les produits.
	 */
	private $calculated_prices = array();

	/**
	 * Constructeur privé pour empêcher l'instantiation directe.
	 */
	private function __construct() {
		$this->ecotaxe_info_html          = sprintf( self::ECOTAXE_HTML, wc_price( self::ECOTAXE_RATE_INCL_TAXES ) );
		$this->include_shipping_html      = self::INCLUDE_SHIPPING_HTML;
		$this->quantity_discount_msg_html = self::QUANTITY_DISCOUNT_MSG_HTML;
		$this->is_in_thirteen             = km_is_shipping_zone_in_thirteen();
		$this->current_shipping_zone_id   = km_get_current_shipping_zone_id();
		$this->is_big_bag_decreasing_zone = km_is_big_bag_price_decreasing_zone( $this->current_shipping_zone_id );

		$this->register();
	}

	/**
	 * Enregistre les filtres et actions WordPress nécessaires.
	 */
	private function register() {
		if ( true === is_admin() ) {
			return;
		}

		add_filter( 'woocommerce_is_purchasable', array( $this, 'handle_product_purchasability' ), 10, 2 );
		add_filter( 'woocommerce_product_get_price', array( $this, 'get_product_price_based_on_shipping_zone' ), 10, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'get_product_price_based_on_shipping_zone' ), 10, 2 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'maybe_display_include_shipping_html' ), 99, 2 );
		add_filter( 'woocommerce_variable_price_html', array( $this, 'adjust_variable_product_price_html' ), 99, 2 );
		add_filter( 'woocommerce_available_variation', array( $this, 'filter_available_variations' ), 10, 3 );
		add_action( 'wp', array( $this, 'set_prices_on_zip_or_zone_missing' ) );
	}

	/**
	 * Définit les prix lorsque le code postal ou la zone de livraison est manquant.
	 */
	public function set_prices_on_zip_or_zone_missing() {
		if ( ! $this->current_shipping_zone_id ) {
			add_filter( 'woocommerce_is_purchasable', '__return_false' );
			add_filter( 'woocommerce_get_price_html', array( $this, 'display_required_postcode_message' ), 99, 2 );
		}
	}

	/**
	 * Gère la disponibilité à l'achat des produits.
	 *
	 * @param bool       $is_purchasable Indique si le produit est achetable.
	 * @param WC_Product $product Le produit à vérifier.
	 * @return bool Retourne true si le produit est achetable, false sinon.
	 */
	public function handle_product_purchasability( $is_purchasable, $product ) {
		if ( true === $product->is_type( 'variation' ) ) {
			$sales_area = $product->get_meta( '_product_sales_area' );
			if ( true === $this->is_sales_area_allowed( $sales_area, $product ) ) {
				return true;
			}
		}

		$sales_area = $product->get_meta( '_product_sales_area' );
		if ( true !== $this->is_sales_area_allowed( $sales_area, $product ) ) {
			$this->modify_product_status( $product, 'unpurchasable' );
			return false;
		}

		if ( true === $product->is_type( 'variable' ) ) {
			return $this->handle_variable_product( $product );
		} elseif ( true !== $this->is_in_thirteen && true !== km_is_product_shippable_out_13( $product ) ) {
			$this->modify_product_status( $product, 'unpurchasable' );
			return false;
		}

		return true;
	}

	/**
	 * Gère la disponibilité des produits variables.
	 *
	 * @param WC_Product $product Le produit variable à vérifier.
	 * @return bool Retourne true si le produit est achetable, false sinon.
	 */
	private function handle_variable_product( $product ) {
		$all_variations_out_of_stock  = true;
		$all_variations_unpurchasable = true;

		foreach ( $product->get_children() as $variation_id ) {
			$variation             = wc_get_product( $variation_id );
			$is_variation_disabled = $this->is_variation_disabled( $product, $variation );

			if ( true === $variation->is_in_stock() ) {
				$all_variations_out_of_stock = false;
			}

			$sales_area = $variation->get_meta( '_product_sales_area' );

			if ( ! empty( $sales_area ) && true === $this->is_sales_area_allowed( $sales_area, $variation ) ) {
				if ( true !== $is_variation_disabled ) {
					$all_variations_unpurchasable = false;
				}
			} else {
				$product_sales_area = $product->get_meta( '_product_sales_area' );
				if ( true !== $is_variation_disabled && true === $this->is_sales_area_allowed( $product_sales_area, $product ) ) {
					$all_variations_unpurchasable = false;
				}
			}
		}

		if ( true === $all_variations_out_of_stock ) {
			$this->modify_product_status( $product, 'out_of_stock' );
		}

		if ( true === $all_variations_unpurchasable ) {
			$this->modify_product_status( $product, 'unpurchasable' );
		}

		return ! ( true === $all_variations_out_of_stock || true === $all_variations_unpurchasable );
	}

	/**
	 * Filtre les variations disponibles pour le produit.
	 *
	 * @param array      $variation_data Les données de variation.
	 * @param WC_Product $product Le produit parent.
	 * @param WC_Product $variation La variation.
	 * @return array Les données de variation modifiées.
	 */
	public function filter_available_variations( $variation_data, $product, $variation ) {
		if ( true === $this->is_variation_disabled( $product, $variation ) ) {
			$variation_data['is_purchasable']      = false;
			$variation_data['variation_is_active'] = false;
			$variation_data['availability_html']   = '<p class="stock out-of-stock">Indisponible dans votre zone de livraison</p>';
			return $variation_data;
		}

		$this->maybe_add_ecotax_to_variation( $variation_data, $product, $variation );

		return $variation_data;
	}

	/**
	 * Ajoute éventuellement l'écotaxe à une variation.
	 *
	 * @param array      $variation_data Les données de variation.
	 * @param WC_Product $product Le produit parent.
	 * @param WC_Product $variation La variation.
	 */
	private function maybe_add_ecotax_to_variation( &$variation_data, $product, $variation ) {
		$meta_ecotax       = $product->get_meta( '_has_ecotax' );
		$parent_ecotaxe    = 'yes' === $meta_ecotax || '1' === $meta_ecotax;
		$variation_ecotaxe = $variation->get_meta( '_has_ecotax' );
		$has_ecotaxe       = 'yes' === $variation_ecotaxe || ( 'no' !== $variation_ecotaxe && $parent_ecotaxe );

		if ( $has_ecotaxe ) {
			$variation_price                 = wc_get_price_to_display( $variation );
			$variation_data['display_price'] = $variation_price + self::ECOTAXE_RATE;

			if ( strpos( $variation_data['price_html'], sprintf( self::ECOTAXE_HTML, wc_price( self::ECOTAXE_RATE_INCL_TAXES ) ) ) === false ) {
				$variation_data['price_html'] .= sprintf( self::ECOTAXE_HTML, wc_price( self::ECOTAXE_RATE_INCL_TAXES ) );
			}
		}
	}

	/**
	 * Vérifie si une variation est désactivée.
	 *
	 * @param WC_Product $product Le produit parent.
	 * @param WC_Product $variation La variation à vérifier.
	 * @return bool Retourne true si la variation est désactivée, false sinon.
	 */
	private function is_variation_disabled( $product, $variation ) {
		$sales_area = $variation->get_meta( '_product_sales_area' );
		return true !== $this->is_sales_area_allowed( $sales_area, $product, $variation );
	}

	/**
	 * Vérifie si une zone de vente est autorisée.
	 *
	 * @param string          $sales_area La zone de vente.
	 * @param WC_Product      $product Le produit parent.
	 * @param WC_Product|null $variation La variation (facultatif).
	 * @return bool Retourne true si la zone de vente est autorisée, false sinon.
	 */
	private function is_sales_area_allowed( $sales_area, $product, $variation = null ) {
		$zone_id = (int) $this->current_shipping_zone_id;

		if ( $variation ) {
			$variation_sales_area = $variation->get_meta( '_product_sales_area' );
			$sales_area           = empty( $variation_sales_area ) || 'undefined' === $variation_sales_area ? $product->get_meta( '_product_sales_area' ) : $variation_sales_area;
		}

		$sales_area = empty( $sales_area ) || 'undefined' === $sales_area ? 'all' : $sales_area;

		switch ( $sales_area ) {
			case 'all':
				return true;
			case 'in_thirteen_only':
				return true === $this->is_in_thirteen;
			case 'out_thirteen_only':
				return true !== $this->is_in_thirteen;
			case 'custom_zones':
				$custom_zones = $variation ? $variation->get_meta( '_custom_product_shipping_zones' ) : $product->get_meta( '_custom_product_shipping_zones' );
				return is_array( $custom_zones ) && in_array( $zone_id, $custom_zones, true );
			default:
				return true;
		}
	}

	/**
	 * Modifie le statut d'un produit.
	 *
	 * @param WC_Product $product Le produit.
	 * @param string     $status Le statut à appliquer (unpurchasable, out_of_stock).
	 */
	private function modify_product_status( $product, $status ) {
		$product_id = $product->get_id();
		if ( 'unpurchasable' === $status && true !== in_array( $product_id, $this->unpurchasable_products ) ) {
			$this->unpurchasable_products[] = $product_id;
		} elseif ( 'out_of_stock' === $status && true !== in_array( $product_id, $this->out_of_stock_products ) ) {
			$this->out_of_stock_products[] = $product_id;
		}
		add_filter( 'woocommerce_get_price_html', array( $this, 'display_product_status_message' ), 99, 2 );
	}


	/**
	 * Affiche un message de statut de produit.
	 *
	 * @param string     $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @return string Le prix ou le message de statut du produit.
	 */
	public function display_product_status_message( $price, $product ) {
		$product_id = $product->get_id();
		$messages   = array();

		if ( true === in_array( $product_id, $this->unpurchasable_products ) ) {
			$messages[] = '<p class="km-price-info">Indisponible dans votre zone de livraison</p>';
		}

		if ( true === in_array( $product_id, $this->out_of_stock_products ) ) {
			$messages[] = '<p class="km-price-info">En rupture de stock</p>';
		}

		return ! empty( $messages ) ? implode( ' ', $messages ) : $price;
	}

	/**
	 * Obtient le prix du produit basé sur la zone de livraison.
	 *
	 * @param float      $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @param int|null   $zone_id L'ID de la zone de livraison (facultatif).
	 * @param bool       $force_recalc Indique si le recalcul est forcé.
	 * @return float Le prix calculé du produit.
	 */
	public function get_product_price_based_on_shipping_zone( $price, $product, $zone_id = null, $force_recalc = false ) {
		if ( is_null( $zone_id ) ) {
			$zone_id = $this->current_shipping_zone_id;
		}

		$cache_key = $product->get_id() . '_' . $zone_id;

		if ( ( true !== $force_recalc || true === empty( $product->get_meta( '_atoonext_sync', true ) ) ) && true === isset( $this->calculated_prices[ $cache_key ] ) ) {
			return $this->calculated_prices[ $cache_key ];
		}

		if ( true === ! empty( $product->get_meta( 'is_free_product' ) ) ) {
			return $price;
		}

		if ( true === did_action( 'woocommerce_before_calculate_totals' ) && true === km_is_big_bag_price_decreasing_zone( $zone_id ) && ( true === km_is_big_bag( $product ) || true === km_is_big_bag_and_slab( $product ) ) ) {
			$price = $this->calculate_localized_product_price( $price, $product, $zone_id, true );
		} elseif ( ! empty( $product->get_meta( '_atoonext_sync', true ) ) || true === $force_recalc ) {
			$price = $this->calculate_localized_product_price( $price, $product, $zone_id );
		} else {
			$price = $this->get_localized_product_price( $price, $product, $zone_id );
		}
		return $price;
	}

	/**
	 * Calcule le prix localisé du produit.
	 *
	 * @param float      $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @param int        $zone_id L'ID de la zone de livraison.
	 * @param bool       $is_big_bag Indique si le produit est un big bag.
	 * @return float Le prix calculé du produit.
	 */
	private function calculate_localized_product_price( $price, $product, $zone_id, $is_big_bag = false ) {
		$shipping_product = $this->get_shipping_product( $product, $zone_id, $is_big_bag );
		$price            = $this->add_ecotax_to_price( $price, $product );

		if ( $shipping_product instanceof WC_Product ) {
			$shipping_price = $shipping_product->get_price( 'edit' );
			if ( is_numeric( $shipping_price ) ) {
				$price += $shipping_price;
			}
		}

		if ( ! did_action( 'woocommerce_before_calculate_totals' ) ) {
			$this->update_localized_product_price( $product, $zone_id, $price );
		}

		return $price;
	}

	/**
	 * Ajoute éventuellement l'écotaxe au prix du produit.
	 *
	 * @param float      $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @return float Le prix avec l'écotaxe ajoutée.
	 */
	private function add_ecotax_to_price( $price, $product ) {
		$parent_ecotaxe        = false;
		$variation_has_ecotaxe = false;

		if ( $product->is_type( 'variation' ) ) {
			$ecotax_parent_meta = get_post_meta( $product->get_parent_id(), '_has_ecotax', true );
			$parent_ecotaxe     = '1' === $ecotax_parent_meta || 'yes' === $ecotax_parent_meta;

			$variation_ecotax      = $product->get_meta( '_has_ecotax' );
			$variation_has_ecotaxe = 'yes' === $variation_ecotax || ( 'no' !== $variation_ecotax && $parent_ecotaxe );
		} else {
			$ecotax         = $product->get_meta( '_has_ecotax' );
			$parent_ecotaxe = 'yes' === $ecotax || '1' === $ecotax;
		}

		if ( $parent_ecotaxe || $variation_has_ecotaxe ) {
			$price += self::ECOTAXE_RATE;
		}

		return $price;
	}

	/**
	 * Obtient le produit de livraison associé.
	 *
	 * @param WC_Product $product Le produit.
	 * @param int        $zone_id L'ID de la zone de livraison.
	 * @param bool       $is_big_bag Indique si le produit est un big bag.
	 * @return WC_Product|null Le produit de livraison associé.
	 */
	private function get_shipping_product( $product, $zone_id, $is_big_bag ) {
		if ( km_is_big_bag_price_decreasing_zone( $zone_id ) && ( $is_big_bag || km_is_big_bag( $product ) || km_is_big_bag_and_slab( $product ) ) ) {
			return km_get_big_bag_shipping_product( $product );
		}
		return km_get_related_shipping_product( $product );
	}

	/**
	 * Obtient le prix localisé du produit.
	 *
	 * @param float      $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @param int        $zone_id L'ID de la zone de livraison.
	 * @return float Le prix localisé du produit.
	 */
	private function get_localized_product_price( $price, $product, $zone_id ) {
		$localized_product_price = $product->get_meta( '_price_zone_' . $zone_id, true );

		if ( ! empty( $localized_product_price ) && is_numeric( $localized_product_price ) ) {
			return $localized_product_price;
		}
		return $this->calculate_localized_product_price( $price, $product, $zone_id );
	}

	/**
	 * Met à jour le prix localisé du produit.
	 *
	 * @param WC_Product $product Le produit.
	 * @param int        $zone_id L'ID de la zone de livraison.
	 * @param float      $price Le prix du produit.
	 */
	private function update_localized_product_price( $product, $zone_id, $price ) {
		$product_id = $product->get_id();
		$updated    = update_post_meta( $product_id, '_price_zone_' . (string) $zone_id, $price );
		if ( false !== $updated ) {
			delete_post_meta( $product_id, '_atoonext_sync' );
		}
	}

	/**
	 * Affiche éventuellement le HTML d'inclusion de livraison.
	 *
	 * @param string     $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @return string Le prix HTML avec le message d'inclusion de livraison.
	 */
	public function maybe_display_include_shipping_html( $price, $product ) {
		if ( ! km_is_shipping_zone_in_thirteen() && ! $product->is_type( 'variation' ) ) {
			$price .= $this->include_shipping_html;

			if ( is_product() && km_is_big_bag_price_decreasing_zone() && ( km_is_big_bag( $product ) || km_is_big_bag_and_slab( $product ) ) ) {
				$price .= $this->quantity_discount_msg_html;
			}
		} elseif ( km_is_big_bag_price_decreasing_zone() && ( km_is_big_bag( $product ) || km_is_big_bag_and_slab( $product ) ) ) {
			$price .= $this->quantity_discount_msg_html;
		}

		return $price;
	}

	/**
	 * Vérifie si un produit a un produit de livraison associé avec un prix supérieur à 0€.
	 *
	 * @param WC_Product $product Le produit (variation) à vérifier.
	 * @param int|null   $zone_id L'ID de la zone de livraison (facultatif).
	 * @return bool Retourne true si un produit de livraison existe et que son prix est supérieur à 0€, false sinon.
	 */
	public function get_shipping_product_price( $product, $zone_id = null ) {
		$shipping_product = $this->get_shipping_product( $product, $zone_id, false );

		if ( ! $shipping_product ) {
			return false;
		}

		$shipping_product_price = $shipping_product->get_price();

		return $shipping_product && $shipping_product_price > 0 ? $shipping_product_price : false;
	}

	/**
	 * Ajuste le prix HTML des produits variables.
	 *
	 * @param string     $price Le prix du produit.
	 * @param WC_Product $product Le produit variable.
	 * @return string Le prix HTML ajusté.
	 */
	public function adjust_variable_product_price_html( $price, $product ) {
		$prices      = array();
		$has_ecotaxe = false;

		if ( ! $product->is_purchasable() ) {
			return $price;
		}

		if ( ! $product->get_meta( '_atoonext_sync', true ) && $product->get_meta( '_price_range_' . $this->current_shipping_zone_id, true ) ) {
			return $product->get_meta( '_price_range_' . $this->current_shipping_zone_id, true );
		}

		$parent_ecotaxe = $product->get_meta( '_has_ecotax' ) === 'yes' || $product->get_meta( '_has_ecotax' ) === '1';

		foreach ( $product->get_available_variations() as $variation ) {
			$variation_obj = wc_get_product( $variation['variation_id'] );

			if ( $variation_obj->is_purchasable() && ! $this->is_variation_disabled( $product, $variation_obj ) ) {
				$prices[] = wc_get_price_including_tax( $variation_obj );

				$variation_ecotaxe = $variation_obj->get_meta( '_has_ecotax' );
				if ( 'yes' === $variation_ecotaxe || ( 'no' !== $variation_ecotaxe && $parent_ecotaxe ) ) {
					$has_ecotaxe = true;
				}
			}
		}

		if ( ! empty( $prices ) ) {
			$min_price = min( $prices );
			$max_price = max( $prices );

			$price = ( $min_price === $max_price ) ? wc_price( $min_price ) : wc_format_price_range( $min_price, $max_price );

			if ( $has_ecotaxe ) {
				if ( strpos( $price, $this->ecotaxe_info_html ) === false ) {
					$price .= $this->ecotaxe_info_html;
				}
			}

			update_post_meta( $product->get_id(), '_price_range_' . $this->current_shipping_zone_id, $price );
		}
		return $price;
	}

	/**
	 * Affiche un message si le code postal est requis pour afficher le prix.
	 *
	 * @param string     $price Le prix du produit.
	 * @param WC_Product $product Le produit.
	 * @return string Le message requis ou le prix.
	 */
	public function display_required_postcode_message( $price, $product ) {
		return ! $this->current_shipping_zone_id ? __( 'L\'affichage du prix requiert un code postal', 'kingmateriaux' ) : $price;
	}

	/**
	 * Obtient le total des écotaxes pour le contexte donné.
	 *
	 * @param string $context Le contexte ('cart' ou 'order').
	 * @return float Le total des écotaxes.
	 */
	public function get_total_ecotaxe( $context = 'cart' ) {
		$total_ecotaxe = 0;
		$items         = ( 'cart' === $context ) ? WC()->cart->get_cart() : WC()->order->get_items();

		foreach ( $items as $item ) {
			if ( true === $item['_has_ecotax'] ) {
				$total_ecotaxe += self::ECOTAXE_RATE_INCL_TAXES * $item['quantity'];
			}
		}
		return $total_ecotaxe;
	}
}
