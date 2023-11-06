<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles dynamic pricing based on shipping zones and classes in WooCommerce.
 */

class KM_Shipping_Zone {

    /**
     * The single instance of the class.
     *
     * @var KM_Shipping_Zone|null
     */

    use SingletonTrait;

    /**
     * The shipping zone ID.
     *
     * @var int|null
     */
    public $shipping_zone_id = null;

    /**
     * The shipping zone name.
     *
     * @var string|null
     */
    public $shipping_zone_name = '';

    /**
     * Constructor.
     *
     * The constructor is protected to prevent creating a new instance from outside
     * and to prevent creating multiple instances through the `new` keyword.
     */
    private function __construct() {
        $this->shipping_zone_id   = $this->get_shipping_zone_id_from_cookie();
        $this->shipping_zone_name = $this->get_shipping_zone_name();

        $this->register();
    }

    public function register() {
        add_action( 'wp_ajax_get_shipping_zone_id_from_zip', array( $this, 'get_shipping_zone_id_from_zip' ) );
        add_action( 'wp_ajax_nopriv_get_shipping_zone_id_from_zip', array( $this, 'get_shipping_zone_id_from_zip' ) );
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
        $zone_id = isset( $_COOKIE['shipping_zone'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['shipping_zone'] ) ) : null;

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
     * Checks if the current shipping zone is in the thirtheen.
     *
     * @return bool True if the shipping zone is in the thirtheen, false otherwise.
     */

    public function is_in_thirtheen() {

        $shipping_zone_id = $this->get_shipping_zone_id_from_cookie();

        if ( in_array( $shipping_zone_id, array( 12, 13, 14, 15, 16, 17 ) ) ) {
            return true;
        }

        return false;
    }


    /** 
     * Ajax callback to get the shipping zone ID from a zip code.
     * 
     * @return void | json
     */
    public function get_shipping_zone_id_from_zip() {

        $nonce_value = isset( $_POST['nonce_header_postcode'] ) && !empty( $_POST['nonce_header_postcode'] ) ? wp_unslash( $_POST['nonce_header_postcode'] ) : '';
        $nonce_value = sanitize_text_field( $nonce_value );

        if ( !wp_verify_nonce( $nonce_value, 'get_shipping_zone_id_from_zip' ) ) {
            wp_send_json_error( array( 'message' => 'La vérification du nonce a échoué.' ) );
        }

        $zip_code = isset( $_POST['zip'] ) && !empty( $_POST['zip'] ) ? wp_unslash( $_POST['zip'] ) : '';
        $zip_code = sanitize_text_field( $zip_code );

        setcookie( 'shipping_zone', '', time() - 3600, '/' );

        $shipping_zones = WC_Shipping_Zones::get_zones();
        $found_zone     = null;

        try {
            foreach ( $shipping_zones as $zone_data ) {
                $zone           = new WC_Shipping_Zone( $zone_data['id'] );
                $zone_locations = $zone->get_zone_locations();

                foreach ( $zone_locations as $location ) {
                    if ( strpos( $location->code, '...' ) !== false ) {
                        list($start_zip, $end_zip) = explode( '...', $location->code );
                        if ( $zip_code >= $start_zip && $zip_code <= $end_zip ) {
                            $found_zone = $zone_data['id'];
                            break 2; // Break out of both foreach loops
                        }
                    } else {
                        if ( $zip_code === $location->code ) {
                            $found_zone = $zone_data['id'];
                            break 2;
                        }
                    }
                }
            }
            wp_send_json_success( $found_zone );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => 'Une erreur est survenue : ' . $e->getMessage() ) );
        }
        wp_die();
    }

}
