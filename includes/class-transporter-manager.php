<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles transporter management.
 */

class KM_Transporter_Manager {

	use SingletonTrait;

	/**
	 * Transporters
	 *
	 * @var array
	 */
	private $transporters;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {

		$transporter_field  = get_field_object( 'field_6536a052fb38f', 'group_6536a051d2136' );
		$this->transporters = $transporter_field['choices'];

		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_transporter_column' ) );
		add_filter( 'woocommerce_email_enabled_customer_completed_order', array( $this, 'block_completed_order_email_if_transporter_undefined' ), 10, 2 );
		add_filter( 'woocommerce_email_subject_customer_completed_order', array( $this, 'modify_completed_order_email_subject' ), 10, 2 );

		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'add_transporter_to_order_status_column' ), 10, 2 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'show_transporter_data' ), 20, 2 );

		add_action( 'km_email_transporter_content', array( $this, 'set_email_transporter_content' ), 10, 1 );

		add_action( 'wp_ajax_save_transporter', array( $this, 'save_transporter_callback' ) );
		add_action( 'wp_ajax_nopriv_save_transporter', array( $this, 'save_transporter_callback' ) );
	}

	/**
	 * Ajouter la colonne 'Transporteur' à la liste des commandes
	 *
	 * @param $columns
	 * @return mixed
	 */
	public function add_transporter_column( $columns ) {
		$columns['transporter_column'] = __( 'Transporteur', 'kingmateriaux' );
		return $columns;
	}

	/**
	 * Bloque l'envoi de l'email de commande terminée si le transporteur est 'non-defini'.
	 *
	 * @param bool     $enabled Indique si l'email doit être envoyé.
	 * @param WC_Order $order L'objet commande concerné.
	 * @return bool Le statut modifié indiquant si l'email doit être envoyé.
	 */
	public function block_completed_order_email_if_transporter_undefined( $enabled, $order ) {

		if ( ! $order ) {
			return $enabled;
		}

		$transporter_slug = get_post_meta( $order->get_id(), 'transporteur', true );

		if ( 'non-defini' === $transporter_slug ) {
			return false; // Désactive l'envoi de l'email.
		}

		return $enabled;
	}

	/** Changer le sujet de l'email de commande terminée en fonction de la valeur du champ ACF 'transporteur'
	 *
	 * @param $subject
	 * @param $order
	 * @return string
	 */
	public function modify_completed_order_email_subject( $subject, $order ) {
		$transporter_slug = get_post_meta( $order->get_id(), 'transporteur', true );

		if ( $transporter_slug && 'non-defini' === $transporter_slug ) {
			return $subject;
		} elseif ( $transporter_slug && 'king-drive' === $transporter_slug ) {
			/* translators: %s the selected transporter */
			$subject = __( 'Votre commande Drive est prête !', 'kingmateriaux' );
		} elseif ( $transporter_slug ) {
			/* translators: %s the selected transporter */
			$subject = sprintf( __( 'Votre commande a été expédié avec %s.', 'kingmateriaux' ), $this->transporters[ $transporter_slug ] );
		}

		return $subject;
	}

	/**
	 * Afficher le contenu de l'email de commande terminée en fonction de la valeur du champ ACF 'transporteur'
	 *
	 * @param $order_id L'ID de la commande.
	 * @return void | string
	 */
	public function set_email_transporter_content( $order_id ) {

		$transp_slug = get_post_meta( $order_id, 'transporteur', true );

		$file = get_stylesheet_directory() . '/templates/emails/transporters/' . $transp_slug . '.php';

		if ( array_key_exists( $transp_slug, $this->transporters ) && file_exists( $file ) ) {
			require_once $file;
			$this->add_transporter_jo_message( $order_id, $transp_slug );
		} else {
			return '<p>' . esc_html__( 'We have finished processing your order.', 'woocommerce' ) . '</p>';
		}
	}

	function add_transporter_jo_message( $order_id, $transp_slug ) {

		if ( ! in_array( $transp_slug, array( 'cargomatic', 'khuene', 'fragner' ), true ) ) {
			return;
		}

		$today = new DateTime();
		$start = new DateTime( '2024-07-01' );
		$end   = new DateTime( '2024-09-10' );

		if ( $today < $start || $today > $end ) {
			return;
		}

		$order = wc_get_order( $order_id );

		$shipping_postcode = $order->get_shipping_postcode();

		if ( ! in_array( substr( (string) $shipping_postcode, 0, 2 ), array( '28', '45', '75', '77', '78', '91', '92', '93', '94', '95' ), true ) ) {
			return;
		}

		require_once get_stylesheet_directory() . '/templates/emails/transporters/jo-notice.php';
	}

	/**
	 * Ajouter le choix du transporteur dans la colonne 'Statut de la commande'
	 *
	 * @param $columns
	 * @param $post_id
	 *
	 * @return void
	 */
	public function add_transporter_to_order_status_column( $column, $post_id ) {

		// Récupérer la commande actuelle.
		$order = wc_get_order( $post_id );
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
				$transporter_slug = get_post_meta( $order->get_id(), 'transporteur', true );

				// Afficher le transporteur si défini.
				if ( $transporter_slug ) {
					echo '<mark class="order-status status-completed"><span>' . esc_html( $this->transporters[ $transporter_slug ] ) . '</span></mark>';
				}
			}
		}
	}

	/**
	 * Afficher les données du champ ACF 'transporteur' dans la colonne 'Transporter'
	 *
	 * @param $column
	 * @param $post_id
	 *
	 * @return void
	 */
	public function show_transporter_data( $column, $post_id ) {

		$columns['transporter_column'] = __( 'Transporteur', 'kingmateriaux' );

		if ( 'transporter_column' === $column ) {

			// Récupérer les données du champ ACF 'transporteur'.
			$transp_name = get_post_meta( $post_id, 'transporteur', true );
			$transp_slug = sanitize_title( $transp_name );

			// Vérifie si le transporteur est dans le tableau $transporters.
			if ( array_key_exists( $transp_slug, $this->transporters ) ) {
				echo '<mark class="transp-label ' . esc_html( $transp_slug ) . '">' . esc_html( $this->transporters[ $transp_slug ] ) . '</mark>';
			} else {
				echo '<mark class="transp-label undefined">' . esc_html__( 'Non défini', 'kingmateriaux' ) . '</mark>';
			}
		}
	}

	/**
	 * Save the transporter value from the order admin page
	 *
	 * @return void
	 */
	public function save_transporter_callback() {
		if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['transporteur'] ) ) {
			wp_send_json_error( esc_html__( 'Erreur lors de la mise à jour.', 'kingmateriaux' ) );
		}

		$post_id      = intval( $_POST['post_id'] );
		$transporteur = sanitize_text_field( $_POST['transporteur'] );

		update_post_meta( $post_id, 'transporteur', $transporteur );
		wp_send_json_success( esc_html__( 'Transporteur mis à jour.', 'kingmateriaux' ) );
	}
}
