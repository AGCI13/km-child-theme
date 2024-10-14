<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles Big bag management based on product added in WooCommerce cart.
 */
class KM_Big_Bag_Manager {

	use SingletonTrait;

	private $big_bag_decreasing_price_zone  = array( 5, 6, 7 );
	private $count_big_bag_in_cart          = 0;
	private $count_big_bag_and_slab_in_cart = 0;
	private $one_big_bag_shipping_price;
	private $one_big_bag_and_slab_shipping_price;
	private $product_types_cache     = array();
	private $shipping_products_cache = array();

	public function __construct() {
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'adjust_cart_item_prices' ), 20, 1 );
	}

	public function product_has_decreasing_shipping_price( $product ) {
		return $this->is_big_bag( $product ) || $this->is_big_bag_and_slab( $product );
	}

	public function is_big_bag_price_decreasing_zone( $zone_id = null ) {
		$zone_id = $zone_id ?: km_get_current_shipping_zone_id();
		return in_array( $zone_id, $this->big_bag_decreasing_price_zone, true );
	}

	public function adjust_cart_item_prices( $cart ) {
		if ( ( is_admin() && ! defined( 'DOING_AJAX' ) ) || did_action( 'woocommerce_before_calculate_totals' ) >= 2
			|| ! $this->is_big_bag_price_decreasing_zone() || ! $this->count_items_with_decreasing_shipping_price_in_cart() ) {
			return;
		}

		$cart_content   = $cart->get_cart();
		$total_quantity = $this->count_big_bag_in_cart + $this->count_big_bag_and_slab_in_cart;

		foreach ( $cart_content as $cart_item ) {
			$product_id = $cart_item['variation_id'] ?: $cart_item['product_id'];

			if ( $this->is_big_bag( $product_id ) ) {
				$one_big_bag_shipping_price = $this->one_big_bag_shipping_price ?: $this->get_big_bag_shipping_product_price( $product_id );
			} elseif ( $this->is_big_bag_and_slab( $product_id ) ) {
				$one_big_bag_shipping_price = $this->one_big_bag_and_slab_shipping_price ?: $this->get_big_bag_shipping_product_price( $cart_item['variation_id'] );
			} else {
				continue;
			}

			if ( ! $one_big_bag_shipping_price ) {
				continue;
			}

			$big_bags_shipping_price = $this->calculate_big_bags_shipping_price( $product_id, $total_quantity, $one_big_bag_shipping_price );
			$raw_item_price          = floatval( $cart_item['data']->get_price( 'edit' ) );

			// Assurez-vous que le prix de base inclut déjà le prix de livraison pour un big bag
			$base_price_with_shipping = $raw_item_price;

			// Calculez le nouveau prix en ajoutant la différence entre le prix d'expédition dégressif et le prix d'expédition standard
			$new_price = $base_price_with_shipping + ( $big_bags_shipping_price / $total_quantity ) - $one_big_bag_shipping_price;

			$cart_item['data']->set_price( $new_price );
		}
	}

	public function calculate_big_bags_shipping_price( $product_id, $bigbag_quantity, $one_big_bag_shipping_price ) {
		$bigbag_quantity_modulo   = $bigbag_quantity % 8;
		$bigbag_quantity_division = intdiv( $bigbag_quantity, 8 );

		$shipping_price = $bigbag_quantity_division > 0 ? $bigbag_quantity_division * $this->get_big_bag_shipping_product_price( $product_id, 8 ) : 0;

		if ( $bigbag_quantity_modulo > 0 ) {
			$additional_price = 1 === $bigbag_quantity_modulo ? $one_big_bag_shipping_price : $this->get_big_bag_shipping_product_price( $product_id, $bigbag_quantity_modulo );
			$shipping_price  += $additional_price;
		}

		return $shipping_price;
	}

	public function get_big_bag_quantity_in_cart( $cart = '' ) {
		if ( empty( $cart ) ) {
			$cart = WC()->cart->get_cart();
		}

		$count = 0;
		foreach ( $cart as $cart_item ) {
			$product_id = $cart_item['variation_id'] ?: $cart_item['product_id'];
			if ( $this->is_big_bag( $product_id ) || $this->is_big_bag_and_slab( $product_id ) ) {
				$count += $cart_item['quantity'];
			}
		}
		return $count;
	}

	public function is_big_bag( $product ) {
		return $this->check_product_type( $product, 'big_bag' );
	}

	public function is_big_bag_and_slab( $product ) {
		return $this->check_product_type( $product, 'big_bag_and_slab' );
	}

	private function check_product_type( $product, $type ) {
		$product    = $product instanceof WC_Product ? $product : wc_get_product( $product );
		$product_id = $product->get_id();

		if ( ! isset( $this->product_types_cache[ $product_id ] ) ) {
			if ( $product->is_type( 'variation' ) ) {
				$variation_type                           = get_field( '_product_type', $product_id );
				$this->product_types_cache[ $product_id ] = $variation_type ?: get_field( '_product_type', $product->get_parent_id() );
			} else {
				$this->product_types_cache[ $product_id ] = get_field( '_product_type', $product_id );
			}
		}

		return $this->product_types_cache[ $product_id ] === $type;
	}

	public function get_big_bag_shipping_product( $product_id, $qty = 1 ) {
		$cache_key = $product_id . '_' . $qty;
		if ( ! isset( $this->shipping_products_cache[ $cache_key ] ) ) {
			$shipping_product_name                       = $this->is_big_bag( $product_id ) ? "{$qty}-big-bag-degressif" : "prix-degressif-bb-dalles-{$qty}";
			$shipping_product_post                       = get_page_by_path( $shipping_product_name, OBJECT, 'product' );
			$this->shipping_products_cache[ $cache_key ] = $shipping_product_post ? wc_get_product( $shipping_product_post->ID ) : null;
		}
		return $this->shipping_products_cache[ $cache_key ];
	}

	public function get_big_bag_shipping_product_price( $product, $qty = 1 ) {
		$shipping_product = $this->get_big_bag_shipping_product( $product, $qty );
		return $shipping_product ? floatval( $shipping_product->get_price( 'edit' ) ) : null;
	}

	public function count_items_with_decreasing_shipping_price_in_cart() {
		$cart                        = WC()->cart->get_cart();
		$this->count_big_bag_in_cart = $this->count_big_bag_and_slab_in_cart = 0;

		foreach ( $cart as $cart_item ) {
			$product_id = $cart_item['variation_id'] ?: $cart_item['product_id'];
			if ( $this->is_big_bag( $product_id ) ) {
				$this->count_big_bag_in_cart += $cart_item['quantity'];
			} elseif ( $this->is_big_bag_and_slab( $product_id ) ) {
				$this->count_big_bag_and_slab_in_cart += $cart_item['quantity'];
			}
		}

		return $this->count_big_bag_in_cart > 0 || $this->count_big_bag_and_slab_in_cart > 0;
	}
}
