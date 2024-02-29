<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles dynamic pricing based on shipping zones and classes in WooCommerce.
 */

class KM_Order_Processing {

	/**
	 * The single instance of the class.
	 *
	 * @var KM_Order_Processing|null
	 */

	use SingletonTrait;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	private function __construct() {
		$this->register();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	private function register() {
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_drive_checkout_fields' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_ecotax_to_order_items' ), 10, 4 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_custom_order_item_meta' ), 50, 3 );
	}

	/**
	 * Save drive checkout fields
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $data  The posted data.
	 * @return void
	 */
	public function save_drive_checkout_fields( $order, $data ) {

		$custom_fields = array(
			'drive_date',
			'drive_time',
			'shipping_dates',
		);

		foreach ( $custom_fields as $field ) {
			if ( isset( $_POST[ $field ] ) && ! empty( $_POST[ $field ] ) ) {
				$order->update_meta_data( '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
	}

	/**
	 * Add ecotax to order items
	 *
	 * @param WC_Order_Item_Product $item
	 * @param string                $cart_item_key
	 * @param array                 $values
	 * @param WC_Order              $order
	 * @return void
	 */
	public function add_ecotax_to_order_items( $item, $cart_item_key, $values, $order ) {
		if ( isset( $values['_has_ecotax'] ) && $values['_has_ecotax'] ) {
			$item->add_meta_data( '_has_ecotax', true );
		}
	}

	/**
	 *  Add custom order meta
	 *
	 * @param int      $order_id
	 * @param array    $posted_data
	 * @param WC_Order $order
	 * @return void
	 */
	public function add_custom_order_item_meta( $order_id, $posted_data, $order ) {
		if ( ! $order ) {
			return;
		}

		$first_item_processed = false;

		foreach ( $order->get_items() as $item_id => $item ) {
			$item_data  = $item->get_data();
			$product_id = $item_data['variation_id'] ? $item_data['variation_id'] : $item_data['product_id'];
			$product    = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			// Utiliser get_price('edit') pour obtenir le prix de base du produit.
			$product_price_excl_tax = $product->get_price( 'edit' );

			// Calculer le prix TTC en ajoutant la TVA.
			$tax_rates         = WC_Tax::get_rates( $product->get_tax_class() );
			$tax_rate          = WC_Tax::get_rate_percent_value( array_shift( array_keys( $tax_rates ) ) );
			$product_tax_price = $product_price_excl_tax * ( $tax_rate / 100 );

			$ecotaxe_rate = km_get_ecotaxe_rate();

			if ( $item->get_meta( '_has_ecotax', true ) ) {
				$product_price_excl_tax += $ecotaxe_rate;
				$product_tax_price      += km_get_ecotaxe_rate( true ) - $ecotaxe_rate;
			}

			wc_update_order_item_meta( $item_id, '_actual_product_price_excl', $product_price_excl_tax );
			wc_update_order_item_meta( $item_id, '_actual_product_tax_price', $product_tax_price );

			if ( stripos( $item_data['name'], 'vrac' ) !== false ) {
				wc_update_order_item_meta( $item_id, '_tonnes', $item_data['quantity'] );
			}

			$is_in_thirteen = km_is_shipping_zone_in_thirteen();

			if ( $is_in_thirteen && ! $first_item_processed ) {
				$first_item_processed = true;
				$this->add_shipping_meta_data( $item_id, $_POST );
			} elseif ( ! $is_in_thirteen ) {
				$this->add_shipping_product_meta_data( $item_id, $product );
			}
		}

		update_post_meta( $order_id, '_cookie_cp', km_get_shipping_postcode() );
	}

	/**
	 * Add shipping meta data
	 *
	 * @param int   $item_id
	 * @param array $post_data
	 * @return void
	 */
	private function add_shipping_meta_data( $item_id, $post_data ) {
		$km_shipping_sku        = sanitize_text_field( $post_data['km_shipping_sku'] ?? '0' );
		$product_price_excl_tax = floatval( $post_data['km_shipping_price'] ?? '0' );
		$shipping_tax_amount    = floatval( $post_data['km_shipping_tax'] ?? '0' );

		if ( ! empty( $km_shipping_sku ) ) {
			wc_update_order_item_meta( $item_id, '_ugs_product_shipping', $km_shipping_sku );
			wc_update_order_item_meta( $item_id, '_shipping_price_product_excl', $product_price_excl_tax );
			wc_update_order_item_meta( $item_id, '_tax_prices_on_product_shipping', $shipping_tax_amount );
		}
	}

	/**
	 * Add shipping product meta data
	 *
	 * @param int        $item_id
	 * @param WC_Product $product
	 * @return void
	 */
	private function add_shipping_product_meta_data( $item_id, $product ) {
		$shipping_product = km_get_related_shipping_product( $product );

		if ( $shipping_product ) {
			$shipping_price_excl_tax = wc_get_price_excluding_tax( $shipping_product );
			$shipping_tax_amount     = wc_get_price_including_tax( $shipping_product ) - $shipping_price_excl_tax;

			wc_update_order_item_meta( $item_id, '_ugs_product_shipping', $shipping_product->get_sku() );
			wc_update_order_item_meta( $item_id, '_shipping_price_product_excl', $shipping_price_excl_tax );
			wc_update_order_item_meta( $item_id, '_tax_prices_on_product_shipping', $shipping_tax_amount );
		}
	}
}
