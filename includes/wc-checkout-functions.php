<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Rends le code postal non-modifiable dans le tunnel de commande
 *
 * @param $fields
 * @return array
 */
function km_override_checkout_fields( $fields ): array {
	$fields['billing']['billing_postcode']['km_attributes'] = array( 'readonly' => 'readonly' );
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'km_override_checkout_fields' );

/**
 * Rends le code postal non modifiable dans l'adresse de livraison woocommerce
 *
 * @param $address_fields
 * @return array
 */
function km_override_default_address_fields( $address_fields ): array {
	// Vérifie si l'utilisateur est sur la page de livraison
	if ( is_wc_endpoint_url( 'edit-address' ) && isset( $_GET['address'] ) && $_GET['address'] === 'shipping' ) {
		$address_fields['postcode']['km_attributes'] = array( 'readonly' => 'readonly' );
	}
	return $address_fields;
}
add_filter( 'woocommerce_default_address_fields', 'km_override_default_address_fields' );

/**
 * Remplit automatiquement le champ code postal avec le cookie
 *
 * @return void
 */
function km_override_checkout_init(): void {
	if ( isset( $_COOKIE['zip_code'] ) ) {
		$zip_code                  = explode( '-', $_COOKIE['zip_code'] )[0];
		$_POST['billing_postcode'] = $zip_code;
	}
}
add_action( 'woocommerce_checkout_init', 'km_override_checkout_init' );