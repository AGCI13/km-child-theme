<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
function add_clear_cart_button() {
	?>
	<div class="cart-actions">
		<a class="cart-action-link clear-cart" href="<?php echo esc_url( add_query_arg( 'clear-cart', 'yes' ) ); ?>">
			<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/trash-alt.svg' ); ?>" alt="Empty cart icon"/>
			<?php esc_html_e( 'Vider le panier', 'kingmateriaux' ); ?> 
		</a>
	</div>
	<?php
}
add_action( 'woocommerce_before_cart_table', 'add_clear_cart_button', 1, 0 );

/**
 * Vide le panier si l'URL contient le paramètre clear-cart=yes
 *
 * @param array $available_methods
 * @return array
 */
function clear_cart_url() {
	if ( isset( $_GET['clear-cart'] ) && 'yes' === $_GET['clear-cart'] ) {
		WC()->cart->empty_cart();
		wp_redirect( wc_get_cart_url() );
		exit;
	}
}
add_action( 'init', 'clear_cart_url' );

/**
 * Ajoute le champ de saisie du code promo après le total de la commande
 *
 * @return void
 */
function after_cart_coupon_content() {

	// Add email to WC session
	$email = WC()->session->get( 'wac_email' );

	if ( is_user_logged_in() || ! empty( $email ) ) {
		return;
	}
	?>
	<div id="km-customer-email-marketing">
		<p class="label">
			<?php printf( esc_html__( 'Renseignez votre e-mail et bénéficiez d\'un code promo de %1$s-10%%%2$s pour valider votre panier !', 'kingmateriaux' ), '<span class="highlighted">', '</span>' ); ?>
		</p>
		<p class="form">
			<input type="email" class="input-text" name="" placeholder="<?php echo esc_html__( 'Adresse e-mail', 'kingmateriaux' ); ?>"/>
			<button class="btn btn-primary confirm-btn">	<?php echo esc_html__( 'Valider', 'kingmateriaux' ); ?></button>
		</p>
	</div>
	<?php
}
add_action( 'woocommerce_after_cart_table', 'after_cart_coupon_content' );
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
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	$km_dynamique_pricing = KM_Dynamic_Pricing::get_instance();

	if ( $km_dynamique_pricing->product_is_bulk_or_bigbag( $cart_item['data'] ) ) {
		$price_html .= '<br><small class="ecotaxe-amount">' . sprintf( __( 'Dont %s d\'Ecotaxe', 'kingmateriaux' ), wc_price( $km_dynamique_pricing->ecotaxe_rate ) ) . '</small>';
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
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	$km_dynamique_pricing = KM_Dynamic_Pricing::get_instance();

	if ( $km_dynamique_pricing->product_is_bulk_or_bigbag( $cart_item['data'] ) ) {
		$ecotaxe_total  = $km_dynamique_pricing->ecotaxe_rate * $cart_item['quantity'];
		$subtotal_html .= '<br><small class="ecotaxe-amount">' . sprintf( __( 'Dont %s d\'Ecotaxe', 'kingmateriaux' ), wc_price( $ecotaxe_total ) ) . '</small>';
	}
	return $subtotal_html;
}
add_filter( 'woocommerce_cart_item_subtotal', 'km_display_ecotaxe_with_subtotal', 10, 3 );

/**
 * Ajoute l'éco-taxe au prix de l'article dans le panier
 *
 * @param WC_Cart $cart
 * @return void
 */
function km_add_ecotaxe_to_cart_item_prices( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	// S'assurer que cette action n'est exécutée qu'une seule fois.
	if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
		return;
	}

	$km_dynamique_pricing = KM_Dynamic_Pricing::get_instance();

	foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
		if ( $km_dynamique_pricing->product_is_bulk_or_bigbag( $cart_item['data'] ) ) {
			// Ajouter l'éco-taxe au prix de l'article dans le panier.
			$original_price = $cart_item['data']->get_price();
			$new_price      = $original_price + $km_dynamique_pricing->ecotaxe_rate;
			$cart_item['data']->set_price( $new_price );
		}
	}
}
add_action( 'woocommerce_before_calculate_totals', 'km_add_ecotaxe_to_cart_item_prices', 10, 1 );

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
function add_ecotax_to_order_total_html( $value ) {
	$ecotax_amount = 50; // Montant fictif de l'écotax. Récupérez la valeur réelle selon votre besoin.
	$ecotax_text   = sprintf( __( 'et %s d\'Écotaxe', 'kingmateriaux' ), wc_price( $ecotax_amount ) );
	return str_replace( 'tva)', 'TVA ' . $ecotax_text . ')', $value );
}
add_filter( 'woocommerce_cart_totals_order_total_html', 'add_ecotax_to_order_total_html', 10, 1 );

/**
 *  --------------- END ECO-TAX ----------------------
 */


/**
 * --------------- START RECAP ----------------------
 */

/**
 * Ajoute le récapitulatif de la commande sous le panier
 *
 * @return void
 */

function display_shipping_info_text() {
	if ( KM_Shipping_Zone::get_instance()->is_in_thirteen() ) {
		$value = __( 'Choix à l\'étape suivante', 'kingmateriaux' );
	} else {
		$value = __( 'Incluse', 'kingmateriaux' );
	}
	?>
		<tr class="shipping-info">
			<th><?php esc_html_e( 'Livraison', 'kingmateriaux' ); ?></th>
			<td data-title="<?php esc_html_e( 'Livraison', 'kingmateriaux' ); ?>">
				<?php echo esc_html( $value ); ?>
				<?php echo do_shortcode( '[estimate_delivery_date]' ); ?>
			</td>
		</tr>
	<?php
}
add_filter( 'woocommerce_cart_totals_before_order_total', 'display_shipping_info_text', 10 );

/**
 * Ajoute le champ de saisie du code promo après le total de la commande
 *
 * @return void
 */
function km_add_cart_totals_after_order_total() {
	$coupon  = isset( $_GET['coupon'] ) ? esc_attr( $_GET['coupon'] ) : false;
	$applied = false;
	$message = '';

	// Vérifier si le coupon est soumis et pas déjà appliqué
	if ( $coupon && ! WC()->cart->has_discount( $coupon ) ) {
		$applied = WC()->cart->apply_coupon( $coupon );
		$message = $applied ? sprintf( __( 'Code promo "%s" appliqué.' ), $coupon ) : __( 'Ce code promo est invalide' );

		if ( $applied ) {
			foreach ( WC()->cart->get_coupons() as $code => $coupon_obj ) :
				?>
				<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
					<th><?php wc_cart_totals_coupon_label( $coupon_obj ); ?></th>
					<td data-title="<?php echo esc_attr( wc_cart_totals_coupon_label( $coupon_obj, false ) ); ?>"><?php wc_cart_totals_coupon_html( $coupon_obj ); ?></td>
				</tr>
				<?php
			endforeach;
		}
	}

	// Afficher le formulaire du coupon
	if ( ! WC()->cart->has_discount( $coupon ) ) {
		?>
			<tr class="coupon">
				<th><?php esc_html_e( 'Code Promo', 'kingmateriaux' ); ?></th>
				<td id="km-coupon-label" data-title="<?php esc_html_e( 'Vous avez un code promo ?', 'kingmateriaux' ); ?>">
					<form id="coupon-redeem" class="redeem-coupon"> 
						<input type="text" name="coupon" id="coupon" value="<?php echo esc_attr( $coupon ); ?>"/>
						<input type="submit" class="btn btn-secondary" name="redeem-coupon" value="<?php esc_html_e( 'Valider' ); ?>" />
					</form>
					<?php if ( $coupon ) : ?>
						<p class="result"><?php echo esc_html( $message ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		<?php
	}
}
add_action( 'woocommerce_cart_totals_before_order_total', 'km_add_cart_totals_after_order_total', 20 );

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
		echo '<small class="cart-item-meta">⚠ Les palettes de parpaings sont consignées au prix de 28,80 € TTC la palette. Nous vous invitons à retourner la ou les palettes dans nos locaux, nous vous rembourserons 20,40 € TTC par palette. ⚠</small>';
	}
}
add_action( 'woocommerce_after_cart_item_name', 'km_add_pallet_description_under_product_name', 10, 2 );

/**
 * Ajoute les métadonnées de la palette sur la page produit
 *
 * @return void
 */
function km_palett_product_meta_in_cart() {
	global $product;

	// Obtenir l'ID du produit.
	$product_id = $product->get_id();

	// Récupérer les valeurs des métadonnées.
	$quantite_par_palette = get_post_meta( $product_id, '_quantite_par_palette', true ) ?: 'Non renseigné';
	$palette_a_partir_de  = get_post_meta( $product_id, '_palette_a_partir_de', true ) ?: 'Non renseigné';

	// Afficher les métadonnées sur la page produit.
	echo '<div class="product-palett-meta"><h4>DEBUG</h4>'
	. '<p>Quantité par palette : ' . esc_html( $quantite_par_palette ) . '</p>'
	. '<p>Palette à partir de : ' . esc_html( $palette_a_partir_de ) . '</p>'
	. '</div>';
}
// Ajouter l'action au résumé du produit WooCommerce.
add_action( 'woocommerce_after_add_to_cart_form', 'km_palett_product_meta_in_cart' );

