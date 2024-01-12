<?php
/**
 * @package HelloElementor
 * @subpackage Kingmateriaux
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div id="add-to-cart-confirmation-modal" class="km-modal">
	<div class="km-modal-dialog" role="document">
		<?php if ( 'collection' === $big_bag_type ) : ?>
			<h3><?php esc_html_e( 'Enlèvement Big Bag' ); ?></h3>
			<p><?php esc_html_e( 'Pour récupérer le(s) big bag(s), le camion aura besoin d\'une largeur de rue de 4m et le camion pourra se positionner à 5m maximum du bord de la route.' ); ?> 
			</p>
			<?php else : ?>
			<h3><?php esc_html_e( 'Livraison/enlèvement Big Bag' ); ?></h3>
			<p><?php esc_html_e( 'Pour livrer et récupérer le(s) big bag(s), le camion aura besoin d\'une largeur de rue de 4m et le camion pourra se positionner à 5m maximum du bord de la route.' ); ?> 
			<?php endif; ?>
			</p>
		<p><?php esc_html_e( 'En cliquant sur confirmer, vous accepter nos conditions d\'enlèvement.' ); ?>
		</p>
		<div class="km-form-fields">
			<div class="modal-actions inline">
					<button class="btn-cancel btn btn-secondary"><?php esc_html_e( 'Annuler', 'kingmateriaux' ); ?></button>
				<button class="btn-confirm btn btn-primary" data-action="big_bag_user_accept">
					<span class="btn-confirm-label"><?php esc_html_e( 'Confirmer', 'kingmateriaux' ); ?></span>
					<span class="btn-confirm-loader"></span>
				</button>
			</div>
		</div>
	</div>
</div>
