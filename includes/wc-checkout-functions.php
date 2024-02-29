<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Remplit automatiquement le champ code postal avec le cookie
 *
 * @return void
 */
function km_override_checkout_init(): void {
	if ( isset( $_COOKIE['zip_code'] ) ) {
		$_POST['shipping_postcode'] = explode( '-', $_COOKIE['zip_code'] )[0];
	}
}
add_action( 'woocommerce_checkout_init', 'km_override_checkout_init' );

/**
 * Empèche le passage de commande sans shipping method
 *
 * @return void
 */
function km_require_shipping_method() {
	$packages = WC()->shipping->get_packages();

	foreach ( $packages as $i => $package ) {
		if ( ! isset( $package['rates'] ) || empty( $package['rates'] ) ) {
			wc_add_notice( __( 'Veuillez sélectionner un mode de livraison.', 'woocommerce' ), 'error' );
		}
	}
}
add_action( 'woocommerce_checkout_process', 'km_require_shipping_method' );

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

	// Get the days of the week and the specific dates to exclude.
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
		$formatted_date = date_i18n( 'd F Y', $date );

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

	$packages = WC()->shipping->get_packages();

	foreach ( $packages as $i => $package ) {
		if ( ! isset( $package['rates'] ) || empty( $package['rates'] ) ) {
			return;
		}
	}

	if ( ! $chosen_shipping_methods || empty( $chosen_shipping_methods ) ) {
		return;
	} elseif ( in_array( 'drive', $chosen_shipping_methods, true ) ) {
		$shipping_label = __( 'À récupérer au King Drive', 'kingmateriaux' );
	} elseif ( in_array( 'out13', $chosen_shipping_methods, true ) || in_array( 'included', $chosen_shipping_methods, true ) ) {
		$shipping_label = __( 'Frais de livraison', 'kingmateriaux' );
		$shipping_cost  = __( 'Inclus', 'kingmateriaux' );
		$shipping_date  = km_get_shipping_dates();
	} else {
		$shipping_label = __( 'Frais de livraison', 'kingmateriaux' );
		$shipping_date  = km_get_shipping_dates();
		$shipping_cost  = WC()->cart->get_cart_shipping_total();
	}
	?>
	<tr class="shipping">
		<th><?php echo esc_html( $shipping_label ); ?></th>
		<td data-title="<?php echo esc_html( $shipping_label ); ?>">
			<span class="shipping-cost"><?php echo $shipping_cost; ?></span>
			<tr><td colspan="2" class="km-cart-longest-delay"><?php echo esc_html( $shipping_date ); ?></td></tr>
		</td>
	</tr>
	<?php
}
// add_action( 'woocommerce_review_order_before_order_total', 'km_add_shipping_cost_to_cart_total', 20 );

/**
 * Ajoute les champs cachés pour les données de livraison.
 *
 * @return void
 */
function km_add_custom_hidden_fields_to_checkout() {

	if ( ! km_is_shipping_zone_in_thirteen() ) {
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

	if ( WC()->cart->is_empty() ) {
		return;
	}

	$cart_weight        = WC()->cart->get_cart_contents_weight();
	$chosen_method_data = get_option( 'woocommerce_' . $chosen_method . '_settings' );

	$contains_benne = false;
	foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
		if ( stripos( $cart_item['data']->get_name(), 'benne' ) !== false ) {
			$contains_benne = true;
			break;
		}
	}

	$show_conditions = false;

	if ( ( $cart_weight > 2000 && ( ! empty( $chosen_method_data['unload_condition'] ) || ! empty( $chosen_method_data['access_condition'] ) ) ) || $contains_benne ) {
		$show_conditions = true;
	}

	if ( $show_conditions ) {
		?>
		<h4><?php esc_html_e( 'Pour valider votre mode de livraison, veuillez accepter les conditions suivantes :', 'kingmateriaux' ); ?></h4>
		<?php

		if ( $cart_weight > 2000 && ! empty( $chosen_method_data['access_condition'] ) ) {
			?>
			<div class="shipping-condition validate-required">
				<input type="checkbox" name="delivery_access_confirmation" id="delivery-access-confirmation" required>
				<label for="delivery-access-confirmation"><?php echo esc_html( $chosen_method_data['access_condition'] ); ?><span style="color:red">*</span></label>
			</div>
			<?php
		}
		if ( $cart_weight > 2000 && ! empty( $chosen_method_data['unload_condition'] ) ) {
			?>
			<div class="shipping-condition validate-required">
				<input type="checkbox" name="delivery_unloading_confirmation" id="delivery-unloading-confirmation" required>
				<label for="delivery-unloading-confirmation"><?php echo esc_html( $chosen_method_data['unload_condition'] ); ?><span style="color:red">*</span></label>
			</div>
			<?php
		}

		if ( $contains_benne ) {
			?>
			<div class="shipping-condition validate-required">
				<input type="checkbox" name="delivery_benne_confirmation" id="delivery-benne-confirmation" required>
				<label for="delivery-benne-confirmation"><?php esc_html_e( 'Les bennes placées sur la voie publique doivent obligatoirement faire l’objet d’une demande d’autorisation d’occupation temporaire (AOT) auprès de votre mairie.', 'kingmateriaux' ); ?><span style="color:red">*</span></label>
			</div>
			<?php
		}
	}
}
add_action( 'km_after_shipping_rate', 'km_add_shipping_rate_conditions', 10, 1 );


/**
 * Ajoute les conditions de livraison pour les modes de livraison.
 *
 * @param string $chosen_method Le mode de livraison choisi.
 * @return void
 */
function km_display_shipping_dates( $chosen_method ) {
	$shipping_dates = km_get_shipping_dates();

	if ( ! $shipping_dates ) {
		return;
	}
	?>
		<div class="km-checkout-shipping-info">	
			<img src="<?php echo esc_html( get_stylesheet_directory_uri() . '/assets/img/icon-camion-livraison.png' ); ?>" alt="camion-livraison">
			<?php echo esc_html( $shipping_dates ); ?>
			<input type="hidden" name="shipping_dates" value="<?php echo esc_html( $shipping_dates ); ?>">
		</div>
	<?php
}
add_action( 'km_after_shipping_rate', 'km_display_shipping_dates', 20, 1 );

/**
 * Ajout une case à chocher pour s'inscrire à la newsletter sur la page de paiement
 *
 * @return void
 */
function km_add_newsletter_checkbox() {
	woocommerce_form_field(
		'inscription_newsletter',
		array(
			'type'        => 'checkbox',
			'class'       => array( 'form-row newsletter' ),
			'label_class' => array( 'woocommerce-form__label woocommerce-form__label-for-checkbox checkbox' ),
			'input_class' => array( 'woocommerce-form__input woocommerce-form__input-checkbox input-checkbox' ),
			'required'    => false,
			'label'       => __( 'Je m\'inscris à la newsletter et je profite d\'offres exclusives !' ),
		)
	);
}
add_action( 'woocommerce_review_order_before_submit', 'km_add_newsletter_checkbox', 9 );


/**
 * Autoriser les utilisateurs non connectés à payer pour une commande.
 *
 * @param array $allcaps Les capacités de l'utilisateur.
 * @param array $caps    Les capacités demandées.
 * @param array $args    Arguments supplémentaires.
 *
 * @return array
 */
function km_order_pay_without_login( $allcaps, $caps, $args ) {
	if ( isset( $caps[0], $_GET['key'] ) ) {
		if ( 'pay_for_order' === $caps[0] ) {
			$order_id = isset( $args[2] ) ? $args[2] : null;
			$order    = wc_get_order( $order_id );
			if ( $order ) {
				$allcaps['pay_for_order'] = true;
			}
		}
	}
	return $allcaps;
}
add_filter( 'user_has_cap', 'km_order_pay_without_login', 9999, 3 );
add_filter( 'woocommerce_order_email_verification_required', '__return_false', 9999 );
