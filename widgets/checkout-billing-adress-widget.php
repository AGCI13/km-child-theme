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

		$checkout = WC()->checkout();

		do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

		<div class="woocommerce-billing-fields__field-wrapper">
		<div class="elementor-element elementor-widget h2_grey_back">
			<h2><?php esc_html_e( 'Adresse de facturation', 'kingmateriaux' ); ?></h2>
		</div>
		<h4><?php esc_html_e( 'Utiliser l’adresse de livraison comme adresse de facturation ?', 'kingmateriaux' ); ?></h4>

		<div class="woocommerce-billing-actions">
			<span class="bool-action true selected">
				<?php esc_html_e( 'Oui', 'kingmateriaux' ); ?>	
			</span>
			<span class="bool-action false">
				<?php esc_html_e( 'Non', 'kingmateriaux' ); ?>	
			</span>
		</div>

		<div class="woocommerce-billing-fields">
			<?php
			$fields = $checkout->get_checkout_fields( 'billing' );

			foreach ( $fields as $key => $field ) {
				woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
			}

			do_action( 'woocommerce_checkout_billing' );
			?>
			</div>
		</div>
		<?php
		do_action( 'woocommerce_after_checkout_billing_form', $checkout );
	}
}
