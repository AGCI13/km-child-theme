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
	global $post;

	// Récupérer la commande actuelle.
	$order = wc_get_order( $post->ID );
	if ( ! $order ) {
		return;
	}

	// Afficher le nombre d'articles pour la colonne 'order_items'.
	if ( 'order_items' === $column ) {
		$items_count = $order->get_item_count();
		echo $items_count;
	}

	// Personnaliser l'affichage pour la colonne 'order_status'.
	if ( 'order_status' === $column ) {
		// Si la commande est en attente et payée par virement bancaire (bacs).
		if ( 'on-hold' === $order->get_status() && 'bacs' === $order->get_payment_method() ) {
			echo '<style>.order-status.status-on-hold { background:#f9e466!important; }</style>';
		}

		// Si la commande est terminée.
		if ( 'completed' === $order->get_status() ) {
			// Récupérer la valeur du champ 'transporteur'.
			$transporter = get_post_meta( $order->get_id(), 'transporteur', true );

			// Afficher le transporteur si défini.
			if ( $transporter ) {
				echo '<mark class="order-status status-completed"><span>' . esc_html( $transporter ) . '</span></mark>';
			}
		}
	}
}
add_action( 'manage_shop_order_posts_custom_column', 'km_add_transporter_to_order_status_column' );

/**
 * Ajoute les données du calendrier du drive à la commande
 *
 * @param WC_Order $order
 * @return void
 */
function km_display_drive_details_in_admin_order( $order ) {
	// Get the drive date and the drive time from the order meta.
	$drive_date = get_post_meta( $order->get_id(), '_drive_date', true );
	$drive_time = get_post_meta( $order->get_id(), '_drive_time', true );

	// If there is no drive date and no drive time, we don't need to display anything .
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
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'km_display_drive_details_in_admin_order' );

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

/**
 * Save the transporter value from the order admin page
 */
function km_save_transporteur_callback() {
	$post_id      = intval( $_POST['post_id'] );
	$transporteur = sanitize_text_field( $_POST['transporteur'] );

	if ( $post_id && $transporteur ) {
		update_post_meta( $post_id, 'transporteur', $transporteur );
		echo 'La valeur du transporteur a été mise à jour.';
	} else {
		echo 'Erreur lors de la mise à jour.';
	}

	wp_die(); // Arrête l'exécution du script.
}
add_action( 'wp_ajax_save_transporteur', 'km_save_transporteur_callback' );
add_action( 'wp_ajax_nopriv_save_transporteur', 'km_save_transporteur_callback' );
