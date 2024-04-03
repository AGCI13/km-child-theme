<?php
/**
 * @package HelloElementor
 * @subpackage Kingmateriaux
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="km-modal modal-postcode <?php echo esc_html( $active ); ?>">
			<div class="km-modal-dialog" role="document">
				<form class="form-postcode" method="POST"> 
					<?php
					if ( $shipping_zone_id ) :
						?>
						<img class="modal-postcode-close km-modal-close" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/cross.svg' ); ?>" alt="close modal"></span>
					<?php endif; ?>
					<p>
				<?php
					printf(
						esc_html__(
							'Pour voir les tarifs de nos produits, renseignez votre %1$s code postal de livraison à 5 chiffres. %2$s',
							'kingmateriaux'
						),
						'<span class="btn-link">',
						'</span>'
					);
					?>
					</p>
					<div class="km-form-fields">
						<input class="country" name="country" type="hidden" value="FR">
						<input class="postcode" name="postcode" type="text" maxlength="5" placeholder="Code postal" required>
						<span for="postcode" class="postcode_label" class="km-error"></span>
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
