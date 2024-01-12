<?php
class Postcode_Form_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'km_postcode_form';
	}

	public function get_title() {
		return esc_html__( 'KM Formulaire Code Postal', 'elementor-addon' );
	}

	public function get_icon() {
		return 'eicon-map-pin';
	}

	public function get_categories() {
		return array( 'kingmateriaux' );
	}

	protected function render() {
		$shipping_zone    = KM_Shipping_Zone::get_instance();
		$zip_code         = $shipping_zone->zip_code ?: '';
		$shipping_zone_id = $shipping_zone->shipping_zone_id ?: '';
		?>
		<div class="header_postcode">
			<?php if ( $zip_code && $shipping_zone_id ) : ?>
				<p><?php esc_html_e( 'Code postal', 'kingmateriaux' ); ?> : <span class="btn-link modal_pc_open_btn"><?php echo esc_html( $zip_code ); ?></span></p>
				<?php else : ?>
				<p><?php esc_html_e( 'Pour voir nos tarifs', 'kingmateriaux' ); ?> : <span class="btn-link modal_pc_open_btn"><?php esc_html_e( 'Rentrez votre code postal', 'kingmateriaux' ); ?></span></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
