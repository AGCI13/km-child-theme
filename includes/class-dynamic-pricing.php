<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class KM_Dynamic_Pricing {

	use SingletonTrait;

	public $ecotaxe_rate            = 0.50;
	public $ecotaxe_rate_incl_taxes = 0.60;
	public $ecotaxe_info_html;
	public $include_shipping_html;
	public $quantity_discount_msg_html;
	private $unpurchasable_products = array();
	private $out_of_stock_products  = array();
	private $is_in_thirteen;
	private $current_shipping_zone_id;
	private $is_big_bag_decreasing_zone;
	private $calculated_prices = array();

	private function __construct() {
		$this->ecotaxe_info_html          = '<div class="km-product-ecotaxe">' . sprintf( __( 'Dont %s d\'écotaxe', 'kingmateriaux' ), wc_price( $this->ecotaxe_rate_incl_taxes ) ) . '</div>';
		$this->include_shipping_html      = '<div class="km-include-shipping">' . esc_html__( 'Livraison incluse', 'kingmateriaux' ) . '</div>';
		$this->quantity_discount_msg_html = '<div class="km-include-shipping">' . esc_html__( 'Tarifs dégressifs en fonction des quantités (visible uniquement dans le panier)', 'kingmateriaux' ) . '</div>';
		$this->is_in_thirteen             = km_is_shipping_zone_in_thirteen();
		$this->current_shipping_zone_id   = km_get_current_shipping_zone_id();
		$this->is_big_bag_decreasing_zone = km_is_big_bag_price_decreasing_zone( $this->current_shipping_zone_id );

		$this->register();
	}

	private function register() {
		if ( is_admin() ) {
			return;
		}

		add_filter( 'woocommerce_is_purchasable', array( $this, 'handle_product_purchasability' ), 10, 2 );
		add_filter( 'woocommerce_product_get_price', array( $this, 'get_product_price_based_on_shipping_zone' ), 10, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'get_product_price_based_on_shipping_zone' ), 10, 2 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'adjust_simple_product_price_html' ), 98, 2 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'maybe_display_include_shipping_html' ), 99, 2 );
		add_filter( 'woocommerce_variable_price_html', array( $this, 'adjust_variable_product_price_html' ), 99, 2 );
		add_filter( 'woocommerce_available_variation', array( $this, 'filter_available_variations' ), 10, 3 );
		add_action( 'wp', array( $this, 'set_prices_on_zip_or_zone_missing' ) );
	}

	public function set_prices_on_zip_or_zone_missing() {
		if ( $this->current_shipping_zone_id ) {
			return;
		}
		add_filter( 'woocommerce_is_purchasable', '__return_false' );
		add_filter( 'woocommerce_get_price_html', array( $this, 'display_required_postcode_message' ), 99, 2 );
	}

	public function handle_product_purchasability( $is_purchasable, $product ) {
		$product_id = $product->get_id();

		if ( ! $product_id ) {
			return true;
		}

		if ( $product->is_type( 'variation' ) ) {
			$sales_area = get_post_meta( $product_id, '_product_sales_area', true );
			if ( $this->is_sales_area_allowed( $sales_area, $product->get_parent_id(), $product_id ) ) {
				return true;
			}
		}

		$sales_area = get_post_meta( $product_id, '_product_sales_area', true );
		if ( ! $this->is_sales_area_allowed( $sales_area, $product_id ) ) {
			$this->modify_product_status( $product_id, 'unpurchasable' );
			return false;
		}

		if ( $product->is_type( 'variable' ) ) {
			return $this->handle_variable_product( $product );
		} elseif ( ! $this->is_in_thirteen && ! km_is_product_shippable_out_13( $product ) ) {
			$this->modify_product_status( $product_id, 'unpurchasable' );
			return false;
		}

		return true;
	}

	private function handle_variable_product( $product ) {
		$product_id                   = $product->get_id();
		$all_variations_out_of_stock  = true;
		$all_variations_unpurchasable = true;

		foreach ( $product->get_children() as $variation_id ) {
			$variation             = wc_get_product( $variation_id );
			$is_variation_disabled = $this->is_variation_disabled( $product, $variation );

			if ( $variation->is_in_stock() ) {
				$all_variations_out_of_stock = false;
			}

			$sales_area = get_post_meta( $variation_id, '_product_sales_area', true );

			if ( ! empty( $sales_area ) && $this->is_sales_area_allowed( $sales_area, $variation_id ) ) {
				if ( ! $is_variation_disabled ) {
					$all_variations_unpurchasable = false;
				}
			} else {
				$product_sales_area = get_post_meta( $product_id, '_product_sales_area', true );
				if ( ! $is_variation_disabled && $this->is_sales_area_allowed( $product_sales_area, $product_id ) ) {
					$all_variations_unpurchasable = false;
				}
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

	public function filter_available_variations( $variation_data, $product, $variation ) {
		if ( $this->is_variation_disabled( $product, $variation ) ) {
			$variation_data['is_purchasable']      = false;
			$variation_data['variation_is_active'] = false;
			$variation_data['availability_html']   = '<p class="stock out-of-stock">' . __( 'Indisponible dans votre zone de livraison', 'kingmateriaux' ) . '</p>';
		}
		return $variation_data;
	}

	private function is_variation_disabled( $product, $variation ) {
		$variation_id = $variation->get_id();

		$sales_area = get_post_meta( $variation_id, '_product_sales_area', true );
		if ( $this->is_sales_area_allowed( $sales_area, $product->get_id(), $variation_id ) ) {
			return false;
		}

		return true;
	}

	private function is_sales_area_allowed( $sales_area, $product_id, $variation_id = null ) {
		$zone_id = (int) $this->current_shipping_zone_id;

		if ( $variation_id ) {
			$variation_sales_area = get_post_meta( $variation_id, '_product_sales_area', true );
			if ( empty( $variation_sales_area ) || 'undefined' === $variation_sales_area ) {
				$sales_area = get_post_meta( $product_id, '_product_sales_area', true );
			} else {
				$sales_area = $variation_sales_area;
			}
		}

		if ( empty( $sales_area ) || 'undefined' === $sales_area ) {
			$sales_area = 'all';
		}

		switch ( $sales_area ) {
			case 'all':
				return true;

			case 'in_thirteen_only':
				return $this->is_in_thirteen;

			case 'out_thirteen_only':
				return ! $this->is_in_thirteen;

			case 'custom_zones':
				$custom_zones = $variation_id ? get_post_meta( $variation_id, '_custom_product_shipping_zones', true ) : get_post_meta( $product_id, '_custom_product_shipping_zones', true );
				return is_array( $custom_zones ) && in_array( $zone_id, $custom_zones, true );

			default:
				return true;
		}
	}

	private function modify_product_status( $product_id, $status ) {
		if ( 'unpurchasable' === $status && ! in_array( $product_id, $this->unpurchasable_products ) ) {
			$this->unpurchasable_products[] = $product_id;
		} elseif ( 'out_of_stock' === $status && ! in_array( $product_id, $this->out_of_stock_products ) ) {
			$this->out_of_stock_products[] = $product_id;
		}
		add_filter( 'woocommerce_get_price_html', array( $this, 'display_product_status_message' ), 99, 2 );
	}

	public function display_product_status_message( $price, $product ) {
		$product_id = $product->get_id();
		$messages   = array();

		if ( in_array( $product_id, $this->unpurchasable_products ) ) {
			$messages[] = '<p class="km-price-info">' . __( 'Indisponible dans votre zone de livraison', 'kingmateriaux' ) . '</p>';
		}

		if ( in_array( $product_id, $this->out_of_stock_products ) ) {
			$messages[] = '<p class="km-price-info">' . __( 'En rupture de stock', 'kingmateriaux' ) . '</p>';
		}

		if ( ! empty( $messages ) ) {
			return implode( ' ', $messages );
		}

		return $price;
	}

	public function get_product_price_based_on_shipping_zone( $price, $product, $zone_id = null, $force_recalc = false ) {
		$product_id = $product->get_id();
		$cache_key  = $product_id . '_' . $zone_id;

		if ( ! $force_recalc && isset( $this->calculated_prices[ $cache_key ] ) ) {
			return $this->calculated_prices[ $cache_key ];
		}

		if ( ! empty( $product->get_meta( 'is_free_product' ) ) ) {
			return $price;
		}

		if ( is_null( $zone_id ) ) {
			$zone_id = $this->current_shipping_zone_id;
		}

		if ( km_is_shipping_zone_in_thirteen( $zone_id ) ) {
			if ( $this->product_has_ecotax_meta( $product ) ) {
				$price += $this->ecotaxe_rate;
			}
		} elseif ( did_action( 'woocommerce_before_calculate_totals' ) && km_is_big_bag_price_decreasing_zone( $zone_id ) && ( km_is_big_bag( $product ) || km_is_big_bag_and_slab( $product ) ) ) {
			$price = $this->calculate_localized_product_price( $price, $product, $zone_id, true );
		} elseif ( $force_recalc ) {
			$price = $this->calculate_localized_product_price( $price, $product, $zone_id );
		} else {
			$price = $this->get_localized_product_price( $price, $product, $zone_id );
		}
		return $price;
	}

	private function calculate_localized_product_price( $price, $product, $zone_id, $is_big_bag = false ) {
		$shipping_product = $this->get_shipping_product( $product, $zone_id, $is_big_bag );

		if ( $this->product_has_ecotax_meta( $product ) ) {
			$price += $this->ecotaxe_rate;
		}

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

	private function get_shipping_product( $product, $zone_id, $is_big_bag ) {
		if ( km_is_big_bag_price_decreasing_zone( $zone_id ) && ( $is_big_bag || km_is_big_bag( $product ) || km_is_big_bag_and_slab( $product ) ) ) {
			return km_get_big_bag_shipping_product( $product );
		}
		return km_get_related_shipping_product( $product );
	}

	private function get_localized_product_price( $price, $product, $zone_id ) {
		$localized_product_price = $product->get_meta( '_price_zone_' . $zone_id, true );

		if ( $localized_product_price && is_numeric( $localized_product_price ) ) {
			return $localized_product_price;
		}
		return $this->calculate_localized_product_price( $price, $product, $zone_id );
	}

	private function update_localized_product_price( $product, $zone_id, $price ) {
		$product_id = $product->get_id();
		$check      = update_post_meta( $product_id, '_price_zone_' . $zone_id, $price );

		if ( is_int( $check ) && $check > 0 ) {
			delete_post_meta( $product_id, '_atoonext_sync' );
		}
	}

	public function adjust_simple_product_price_html( $price, $product ) {
		if ( is_product() && $this->product_has_ecotax_meta( $product ) && ( $product->is_type( 'simple' ) || $product->is_type( 'variation' ) ) ) {
			$price .= $this->ecotaxe_info_html;
		}
		return $price;
	}

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

	public function adjust_variable_product_price_html( $price, $product ) {
		$prices      = array();
		$has_ecotaxe = false;

		if ( ! $product->is_purchasable() ) {
			return $price;
		}

		if ( ! $product->get_meta( '_atoonext_sync', true ) && $product->get_meta( '_price_range_' . $this->current_shipping_zone_id, true ) ) {
			return $product->get_meta( '_price_range_' . $this->current_shipping_zone_id, true );
		}

		foreach ( $product->get_available_variations() as $variation ) {
			$variation_obj = wc_get_product( $variation['variation_id'] );

			if ( $variation_obj->is_purchasable() && ! $this->is_variation_disabled( $product, $variation_obj ) ) {
				$prices[] = wc_get_price_including_tax( $variation_obj );

				if ( $this->product_has_ecotax_meta( $product ) || $this->product_has_ecotax_meta( $variation_obj ) ) {
					$has_ecotaxe = true;
				}
			}
		}

		if ( ! empty( $prices ) ) {
			$min_price = min( $prices );
			$max_price = max( $prices );

			$price = $min_price === $max_price ? wc_price( $min_price ) : wc_format_price_range( $min_price, $max_price );

			if ( $has_ecotaxe ) {
				$price .= $this->ecotaxe_info_html;
			}

			update_post_meta( $product->get_id(), '_price_range_' . $this->current_shipping_zone_id, $price );
		}
		return $price;
	}

	public function display_required_postcode_message( $price, $product ) {
		if ( ! $this->current_shipping_zone_id ) {
			return __( 'L\'affichage du prix requiert un code postal', 'kingmateriaux' );
		}
		return $price;
	}

	public function product_has_ecotax_meta( $product ) {
		$ecotax = get_post_meta( $product->get_id(), '_has_ecotax', true );

		return ! empty( $ecotax ) && 'yes' === $ecotax;
	}

	public function get_total_ecotaxe( $context = 'cart' ) {
		$total_ecotaxe = 0;
		$items         = 'cart' === $context ? WC()->cart->get_cart() : WC()->order->get_items();

		foreach ( $items as $item ) {
			if ( $item['_has_ecotax'] ) {
				$total_ecotaxe += $this->ecotaxe_rate_incl_taxes * $item['quantity'];
			}
		}
		return $total_ecotaxe;
	}
}
