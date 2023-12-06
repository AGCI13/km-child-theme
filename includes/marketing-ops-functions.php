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

	send_data_to_cloudops( $name, $email, $opt, $source );
}
add_action( 'elementor_pro/forms/new_record', 'km_handle_email_discount_10_form', 10, 2 );

/**
 *  Send data to CLOUD OPS ENDPOINT
 *
 * @param string $name
 * @param string $email
 * @param string $opt
 * @param string $source
 */
function send_data_to_cloudops( $name, $email, $opt, $source ) {
	// Get current user id if user is connected, else leave empty
	$user_id = get_current_user_id() ?: '';

	// // create a new cURL resource.
	$ch = curl_init();
	// set URL and other appropriate options.
	curl_setopt( $ch, CURLOPT_URL, "https://cloud.web.kingmateriaux.com/woo-subscriptions?woo=$user_id" );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_ENCODING, '' );

	// grab URL and pass it to the browser.
	$response = curl_exec( $ch );

	// close cURL resource, and free up system resources.
	curl_close( $ch );
}
