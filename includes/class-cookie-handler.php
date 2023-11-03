<?php
/**
 * Handles cookie retrieval and validation for Kingmateriaux e-commerce site.
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class KM_Cookie_Handler {

    /**
     * Retrieve the value of a cookie.
     *
     * @param string $cookie_name The name of the cookie to retrieve.
     * @return mixed The value of the cookie or null if not set.
     */
    public static function get_cookie( $cookie_name ) {
        return isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : null;
    }

    /**
     * Validate the zip code retrieved from the cookie.
     *
     * @param string $zip_code The zip code to validate.
     * @return bool True if valid, false otherwise.
     */
    public function validate_zip_code( $zip_code ) {
        // Define the pattern for a valid zip code. Adjust the regex according to your country's zip codes.
        $pattern = '/^[0-9]{5}$/'; // Example for a French zip code.

        return preg_match( $pattern, $zip_code );
    }

    /**
     * Validate the shipping zone retrieved from the cookie.
     *
     * @param string $shipping_zone The shipping zone to validate.
     * @return bool True if valid, false otherwise.
     */
    public function validate_shipping_zone( $shipping_zone ) {
        // Define the valid shipping zones.
        $valid_zones = array( 'local', 'national', 'international' );

        return in_array( $shipping_zone, $valid_zones, true );
    }
}
