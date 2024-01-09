<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles newsletter email discount form.
 */
function km_handle_email_discount_10_form( $record, $handler ) {
	// Assurez-vous que le formulaire est celui que vous voulez.
	$form_name = $record->get_form_settings( 'form_name' );

	// Remplacez 'Votre_Nom_De_Formulaire' par le nom de votre formulaire.
	if ( 'email_discount_10' !== $form_name ) {
		return;
	}

	$raw_fields = $record->get( 'fields' );
	$fields     = array();
	foreach ( $raw_fields as $id => $field ) {
		$fields[ $id ] = $field['value'];
	}

	$opt    = 'true';
	$source = 'nl';
	$name   = $fields['name'] ?? '';
	$email  = $fields['email'] ?? '';

	if ( is_email( $email ) === false ) {
		return;
	}

	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$name         = esc_html( $current_user->user_login );
	}

	km_send_data_to_cloudops( $name, $email, $opt, $source );
}
add_action( 'elementor_pro/forms/new_record', 'km_handle_email_discount_10_form', 10, 2 );

// // CASE NEWSLETTER FORM
// add_action( 'mc4wp_form_subscribed', 'wac_get_newsletter_form_data', 1 );
// function wac_get_newsletter_form_data() {
// $name   = esc_html( $_POST['FNAME'] );
// $email  = esc_html( $_POST['EMAIL'] );
// $opt    = true;
// $source = 'nl';

// Do the magic
// send_data_to_cloudops( $name, $email, $opt, $source );
// }

// CASE EMAIL INPUT CART PAGE
add_action( 'wp_ajax_wac_send_email', 'wac_send_email' );
add_action( 'wp_ajax_nopriv_wac_send_email', 'wac_send_email' );
function wac_send_email() {
	if ( $_POST['wac_email'] ) {

		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$name         = esc_html( $current_user->user_login );
		} else {
			$name = '';
		}

		$email  = filter_var( trim( $_POST['wac_email'] ), FILTER_SANITIZE_EMAIL );
		$opt    = 'true';
		$source = 'panier';

		// Add session data wac-email to hide the email field on cart page
		WC()->session->set( 'wac_email', $email );

		// Do the magic
		send_data_to_cloudops( $name, $email, $opt, $source );

		// Unset vars
		unset( $_POST['wac_email'], $name, $email, $opt, $source );
	}
}

// CASE ORDER CHECKOUT FORM
add_action( 'woocommerce_before_checkout_process', 'km_get_checkout_form_data' );
function km_get_checkout_form_data() {

	if ( ! isset( $_POST['inscription_newsletter'] ) || ! isset( $_POST['billing_first_name'] ) || ! isset( $_POST['billing_email'] ) ) {
		return;
	}

	$name   = esc_html( $_POST['billing_first_name'] );
	$email  = esc_html( $_POST['billing_email'] );
	$opt    = $_POST['inscription_newsletter'] ? 'true' : 'false';
	$source = 'order';

	km_send_data_to_cloudops( $name, $email, $opt, $source );
}

// REACH CLOUD OPS ENDPOINT WITH DATA AS URL PARAMS
function km_send_data_to_cloudops( $name, $email, $opt, $source ) {

	// Get current user id if user is connected, else leave empty
	$user_id = get_current_user_id() ?: '';

	// create a new cURL resource
	$ch = curl_init();

	// set URL and other appropriate options
	curl_setopt( $ch, CURLOPT_URL, "https://cloud.web.kingmateriaux.com/woo-subscriptions?woo=$user_id&email=$email&name=$name&opt=$opt&source=$source" );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_ENCODING, '' );

	// error_log( "Tentative d'envoi des paramêtres vers : https://cloud.web.kingmateriaux.com/woo-subscriptions?woo=$user_id&email=$email&name=$name&opt=$opt&source=$source" );

	// grab URL and pass it to the browser
	$response = curl_exec( $ch );

	// close cURL resource, and free up system resources
	curl_close( $ch );
}
