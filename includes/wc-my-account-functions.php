<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles dynamic pricing based on shipping zones and classes in WooCommerce.
 */
function km_add_whishlist_endpoint() {
	add_rewrite_endpoint( 'whishlist', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'km_add_whishlist_endpoint' );

/**
 * Adds the "Favoris" endpoint to the account menu.
 *
 * @param array $items The account menu items.
 * @return array The modified account menu items.
 */
function km_add_whishlist_to_account_menu( $items ) {
	$items['whishlist'] = __( 'Favoris', 'kingmateriaux' );
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'km_add_whishlist_to_account_menu' );

/**
 * Displays the content of the "Favoris" endpoint.
 */
function km_whishlist_content() {
	echo do_shortcode( '[yith_wcwl_wishlist]' );
}
add_action( 'woocommerce_account_whishlist_endpoint', 'km_whishlist_content' );


/**
 * Adds the "whishlist" query variable.
 *
 * @param array $vars The query variables.
 * @return array The modified query variables.
 */
function km_add_whishlist_query_var( $vars ) {
	$vars[] = 'whishlist';
	return $vars;
}
add_filter( 'query_vars', 'km_add_whishlist_query_var', 0 );

/**
 * Ajoute l'endpoint "moyen_paiement".
 */
function km_add_payment_methods_endpoint() {
    add_rewrite_endpoint( 'moyen-paiement', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'km_add_payment_methods_endpoint' );

/**
 * Affiche le contenu de l'endpoint "moyen_paiement".
 */
function km_display_payment_methods_content() {
    $template = get_stylesheet_directory() . '/woocommerce/myaccount/payment-methods.php';
    if ( file_exists( $template ) ) {
        include $template;
    } else {
        echo '<p>Template pour le moyen de paiement non trouvé.</p>';
    }
}
add_action( 'woocommerce_account_moyen_paiement_endpoint', 'km_display_payment_methods_content' );

/**
 * Ajoute l'onglet "Moyen de paiement" au menu du compte.
 *
 * @param array $items Les éléments du menu du compte.
 * @return array Les éléments du menu du compte modifiés.
 */
function km_add_payment_methods_menu_item( $items ) {
    // L'identifiant de l'onglet doit correspondre à l'identifiant de l'endpoint
    $items['moyen-paiement'] = 'Moyen de paiement';
    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'km_add_payment_methods_menu_item' );



/**
 * Reorders the account menu items.
 *
 * @param array $items The account menu items.
 * @return array The reordered account menu items.
 */
function km_reorder_my_account_menu( $items ) {
	// Define the new order of the items
	$new_order = array(
		'dashboard'       => __( 'Dashboard', 'woocommerce' ),
		'orders'          => __( 'Orders', 'woocommerce' ),
		// 'edit-address'    => __( 'Addresses', 'woocommerce' ),
		'edit-account'    => __( 'Account details', 'woocommerce' ),
		'payment-methods'  => __( 'Moyen de paiement', 'woocommerce' ),
		'whishlist'       => __( 'Mes favoris', 'woocommerce' ),
		'customer-logout' => __( 'Logout', 'woocommerce' ),
	);

	return $new_order;
}
add_filter( 'woocommerce_account_menu_items', 'km_reorder_my_account_menu' );

// Nouvel ordre des valeurs des commandes
function custom_woocommerce_account_orders_columns( $columns ) {
	$new_order = array(
		'order-number'  => __( 'Order', 'woocommerce' ),
		'order-total'   => __( 'Total', 'woocommerce' ),
		'order-date'    => __( 'Date', 'woocommerce' ),
		'order-status'  => __( 'Status', 'woocommerce' ),
		'order-actions' => __( 'Actions', 'woocommerce' ),
	);
	return $new_order;
}
add_filter( 'woocommerce_account_orders_columns', 'custom_woocommerce_account_orders_columns', 100 );
