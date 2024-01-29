<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 *  Disable WooCommerce Admin New Order Notification
 */
function km_disable_new_user_notification_to_admin( $wp_new_user_notification_email_admin, $user, $blogname ) {
	return false;
}
add_filter( 'wp_new_user_notification_email_admin', 'km_disable_new_user_notification_to_admin', 10, 3 );

/**
 * Disable WooCommerce Admin password change Notification
 */
add_filter( 'send_password_change_email', '__return_false' );
add_filter( 'woocommerce_disable_password_change_notification', '__return_true' );

/**
 * Enable WooCommerce customer session if not already set
 */
function km_wc_customer_session_enabler() {
	if ( ! is_admin() && isset( WC()->session ) && ! WC()->session->has_session() ) {
		WC()->session->set_customer_session_cookie( true );
	}
}
add_action( 'woocommerce_init', 'km_wc_customer_session_enabler' );

/**
 * Envoyer l'email de nouveau compte client de WooCommerce lorsqu'un utilisateur s'inscrit.
 *
 * @param array   $wp_new_user_notification_email Array contenant les informations de l'email.
 * @param WP_User $user                           Utilisateur qui vient de s'inscrire.
 * @param string  $blogname                       Nom du site.
 *
 * @return array
 */
function km_set_new_customer_notification_email( $wp_new_user_notification_email, $user, $blogname ) {
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
add_filter( 'wp_new_user_notification_email', 'km_set_new_customer_notification_email', 10, 3 );

/**
 * Utility function to display BACS accounts details
 */
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

/**
 * Filtrer les éléments de menus en fonction de la zone de livraison.
 *
 * @param array    $items Les éléments de menu.
 * @param stdClass $args  Les arguments de wp_nav_menu().
 *
 * @return array
 */
function km_filter_menu_items( $items, $args ) {
	// Obtenez l'instance de KM_Shipping_Zone.
	$shipping_zone = KM_Shipping_Zone::get_instance();

	// Si on est pas dans la zone 13, masquez certaines catégories et leurs enfants.
	if ( ! $shipping_zone->is_in_thirteen ) {
		$menu_to_remove = array();

		foreach ( $items as $key => $item ) {
			// Identifiez les éléments de menu "Matériaux" et "Locations".
			if ( false !== strpos( $item->url, 'materiaux-de-construction' ) || false !== strpos( $item->url, 'location' ) ) {
				$menu_to_remove[] = $item->ID;
				unset( $items[ $key ] );
			}
		}

		// Parcourez à nouveau pour supprimer les enfants de ces éléments de menu.
		foreach ( $items as $key => $item ) {
			if ( in_array( $item->menu_item_parent, $menu_to_remove ) ) {
				unset( $items[ $key ] );
			}
		}
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'km_filter_menu_items', 10, 2 );



// add_action( 'woocommerce_thankyou', 'plausible_revenue_tracking' );
function plausible_revenue_tracking( $order_id ) {
	$order = wc_get_order( $order_id );
	?>
	<script data-domain="kingmateriaux.com" src="https://plausible.io/js/script.manual.revenue.pageview-props.js"></script>
	<script>
	const amount = "<?php echo $order->get_total(); ?>"
	const currency = "<?php echo $order->get_currency(); ?>"

	const orderId = "<?php echo $order->get_id(); ?>"
	const itemCount = <?php echo $order->get_item_count(); ?>

	window.plausible("Achat", {
		revenue: {amount: amount, currency: currency},
		props: {orderId: orderId, itemCount: itemCount}
	})
	</script>
	<?php
}
