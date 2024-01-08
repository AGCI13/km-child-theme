<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Rends le code postal non-modifiable dans le tunnel de commande
 *
 * @param $fields
 * @return array
 */
function km_override_checkout_fields( $fields ): array {
	$fields['billing']['billing_postcode']['km_attributes'] = array( 'readonly' => 'readonly' );
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'km_override_checkout_fields' );

/**
 * Rends le code postal non modifiable dans l'adresse de livraison woocommerce
 *
 * @param $address_fields
 * @return array
 */
function km_override_default_address_fields( $address_fields ): array {
	// Vérifie si l'utilisateur est sur la page de livraison
	if ( is_wc_endpoint_url( 'edit-address' ) && isset( $_GET['address'] ) && $_GET['address'] === 'shipping' ) {
		$address_fields['postcode']['km_attributes'] = array( 'readonly' => 'readonly' );
	}
	return $address_fields;
}
add_filter( 'woocommerce_default_address_fields', 'km_override_default_address_fields' );

/**
 * Remplit automatiquement le champ code postal avec le cookie
 *
 * @return void
 */
function km_override_checkout_init(): void {
	if ( isset( $_COOKIE['zip_code'] ) ) {
		$zip_code                  = explode( '-', $_COOKIE['zip_code'] )[0];
		$_POST['billing_postcode'] = $zip_code;
	}
}
add_action( 'woocommerce_checkout_init', 'km_override_checkout_init' );

/**
 * Ajoute un champ de date et d'heure de retrait
 *
 * @return void
 */
function validate_drive_date_time() {
	if ( 'drive' !== WC()->session->get( 'chosen_shipping_methods' )[0] ) {
		return;
	}

	if ( isset( $_POST['drive_date'] ) && empty( $_POST['drive_date'] ) ) {
		wc_add_notice( __( 'Veuillez choisir une date dans le calendrier du King Drive.', 'kingmateriaux' ), 'error' );
	}

	if ( isset( $_POST['drive_time'] ) && empty( $_POST['drive_time'] ) ) {
		wc_add_notice( __( 'Veuillez choisir un créneau horaire dans le calendrier du King Drive.', 'kingmateriaux' ), 'error' );
	}
}
add_action( 'woocommerce_checkout_process', 'validate_drive_date_time' );

/**
 * Relance la fonction km_get_drive_available_days() pour charger plus de jours.
 *
 * @return string
 */
function km_get_more_drive_available_days() {
	$days = km_get_drive_available_days();
	wp_send_json_success( $days );
}
add_action( 'wp_ajax_get_drive_available_days', 'km_get_more_drive_available_days' );
add_action( 'wp_ajax_nopriv_get_drive_available_days', 'km_get_more_drive_available_days' );

/**
 * Génère la liste de jour disponible pour le drive en fonction des réglages dans Woocommerce > Expédition > King Drive.
 *
 * @return string
 */
function km_get_drive_available_days() {
	$days = '';

	// Get the days of the week and the specific dates to exclude
	$drive_settings         = get_option( 'woocommerce_drive_settings', '' );
	$unavailable_days       = isset( $drive_settings['unavailable_days'] ) ? $drive_settings['unavailable_days'] : '';
	$unavailable_days_array = ! empty( $unavailable_days ) ? explode( ',', $unavailable_days ) : array();

	$unavailable_dates       = isset( $drive_settings['unavailable_dates'] ) ? $drive_settings['unavailable_dates'] : '';
	$unavailable_dates_array = ! empty( $unavailable_dates ) ? explode( ',', $unavailable_dates ) : array();

	$drive_day_offset = isset( $drive_settings['day_offset'] ) ? intval( $drive_settings['day_offset'] ) : 0;
	$offset           = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : $drive_day_offset;

	$day_num = isset( $drive_settings['day_num'] ) && is_numeric( $drive_settings['day_num'] ) ? intval( $drive_settings['day_num'] ) : 20;

	for ( $i = $offset; $i < $offset + $day_num; $i++ ) {
		$date           = strtotime( '+' . $i . ' days' );
		$day_name       = strtolower( date_i18n( 'l', $date ) );
		$formatted_date = date_i18n( 'Y-m-d', $date );

		if ( in_array( $day_name, $unavailable_days_array ) || in_array( $formatted_date, $unavailable_dates_array ) ) {
			++$offset;
			continue;
		}

		$day_label = date_i18n( 'l d F', $date );
		$days     .= '<li class="day" data-date="' . esc_html( $formatted_date ) . '">' . esc_html( $day_label ) . '</li>';
	}

	return $days;
}

/**
 * Ajouter le montant des frais de livraison dans le total du panier avec le hook woocommerce_review_order_before_shipping
 *
 * @return void
 */
function km_add_shipping_cost_to_cart_total() {

	$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

	if ( ! $chosen_shipping_methods || empty( $chosen_shipping_methods ) ) {
		return;
	} elseif ( in_array( 'drive', $chosen_shipping_methods, true ) ) {
		$shipping_label = __( 'À récupérer au King Drive', 'kingmateriaux' );
	} elseif ( in_array( 'out13', $chosen_shipping_methods, true ) ) {
		$shipping_label = __( 'Frais de livraison', 'kingmateriaux' );
		$shipping_cost  = __( 'Inclus', 'kingmateriaux' );
		$shiping_date   = km_display_shipping_delays_after_shipping();
	} else {
		$shipping_label = __( 'Frais de livraison', 'kingmateriaux' );
		$shiping_date   = km_display_shipping_delays_after_shipping();
		$shipping_cost  = WC()->cart->get_cart_shipping_total();
	}
	?>
	<tr class="shipping">
		<th><?php echo esc_html( $shipping_label ); ?></th>
		<td data-title="<?php echo esc_html( $shipping_label ); ?>">
		<span class="shipping-cost"><?php echo $shipping_cost; ?></span>
		<?php echo $shiping_date; ?>
		</td>
	</tr>
	<?php
}
add_action( 'woocommerce_review_order_before_order_total', 'km_add_shipping_cost_to_cart_total', 20 );

function km_display_shipping_delays_after_shipping() {

	$html = '';

	// Récupérer l'ID de la zone de livraison.
	$shipping_zone_id = KM_Shipping_Zone::get_instance()->shipping_zone_id;

	$longest_min_delay = 0;
	$longest_max_delay = 0;

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product_id = $cart_item['product_id'];

		// Vérifier si des délais de livraison personnalisés sont définis via ACF.
		$custom_delays_hs = get_field( 'product_shipping_delays_product_shipping_delays_hs', $product_id );
		$custom_delays_ls = get_field( 'product_shipping_delays_product_shipping_delays_ls', $product_id );

		// Déterminer la saison actuelle.
		$current_month  = date( 'n' );
		$is_high_season = $current_month >= 3 && $current_month <= 8; // De Mars à Août.

		// Récupérer les délais de livraison en fonction de la saison et des données personnalisées.
		$min_shipping_days = $is_high_season ? ( empty( $custom_delays_hs['min_shipping_days_hs'] ) ?? get_option( 'min_shipping_days_hs_' . $shipping_zone_id ) ) : ( empty( $custom_delays_ls['min_shipping_days_ls'] ) ? get_option( 'min_shipping_days_ls_' . $shipping_zone_id ) : $custom_delays_ls['min_shipping_days_ls'] );
		$max_shipping_days = $is_high_season ? ( empty( $custom_delays_hs['max_shipping_days_hs'] ) ?? get_option( 'max_shipping_days_hs_' . $shipping_zone_id ) ) : ( empty( $custom_delays_ls['max_shipping_days_ls'] ) ? get_option( 'max_shipping_days_ls_' . $shipping_zone_id ) : $custom_delays_ls['max_shipping_days_ls'] );

		// Vérifier si les informations sont disponibles.
		if ( empty( $min_shipping_days ) && empty( $max_shipping_days ) ) {
			return; // Si les deux sont manquants, ne rien afficher.
		}

		if ( $min_shipping_days > $longest_min_delay ) {
			$longest_min_delay = $min_shipping_days;
		}

		if ( $max_shipping_days > $longest_max_delay ) {
			$longest_max_delay = $max_shipping_days;
		}
	}

	if ( 0 === $longest_min_delay && 0 === $longest_max_delay ) {
		return; // Si les deux sont manquants, ne rien afficher.
	}
		$current_date  = new DateTime(); // Date actuelle.
		$delivery_date = clone $current_date;

		// Si les délais minimum et maximum sont identiques, affichez une seule date.
	if ( 0 === $longest_min_delay || 0 === $longest_max_delay || $longest_min_delay === $longest_max_delay ) {
		$delivery_date->add( new DateInterval( 'P' . $longest_min_delay . 'D' ) );
		$formatted_date = $delivery_date->format( 'd/m/Y' );
		$html          .= '<tr><td colspan="2" class="km-cart-longest-delay">Livraison prévue le ' . $formatted_date . '</td></tr>';
	} else {
		// Calculer et afficher une plage de dates si les délais sont différents.
		$min_delivery_date = clone $delivery_date;
		$min_delivery_date->add( new DateInterval( 'P' . $longest_min_delay . 'D' ) );
		$formatted_min_date = $min_delivery_date->format( 'd/m/Y' );

		$max_delivery_date = clone $delivery_date;
		$max_delivery_date->add( new DateInterval( 'P' . $longest_max_delay . 'D' ) );
		$formatted_max_date = $max_delivery_date->format( 'd/m/Y' );

		$html .= '<tr><td colspan="2" class="km-cart-longest-delay">Livraison prévue entre le ' . $formatted_min_date . ' et le ' . $formatted_max_date . '</td></tr>';
	}

	return $html;
}

function km_add_custom_hidden_fields_to_checkout() {

	// Ajouter la condition, if is thirteen.
	$km_shipping_zone = KM_Shipping_Zone::get_instance();
	if ( ! $km_shipping_zone->is_in_thirteen() ) {
		return;
	}

	// Ajouter un champ caché pour km_shipping_sku.
	woocommerce_form_field(
		'km_shipping_sku',
		array(
			'type'  => 'hidden',
			'class' => array( 'km-shipping-sku-field' ),
		),
	);

	// Ajouter un champ caché pour km_shipping_price.
	woocommerce_form_field(
		'km_shipping_price',
		array(
			'type'  => 'hidden',
			'class' => array( 'km-shipping-price-field' ),
		),
	);

	// Ajouter un champ caché pour km_shipping_tax.
	woocommerce_form_field(
		'km_shipping_tax',
		array(
			'type'  => 'hidden',
			'class' => array( 'km-shipping-tax-field' ),
		),
	);
}

add_action( 'woocommerce_after_checkout_billing_form', 'km_add_custom_hidden_fields_to_checkout' );

/**
 * Ajoute les conditions de livraison pour les modes de livraison.
 *
 * @param string $chosen_method Le mode de livraison choisi.
 * @return void
 */
function km_add_shipping_rate_conditions( $chosen_method ) {
	// Get WC Cart weight.
	$cart_weight        = WC()->cart->get_cart_contents_weight();
	$chosen_method_data = get_option( 'woocommerce_' . $chosen_method . '_settings' );

	// Check if cart contains product named "benne".
	$contains_benne = false;
	foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
		if ( strpos( $cart_item['data']->get_name(), 'benne' ) !== false ) {
			$contains_benne = true;
			break;
		}
	}

	ob_start();

	if ( $cart_weight > 2000 && ( ! empty( $chosen_method_data['unload_condition'] ) || ! empty( $chosen_method_data['access_condition'] ) ) ) :
		?>
		<h4><?php esc_html_e( 'Pour valider votre mode de livraison, veuillez accepter les conditions suivantes :', 'kingmateriaux' ); ?></h4>

			<?php if ( ! empty( $chosen_method_data['access_condition'] ) ) : ?>
			<div class="shipping-condition validate-required">
				<input type="checkbox" name="delivery_access_confirmation" id="delivery-access-confirmation" required>
				<label for="delivery-access-confirmation"><?php echo esc_html( $chosen_method_data['access_condition'] ); ?><span style="color:red">*</span></label>
			</div>
		<?php endif; ?>

			<?php if ( ! empty( $chosen_method_data['unload_condition'] ) ) : ?>
			<div class="shipping-condition validate-required">
				<input type="checkbox" name="delivery_unloading_confirmation" id="delivery-unloading-confirmation" required>
				<label for="delivery-unloading-confirmation"><?php echo esc_html( $chosen_method_data['unload_condition'] ); ?><span style="color:red">*</span></label>
			</div>
		<?php endif; ?>
		<?php
	endif;
	?>

	<?php if ( $contains_benne ) : ?>
		<div class="shipping-condition validate-required">
			<input type="checkbox" name="delivery_benne_confirmation" id="delivery-benne-confirmation" required>
			<label for="delivery-benne-confirmation"><?php esc_html_e( 'Les bennes placées sur la voie publique doivent obligatoirement faire l’objet d’une demande d’autorisation d’occupation temporaire (AOT) auprès de votre mairie.', 'kingmateriaux' ); ?><span style="color:red">*</span></label>
		</div>
		<?php
	endif;
}
add_action( 'km_after_shipping_rate', 'km_add_shipping_rate_conditions', 10, 1 );

/** --------------  DEBUG CODE START ----------------- */

function km_display_shipping_info_in_footer() {
	$km_shipping_zone = KM_Shipping_Zone::get_instance();
	if ( is_admin() || ! is_checkout() || ! is_user_logged_in() || ! current_user_can( 'manage_options' ) || ! $km_shipping_zone->is_in_thirteen() ) {
		return;
	}
	// Vérifier si sur la page de paiement

		// Noms des cookies que vous pourriez avoir définis
		$shipping_methods = array( 'option-1', 'option-1-express', 'option-2', 'option-2-express' );

		echo '<div id="km-shipping-info-debug" class="km-debug-bar">';
		echo '<h4>DEBUG</h4><img class="modal-debug-close km-modal-close" src="' . esc_url( get_stylesheet_directory_uri() . '/assets/img/cross.svg' ) . '" alt="close modal"></span>';
		echo '<button class="btn btn-primary km-recalc-cart">Recalculer le panier</button><script>jQuery(document).ready(function(){jQuery(".km-recalc-cart").on("click",function(){jQuery("body").trigger("update_checkout");});});</script>';
		echo '<div class="debug-content"><p>Les couts de livraisons sont <strong>calculés lors de la mise à jour du panier</strong>. Pour l\'heure, le VRAC est compté à part. Si une plaque de placo est présente, tous les produits isolation sont comptés à part.</p>';

	foreach ( $shipping_methods as $method ) {
		$cookie_name = 'km_shipping_cost_' . $method;

		if ( isset( $_COOKIE[ sanitize_title( $cookie_name ) ] ) ) {
			$shipping_info = json_decode( stripslashes( $_COOKIE[ $cookie_name ] ), true );

			echo '<table>';
			echo '<thead><tr><th colspan="2">Coûts de livraison pour ' . esc_html( $method ) . ':</th></tr></thead>';
			echo '<tbody>';
			foreach ( $shipping_info as $key => $value ) {
				if ( strpos( $key, 'poids' ) !== false ) {
					$value = esc_html( $value ) . ' Kg';
				} elseif ( strpos( $key, 'placo' ) !== false ) {
					$value = esc_html( $value );
				} elseif ( strpos( $key, 'prix' ) !== false ) {
					$value = esc_html( $value ) . ' €';
				} else {
					$value = esc_html( $value );
				}
				echo '<tr><td>' . esc_html( $key ) . '</td><td>' . esc_html( $value ) . '</td></tr>';
			}
			echo '</tbody>';
			echo '</table>';
		}
	}

	echo '</div></div>';
}
add_action( 'wp_footer', 'km_display_shipping_info_in_footer' );

/** --------------  DEBUG CODE END ----------------- */
