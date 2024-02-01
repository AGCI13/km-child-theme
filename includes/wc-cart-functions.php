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
	$km_shipping_zone = KM_Shipping_Zone::get_instance();
	if ( $km_shipping_zone->is_in_thirteen && is_cart() && 'Total' === $text ) {
		$translated_text = 'Total hors livraison';
	}
	return $translated_text;
}
add_filter( 'gettext', 'km_change_cart_totals_text', 20, 3 );

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
 * Affiche la mention de l'éco-taxe sous le prix unitaire
 *
 * @param string $price_html
 * @param array  $cart_item
 * @param string $cart_item_key
 * @return string
 */
function km_display_ecotaxe_with_unit_price( $price_html, $cart_item, $cart_item_key ) {
	if ( is_admin() ) {
		return;
	}
	$km_dynamique_pricing = KM_Dynamic_Pricing::get_instance();

	if ( $cart_item['_has_ecotax'] ) {
		$price_html .= '<br><small class="ecotaxe-amount">' . sprintf( __( 'Dont %s d\'Ecotaxe', 'kingmateriaux' ), wc_price( $km_dynamique_pricing->ecotaxe_rate_incl_taxes ) ) . '</small>';
	}
	return $price_html;
}
add_filter( 'woocommerce_cart_item_price', 'km_display_ecotaxe_with_unit_price', 10, 3 );

/**
 * Affiche la mention de l'éco-taxe sous le sous-total
 *
 * @param string $subtotal_html
 * @param array  $cart_item
 * @param string $cart_item_key
 * @return string
 */
function km_display_ecotaxe_with_subtotal( $subtotal_html, $cart_item, $cart_item_key ) {
	if ( is_admin() ) {
		return;
	}
	$km_dynamique_pricing = KM_Dynamic_Pricing::get_instance();

	if ( $cart_item['_has_ecotax'] ) {
		$ecotaxe_total  = $km_dynamique_pricing->ecotaxe_rate_incl_taxes * $cart_item['quantity'];
		$subtotal_html .= '<br><small class="ecotaxe-amount">' . sprintf( __( 'Dont %s d\'Ecotaxe', 'kingmateriaux' ), wc_price( $ecotaxe_total ) ) . '</small>';
	}
	return $subtotal_html;
}
add_filter( 'woocommerce_cart_item_subtotal', 'km_display_ecotaxe_with_subtotal', 10, 3 );


/**
 * Affiche le message de l'éco-taxe sous le panier
 *
 * @return void
 */
function km_ecotaxe_message_display() {
	?>
	<tr>
		<td colspan="100%" class="km-ecotaxe-row" >
			<div  class="km-ecotaxe-message">	
				<img src="<?php echo esc_html( get_stylesheet_directory_uri() . '/assets/img/ecotaxe.png' ); ?>" alt="">
				<p><?php esc_html_e( "Cette taxe s'applique pour contribuer à limiter et/ou à atténuer ou réparer certains effets d’actions générant des détériorations environnementales.", 'kingmateriaux' ); ?>
			</p>
			</div>
		</td>
	</tr>
	<?php
}
add_action( 'woocommerce_cart_contents', 'km_ecotaxe_message_display', 99 );


/**
 * Ajoute le montant de l'éco-taxe au total de la commande
 *
 * @param string $value
 * @return string
 */
function km_add_ecotax_to_order_total_html( $html ) {

	$km_dynamic_pricing = KM_Dynamic_Pricing::get_instance();
	$total_ecotaxe      = $km_dynamic_pricing->get_total_ecotaxe();

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
	// Vérifiez si WC_Cart est initialisé
	if ( is_admin() || ! is_a( WC()->cart, 'WC_Cart' ) ) {
		return;
	}

	$km_shipping_zone = KM_Shipping_Zone::get_instance();

	// Vérifie si les conditions pour afficher les informations de livraison sont remplies
	if ( ! $km_shipping_zone->zip_code || ( ! $km_shipping_zone->is_in_thirteen() && ! $km_shipping_zone->shipping_zone_id ) ) {
		return;
	}

	$shipping_html = km_get_shipping_info_text( $km_shipping_zone );

	?>
	<tr class="shipping-info">
		<th><?php esc_html_e( 'Expédition', 'kingmateriaux' ); ?></th>
		<td data-title="<?php esc_html_e( 'Expédition', 'kingmateriaux' ); ?>">
			<?php echo $shipping_html; ?>
		</td>
	</tr>
	<?php
	do_action( 'km_after_checkout_shipping' );
}

/**
 * Retourne le texte à afficher pour les informations de livraison
 *
 * @param KM_Shipping_Zone $km_shipping_zone
 * @return string
 */
function km_get_shipping_info_text( $km_shipping_zone ) {
	if ( $km_shipping_zone->is_in_thirteen() ) {
		$shipping_text = __( 'Calcul à l\'étape suivante', 'kingmateriaux' );
	} elseif ( $km_shipping_zone->shipping_zone_id ) {
		$shipping_text = __( 'Incluse', 'kingmateriaux' );
	} else {
		return '';
	}

	$shipping_text .= '<br>' . __( 'Livraison à ', 'kingmateriaux' ) . '<b>' . $km_shipping_zone->zip_code . '</b>';
	$shipping_text .= '<br><a class="btn-link modal_pc_open_btn" href="#">' . __( 'Modifier le code postal', 'kingmateriaux' ) . '</a>';

	return $shipping_text;
}

add_filter( 'woocommerce_cart_totals_before_order_total', 'km_display_shipping_info_text', 10 );

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
	// Afficher les coupons déjà appliqués.
	foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
		?>
	<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
		<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
		<td data-title="<?php echo esc_attr( wc_cart_totals_coupon_label( $coupon, false ) ); ?>"><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
	</tr>
		<?php
	}
}
add_action( 'woocommerce_cart_totals_before_order_total', 'km_add_redeem_coupon_in_cart_totals', 90 );

/**
 * Ajoute le champ de saisie du code promo après le total de la commande
 *
 * @param array  $cart_item
 * @param string $cart_item_key
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
