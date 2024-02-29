<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Enregistrer le statut de commande : "Prévue".
function km_register_scheduled_order_status() {
	register_post_status(
		'wc-scheduled',
		array(
			'label'                     => __( 'Prévue', 'kingmateriaux' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Prévue <span class="count">(%s)</span>', 'Prévue <span class="count">(%s)</span>', 'kingmateriaux' ),
		)
	);
}
add_action( 'init', 'km_register_scheduled_order_status' );

// Enregistrer le statut de commande : "En cours de SAV".
function km_register_sav_order_status() {
	register_post_status(
		'wc-sav',
		array(
			'label'                     => __( 'En cours de SAV', 'kingmateriaux' ),
			'public'                    => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list'    => true,
			'exclude_from_search'       => false,
			'label_count'               => _n_noop( 'En cours de SAV (%s)', 'En cours de SAV (%s)', 'kingmateriaux' ),
		)
	);
}
add_action( 'init', 'km_register_sav_order_status' );

// Ajouter le statut de commande : "Prévue"
function km_add_scheduled_order_status( $order_statuses ) {
	$new_order_statuses = array();

	// ajoutez le nouveau statut de commande après le statut de commande en attente
	foreach ( $order_statuses as $key => $status ) {
		$new_order_statuses[ $key ] = $status;

		if ( 'wc-pending' === $key ) {
			$new_order_statuses['wc-scheduled'] = 'Prévue';
		}
	}

	return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'km_add_scheduled_order_status' );

/**
 * Ajoute le statut de commande 'En cours de SAV'
 *
 * @param $order_statuses
 * @return array
 */
function km_add_sav_to_order_statuses( $order_statuses ): array {
	$new_order_statuses = array();
	foreach ( $order_statuses as $key => $status ) {
		$new_order_statuses[ $key ] = $status;
		if ( 'wc-on-hold' === $key ) {
			$new_order_statuses['wc-sav'] = 'En cours de SAV';
		}
	}
	return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'km_add_sav_to_order_statuses' );

/**
 * Ajoute les données du calendrier du drive à la commande
 *
 * @param WC_Order $order
 * @return void
 */
function km_display_drive_details_in_admin_order( $order ) {

	$order_id = $order->get_id();

	$drive_date = get_post_meta( $order_id, '_drive_date', true );
	$drive_time = get_post_meta( $order_id, '_drive_time', true );

	if ( empty( $drive_date ) && empty( $drive_time ) ) {
		return;
	}

	$html = '<strong>Récupération de la commande au Drive le ';

	if ( $drive_date ) {
		$html .= $drive_date;
	}

	if ( $drive_time ) {
		$html .= ' à ' . $drive_time;
	}

	$html .= '</strong>';

	// Display the HTML.
	echo $html;
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'km_display_drive_details_in_admin_order' );

/**
 * Ajoute les données de livraison à la commande
 *
 * @param WC_Order $order
 * @return void
 */
function km_display_shipping_details_in_admin_order( $order ) {

	$shipping_date = get_post_meta( $order->get_id(), '_shipping_dates', true );

	if ( empty( $shipping_date ) ) {
		return;
	}
	echo '<strong>' . esc_html( $shipping_date ) . '</strong>';
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'km_display_shipping_details_in_admin_order' );

/**
 * Ajoute les données du calendrier du drive à l'email de commande
 *
 * @param WC_Order $order
 * @param int      $order_id
 * @return void
 */
function km_maybe_copy_shipping_to_billing( $order_id ) {
	if ( isset( $_POST['different_billing_address'] ) && 'false' === $_POST['different_billing_address'] ) {
		// La case est cochée, copier l'adresse de livraison dans l'adresse de facturation.
		$shipping_fields = array(
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'postcode',
			'country',
			'state',
		);

		foreach ( $shipping_fields as $field ) {
			if ( isset( $_POST[ 'shipping_' . $field ] ) && ! empty( $_POST[ 'shipping_' . $field ] ) ) {
				update_post_meta( $order_id, '_billing_' . $field, sanitize_text_field( $_POST[ 'shipping_' . $field ] ) );
			}
		}
	}
}
add_action( 'woocommerce_checkout_update_order_meta', 'km_maybe_copy_shipping_to_billing' );

function km_modify_order_number_column( $column ) {
	global $post;

	if ( 'order_number' === $column ) {
		// Obtenir l'objet commande pour le post actuel.
		$the_order = wc_get_order( $post->ID );

		// Obtenir l'ID du client associé à la commande.
		$customer_id = $the_order->get_customer_id();

		// Récupérer les commandes du client.
		$customer_orders = wc_get_orders( array( 'customer_id' => $customer_id ) );

		// Compter le nombre de commandes.
		$order_count = count( $customer_orders );
		$user_note   = get_user_meta( $customer_id, 'user_note', true );
		$user_note   = empty( $user_note ) ? 'Aucune note' : esc_attr( $user_note );

		// Si le client a plus de 2 commandes, appliquez un style personnalisé.
		if ( $order_count > 1 ) {
			echo '<style type="text/css">
                #post-' . esc_attr( $the_order->get_id() ) . ' .order-view { 
                    color: #1f7800;
                }
            </style>';
		}

		// Ajoutez un attribut de données personnalisé avec la note de l'utilisateur.
		echo '<a href="#" class="km-note-preview" data-user-id="' . esc_attr( $customer_id ) . '" data-user-note="' . $user_note . '" style="margin-left:10px;">Note</a>';
	}
}
add_action( 'manage_shop_order_posts_custom_column', 'km_modify_order_number_column' );

/**
 * Desactiver l'email de commande terminée si le champ ACF 'transporteur' n'est pas défini
 *
 * @param bool     $enabled
 * @param WC_Order $order
 * @return bool
 */
function km_disable_completed_order_email( $enabled, $order ) {
	// Vérifiez si l'objet $order est valide.
	if ( ! is_a( $order, 'WC_Order' ) ) {
		return $enabled;
	}

	// Récupérez le post meta "transporteur".
	$transporteur = get_post_meta( $order->get_id(), 'transporteur', true );

	// Si le post meta "transporteur" n'est pas défini, désactivez l'email.
	if ( 'Non défini' === $transporteur || ! isset( $transporteur ) || empty( $transporteur ) ) {
		$enabled = false;
	}

	return $enabled;
}
add_filter( 'woocommerce_email_enabled_customer_completed_order', 'km_disable_completed_order_email', 10, 2 );
