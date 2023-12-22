<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function km_wc_customer_session_enabler() {
	if ( ! is_admin() && isset( WC()->session ) && ! WC()->session->has_session() ) {
		WC()->session->set_customer_session_cookie( true );
	}
}


add_action( 'woocommerce_init', 'km_wc_customer_session_enabler' );


// Utility function, to display BACS accounts details
function km_get_bacs_account_details_html() {

	$gateway   = new WC_Gateway_BACS();
	// $country   = WC()->countries->get_base_country();
	// $locale    = $gateway->get_country_locale();
	$bacs_info = get_option( 'woocommerce_bacs_accounts' );
	if ( ! $bacs_info ) {
		return;
	}

	$bank_name = esc_attr( wp_unslash( $bacs_info[0]['bank_name'] ) );
	$iban_code = esc_attr( $bacs_info[0]['iban'] );
	$bic_code  = esc_attr( $bacs_info[0]['bic'] );
	ob_start();
	?>
		<h3><?php _e( 'Nos coordonnées bancaires :' ); ?></h3>
		<p class="bank_name"><?php _e( 'Banque' ); ?>: <strong><?php echo $bank_name; ?></strong></p>
		<p class="iban"><?php _e( 'IBAN' ); ?>: <strong><?php echo $iban_code; ?></strong></p>
		<p class="bic"><?php _e( 'BIC' ); ?>: <strong><?php echo $bic_code; ?></strong></p>
	<?php

	$output = ob_get_clean();

	return $output;
}
