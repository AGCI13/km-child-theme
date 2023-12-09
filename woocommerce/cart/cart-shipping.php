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
 * @version 7.3.0
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
$km_shipping_zone   = KM_Shipping_zone::get_instance();
$checkout           = WC()->checkout();
$chosen_method_data = get_option( 'woocommerce_' . $chosen_method . '_settings' );
?>
<tr class="woocommerce-shipping-totals shipping" style="display:table-row">
	<td>
	<?php if ( $available_methods ) : ?>	
		<?php if ( $shipping_methods ) : ?>
			<div id="shipping-method-shipping" class="woocommerce-shipping-methods">
				<div class="km-shipping-header">
					<span class="select-shipping" data-shipping="shipping"></span>
					<h3><?php esc_html_e( 'Livraison à Domicile ou sur Chantier', 'kingmateriaux' ); ?></h3>
					<?php if ( $km_shipping_zone->is_in_thirteen() ) : ?>
						<span class="shipping-cost"><?php esc_html_e( 'Sélectionnez une option', 'kingmateriaux' ); ?></span>
					<?php else : ?>
						<span class="shipping-cost"><?php esc_html_e( 'Inclus', 'kingmateriaux' ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( preg_match( "/\b{'option'}\b/i", $chosen_method ) !== false ) : ?>
					<div class="km-shipping-options">
						<?php foreach ( $shipping_methods as $method ) : ?>
							<div class="km-shipping-option  <?php echo $chosen_method === $method->id || count( $shipping_methods ) === 1 ? 'selected' : ''; ?>">
									<span class="select-shipping-option" data-shipping="shipping-option"></span>
									<div class="km-shipping-option-content">
										<?php
										if ( 1 < count( $shipping_methods ) ) {
											printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', $index, esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ), checked( $method->id, $chosen_method, false ) ); // WPCS: XSS ok.
										} else {
											printf( '<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" />', $index, esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ) ); // WPCS: XSS ok.
										}
										printf( '<label for="shipping_method_%1$s_%2$s">%3$s</label>', $index, esc_attr( sanitize_title( $method->id ) ), wc_cart_totals_shipping_method_label( $method ) ); // WPCS: XSS ok.
										?>
										<br>
										<?php
										echo esc_html( get_option( 'woocommerce_' . esc_attr( sanitize_title( $method->id ) ) . '_settings' )['description'] );
										do_action( 'woocommerce_after_shipping_rate', $method, $index );
										?>
								</div>
							</div>
						<?php endforeach; ?>

						<!-- Start Shipping Conditions -->					
								<?php if ( ! empty( $chosen_method_data['unload_condition'] ) || ! empty( $chosen_method_data['access_condition'] ) ) : ?>
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
								<?php endif; ?>
						<!-- End Shipping Conditions -->
						
					</div>
					<?php endif; ?>	
			</div>
		<?php endif; ?>	

			<?php if ( $drive_methods ) : ?>
				<div id="shipping-method-drive" class="woocommerce-shipping-methods">
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
						<div class="drive-datetimepicker">
							<h3><?php esc_html_e( 'Sélectionnez une date*', 'kingmateriaux' ); ?></h3>
							<div class="drive-datepicker-day">	
								<ul class="day-list">
									<?php echo km_get_drive_available_days(); ?>
								</ul>
								<div class="load-more-days modal-actions inline">
									<button class="btn-confirm btn btn-secondary">
										<span class="btn-confirm-label">
											<?php esc_html_e( '+ de jours', 'kingmateriaux' ); ?>
										</span>
										<span class="btn-confirm-loader"></span>
									</button>
								</div>
							</div>

							<h3><?php esc_html_e( 'Sélectionnez un créneau horaire*', 'kingmateriaux' ); ?></h3>
							<div class="drive-datepicker-time shopengine_woocommerce_shipping_methods">
							<!-- Morning Slots -->
								<div class="time-slot morning">
								<h4>Matin</h4>
								<div class="slots">
									<div class="slot" data-time="07h00">07h00</div>
									<div class="slot" data-time="07h30">07h30</div>
									<div class="slot" data-time="08h00">08h00</div>
									<div class="slot" data-time="08h30">08h30</div>
									<div class="slot" data-time="09h00">09h00</div>
									<div class="slot" data-time="09h30">09h30</div>
									<div class="slot" data-time="10h00">10h00</div>
									<div class="slot" data-time="10h30">10h30</div>
									<div class="slot" data-time="11h00">11h00</div>
									<div class="slot" data-time="11h30">11h30</div>
								</div>
								</div>
								<!-- Afternoon Slots -->
								<div class="time-slot afternoon">
								<h4>Après-midi</h4>
								<div class="slots">
									<div class="slot" data-time="13h00">13h00</div>
									<div class="slot" data-time="13h30">13h30</div>
									<div class="slot" data-time="14h00">14h00</div>
									<div class="slot" data-time="14h30">14h30</div>
									<div class="slot" data-time="15h00">15h00</div>
									<div class="slot" data-time="15h30">15h30</div>
									<div class="slot" data-time="16h00">16h00</div>
									<div class="slot" data-time="16h30">16h30</div>
									<div class="slot" data-time="17h00">17h00</div>
									<div class="slot" data-time="17h30">17h30</div>
								</div>
								</div>

							<!-- Evening Slot -->
							<div class="time-slot evening">
									<h4>Soir</h4>
									<div class="slots">
										<div class="slot">18h00</div>
									</div>
								</div>
							</div>
							
							<?php if ( $drive_method_settings['location'] ) : ?>
							<div class="drive-location-adress">
								<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/location-pin.svg' ); ?>" alt="King Drive pin">
								<?php echo wp_kses_post( wpautop( $drive_method_settings['location'] ) ); ?>
							</div>
							<?php endif; ?>
							<p id="drive-date-wrapper" class="form-row must-validate validate-required">
								<span class="woocommerce-input-wrapper">
									<input type="hidden" name="drive_date" class="input-text drive_date" value="">
								</span>
							</p>
							<p id="drive-time-wrapper"  class="form-row must-validate validate-required">
								<span class="woocommerce-input-wrapper">
									<input type="hidden" name="drive_time" class="input-text drive_time" value="">
								</span>
							</p>
						</div>
					</div>
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
