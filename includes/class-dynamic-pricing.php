<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use KM_Shipping_zone;

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
        // Hook pour le produit simple
        add_filter( 'woocommerce_product_get_price', array( $this, 'change_product_price_based_on_shipping_zone' ), 1, 2 );
        // Hook pour les variations de produit
        add_filter( 'woocommerce_product_variation_get_price', array( $this, 'change_product_price_based_on_shipping_zone' ), 1, 2 );
        // Hook pour les prices des variations de produit (notamment min et max)
        add_filter( 'woocommerce_variation_prices', array( $this, 'change_variation_prices_based_on_shipping_zone' ), 10, 3 );
    }

    /**
     * Obtient le prix d'un produit par son titre.
     *
     * @param string $product_title Le titre du produit.
     * @return float|null Le prix du produit ou null si le produit n'est pas trouvé.
     */
    public function get_price_by_product_title( $product_title ) {

        // Arguments pour la requête
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 1,
            'post_status'    => array( 'private' ),
            'name'           => sanitize_title( $product_title ),
        );

        // Effectuer la requête
        $products = get_posts( $args );

        // Si un produit est trouvé, retourner son prix
        if ( !empty( $products ) ) {
            $product = wc_get_product( $products[0]->ID );
            return $product->get_price();
        }

        return null;
    }

    public function change_product_price_based_on_shipping_zone( $price, $product ) {

        if ( $this->km_shipping_zone->is_in_thirtheen() ) {
            return $price;
        }

        // Obtenir la classe de livraison du produit
        $shipping_class_id = $product->get_shipping_class_id();

        if ( $shipping_class_id ) {
            // Récupérer l'objet de la classe de livraison
            $shipping_class_term = get_term( $shipping_class_id, 'product_shipping_class' );

            if ( $shipping_class_term && !is_wp_error( $shipping_class_term ) ) {
                // Récupérer le nom de la classe de livraison
                $shipping_class_name = $shipping_class_term->name;

                $shipping_price_name = $this->km_shipping_zone->shipping_zone_name . ' ' . $shipping_class_name;
                // echo '<h3>$shipping_price_name</h3><pre>' . var_export( $shipping_price_name, true ) . '</pre>';

                $shipping_price = $this->get_price_by_product_title( $shipping_price_name );

                if ( $shipping_price > 0 ) {
                    $price += $shipping_price;
                }
            }
        }

        return $price;
    }

    public function change_variation_prices_based_on_shipping_zone( $prices, $variation, $product ) {
        foreach ( $prices as $price_type => $variation_prices ) {
            foreach ( $variation_prices as $variation_id => $price ) {
                $variation_prices[ $variation_id ] = $this->change_product_price_based_on_shipping_zone( $price, wc_get_product( $variation_id ) );
            }
            $prices[ $price_type ] = $variation_prices;
        }
        return $prices;
    }

}
