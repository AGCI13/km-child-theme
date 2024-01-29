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
		<h3><?php esc_html_e( 'Ce produit est palétisé' ); ?></h3>
		<p><?php esc_html_e( 'En plus de votre produit, une ou plusieurs palettes consignée(s) seront ajoutées automatiquement à votre panier.' ); ?></p>
		<p><?php esc_html_e( '(28,80 € TTC la palette, remboursable à hauteur de 20,40 € TTC par palette).' ); ?></p>
		<div class="km-form-fields">
			<div class="modal-actions inline">
				<button class="btn-cancel btn btn-secondary"><?php esc_html_e( 'Annuler', 'kingmateriaux' ); ?></button>
				<button class="btn-confirm btn btn-primary" data-action="pallet_user_accept">
					<span class="btn-confirm-label"><?php esc_html_e( 'Confirmer', 'kingmateriaux' ); ?></span>
					<span class="btn-confirm-loader"></span>
				</button>
				</div>
			</div>
	</div>
</div>
