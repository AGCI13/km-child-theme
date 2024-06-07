<?php
/**
 * Shipping Methods Display
 *
 * In 2.1 we show methods per package. This allows for multiple methods per order if so desired.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.8.0
 */

defined( 'ABSPATH' ) || exit;

$formatted_destination    = isset( $formatted_destination ) ? $formatted_destination : WC()->countries->get_formatted_address( $package['destination'], ', ' );
$has_calculated_shipping  = ! empty( $has_calculated_shipping );
$show_shipping_calculator = ! empty( $show_shipping_calculator );
$calculator_text          = '';
if ( $available_methods ) {
	foreach ( $available_methods as $method ) {
		if ( 'drive' === $method->id ) {
			$drive_methods[] = $method;
		} else {
			$shipping_methods[] = $method;
		}
	}
}
$checkout          = WC()->checkout();
$shipping_selected = 'selected';
if ( 'drive' === $chosen_method ) {
	$drive_selected    = 'selected';
	$shipping_selected = '';
}
?>
<tr class="woocommerce-shipping-totals shipping" style="display:table-row">
	<td>
	<?php if ( $available_methods ) : ?>	
		<?php if ( $shipping_methods ) : ?>
			<div id="shipping-method-shipping" class="woocommerce-shipping-methods <?php echo esc_html( $shipping_selected ); ?>">	
				<div class="km-shipping-header">
					<span class="select-shipping <?php echo esc_html( $shipping_selected ); ?>" data-shipping="shipping"></span>
					<h3><?php esc_html_e( 'Livraison à Domicile ou sur Chantier', 'kingmateriaux' ); ?></h3>

					<?php
					// check if shipping methods has more than one entry and has a price > 0.
					foreach ( $shipping_methods as $shipping_method ) {
						if ( $shipping_method->get_meta_data()['shipping_price_excl_tax'] > 0 ) {
							$shipping_has_price = true;
						}
					}
					if ( count( $shipping_methods ) > 1 ) :
						?>
						<span class="shipping-cost"><?php esc_html_e( 'Sélectionnez une option', 'kingmateriaux' ); ?></span>
					<?php elseif ( ! $shipping_has_price ) : ?>
						<span class="shipping-cost"><?php esc_html_e( 'Incluse', 'kingmateriaux' ); ?></span>
					<?php endif; ?>
				</div>
					<div class="km-shipping-options">
						<?php foreach ( $shipping_methods as $method ) : ?>

							<div class="km-shipping-option  <?php echo $chosen_method === $method->id ? 'selected' : ''; ?>" 
							data-shipping-sku="<?php echo esc_attr( $method->get_meta_data()['shipping_ugs'] ); ?>"
							data-shipping-price="<?php echo esc_attr( $method->get_meta_data()['shipping_price_excl_tax'] ); ?>"
							data-shipping-tax="<?php echo esc_attr( $method->get_meta_data()['shipping_tax'] ); ?>">

									<span class="select-shipping-option" data-shipping="shipping-option"></span>
									<div class="km-shipping-option-content">
										<?php
										printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', $index, esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ), checked( $method->id, $chosen_method, false ) ); // WPCS: XSS ok.
										printf( '<label for="shipping_method_%1$s_%2$s">%3$s</label>', $index, esc_attr( sanitize_title( $method->id ) ), wc_cart_totals_shipping_method_label( $method ) ); // WPCS: XSS ok.
										?>
										<br>
										<?php
										echo esc_html( $method->get_meta_data()['description'] );
										do_action( 'woocommerce_after_shipping_rate', $method, $index );
										?>
								</div>
							</div>
						<?php endforeach; ?>

						<?php do_action( 'km_after_shipping_rate', $chosen_method ); ?>
					</div>
			</div>
		<?php endif; ?>	

			<?php if ( $drive_methods ) : ?>
				<div id="shipping-method-drive" class="woocommerce-shipping-methods <?php echo esc_html( $drive_selected ); ?>">
				<?php foreach ( $drive_methods as $method ) : ?>
					<?php $drive_method_settings = get_option( 'woocommerce_' . esc_attr( sanitize_title( $method->id ) ) . '_settings' ); ?>
						<div class="km-shipping-header" data-shipping="drive">
							<span class="select-shipping" data-shipping="drive"></span>
							<h3><?php echo esc_html( wc_cart_totals_shipping_method_label( $method ) ); ?> <small class="drive-location"><?php esc_html_e( '(Rognac 13340)', 'kingmateriaux' ); ?></small></h3>
							<span class="shipping-cost"><?php esc_html_e( 'Gratuit', 'kingmateriaux' ); ?></span>
						</div>
							<?php
							printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', $index, esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ), checked( $method->id, $chosen_method, false ) ); // WPCS: XSS ok.
							printf( '<label for="shipping_method_%1$s_%2$s">%3$s</label>', $index, esc_attr( sanitize_title( $method->id ) ), esc_html( wc_cart_totals_shipping_method_label( $method ) ) ); // WPCS: XSS ok.
							?>
				<?php endforeach; ?>
				<?php do_action( 'woocommerce_after_shipping_rate', $method, $index ); ?>
				<?php do_action( 'km_after_drive_method' ); ?>
			<?php endif; ?>

			<?php if ( is_cart() ) : ?>
				<p class="woocommerce-shipping-destination">
					<?php
					if ( $formatted_destination ) {
						// Translators: $s shipping destination.
						printf( esc_html__( 'Shipping to %s.', 'woocommerce' ) . ' ', '<strong>' . esc_html( $formatted_destination ) . '</strong>' );
						$calculator_text = esc_html__( 'Change address', 'woocommerce' );
					} else {
						echo wp_kses_post( apply_filters( 'woocommerce_shipping_estimate_html', __( 'Shipping options will be updated during checkout.', 'woocommerce' ) ) );
					}
					?>
				</p>
			<?php endif; ?>
			<?php
			elseif ( ! $has_calculated_shipping || ! $formatted_destination ) :
				if ( is_cart() && 'no' === get_option( 'woocommerce_enable_shipping_calc' ) ) {
					echo wp_kses_post( apply_filters( 'woocommerce_shipping_not_enabled_on_cart_html', __( 'Shipping costs are calculated during checkout.', 'woocommerce' ) ) );
				} else {
					echo wp_kses_post( apply_filters( 'woocommerce_shipping_may_be_available_html', __( 'Enter your address to view shipping options.', 'woocommerce' ) ) );
				}
		elseif ( ! is_cart() ) :
			echo wp_kses_post( apply_filters( 'woocommerce_no_shipping_available_html', __( 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce' ) ) );
		else :
			echo wp_kses_post(
				/**
				 * Provides a means of overriding the default 'no shipping available' HTML string.
				 *
				 * @since 3.0.0
				 *
				 * @param string $html                  HTML message.
				 * @param string $formatted_destination The formatted shipping destination.
				 */
				apply_filters(
					'woocommerce_cart_no_shipping_available_html',
					// Translators: $s shipping destination.
					sprintf( esc_html__( 'No shipping options were found for %s.', 'woocommerce' ) . ' ', '<strong>' . esc_html( $formatted_destination ) . '</strong>' ),
					$formatted_destination
				)
			);
			$calculator_text = esc_html__( 'Enter a different address', 'woocommerce' );
		endif;
		?>

		<?php if ( $show_package_details ) : ?>
			<?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>'; ?>
		<?php endif; ?>

		<?php if ( $show_shipping_calculator ) : ?>
			<?php woocommerce_shipping_calculator( $calculator_text ); ?>
		<?php endif; ?>
	</td>
</tr>
