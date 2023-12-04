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

function km_display_shipping_info_in_footer() {
	// Vérifier si sur la page de paiement
	if ( is_checkout() && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
		// Noms des cookies que vous pourriez avoir définis
		$shipping_methods = array( 'option-1', 'option-1-express', 'option-2', 'option-2-express' );

		echo '<div id="km-shipping-info-debug">';
		echo '<img class="modal-debug-close km-modal-close" src="' . esc_url( get_stylesheet_directory_uri() . '/assets/img/cross.svg' ) . '" alt="close modal"></span>';
		echo '<h3>DEBUG</h3><p>Les couts de livraisons sont <strong>calculés lors de la mise à jour du panier</strong>. Pour l\'heure, le VRAC est compté à part. Si une plaque de placo est présente, tous les produits isolation sont comptés à part.</p>';
		foreach ( $shipping_methods as $method ) {
			$cookie_name = 'km_shipping_cost_' . $method;

			if ( isset( $_COOKIE[ sanitize_title( $cookie_name ) ] ) ) {
				$shipping_info = json_decode( stripslashes( $_COOKIE[ $cookie_name ] ), true );

				echo '<h4>Coûts de livraison pour ' . esc_html( $method ) . ':</h4>';
				echo '<ul>';
				foreach ( $shipping_info as $key => $value ) {
					if ( strpos( $key, 'poids' ) !== false ) {
						$value = esc_html( $value ) . ' Kg';
					} elseif ( strpos( $key, 'placo' ) !== false ) {
						$value = esc_html( $value );
					} else {
						$value = esc_html( $value ) . ' €';
					}
					echo '<li>' . esc_html( $key ) . ': ' . esc_html( $value ) . '</li>';
				}
				echo '</ul>';
			}
		}

		echo '</div>';
	}
}
add_action( 'wp_footer', 'km_display_shipping_info_in_footer' );
