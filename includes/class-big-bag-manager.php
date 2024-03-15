<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles Big bag management based on product added in WooCommerce cart.
 */

class KM_Big_Bag_Manager {

	use SingletonTrait;

	/**
	 * The shipping zone IDs where the big bag price is not decreasing.
	 *
	 * @var array
	 */
	private $big_bag_decreasing_price_zone = array( 5, 6, 7 );

	/**
	 * The big bag slabs product IDs.
	 *
	 * @var array
	 */
	private $big_bag_slabs_ids = array( 96800, 96815 );


	/**
	 * The count of big bags in the cart.
	 *
	 * @var int
	 */
	private $count_big_bag_in_cart = 0;

	/**
	 * The count of big bags and slabs in the cart.
	 *
	 * @var int
	 */
	private $count_big_bag_and_slab_in_cart = 0;

	/**
	 * The price of shipping for one big bag.
	 *
	 * @var float
	 */
	private $one_big_bag_shipping_price;

	/**
	 * The price of shipping for one big bag and slab.
	 *
	 * @var float
	 */
	private $one_big_bag_and_slab_shipping_price;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'adjust_cart_item_prices' ), 20, 1 );
	}

	/**
	 * Check if the product is a big bag.
	 *
	 * @param int|WC_Product $product The product ID or the product object.
	 * @return bool
	 */
	public function product_has_decreasing_shipping_price( $product ) {
		return $this->is_big_bag( $product ) || $this->is_big_bag_and_slab( $product );
	}

	/**
	 * Check if the decreasing price can apply to big bags for the current shipping zone.
	 *
	 * @return bool
	 */
	public function is_big_bag_price_decreasing_zone( $zone_id = null ) {
		$zone_id = $zone_id ? $zone_id : km_get_current_shipping_zone_id();
		return in_array( $zone_id, $this->big_bag_decreasing_price_zone, true );
	}

	/***
	 * Adjust cart item prices based on big bags shipping price.
	 *
	 * @param WC_Cart $cart The cart object.
	 */
	public function adjust_cart_item_prices( $cart ) {

		if ( ( is_admin() && ! defined( 'DOING_AJAX' ) ) || did_action( 'woocommerce_before_calculate_totals' ) >= 2
		|| ! $this->is_big_bag_price_decreasing_zone() || ! $this->count_items_with_decreasing_shipping_price_in_cart() ) {
			return;
		}

		$cart_content = $cart->get_cart();

		foreach ( $cart_content as $cart_item ) {

			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

			if ( $this->is_big_bag( $product_id ) ) {
				$total_quantity             = $this->count_big_bag_in_cart + $this->count_big_bag_and_slab_in_cart;
				$one_big_bag_shipping_price = $this->one_big_bag_shipping_price ? $this->one_big_bag_shipping_price : $this->get_big_bag_shipping_product_price( $product_id );
				error_log( var_export( $one_big_bag_shipping_price, true ) );
			} elseif ( $this->is_big_bag_and_slab( $product_id ) ) {
				$total_quantity             = $this->count_big_bag_in_cart + $this->count_big_bag_and_slab_in_cart;
				$one_big_bag_shipping_price = $this->one_big_bag_and_slab_shipping_price ? $this->one_big_bag_and_slab_shipping_price : $this->get_big_bag_shipping_product_price( $cart_item['variation_id'] );
				error_log( var_export( $one_big_bag_shipping_price, true ) );
			} else {
				continue;
			}

			if ( ! $one_big_bag_shipping_price ) {
				continue;
			}

			$big_bags_shipping_price = $this->calculate_big_bags_shipping_price( $product_id, $total_quantity, $one_big_bag_shipping_price );
			$raw_item_price          = floatval( $cart_item['data']->get_price( 'edit' ) );
			$new_price               = $raw_item_price + ( $big_bags_shipping_price / $total_quantity ) - $one_big_bag_shipping_price;
			error_log( var_export( $new_price, true ) );

			$cart_item['data']->set_price( $new_price );
		}
	}

	/**
	 * Calcule le prix de livraison des big bags.
	 *
	 * @param int $product_id L'ID du produit.
	 * @param int $bigbag_quantity La quantité de big bags.
	 * @return float Le prix de livraison des big bags.
	 */
	public function calculate_big_bags_shipping_price( $product_id, $bigbag_quantity, $one_big_bag_shipping_price ) {
		$shipping_price = 0;

		$bigbag_quantity_modulo   = $bigbag_quantity % 8;
		$bigbag_quantity_division = intdiv( $bigbag_quantity, 8 );

		if ( $bigbag_quantity_division > 0 ) {
			$shipping_price += $bigbag_quantity_division * $this->get_big_bag_shipping_product_price( $product_id, 8 );
		}

		if ( $bigbag_quantity_modulo > 0 ) {
			$additional_price = 1 === $bigbag_quantity_modulo ? $one_big_bag_shipping_price : $this->get_big_bag_shipping_product_price( $product_id, $bigbag_quantity_modulo );
			$shipping_price  += $additional_price;
		}

		return $shipping_price;
	}

	/**
	 * Récupère la quantité de big bag dans le panier.
	 *
	 * @param array $cart Le panier.
	 * @return int La quantité de big bag dans le panier.
	 */
	public function get_big_bag_quantity_in_cart( $cart = '' ) {

		if ( empty( $cart ) ) {
			$cart = WC()->cart->get_cart();
		}

		$count = 0;

		foreach ( $cart as $cart_item ) {
			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

			if ( $this->is_big_bag( $product_id ) ) {
				$count += $cart_item['quantity'];
			} if ( $this->is_big_bag_and_slab( $product_id ) ) {
				$count += $cart_item['quantity'];
			}
		}
		return $count;
	}

	/**
	 * Vérifie si le produit est un big bag.
	 *
	 * @param int|WC_Product $product L'ID du produit ou l'objet produit.
	 * @return bool Vrai si le produit est un big bag, faux sinon.
	 */
	public function is_big_bag( $product ) {
		$product_id = $product instanceof WC_Product ? $product->get_id() : $product;

		if ( has_term( 'location-big-bag', 'product_cat', $product_id ) ||
			( stripos( wc_get_product( $product_id )->get_name(), 'big bag' ) !== false && ! $this->is_big_bag_and_slab( $product ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Vérifie si le produit est un big bag de dalles.
	 *
	 * @param int|WC_Product $product L'ID du produit ou l'objet produit.
	 * @return bool Vrai si le produit est un big bag de dalles, faux sinon.
	 */
	public function is_big_bag_and_slab( $product ) {

		$product = $product instanceof WC_Product ? $product : wc_get_product( $product );

		if ( $product->is_type( 'variation' ) ) {
			$product_id = $product->get_parent_id();
		} else {
			$product_id = $product->get_id();
		}

		return in_array( $product_id, $this->big_bag_slabs_ids, true );
	}

	/**
	 * Récupère le produit de livraison associé à un produit big bag.
	 *
	 * @param WC_Product $product Le produit pour lequel récupérer le produit de livraison.
	 * @return WC_Product
	 */
	public function get_big_bag_shipping_product( $product_id, $qty = 1 ) {
		$shipping_product_name = $this->is_big_bag( $product_id ) ? "{$qty}-big-bag-degressif" : "prix-degressif-bb-dalles-{$qty}";
		$shipping_product_post = get_page_by_path( $shipping_product_name, OBJECT, 'product' );

		if ( null === $shipping_product_post ) {
			return null;
		}

		return wc_get_product( $shipping_product_post->ID );
	}
	/**
	 * Récupère le prix du produit de livraison associé à un produit.
	 *
	 * @param WC_Product $product Le produit pour lequel récupérer le prix du produit de livraison.
	 * @param int        $qty La quantité de big bags.
	 * @return int|bool Le prix du produit de livra ison ou faux si le produit de livraison n'existe pas.
	 */
	public function get_big_bag_shipping_product_price( $product, $qty = 1 ) {
		$shipping_product = $this->get_big_bag_shipping_product( $product, $qty );
		if ( ! $shipping_product ) {
			return null;
		}
		return floatval( $shipping_product->get_price( 'edit' ) );
	}

	/**
	 * Vérifie si un big bag est dans le panier.
	 *
	 * @return bool
	 */
	public function count_items_with_decreasing_shipping_price_in_cart() {

		$cart = WC()->cart->get_cart();

		foreach ( $cart as $cart_item ) {
			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

			// Vérifier si le produit est un big bag ou un big bag et dalle et si la zone actuelle permet un prix décroissant.
			if ( ( $this->is_big_bag( $product_id ) ) ) {
				$this->count_big_bag_in_cart += $cart_item['quantity'];
			}

			if ( ( $this->is_big_bag_and_slab( $product_id ) ) ) {
				$this->count_big_bag_and_slab_in_cart += $cart_item['quantity'];
			}
		}

		if ( $this->count_big_bag_in_cart > 0 || $this->count_big_bag_and_slab_in_cart > 0 ) {
			return true;
		}

		return false;
	}
}
