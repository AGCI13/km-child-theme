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

	$gateway = new WC_Gateway_BACS();
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

add_filter( 'wp_new_user_notification_email', 'set_new_customer_notification_email', 10, 3 );
/**
 * Envoyer l'email de nouveau compte client de WooCommerce lorsqu'un utilisateur s'inscrit.
 *
 * @param array   $wp_new_user_notification_email Array contenant les informations de l'email.
 * @param WP_User $user                           Utilisateur qui vient de s'inscrire.
 * @param string  $blogname                       Nom du site.
 *
 * @return array
 */
function set_new_customer_notification_email( $wp_new_user_notification_email, $user, $blogname ) {
	// Vérifier si l'utilisateur a le rôle de "customer".
	if ( in_array( 'customer', (array) $user->roles ) ) {
		// Assurez-vous que WooCommerce est actif.
		if ( function_exists( 'WC' ) ) {
			// Récupérer l'instance de l'envoyeur d'email de WooCommerce.
			$mailer = WC()->mailer();
			// Récupérer l'email de nouveau compte client.
			$email = $mailer->emails['WC_Email_Customer_New_Account'];
			// Déclencher l'envoi de l'email.
			$email->trigger( $user->ID, null, false );

			// Retourner un tableau vide pour désactiver l'email par défaut de WordPress.
			return array();
		}
	}

	// Pour les autres rôles, utiliser l'email par défaut de WordPress.
	return $wp_new_user_notification_email;
}
