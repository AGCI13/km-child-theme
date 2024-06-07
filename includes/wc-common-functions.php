<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Affiche la page de connexion si l'utilisateur n'est pas connecté.
 */
function km_redirect_to_login_if_not_logged_in() {
	// Vérifie si l'utilisateur n'est pas connecté et tente d'accéder à "mon-compte" mais pas à "mot-passe-perdu".
	if ( ! is_user_logged_in() && strpos( $_SERVER['REQUEST_URI'], 'mon-compte' ) !== false
	&& strpos( $_SERVER['REQUEST_URI'], 'mon-compte/mot-passe-perdu' ) === false ) {
		wp_safe_redirect( site_url( '/se-connecter/' ) );
		exit;
	}
}
add_action( 'template_redirect', 'km_redirect_to_login_if_not_logged_in' );


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
	if ( in_array( 'customer', (array) $user->roles ) ) {
		if ( function_exists( 'WC' ) ) {
			$mailer = WC()->mailer();
			$email  = $mailer->emails['WC_Email_Customer_New_Account'];
			$email->trigger( $user->ID, null, false );
			return array();
		}
	}
	return $wp_new_user_notification_email;
}
add_filter( 'wp_new_user_notification_email', 'km_set_new_customer_notification_email', 10, 3 );

/**
 * Utility function to display BACS accounts details
 */
function km_get_bacs_account_details_html() {

	// $gateway = new WC_Gateway_BACS();
	// $country   = WC()->countries->get_base_country();
	// $locale    = $gateway->get_country_locale();
	$bacs_info = get_option( 'woocommerce_bacs_accounts' );
	if ( ! $bacs_info ) {
		return;
	}

	$bank_name = isset( $bacs_info[0]['bank_name'] ) && ! empty( $bacs_info[0]['bank_name'] ) ? wp_unslash( $bacs_info[0]['bank_name'] ) : '';
	$iban_code = isset( $bacs_info[0]['iban'] ) && ! empty( $bacs_info[0]['iban'] ) ? wp_unslash( $bacs_info[0]['iban'] ) : '';
	$bic_code  = isset( $bacs_info[0]['bic'] ) && ! empty( $bacs_info[0]['bic'] ) ? wp_unslash( $bacs_info[0]['bic'] ) : '';

	if ( empty( $bank_name ) || empty( $iban_code ) || empty( $bic_code ) ) {
		return;
	}

	ob_start();
	?>
		<h3><?php esc_html_e( 'Nos coordonnées bancaires :', 'kingmateriaux' ); ?></h3>

		<?php if ( ! empty( $bank_name ) ) : ?>
			<p class="bank_name"><?php esc_html_e( 'Banque', 'kingmateriaux' ); ?>: <strong><?php echo esc_attr( $bank_name ); ?></strong></p>
		<?php endif; ?>

		<?php if ( ! empty( $iban_code ) ) : ?>
			<p class="iban"><?php esc_html_e( 'IBAN', 'kingmateriaux' ); ?>: <strong><?php echo esc_attr( $iban_code ); ?></strong></p>
		<?php endif; ?>

		<?php if ( ! empty( $bic_code ) ) : ?>
			<p class="bic"><?php esc_html_e( 'BIC', 'kingmateriaux' ); ?>: <strong><?php echo esc_attr( $bic_code ); ?></strong></p>
		<?php endif; ?>

	<?php
	$output = ob_get_clean();
	return $output;
}

function km_get_product_from_title( $product_title ) {

	if ( ! $product_title ) {
		return;
	}

	$args = array(
		'fields'         => 'ids',
		'post_type'      => 'product',
		'post_status'    => array( 'private' ),
		'posts_per_page' => 1,
		'title'          => $product_title,
		'exact'          => true,
	);

	$shipping_products_posts = get_posts( $args );

	if ( ! $shipping_products_posts ) {
		return;
	}

	return wc_get_product( $shipping_products_posts[0] );
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

	if ( km_is_shipping_zone_in_thirteen() ) {
		return $items;
	}

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

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'km_filter_menu_items', 10, 2 );

function km_register_force_price_calculation_query_vars( $vars ) {
	$vars[] = 'force-recalc-prices';
	return $vars;
}
add_filter( 'query_vars', 'km_register_force_price_calculation_query_vars' );

function km_maybe_show_email_banner_anniversary() {
	if ( strtotime( gmdate( 'Y-m-d' ) ) >= strtotime( '2024-04-01' ) && strtotime( gmdate( 'Y-m-d' ) ) <= strtotime( '2024-04-30' ) ) {
		?>
		<a href="https://kingmateriaux.com/wp-content/uploads/2024/03/reglement-KM-concours-7-ans.pdf"><img style="margin-bottom:40px;" src="<?php	echo esc_url( get_stylesheet_directory_uri() . '/assets/img/email-banner-anniversary-offer.jpg' ); ?>"></a>
		<?php
	}
}
