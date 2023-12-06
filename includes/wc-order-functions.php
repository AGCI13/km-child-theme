<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Enregistrer le statut de commande : "Prévue"
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

// Enregistrer le statut de commande : "En cours de SAV"
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

// Ajouter la colonne 'Transporteur' à la liste des commandes
function km_add_transporter_column( $columns ) {
	$columns['transporter_column'] = __( 'Transporteur', 'kingmateriaux' );
	return $columns;
}
add_filter( 'manage_edit-shop_order_columns', 'km_add_transporter_column' );

// Afficher les données du champ ACF 'transporteur' dans la colonne 'Transporter'
function km_show_transporter_data( $column ) {
	global $post;

	if ( 'transporter_column' === $column ) {

		// Récupérer les données du champ ACF 'transporteur'
		$transp_name = get_post_meta( $post->ID, 'transporteur', true );
		$transp_slug = sanitize_title( $transp_name );

		switch ( $transp_slug ) {
			case 'king':
				echo '<mark class="transp-label ' . esc_html( $transp_slug ) . '">' . esc_html( $transp_name ) . '</mark>';
				break;
			case 'kuehne':
				echo '<mark class="transp-label ' . esc_html( $transp_slug ) . '">' . esc_html( $transp_name ) . '</mark>';
				break;
			case 'fragner':
				echo '<mark class="transp-label ' . esc_html( $transp_slug ) . '">' . esc_html( $transp_name ) . '</mark>';
				break;
			case 'geotextile':
				echo '<mark class="transp-label ' . esc_html( $transp_slug ) . '">' . esc_html( $transp_name ) . '</mark>';
				break;
			case 'tred':
				echo '<mark class="transp-label ' . esc_html( $transp_slug ) . '">' . esc_html( $transp_name ) . '</mark>';
				break;
			default:
				echo '<mark class="transp-label undefined">' . __( 'Non défini', 'kingmateriaux' ) . '</mark>';
				break;
		}
	}
}
add_action( 'manage_shop_order_posts_custom_column', 'km_show_transporter_data' );

// Changer le sujet de l'email de commande terminée en fonction de la valeur du champ ACF 'transporteur'
function km_custom_completed_order_email_subject( $subject, $order ) {
	// Obtenez la valeur du champ ACF 'transporteur' pour cette commande.
	$transporter = get_post_meta( $order->get_id(), 'transporteur', true );

	// Modifiez l'objet de l'email en fonction de la valeur du champ 'transporteur'.
	if ( $transporter ) {
		$subject = sprintf( 'Votre a été expédié avec %s.', $transporter );
	}
	return $subject;
}
// TODO: Usage à confirmer
// add_filter('woocommerce_email_subject_customer_completed_order', 'km_custom_completed_order_email_subject', 10, 2);

function km_add_transporter_to_order_status_column( $column ) {
	global $post, $the_order;

	if ( 'order_status' === $column ) {
		// Si $the_order n'est pas défini ou si ce n'est pas la bonne commande, récupérez la commande.
		if ( ! $the_order || $the_order->get_id() !== $post->ID ) {
			$the_order = wc_get_order( $post->ID );
		}

		// Vérifiez si la commande est terminée.
		if ( $the_order && 'completed' === $the_order->get_status() ) {
			// Obtenez la valeur du champ ACF 'transporteur' pour cette commande.
			$transporter = get_post_meta( $the_order->get_id(), 'transporteur', true );

			// Si le transporteur est défini, ajoutez-le à l'étiquette d'état de commande.
			if ( $transporter ) {
				echo '<mark class="order-status status-completed"><span>' . esc_html( $transporter ) . '</span></mark>';
			}
		}
	}
}
add_action( 'manage_shop_order_posts_custom_column', 'km_add_transporter_to_order_status_column' );

// Ajoute une colonne pour le nombre de produits commandés
function km_add_items_column( $columns ) {
	$columns['order_items'] = __( 'Nombre d\'article(s)', 'woocommerce' );
	return $columns;
}
add_filter( 'manage_edit-shop_order_columns', 'km_add_items_column' );

// Remplit la colonne avec le nombre de produits pour chaque commande
function km_display_items_column( $column ) {
	global $post;

	if ( 'order_items' === $column ) {
		$order       = wc_get_order( $post->ID );
		$items_count = $order->get_item_count();
		echo $items_count;
	}
}
add_action( 'manage_shop_order_posts_custom_column', 'km_display_items_column' );


/**
 * Ajoute les données du calendrier du drive à la commande
 *
 * @param WC_Order $order
 * @return void
 */
function display_drive_details_in_admin_order( $order ) {
	// Get the drive date and the drive time from the order meta.
	$drive_date = get_post_meta( $order->get_id(), '_drive_date', true );
	$drive_time = get_post_meta( $order->get_id(), '_drive_time', true );

	// If there is no drive date and no drive time, we don't need to display anything.
	if ( empty( $drive_date ) && empty( $drive_time ) ) {
		return;
	}

	// We will store our HTML in this variable.
	$html = '<strong>Récupération de la commande au Drive le ';

	// If we have a drive date, add it to the HTML.
	if ( $drive_date ) {
		$html .= $drive_date;
	}

	// If we have a drive time, add it to the HTML.
	if ( $drive_time ) {
		$html .= ' à ' . $drive_time;
	}

	$html .= '</strong>';

	// Display the HTML.
	echo $html;
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'display_drive_details_in_admin_order' );
