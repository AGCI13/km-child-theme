<?php

if ( !defined( 'ABSPATH' ) ) {
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
    protected static $instance = null;

    public $shipping_zone_id;
    public $shipping_zone_name;

    /**
     * Constructor.
     *
     * The constructor is protected to prevent creating a new instance from outside
     * and to prevent creating multiple instances through the `new` keyword.
     */
    public function __construct() {
        var_dump($_COOKIE);
        $this->shipping_zone_id = $this->get_shipping_zone_id_from_cookie();
        var_dump( $this->shipping_zone_id );
        $this->shipping_zone_name = $this->get_shipping_zone_name();
        var_dump( $this->shipping_zone_name );

        $this->register();
    }

    // // Prevent the instance from being cloned (which creates a second instance of it)
    // public function __clone() {
    // }

    // // Prevent from being unserialized (which would create a second instance of it)
    // public function __wakeup() {
    // }

    public function register() {

        if ( !$this->is_in_thirtheen() ) {
            // Hook pour le produit simple
            add_filter( 'woocommerce_product_get_price', array( $this, 'km_adjust_price_based_on_shipping_zone' ), 10, 2 );

            // Hook pour les variations de produit
            add_filter( 'woocommerce_product_variation_get_price', array( $this, 'km_adjust_price_based_on_shipping_zone' ), 10, 2 );
        }

    }

    /**
     * Retrieves the shipping class for a given product.
     *
     * @param int|WC_Product $product The product ID or product object.
     * @return string|false The shipping class slug or false on failure.
     */
    public function get_product_shipping_class( $product ) {
        // If an ID is passed, get the product object
        if ( is_numeric( $product ) ) {
            $product = wc_get_product( $product );
        }

        // If the product doesn't exist, return false
        if ( !$product instanceof WC_Product ) {
            return false;
        }

        // Get the shipping class ID
        $shipping_class_id = $product->get_shipping_class_id();

        // If there is no shipping class ID, return false
        if ( empty( $shipping_class_id ) ) {
            return false;
        }

        // Get the shipping class term
        $shipping_class_term = get_term( $shipping_class_id, 'product_shipping_class' );

        // Return the shipping class slug or false if not found
        return ( !is_wp_error( $shipping_class_term ) && $shipping_class_term ) ? $shipping_class_term->slug : false;
    }

    /**
     * Retrieves the shipping zone ID from the 'shipping_zone' cookie.
     *
     * @return int|null The shipping zone ID or null if the cookie is not set or the value is invalid.
     */
    public function get_shipping_zone_id_from_cookie() {
        // Retrieve the 'shipping_zone' cookie value using the KM_Cookie_Handler
        $zone_id = KM_Cookie_Handler::get_cookie( 'shipping_zone' );

        // Validate the zone ID to ensure it's a positive integer
        $zone_id = is_numeric( $zone_id ) ? (int) $zone_id : null;

        // Return the zone ID if it is a valid number, null otherwise
        return ( $zone_id > 0 ) ? $zone_id : null;
    }

    /**
     * Gets the shipping zone name using the ID from the 'shipping_zone' cookie.
     *
     * @return string|null The name of the shipping zone or null if the zone does not exist.
     */
    public function get_shipping_zone_name() {

        // First, get the shipping zone ID
        $shipping_zone_id = $this->shipping_zone_id ?: $this->get_shipping_zone_id_from_cookie();

        // If no valid zone ID is found, return null
        if ( null === $shipping_zone_id ) {
            return null;
        }

        // Get the shipping zone object by ID
        $shipping_zone = new WC_Shipping_Zone( $shipping_zone_id );

        // Check if the shipping zone ID is valid by checking if it's greater than 0
        if ( 0 === $shipping_zone->get_id() ) {
            return null;
        }

        // Return the shipping zone name
        return $shipping_zone->get_zone_name();
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

    public function is_in_thirtheen() {

        $shipping_zone_id = $this->shipping_zone_id ?: $this->get_shipping_zone_id_from_cookie();

        if ( in_array( $shipping_zone_id, array( 12, 13, 14, 15, 16, 17 ) ) ) {
            return true;
        }

        return false;
    }

    public function km_adjust_price_based_on_shipping_zone( $price, $product ) {

        // Obtenir la classe de livraison du produit
        $shipping_class_id = $product->get_shipping_class_id();

        if ( $shipping_class_id ) {
            // Récupérer l'objet de la classe de livraison
            $shipping_class_term = get_term( $shipping_class_id, 'product_shipping_class' );

            if ( $shipping_class_term && !is_wp_error( $shipping_class_term ) ) {
                // Récupérer le nom de la classe de livraison
                $shipping_class_name = $shipping_class_term->name;

                $shipping_price_name = $this->shipping_zone_name . ' ' . $shipping_class_name;

                $shipping_price = $this->get_price_by_product_title( $shipping_price_name );

                if ( $shipping_price > 0 ) {
                    $price += $shipping_price;
                }
            }
        }

        return $price;
    }

}

$km_dynamic_pricing = new KM_Dynamic_Pricing();
