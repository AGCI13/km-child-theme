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

	private $ecotaxe_info_html;
	private $include_shipping_html;
	private $quantity_discount_msg_html;
	private $unpurchasable_products = array();
	private $out_of_stock_products  = array();
	private $is_in_thirteen;
	private $current_shipping_zone_id;
	private $is_big_bag_decreasing_zone;
	private $calculated_prices = array();
	private $cached_results    = array();

	/**
	 * Constructeur privé pour empêcher l'instantiation directe.
	 */
	private function __construct() {
		$this->ecotaxe_info_html          = sprintf( self::ECOTAXE_HTML, wc_price( self::ECOTAXE_RATE_INCL_TAXES ) );
		$this->include_shipping_html      = self::INCLUDE_SHIPPING_HTML;
		$this->quantity_discount_msg_html = self::QUANTITY_DISCOUNT_MSG_HTML;
		$this->is_in_thirteen             = $this->cached_call( 'km_is_shipping_zone_in_thirteen' );
		$this->current_shipping_zone_id   = $this->cached_call( 'km_get_current_shipping_zone_id' );
		$this->is_big_bag_decreasing_zone = $this->cached_call( 'km_is_big_bag_price_decreasing_zone', array( $this->current_shipping_zone_id ) );

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
		add_action( 'save_post_product', array( $this, 'recalculate_localized_prices_on_save' ) );
		add_action( 'woocommerce_save_product_variation', array( $this, 'recalculate_localized_prices_on_save' ), 10, 2 );
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
	 */
	public function handle_product_purchasability( $is_purchasable, $product ) {
		$metadata   = $this->get_product_metadata( $product );
		$sales_area = $metadata['sales_area'];

		if ( true !== $this->is_sales_area_allowed( $sales_area, $product ) ) {
			$this->modify_product_status( $product, 'unpurchasable' );
			return false;
		}

		if ( true === $product->is_type( 'variable' ) ) {
			return $this->handle_variable_product( $product );
		}

		if ( true !== $this->is_in_thirteen && ! $this->get_shipping_product_price( $product ) ) {
			$this->modify_product_status( $product, 'unpurchasable' );
			return false;
		}

		return true;
	}

	/**
	 * Gère la disponibilité des produits variables.
	 */
	private function handle_variable_product( $product ) {
		$all_variations_out_of_stock  = true;
		$all_variations_unpurchasable = true;

		$variations = $product->get_available_variations( 'objects' );

		foreach ( $variations as $variation ) {
			$is_variation_disabled = $this->is_variation_disabled( $product, $variation );

			if ( true === $variation->is_in_stock() ) {
				$all_variations_out_of_stock = false;
			}

			$metadata   = $this->get_product_metadata( $variation );
			$sales_area = $metadata['sales_area'];

			if ( true === $this->is_sales_area_allowed( $sales_area, $variation ) ) {
				if ( true !== $is_variation_disabled ) {
					$all_variations_unpurchasable = false;
				}
			} else {
				$product_metadata   = $this->get_product_metadata( $product );
				$product_sales_area = $product_metadata['sales_area'];
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
	 */
	private function maybe_add_ecotax_to_variation( &$variation_data, $product, $variation ) {
		$product_metadata   = $this->get_product_metadata( $product );
		$variation_metadata = $this->get_product_metadata( $variation );

		$parent_ecotaxe    = 'yes' === $product_metadata['has_ecotax'] || '1' === $product_metadata['has_ecotax'];
		$variation_ecotaxe = $variation_metadata['has_ecotax'];
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
	 */
	private function is_variation_disabled( $product, $variation ) {
		$variation_metadata = $this->get_product_metadata( $variation );
		$sales_area         = $variation_metadata['sales_area'];
		return true !== $this->is_sales_area_allowed( $sales_area, $product );
	}

	/**
	 * Vérifie si une zone de vente est autorisée.
	 */
	private function is_sales_area_allowed( $sales_area, $product ) {
		$zone_id = (string) $this->current_shipping_zone_id;

		switch ( $sales_area ) {
			case 'all':
				return true;
			case 'in_thirteen_only':
				return true === $this->is_in_thirteen;
			case 'out_thirteen_only':
				return true !== $this->is_in_thirteen;
			case 'custom_zones':
				$metadata     = $this->get_product_metadata( $product );
				$custom_zones = maybe_unserialize( $metadata['custom_zones'] );
				return is_array( $custom_zones ) && in_array( $zone_id, $custom_zones, true );
			default:
				return true;
		}
	}

	/**
	 * Modifie le statut d'un produit.
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
	 */
	public function get_product_price_based_on_shipping_zone( $price, $product, $zone_id = null, $force_recalc = false ) {
		if ( is_null( $zone_id ) ) {
			$zone_id = $this->current_shipping_zone_id;
		}

		$force_recalc = isset( $_GET['force-recalc'] );
		$cache_key    = $product->get_id() . '_' . $zone_id;

		if ( ! did_action( 'woocommerce_before_calculate_totals' ) && isset( $this->calculated_prices[ $cache_key ] ) ) {
			return $this->calculated_prices[ $cache_key ];
		}

		$metadata = $this->get_product_metadata( $product );
		if ( true === ! empty( $metadata['is_free_product'] ) ) {
			return $price;
		}

		if ( did_action( 'woocommerce_before_calculate_totals' ) && $this->is_big_bag_decreasing_zone && ( $this->cached_call( 'km_is_big_bag', array( $product ) ) || $this->cached_call( 'km_is_big_bag_and_slab', array( $product ) ) ) ) {
			$price = $this->calculate_localized_product_price( $price, $product, $zone_id, true );
		} else {
			$price = $this->calculate_localized_product_price( $price, $product, $zone_id );
		}

		$this->calculated_prices[ $cache_key ] = $price;

		return $price;
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
	 * Calcule le prix localisé du produit.
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
	 */
	private function add_ecotax_to_price( $price, $product ) {
		$metadata    = $this->get_product_metadata( $product );
		$has_ecotaxe = false;

		if ( $product->is_type( 'variation' ) ) {
			$parent_product    = wc_get_product( $product->get_parent_id() );
			$parent_metadata   = $this->get_product_metadata( $parent_product );
			$parent_ecotaxe    = 'yes' === $parent_metadata['has_ecotax'] || '1' === $parent_metadata['has_ecotax'];
			$variation_ecotaxe = $metadata['has_ecotax'];
			$has_ecotaxe       = 'yes' === $variation_ecotaxe || ( 'no' !== $variation_ecotaxe && $parent_ecotaxe );
		} else {
			$has_ecotaxe = 'yes' === $metadata['has_ecotax'] || '1' === $metadata['has_ecotax'];
		}

		if ( $has_ecotaxe ) {
			$price += self::ECOTAXE_RATE;
		}

		return $price;
	}

	/**
	 * Obtient le produit de livraison associé.
	 */
	private function get_shipping_product( $product, $zone_id, $is_big_bag ) {
		if ( $this->cached_call( 'km_is_big_bag_price_decreasing_zone', array( $zone_id ) ) &&
			( $is_big_bag || $this->cached_call( 'km_is_big_bag', array( $product ) ) || $this->cached_call( 'km_is_big_bag_and_slab', array( $product ) ) ) ) {
			return $this->cached_call( 'km_get_big_bag_shipping_product', array( $product ) );
		}
		return $this->cached_call( 'km_get_related_shipping_product', array( $product ) );
	}

	/**
	 * Met à jour le prix localisé du produit.
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
	 */
	public function maybe_display_include_shipping_html( $price, $product ) {
		$metadata    = $this->get_product_metadata( $product );
		$has_ecotaxe = 'yes' === $metadata['has_ecotax'] || '1' === $metadata['has_ecotax'];

		if ( $has_ecotaxe && strpos( $price, sprintf( self::ECOTAXE_HTML, wc_price( self::ECOTAXE_RATE_INCL_TAXES ) ) ) === false ) {
			$price .= sprintf( self::ECOTAXE_HTML, wc_price( self::ECOTAXE_RATE_INCL_TAXES ) );
		}

		if ( ! $this->is_in_thirteen && ! $product->is_type( 'variation' ) ) {
			$price .= $this->include_shipping_html;

			if ( is_product() && $this->is_big_bag_decreasing_zone &&
				( $this->cached_call( 'km_is_big_bag', array( $product ) ) || $this->cached_call( 'km_is_big_bag_and_slab', array( $product ) ) ) ) {
				$price .= $this->quantity_discount_msg_html;
			}
		} elseif ( $this->is_big_bag_decreasing_zone &&
					( $this->cached_call( 'km_is_big_bag', array( $product ) ) || $this->cached_call( 'km_is_big_bag_and_slab', array( $product ) ) ) ) {
			$price .= $this->quantity_discount_msg_html;
		}

		return $price;
	}

	/**
	 * Vérifie si un produit a un produit de livraison associé avec un prix supérieur à 0€.
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
	 */
	public function adjust_variable_product_price_html( $price, $product ) {
		$prices      = array();
		$has_ecotaxe = false;

		if ( ! $product->is_purchasable() ) {
			return $price;
		}

		$cache_key = 'variable_price_' . $product->get_id() . '_' . $this->current_shipping_zone_id;

		if ( isset( $this->calculated_prices[ $cache_key ] ) ) {
			return $this->calculated_prices[ $cache_key ];
		}

		$product_metadata = $this->get_product_metadata( $product );
		$parent_ecotaxe   = 'yes' === $product_metadata['has_ecotax'] || '1' === $product_metadata['has_ecotax'];

		foreach ( $product->get_available_variations() as $variation ) {
			$variation_obj = wc_get_product( $variation['variation_id'] );

			if ( $variation_obj->is_purchasable() && ! $this->is_variation_disabled( $product, $variation_obj ) ) {
				$prices[] = wc_get_price_including_tax( $variation_obj );

				$variation_metadata = $this->get_product_metadata( $variation_obj );
				$variation_ecotaxe  = $variation_metadata['has_ecotax'];
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

			$this->calculated_prices[ $cache_key ] = $price;
		}
		return $price;
	}

	/**
	 * Affiche un message si le code postal est requis pour afficher le prix.
	 */
	public function display_required_postcode_message( $price, $product ) {
		return ! $this->current_shipping_zone_id ? __( 'L\'affichage du prix requiert un code postal', 'kingmateriaux' ) : $price;
	}

	/**
	 * Obtient le total des écotaxes pour le contexte donné.
	 */
	public function get_total_ecotaxe( $context = 'cart' ) {
		$total_ecotaxe = 0;
		$items         = ( 'cart' === $context ) ? WC()->cart->get_cart() : WC()->order->get_items();

		foreach ( $items as $item ) {
			if ( true === $item['_has_ecotax'] ) {
				$total_ecotaxe += self::ECOTAXE_RATE * $item['quantity'];
			}
		}
		return $total_ecotaxe;
	}

	/**
	 * Récupère les métadonnées d'un produit.
	 */
	private function get_product_metadata( $product ) {
		$product_id = $product->get_id();
		$cache_key  = 'product_metadata_' . $product_id;

		$metadata = wp_cache_get( $cache_key, 'products' );
		if ( false === $metadata ) {
			$metadata = get_post_meta( $product_id );
			wp_cache_set( $cache_key, $metadata, 'products' );
		}

		return array(
			'has_ecotax'      => isset( $metadata['_has_ecotax'] ) ? $metadata['_has_ecotax'][0] : '',
			'sales_area'      => isset( $metadata['_product_sales_area'] ) ? $metadata['_product_sales_area'][0] : '',
			'custom_zones'    => isset( $metadata['_custom_product_shipping_zones'] ) ? $metadata['_custom_product_shipping_zones'][0] : '',
			'is_free_product' => isset( $metadata['is_free_product'] ) ? $metadata['is_free_product'][0] : '',
		);
	}

	/**
	 * Appelle une fonction et met en cache son résultat.
	 */
	private function cached_call( $function_name, $args = array() ) {
		$key = $function_name . serialize( $args );
		if ( ! isset( $this->cached_results[ $key ] ) ) {
			$this->cached_results[ $key ] = call_user_func_array( $function_name, $args );
		}
		return $this->cached_results[ $key ];
	}

	/**
	 * Recalcule les prix localisés pour toutes les zones d'expédition lors de la sauvegarde d'un produit.
	 */
	public function recalculate_localized_prices_on_save( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return;
		}

		// Recalculate for the main product.
		if ( ! $product->is_type( 'variation' ) ) {
			$this->recalculate_prices_for_product_and_zones( $product );
		}

		// Recalculate for each variation.
		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( $variation ) {
					$this->recalculate_prices_for_product_and_zones( $variation );
				}
			}
		}
	}

	/**
	 * Recalcule les prix pour un produit donné pour toutes les zones d'expédition.
	 */
	private function recalculate_prices_for_product_and_zones( $product ) {
		$zones = WC_Shipping_Zones::get_zones();

		foreach ( $zones as $zone ) {
			$zone_id = $zone['id'];
			$this->calculate_localized_product_price( $product->get_price(), $product, $zone_id, true );
		}
	}
}
