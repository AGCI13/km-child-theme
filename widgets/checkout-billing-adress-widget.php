<?php
class Checkout_Billing_Adress_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'km_checkout_billing_adress';
	}

	public function get_title() {
		return esc_html__( 'KM Adresse facturation checkout', 'elementor-addon' );
	}

	public function get_icon() {
		return 'eicon-layout-settings';
	}

	public function get_categories() {
		return array( 'kingmateriaux' );
	}

	protected function render() {

		$checkout        = WC()->checkout();
		$shipping_fields = $checkout->get_checkout_fields( 'shipping' );
		$billing_fields  = $checkout->get_checkout_fields( 'billing' );
		do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	
		<?php
		foreach ( $billing_fields as $key => $field ) {
			if ( 'billing_phone' === $key || 'billing_email' === $key ) {
				woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
			}
		}
		?>


		<!-- Start Shipping adress -->
		<div class="shipping_address">
			<div class="elementor-element elementor-widget h2_grey_back">
				<h2><?php esc_html_e( 'Adresse de livraison', 'kingmateriaux' ); ?></h2>
			</div>
			<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

				<div class="woocommerce-shipping-fields__field-wrapper">
				<?php

				foreach ( $shipping_fields as $key => $field ) {
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				?>
				</div>
			<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>
		</div>
		<!-- End Shipping adress -->

		<div class="woocommerce-billing-fields__field-wrapper">
			<div class="elementor-element elementor-widget h2_grey_back">
				<h2><?php esc_html_e( 'Adresse de facturation', 'kingmateriaux' ); ?></h2>
			</div>

			<div class="woocommerce-billing-actions">
				<h4><?php esc_html_e( 'Utiliser l’adresse de livraison comme adresse de facturation ?', 'kingmateriaux' ); ?></h4>
				<span class="bool-action true selected">
						<?php esc_html_e( 'Oui', 'kingmateriaux' ); ?>	
				</span>
				<span class="bool-action false">
					<?php esc_html_e( 'Non', 'kingmateriaux' ); ?>	
				</span>
				<input type="hidden" class="woocommerce-form__input woocommerce-form__input-checkbox" name="different_billing_address" id="different_billing_address" value="true"/> 
			</div>

			<div class="woocommerce-billing-fields">
				<?php
				foreach ( $billing_fields as $key => $field ) {
					if ( 'billing_phone' === $key || 'billing_email' === $key ) {
						continue;
					}
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				?>
				</div>
			</div>

		<?php
		do_action( 'woocommerce_after_checkout_billing_form', $checkout );
	}
}
