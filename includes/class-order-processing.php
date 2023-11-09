<?php

if ( !defined( 'ABSPATH' ) ) {
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
     *  The shipping zone instance.
     *
     * @var KM_Shipping_zone|null
     */
    public $km_shipping_zone;

    /**
     * Constructor.
     *
     * The constructor is protected to prevent creating a new instance from outside
     * and to prevent creating multiple instances through the `new` keyword.
     */
    private function __construct() {
        $this->km_shipping_zone = KM_Shipping_zone::get_instance();
        $this->register();
    }

    private function register() {
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_custom_order_meta' ), 50, 3 );
    }

    /**
     *  Add custom order meta
     *
     * @param int $order_id
     * @return void
     */
    public function add_custom_order_meta( $order_id, $posted_data, $order ) {
        if ( !$order ) {
            return;
        }

        foreach ( $order->get_items() as $item_id => $item ) {

            $item_data = $item->get_data();

            wc_update_order_item_meta( $item_id, '_actual_product_price_excl', $item_data['total'] );

            wc_update_order_item_meta( $item_id, '_actual_product_tax_price', $item_data['total_tax'] );

            // Check if the product name contains 'VRAC' and update item meta
            if ( strpos( $item_data['name'], 'VRAC' ) !== false ) {
                wc_update_order_item_meta( $item_id, '_tonnes', $item_data['quantity'] );
            }

            if ( $item_data['variation_id'] ) {
                $product = wc_get_product( $item_data['variation_id'] );
            } else {
                $product = wc_get_product( $item_data['product_id'] );
            }

            if ( !$product ) {
                continue;
            }
            $shipping_product = $this->km_shipping_zone->get_related_shipping_product( $product );

            if ( $shipping_product ) {
                // Obtenir le prix TTC
                $price_incl_tax = wc_get_price_including_tax( $shipping_product );
                // Obtenir le prix HT
                $price_excl_tax = wc_get_price_excluding_tax( $shipping_product );
                // Calculer le montant de la taxe
                $tax_amount = $price_incl_tax - $price_excl_tax;

                wc_update_order_item_meta( $item_id, '_ugs_product_shipping', $shipping_product->get_sku() );
                wc_update_order_item_meta( $item_id, '_shipping_price_product_excl', $price_excl_tax );
                wc_update_order_item_meta( $item_id, '_tax_prices_on_product_shipping', $tax_amount );
            }
        }

        update_post_meta( $order_id, '_cookie_cp', $this->km_shipping_zone->zip_code );
    }
}
