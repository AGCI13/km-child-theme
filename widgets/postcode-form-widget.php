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
		$show_popup       = $this->shouldShowPopup( $shipping_zone_id ) ? 'active' : '';

		static $debounce = false;
		if ( ! $debounce ) {
			echo $this->generateModalHtml( $zip_code, $show_popup );
		}

		echo $this->generateHeaderHtml( $zip_code, $shipping_zone_id );
		$debounce = true;
	}

	private function shouldShowPopup( $shipping_zone_id ) {
		return ( is_home() || is_front_page() || is_product() || is_product_category() ) && empty( $shipping_zone_id );
	}


	private function generateModalHtml( $zip_code, $show_popup ) {
		ob_start();
		?>
	<div class="km-modal modal-postcode <?php echo esc_html( $zip_code ); ?> <?php echo esc_html( $show_popup ); ?>">
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
			return ob_get_clean();
	}

	private function generateHeaderHtml( $zip_code, $shipping_zone_id ) {
		ob_start();
		?>
		<div class="header_postcode">
			<?php if ( $zip_code && $shipping_zone_id ) : ?>
				<p><?php esc_html_e( 'Code postal', 'kingmateriaux' ); ?> : <span class="yellow modal_pc_open_btn"><?php echo esc_html( $zip_code ); ?></span></p>
				<?php else : ?>
				<p><?php esc_html_e( 'Pour voir nos tarifs', 'kingmateriaux' ); ?> : <span class="yellow modal_pc_open_btn"><?php esc_html_e( 'Rentrez votre code postal', 'kingmateriaux' ); ?></span></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
