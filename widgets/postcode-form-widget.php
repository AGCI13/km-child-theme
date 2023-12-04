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

		wp_enqueue_style( 'km-postcode-form-style' );
		wp_enqueue_script( 'km-postcode-form-script' );

		$shipping_zone = KM_Shipping_Zone::get_instance();

		$zip_code         = $shipping_zone->zip_code ?: '';
		$shipping_zone_id = $shipping_zone->shipping_zone_id ?: '';
		?>
		<div class="header_postcode">
				<?php if ( $zip_code && $shipping_zone_id ) : ?>
				<p><?php esc_html_e( 'Code postal', 'kingmateriaux' ); ?> : <span class="yellow modal_pc_open_btn"><?php echo esc_html( $zip_code ); ?></span></p>
				<?php else : ?>
				<p><?php esc_html_e( 'Pour voir nos tarifs', 'kingmateriaux' ); ?> : <span class="yellow modal_pc_open_btn"><?php esc_html_e( 'Rentrez votre code postal', 'kingmateriaux' ); ?></span></p>
			<?php endif; ?>
		</div>

		<div class="km-modal modal-postcode <?php echo esc_html( $zip_code ); ?> <?php $shipping_zone_id ? 'active' : ''; ?>">
			<div class="km-modal-dialog" role="document">
				<form class="form-postcode" method="POST"> 
					<img class="modal-postcode-close km-modal-close" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/cross.svg' ); ?>" alt="close modal"></span>
					<p>
					<?php
						printf(
							esc_html__(
								'Pour voir les tarifs de nos produits, renseignez votre %1$s code postal à 5 chiffres. %2$s',
								'kingmateriaux'
							),
							'<span class="yellow">',
							'</span>'
						);
					?>
					</p>
					<div class="km-form-fields">
						<input class="country" name="country" type="hidden" value="FR">
						<input class="zip_code" name="zip_code" type="text" maxlength="5" placeholder="Code postal" required>
						<span for="zip_code" class="zip_code_label" class="km-error"></span>
						<?php wp_nonce_field( 'postcode_submission_handler', 'nonce_postcode' ); ?>
						<div class="modal-actions inline">
							<button class="btn-confirm btn btn-primary">
								<span class="btn-confirm-label"><?php esc_html_e( 'Valider', 'kingmateriaux' ); ?></span>
								<span class="btn-confirm-loader"></span>
							</button>
						</div>
					</div>
				</form>
			</div>
		</div>


		<?php
	}
}
