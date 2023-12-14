<?php
/**
 * @package HelloElementor
 * @subpackage HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div id="km-shipping-delay-wrapper" class="wrap woocommerce">
		<h3><?php esc_html_e( 'Délais de livraison', 'kingmateriaux' ); ?></h3>
		<p><?php esc_html_e( 'Laissez vide un des deux champs pour n\'affichez qun\'un seul nombre de jour.', 'kingmateriaux' ); ?></p>
		<form id="km-shipping-zone-settings" method="POST">
		<table class="form-table wc-shipping-zone-settings">
				<tbody>
				<tr valign="top">
					<th scope="row" class="titledesc"><?php esc_html_e( 'De Mars à Août', 'kingmateriaux' ); ?></th>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc"><?php esc_html_e( 'Délais de livraison min', 'kingmateriaux' ); ?> :</th>
					<td class="forminp">
						<input type="text" data-attribute="min_shipping_days_hs" id="min_shipping_days_hs" name="min_shipping_days_hs" value="<?php echo esc_attr( $min_shipping_days_hs ); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc"><?php esc_html_e( 'Délais de livraison max', 'kingmateriaux' ); ?> :</th>
					<td class="forminp">
						<input type="text" data-attribute="max_shipping_days_hs" id="max_shipping_days_hs" name="max_shipping_days_hs" value="<?php echo esc_attr( $max_shipping_days_hs ); ?>" />
					</td>
				</tr>
				</table>
				<table class="form-table wc-shipping-zone-settings">
				<tr valign="top">
					<th scope="row" class="titledesc"><?php esc_html_e( 'De Septembre à Février', 'kingmateriaux' ); ?></th>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<?php esc_html_e( 'Délais de livraison min', 'kingmateriaux' ); ?> :
					</th>
					<td class="forminp">
						<input type="text" data-attribute="min_shipping_days_ls" id="min_shipping_days_ls" name="min_shipping_days_ls" value="<?php echo esc_attr( $min_shipping_days_ls ); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc"><?php esc_html_e( 'Délais de livraison max', 'kingmateriaux' ); ?> :</th>
					<td class="forminp">
						<input type="text" data-attribute="max_shipping_days_ls" id="max_shipping_days_ls" name="max_shipping_days_ls" value="<?php echo esc_attr( $max_shipping_days_ls ); ?>" />
					</td>
				</tr>
				</tbody>
			</table>

			<input type="hidden"  id="km-zone-id" name="zone_id" value="<?php echo esc_attr( $zone_id ); ?>" />

			<?php wp_nonce_field( 'save_shipping_delays_handler', 'km_save_shipping_delay_nonce' ); ?>
            
			<div class="km-shipping-delay-actions">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer les délais de livraison', 'kingmateriaux' ); ?>	
				</button>
			<div class="spinner"></div>
	</div>
</div>
