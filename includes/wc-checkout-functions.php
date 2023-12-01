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


/**
 * Ajoute un calendrier pour la date de livraison
 *
 * @return void
 */
function km_add_delivery_date_field(): void {

	// Vérifier si la method de livraison local_pickup_plus est disponible.
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
	if ( ! in_array( 'local_pickup_plus', $chosen_methods, true ) ) {
		return;
	}

	$days = array();
	for ( $i = 0; $i < 20; $i++ ) {
		$day = gmdate( 'l d F', strtotime( '+' . $i . ' days' ) );
		if ( 'Sunday' !== gmdate( 'l', strtotime( $day ) ) ) {
			$days[] = $day;
		}
	}
	?>
	<div id="local-pickup-plus-schedule" class="schedule">
		<div class="datepicker-day">	
			<ul>
			<?php foreach ( $days as $i => $day ) : ?>
				<li class="day">
					<?php esc_html_e( $day ); ?>
				</li>
			<?php endforeach; ?>
			</ul>
		</div>
		<div class="datepicker-time">
		<!-- Morning Slots -->
		<div class="time-slot morning">
		<h3>Matin</h3>
			<div class="slots">
				<div class="slot">07h00</div>
				<div class="slot">07h30</div>
				<div class="slot">08h00</div>
				<div class="slot">08h30</div>
				<div class="slot">09h00</div>
				<div class="slot">09h30</div>
				<div class="slot">10h00</div>
				<div class="slot">10h30</div>
				<div class="slot">11h00</div>
				<div class="slot">11h30</div>
			</div>
		</div>
		<!-- Afternoon Slots -->
		<div class="time-slot afternoon">
		<h3>Après-midi</h3>
		<div class="slots">
			<div class="slot">13h00</div>
			<div class="slot">13h30</div>
			<div class="slot">14h00</div>
			<div class="slot">14h30</div>
			<div class="slot">15h00</div>
			<div class="slot">15h30</div>
			<div class="slot">16h00</div>
			<div class="slot">16h30</div>
			<div class="slot">17h00</div>
			<div class="slot">17h30</div>
		</div>
		</div>

		<!-- Evening Slot -->
		<div class="time-slot evening">
		<h3>Soir</h3>
		<div class="slots">
			<div class="slot">18h00</div>
		</div>
		</div>
	</div>
	</div>
	<?php
}
add_action( 'woocommerce_after_checkout_form', 'km_add_delivery_date_field' );
