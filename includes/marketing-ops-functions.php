<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles newsletter email discount form.
 */
function km_handle_discount_newsletter_form( $record, $handler ) {
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
add_action( 'elementor_pro/forms/new_record', 'km_handle_discount_newsletter_form', 10, 2 );

// CASE EMAIL INPUT CART PAGE
add_action( 'wp_ajax_discount_cart_form', 'km_handle_discount_cart_form' );
add_action( 'wp_ajax_nopriv_discount_cart_form', 'km_handle_discount_cart_form' );
function km_handle_discount_cart_form() {

	if ( isset( $_POST['discount_email'] ) && ! empty( $_POST['discount_email'] ) ) {

		$email = filter_var( trim( $_POST['discount_email'] ), FILTER_SANITIZE_EMAIL );

		// Check if email is valid.
		if ( is_email( $email ) === false ) {
			wp_send_json_error( __( 'L_e-mail renseigné n \'est pas valide.', 'kingmateriaux' ) );
		}

		// Get current user id if user is connected, else leave empty.
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$name         = esc_html( $current_user->user_login );
		} else {
			$name = '';
		}

		$opt    = 'true';
		$source = 'panier';

		// Add session data wac-email to hide the email field on cart page.
		WC()->session->set( 'discount_email', $email );

		// Do the magic.
		km_send_data_to_cloudops( $name, $email, $opt, $source );

		// Unset vars.
		unset( $_POST['discount_email'], $name, $email, $opt, $source );

		// Send success message.
		wp_send_json_success( __( 'Votre email à bien été transmis. Vous allez recevoir votre code promo sur celui-ci.', 'kingmateriaux' ) );
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

	// error_log( var_export( "https://cloud.web.kingmateriaux.com/woo-subscriptions?woo=$user_id&email=$email&name=$name&opt=$opt&source=$source", true ) );

	// set URL and other appropriate options
	curl_setopt( $ch, CURLOPT_URL, "https://cloud.web.kingmateriaux.com/woo-subscriptions?woo=$user_id&email=$email&name=$name&opt=$opt&source=$source" );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_ENCODING, '' );

	// grab URL and pass it to the browser
	$response = curl_exec( $ch );

	// close cURL resource, and free up system resources
	curl_close( $ch );
}
