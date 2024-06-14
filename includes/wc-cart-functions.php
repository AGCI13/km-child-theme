<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Change le labelTotal dans le récapitulatif panier
 *
 * @param string $translated_text
 * @param string $text
 * @param string $domain
 * @return string
 */
function km_change_cart_totals_text( $translated_text, $text, $domain ) {
	if ( ! ( is_cart() || is_checkout() ) ) {
		return $translated_text;
	}

	if ( 'Total' === $text ) {
		$translated_text = 'Total TTC hors livraison';
	}
	return $translated_text;
}
add_filter( 'gettext', 'km_change_cart_totals_text', 20, 3 );


/**
 * Change le label Sous Total dans le récapitulatif panier
 *
 * @param string $translated_text
 * @param string $text
 * @param string $domain
 * @return string
 */
function km_change_cart_subtotals_text( $translated_text, $text, $domain ) {
	if ( ! ( is_cart() || is_checkout() ) ) {
		return $translated_text;
	}

	if ( in_array( $text, array( 'Sous-total', 'Subtotal' ) ) ) {
		$translated_text = 'Sous-total HT';
	}
	return $translated_text;
}
add_filter( 'gettext', 'km_change_cart_subtotals_text', 25, 3 );

/**
 * Supprime le calcul des frais de livraison du panier contient
 *
 * @param array $available_methods
 * @return array
 */
function filter_cart_needs_shipping( $needs_shipping ) {
	if ( is_cart() ) {
		$needs_shipping = false;
	}
	return $needs_shipping;
}
add_filter( 'woocommerce_cart_needs_shipping', 'filter_cart_needs_shipping' );

/**
 * Ajoute le bouton "Vider le panier" sur la page panier.
 *
 * @param array $available_methods
 * @return array
 */
function km_add_clear_cart_button() {
	?>
	<div class="cart-actions">
		<a class="cart-action-link clear-cart" href="<?php echo esc_url( add_query_arg( 'clear-cart', 'yes' ) ); ?>">
			<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/trash-alt.svg' ); ?>" alt="Empty cart icon"/>
			<?php esc_html_e( 'Vider le panier', 'kingmateriaux' ); ?> 
		</a>
	</div>
	<?php
}
add_action( 'woocommerce_before_cart_table', 'km_add_clear_cart_button', 1, 0 );

/**
 * Vide le panier si l'URL contient le paramètre clear-cart=yes
 *
 * @param array $available_methods
 * @return array
 */
function km_clear_cart_url() {
	if ( ! is_admin() && isset( $_GET['clear-cart'] ) && 'yes' === $_GET['clear-cart'] ) {
		WC()->cart->empty_cart();
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}
}
add_action( 'init', 'km_clear_cart_url' );

/**
 * Ajoute le champ de saisie du code promo après le total de la commande
 *
 * @return void
 */
function km_after_cart_coupon_content() {

	// Add email to WC session.
	$email = WC()->session->get( 'km_cart_discount_email' );

	if ( is_user_logged_in() || ! empty( $email ) ) {
		return;
	}
	?>
	<div id="km-customer-email-marketing">
		<p class="label">
			<?php printf( esc_html__( 'Renseignez votre e-mail et bénéficiez d\'un code promo de %1$s-10%%%2$s pour valider votre panier !', 'kingmateriaux' ), '<span class="highlighted">', '</span>' ); ?>
		</p>
		<p class="form">
			<input type="email" class="input-text" name="km_cart_discount_email" placeholder="<?php echo esc_html__( 'Adresse e-mail', 'kingmateriaux' ); ?>"/>
			<?php wp_nonce_field( 'discount_cart_form', 'km_cart_discount_email_nonce' ); ?>
			<button id="km-send-marketing-email" class="btn btn-primary confirm-btn"> 	<?php echo esc_html__( 'Valider', 'kingmateriaux' ); ?></button>
		</p>
	</div>
	<?php
}
add_action( 'woocommerce_after_cart_table', 'km_after_cart_coupon_content' );
/**
 *  --------------- START ECO-TAX ----------------------
 */

/**
 * Ajoute le montant de l'éco-taxe au total de la commande
 *
 * @param string $html Le HTML du total d'ecotaxe de la commande.
 * @return string
 */
function km_change_cart_price_html( $price_html, $cart_item, $cart_item_key, $context ) {
	if ( is_admin() ) {
		return;
	}

	$big_bag_quantity = km_get_big_bag_quantity_in_cart();

	$new_price_html = '';

	$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

	if ( $big_bag_quantity > 1 && km_is_big_bag_price_decreasing_zone() && ( km_is_big_bag( $product_id ) ||
		km_is_big_bag_and_slab( $product_id ) ) ) {
			$product       = wc_get_product( $product_id );
			$initial_price = $product->get_price();

		if ( 'subtotal' === $context && is_float( $initial_price ) && $initial_price > 0 ) {
			$initial_price *= $cart_item['quantity'];
		}

			$new_price_html .= '<del>'
			. wc_price( $initial_price )
			. '</del> ';
	}

	$new_price_html .= $price_html;

	if ( $cart_item['_has_ecotax'] ) {
		$ecotax_amount = 'subtotal' === $context ? km_get_ecotaxe_rate() * $cart_item['quantity'] : km_get_ecotaxe_rate();

		$new_price_html .= '<br><small class="ecotaxe-amount">'
		. sprintf( __( 'Dont %s d\'Ecotaxe', 'kingmateriaux' ), wc_price( $ecotax_amount ) )
		. '</small>';
	}

	return $new_price_html;
}

/**
 * Affiche la mention de l'éco-taxe sous le prix unitaire
 *
 * @param string $price_html
 * @param array  $cart_item
 * @param string $cart_item_key
 * @return string
 */
function km_change_product_unit_price( $price_html, $cart_item, $cart_item_key ) {
	return km_change_cart_price_html( $price_html, $cart_item, $cart_item_key, 'unit' );
}
add_filter( 'woocommerce_cart_item_price', 'km_change_product_unit_price', 10, 3 );

/**
 * Affiche la mention de l'éco-taxe sous le sous-total
 *
 * @param string $subtotal_html
 * @param array  $cart_item
 * @param string $cart_item_key
 * @return string
 */
function km_change_product_line_subtotal( $subtotal_html, $cart_item, $cart_item_key ) {
	return km_change_cart_price_html( $subtotal_html, $cart_item, $cart_item_key, 'subtotal' );
}
add_filter( 'woocommerce_cart_item_subtotal', 'km_change_product_line_subtotal', 10, 3 );


/**
 * Affiche les informations relatives au big bag sous le panier
 *
 * @return void
 */
function km_shipping_delays_jo_message() {
	if ( ! is_cart() ) {
		return;
	}

	$current_postcode = km_get_current_shipping_postcode();

	if ( ! in_array( substr( (string) $current_postcode, 0, 2 ), array( '28', '45', '75', '77', '78', '91', '92', '93', '94', '95' ), true ) ) {
		return;
	}

	$today = new DateTime();
	$start = new DateTime( '2024-07-01' );
	$end   = new DateTime( '2024-09-10' );

	if ( $today < $start || $today > $end ) {
		return;
	}
	?>
	<tr class="km-cart-info-row">
		<td colspan="100%">
			<div class="km-cart-info-wrapper km-delay-jo-message">	
				<?php esc_html_e( 'Pendant les épreuves des Jeux Olympiques, se déroulant du 26 juillet au 11 août, et des Jeux Paralympiques, du 28 août au 8 septembre, la région Île-de-France connaîtra un ralentissement du délai de livraison.', 'kingmateriaux' ); ?>
			</div>
		</td>
	</tr>
	<?php
}
add_action( 'woocommerce_cart_contents', 'km_shipping_delays_jo_message', 70 );

/**
 * Affiche les informations relatives au big bag sous le panier
 *
 * @return void
 */
function km_cart_big_bag_discount_info_html() {

	if ( ! km_is_big_bag_price_decreasing_zone() ) {
		return;
	}
	?>
	<tr class="km-cart-info-row">
		<td colspan="100%">
			<div class="km-cart-info-wrapper km-big-bag-discount-message">	
				<img src="<?php echo esc_html( get_stylesheet_directory_uri() . '/assets/img/icon-big-bag.png' ); ?>" alt="icone big bag">
				<?php esc_html_e( 'Tarifs des Big Bags dégressifs en fonction des quantités.', 'kingmateriaux' ); ?>
			</div>
		</td>
	</tr>
	<?php
}
	add_action( 'woocommerce_cart_contents', 'km_cart_big_bag_discount_info_html', 70 );

	/**
	 * Affiche les informations relatives à l'éco-taxe sous le panier
	 *
	 * @return void
	 */
function km_cart_ecotaxe_info_html() {
	?>
	<tr class="km-cart-info-row">
		<td colspan="100%">
			<div class="km-cart-info-wrapper km-ecotaxe-message">	
				<img src="<?php echo esc_html( get_stylesheet_directory_uri() . '/assets/img/ecotaxe.png' ); ?>" alt="icone ecotax">
			<?php esc_html_e( "L'Écotaxe s'applique pour contribuer à limiter, atténuer ou réparer certains effets d’actions générant des détériorations environnementales.", 'kingmateriaux' ); ?>
			</div>
		</td>
	</tr>
		<?php
}
	add_action( 'woocommerce_cart_contents', 'km_cart_ecotaxe_info_html', 80 );

	/**
	 * Affiche les informations de livraison sous le panier
	 *
	 * @return void
	 */
function km_cart_shipping_delays_info() {
	?>
	<tr class="km-cart-info-row">
		<td colspan="100%" class="km-cart-info-row">
			<div class="km-cart-info-wrapper km-shipping-delay-message">    
				<img src="<?php echo esc_html( get_stylesheet_directory_uri() . '/assets/img/icon-camion-livraison.png' ); ?>" alt="camion-livraison">
			<?php echo esc_html( km_get_shipping_dates() ); ?>
			</div>
		</td>
	</tr>
		<?php
}
	add_action( 'woocommerce_cart_contents', 'km_cart_shipping_delays_info', 90 );


	/**
	 * Ajoute le montant de l'éco-taxe au total de la commande
	 *
	 * @param string $html Le HTML du total d'ecotaxe de la commande.
	 * @return string
	 */
function km_add_ecotax_to_order_total_html( $html ) {

	$total_ecotaxe = km_get_total_ecotaxe();

	if ( ! $total_ecotaxe ) {
		return $html;
	}

	$ecotax_text = sprintf( __( 'et %s d\'Écotaxe', 'kingmateriaux' ), wc_price( $total_ecotaxe ) );
	return str_replace( 'tva)', 'TVA ' . $ecotax_text . ')', $html );
}
	add_filter( 'woocommerce_cart_totals_order_total_html', 'km_add_ecotax_to_order_total_html', 10, 1 );

	/**
	 *  --------------- END ECO-TAX ----------------------
	 */


	/**
	 * --------------- START RECAP ----------------------
	 */
function km_display_shipping_info_text() {
	if ( is_admin() || ! is_a( WC()->cart, 'WC_Cart' ) ) {
		return;
	}

	if ( ! km_get_current_shipping_postcode() || ( ! km_is_shipping_zone_in_thirteen() && ! km_get_current_shipping_zone_id() ) ) {
		return;
	}
	?>
	<tr class="shipping-info">
		<th><?php esc_html_e( 'Expédition', 'kingmateriaux' ); ?></th>
		<td data-title="<?php esc_html_e( 'Expédition', 'kingmateriaux' ); ?>">
		<?php echo km_get_shipping_info_text(); ?>
		</td>
	</tr>
		<?php
		do_action( 'km_after_checkout_shipping' );
}

	/**
	 * Retourne le texte à afficher pour les informations de livraison
	 *
	 * @return string
	 */
function km_get_shipping_info_text() {
	if ( km_is_shipping_zone_in_thirteen() ) {
		$shipping_text = __( 'Calcul à l\'étape suivante', 'kingmateriaux' );
	} elseif ( km_get_current_shipping_zone_id() ) {
		$shipping_text = __( 'Incluse', 'kingmateriaux' );
	} else {
		return '';
	}

	$shipping_text .= '<br>' . __( 'Livraison à ', 'kingmateriaux' ) . '<b>' . km_get_current_shipping_postcode() . '</b>';
	$shipping_text .= '<br><a class="btn-link modal_pc_open_btn" href="#">' . __( 'Modifier le code postal', 'kingmateriaux' ) . '</a>';

	return $shipping_text;
}

	add_filter( 'woocommerce_cart_totals_before_order_total', 'km_display_shipping_info_text', 80 );


	/**
	 * Ajoute le champ de saisie du code promo après le total de la commande
	 *
	 * @return void
	 */
	/**
	 * Ajoute le champ de saisie du code promo après le total de la commande
	 *
	 * @return void
	 */
function km_add_redeem_coupon_in_cart_totals() {
	if ( is_admin() || ! is_cart() ) {
		return;
	}

	// Afficher le formulaire du coupon.
	?>
	<tr class="coupon">
		<th><?php esc_html_e( 'Code Promo', 'kingmateriaux' ); ?></th>
		<td class="km-coupon-label" data-title="<?php esc_html_e( 'Vous avez un code promo ?', 'kingmateriaux' ); ?>">
			<form class="woocommerce-coupon-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
				<input type="text" name="coupon_code" class="coupon_code" class="input-text" placeholder="<?php esc_attr_e( 'Code promo', 'kingmateriaux' ); ?>" />
				<input type="submit" class="btn btn-secondary" name="apply_coupon" value="<?php esc_attr_e( 'Appliquer', 'kingmateriaux' ); ?>" />
			</form>
		</td>
	</tr>
		<?php
}
	add_action( 'woocommerce_cart_totals_before_order_total', 'km_add_redeem_coupon_in_cart_totals', 50 );

	/**
	 * Ajoute le champ de saisie du code promo après le total de la commande
	 *
	 * @param array  $cart_item The cart item data.
	 * @param string $cart_item_key The cart item key.
	 * @return void
	 */
function km_add_pallet_description_under_product_name( $cart_item, $cart_item_key ) {
	$product_name = $cart_item['data']->get_name();
	if ( strpos( $product_name, 'Palette' ) !== false ) {
		echo '<small class="cart-item-meta">' . esc_html__( '⚠ Les palettes de parpaings sont consignées au prix de 28,80 € TTC la palette. Nous vous invitons à retourner la ou les palettes dans nos locaux, nous vous rembourserons 20,40 € TTC par palette. ⚠', 'kingmateriaux' ) . '</small>';
	}
}
	add_action( 'woocommerce_after_cart_item_name', 'km_add_pallet_description_under_product_name', 10, 2 );


	/**
	 * Vérifie le poids du panier avant d'ajouter un produit
	 *
	 * @param bool $passed
	 * @param int  $product_id
	 * @param int  $quantity
	 * @return bool
	 */
function km_check_cart_weight_before_adding( $passed, $product_id, $quantity ) {
	$max_weight     = 59999; // Le poids maximum du panier en kg.
	$product        = wc_get_product( $product_id );
	$product_weight = $product->get_weight() * $quantity; // Poids du produit à ajouter.

	// Calculer le poids total du panier actuel.
	$cart_weight = WC()->cart->get_cart_contents_weight();

	// Vérifier si l'ajout du produit dépasse le poids maximum.
	if ( ( $cart_weight + $product_weight ) > $max_weight ) {
		// Ajouter un message d'erreur.
		wc_add_notice( __( 'Désolé, l\'ajout de ce produit dépasse le poids maximum de 60 tonnes autorisé.', 'kingmateriaux' ), 'error' );
		return false;
	}

	return $passed;
}
	add_filter( 'woocommerce_add_to_cart_validation', 'km_check_cart_weight_before_adding', 10, 3 );

	/**
	 * Ajoute ecotaxe au produit dans le panier
	 *
	 * @param array $cart_item_data
	 * @param int   $product_id
	 * @param int   $variation_id
	 * @return array
	 */
function km_add_ecotax_to_cart_item( $cart_item_data, $product_id, $variation_id ) {

	$has_ecotax = get_post_meta( $product_id, '_has_ecotax', true );

	if ( ! $has_ecotax && $variation_id ) {
		$has_ecotax = get_post_meta( $variation_id, '_has_ecotax', true );
	}

	if ( $has_ecotax ) {
		$cart_item_data['_has_ecotax'] = true;
	}

	return $cart_item_data;
}
	add_filter( 'woocommerce_add_cart_item_data', 'km_add_ecotax_to_cart_item', 10, 3 );

	/**
	 * Gère le cas ou un produit est gratuit
	 *
	 * @param WC_Cart $cart
	 * @return void
	 */
function km_manage_cart_free_product_price( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
		return;
	}

	foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {

		if ( ! empty( $cart_item['wdr_free_product'] ) && 'Free' === $cart_item['wdr_free_product'] ) {
			$cart_item['data']->set_price( 0 );
			$cart_item['data']->update_meta_data( 'is_free_product', true );
			$cart_item['data']->save();
		}
	}
}
	add_action( 'woocommerce_before_calculate_totals', 'km_manage_cart_free_product_price', 20, 1 );
