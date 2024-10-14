<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
 * Reorders the account menu items.
 *
 * @param array $items The account menu items.
 * @return array The reordered account menu items.
 */
function km_reorder_my_account_menu( $items ) {

	$new_order = array(
		'dashboard'       => __( 'Dashboard', 'woocommerce' ),
		'orders'          => __( 'Orders', 'woocommerce' ),
		// 'edit-address'    => __( 'Addresses', 'woocommerce' ),
		'edit-account'    => __( 'Account details', 'woocommerce' ),
		// 'payment-methods'  => __( 'Moyen de paiement', 'woocommerce' ),
		'customer-logout' => __( 'Logout', 'woocommerce' ),
	);

	return $new_order;
}
add_filter( 'woocommerce_account_menu_items', 'km_reorder_my_account_menu' );

/**
 * Adds a new column to the "My Orders" table.
 *
 * @param array $columns The columns in the "My Orders" table.
 * @return array The modified columns.
 */
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

/**
 * Ajoute le champ de date d'anniversaire au formulaire de modification du compte WooCommerce.
 */
function km_add_birthday_to_edit_account_form() {
	$user_id  = get_current_user_id();
	$birthday = get_user_meta( $user_id, 'birthday', true );
	?>
<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
    <label for="account_birthday"><?php esc_html_e( 'Date d\'anniversaire', 'woocommerce' ); ?></label>
    <input type="date" class="woocommerce-Input woocommerce-Input--date input-text" name="account_birthday"
        id="account_birthday" value="<?php echo esc_attr( $birthday ); ?>" />
</p>
<?php
}
add_action( 'woocommerce_edit_account_form_fields', 'km_add_birthday_to_edit_account_form' );

// Save the birthday field.
function km_save_birthday_field( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}
	if ( isset( $_POST['account_birthday'] ) ) {
		update_user_meta( $user_id, 'birthday', sanitize_text_field( $_POST['account_birthday'] ) );
	}
	return true;
}	
add_action( 'woocommerce_save_account_details', 'km_save_birthday_field' );