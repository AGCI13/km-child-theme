<?php
class Delivery_Conditions_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'km_delivery_conditions';
	}

	public function get_title() {
		return esc_html__( 'KM Conditions de livraison', 'elementor-addon' );
	}

	public function get_icon() {
		return 'eicon-check-circle';
	}

	public function get_categories() {
		return array( 'kingmateriaux', 'woocommerce' );
	}

	public function render() {
		?>
		<tr>
			<td colspan="6">
				<h4><?php esc_html_e( 'Pour valider votre mode de livraison, veuillez accepter les :', 'kingmateriaux' ); ?></h4>
				<p class="validate-required">
					<input type="checkbox" name="delivery_access_confirmation" id="delivery-access-confirmation" required>
					<label for="delivery-access-confirmation"><?php esc_html_e( 'Conditions d’accès au chantier', 'kingmateriaux' ); ?><span style="color:red">*</span></label>
				<?php esc_html_e( ' Lorem ipsum dolor sit amet, consectetur adipiscing elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'kingmateriaux' ); ?></p>
				<p class="validate-required">
					<input type="checkbox" name="unloading_access_confirmation" id="unloading-access-confirmation" required>
				<label for="unloading-access-confirmation"><?php esc_html_e( 'Conditions de déchargement', 'kingmateriaux' ); ?>
				<span style="color:red">*</span></label>
				<?php esc_html_e( ' Lorem ipsum dolor sit amet, consectetur adipiscing elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'kingmateriaux' ); ?></p>
			</td>
		</tr>	
		<?php
	}
}
