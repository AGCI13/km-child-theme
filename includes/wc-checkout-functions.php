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
	$km_shipping_zone = KM_Shipping_Zone::get_instance();
	if ( ! is_checkout() || ! is_user_logged_in() || ! current_user_can( 'manage_options' ) || ! $km_shipping_zone->is_in_thirteen() ) {
		return;
	}
	// Vérifier si sur la page de paiement

		// Noms des cookies que vous pourriez avoir définis
		$shipping_methods = array( 'option-1', 'option-1-express', 'option-2', 'option-2-express' );

		echo '<div id="km-shipping-info-debug" class="km-debug-bar">';
		echo '<h4>DEBUG</h4><img class="modal-debug-close km-modal-close" src="' . esc_url( get_stylesheet_directory_uri() . '/assets/img/cross.svg' ) . '" alt="close modal"></span>';
		echo '<div class="debug-content"><p>Les couts de livraisons sont <strong>calculés lors de la mise à jour du panier</strong>. Pour l\'heure, le VRAC est compté à part. Si une plaque de placo est présente, tous les produits isolation sont comptés à part.</p>';
	foreach ( $shipping_methods as $method ) {
		$cookie_name = 'km_shipping_cost_' . $method;

		if ( isset( $_COOKIE[ sanitize_title( $cookie_name ) ] ) ) {
			$shipping_info = json_decode( stripslashes( $_COOKIE[ $cookie_name ] ), true );

			echo '<table>';
			echo '<thead><tr><th colspan="2">Coûts de livraison pour ' . esc_html( $method ) . ':</th></tr></thead>';
			echo '<tbody>';
			foreach ( $shipping_info as $key => $value ) {
				if ( strpos( $key, 'poids' ) !== false ) {
					$value = esc_html( $value ) . ' Kg';
				} elseif ( strpos( $key, 'placo' ) !== false ) {
					$value = esc_html( $value );
				} else {
					$value = esc_html( $value ) . ' €';
				}
				echo '<tr><td>' . esc_html( $key ) . '</td><td>' . esc_html( $value ) . '</td></tr>';
			}
			echo '</tbody>';
			echo '</table>';
		}
	}

	echo '</div></div>';
}
add_action( 'wp_footer', 'km_display_shipping_info_in_footer' );


/**
 * Ajoute un champ de date et d'heure de retrait
 *
 * @return void
 */
function validate_drive_date_time() {
	if ( isset( $_POST['drive_date'] ) && empty( $_POST['drive_date'] ) ) {
		wc_add_notice( __( 'Veuillez choisir une date dans le calendrier du King Drive.', 'kingmateriaux' ), 'error' );
	}

	if ( isset( $_POST['drive_time'] ) && empty( $_POST['drive_time'] ) ) {
		wc_add_notice( __( 'Veuillez choisir un créneau horaire dans le calendrier du King Drive.', 'kingmateriaux' ), 'error' );
	}
}
add_action( 'woocommerce_checkout_process', 'validate_drive_date_time' );


/**
 * Ajouter le montant des frais de livraison dans le total du panier avec le hook woocommerce_review_order_before_shipping
 *
 * @return void
 */
function km_add_shipping_cost_to_cart_total() {

	$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

	if ( in_array( 'drive', $chosen_shipping_methods, true ) ) {
		return;
	} elseif ( in_array( 'out13', $chosen_shipping_methods, true ) ) {
		$shipping_cost = 'Inclus';
	} else {
		$shipping_cost = WC()->cart->get_cart_shipping_total();
	}

	?>
	<tr class="shipping">
		<th><?php esc_html_e( 'Frais de livraison', 'kingmateriaux' ); ?></th>
		<td data-title="<?php esc_attr_e( 'Frais de livraison', 'kingmateriaux' ); ?>"><span class="shipping-cost"><?php echo $shipping_cost; ?></td>
	</tr>
	<?php
}
add_action( 'woocommerce_review_order_before_order_total', 'km_add_shipping_cost_to_cart_total', );


function km_get_drive_available_days() {
	$offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
	$days   = '';

	for ( $i = $offset; $i < $offset + 20; $i++ ) {
		$day = date_i18n( 'l d F', strtotime( '+' . $i . ' days' ) );

		if ( false !== strpos( $day, 'dimanche' ) ) {
			continue;
		}
		$days .= '<li class="day" data-date="' . esc_html( $day ) . '">' . esc_html( $day ) . '</li>';
	}

	return $days;
}

function km_get_more_drive_available_days() {
	$days = km_get_drive_available_days();
	wp_send_json_success( $days );
}
add_action( 'wp_ajax_get_drive_available_days', 'km_get_more_drive_available_days' );
add_action( 'wp_ajax_nopriv_get_drive_available_days', 'km_get_more_drive_available_days' );
