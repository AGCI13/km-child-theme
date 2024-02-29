<?php

/**
 * Order details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-order-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;

$text_align     = is_rtl() ? 'right' : 'left';
$payment_method = $order->get_payment_method_title();
do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

<h2><?php echo __( 'Récapitulatif de votre commande', 'woocommerce' ); ?></h2>

<h3><?php echo __( 'Détails de la commande', 'woocommerce' ); ?></h3>

<table>
	<tr>
		<td>
			<?php
			if ( $sent_to_admin ) {
				$before = '<a class="link" href="' . esc_url( $order->get_edit_order_url() ) . '">';
				$after  = '</a>';
			} else {
				$before = '';
				$after  = '';
			}
			echo wp_kses_post( $before . sprintf( __( 'N° de commande : #%s', 'woocommerce' ) . $after, $order->get_order_number() ) );
			?>
		<td>
	</tr>
	<tr>
		<td>
			<?php echo wp_kses_post( $before . sprintf( __( 'Passée le : <time datetime="%1$s">%2$s</time>', 'woocommerce' ), $order->get_date_created()->format( 'c' ), wc_format_datetime( $order->get_date_created() ) ) ); ?>
		<td>
	</tr>
	<tr>
		<td>
			<?php if ( strpos( strtolower( $order->get_shipping_method() ), 'drive' ) !== false ) : ?>
					<strong><?php esc_html_e( 'Récupération de la commande au Drive le', 'woocommerce' ); ?></strong> <?php echo esc_html( get_post_meta( $order->get_id(), '_drive_date', true ) ); ?> à <?php echo esc_html( get_post_meta( $order->get_order_number(), '_drive_time', true ) ); ?>
				<?php else : ?>
					<strong><?php echo esc_html( get_post_meta( $order->get_order_number(), '_shipping_dates', true ) ); ?></strong>
				<?php endif; ?>
		</td>
	</tr>
</table>

<h3><?php echo __( 'Vos produits :', 'woocommerce' ); ?></h3>

<div style="margin-bottom: 40px;">
	<table cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
		<tbody>
			<?php
			echo wc_get_email_order_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$order,
				array(
					'show_sku'      => $sent_to_admin,
					'show_image'    => false,
					'image_size'    => array( 120, 120 ),
					'plain_text'    => $plain_text,
					'sent_to_admin' => $sent_to_admin,
				)
			);
			?>
		</tbody>
	</table>

	<?php if ( $payment_method ) : ?>
		<h3><?php echo __( 'Mode de paiement :', 'woocommerce' ); ?></h3>
		<table>
			<tr>
				<td>
					<p><?php echo $payment_method; ?></p>
				</td>
			</tr>
		</table>
		<?php
	endif;

	do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
	?>
	<div id="km-totals">

		<table>
			<?php
			$item_totals = $order->get_order_item_totals();

			if ( $item_totals ) {
				$i = 0;
				foreach ( $item_totals as $total ) {
					++$i;
					?>
					<tr>
						<th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post( $total['label'] ); ?></th>
						<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post( $total['value'] ); ?></td>
					</tr>
					<?php
				}
			}
			if ( $order->get_customer_note() ) {
				?>
				<tr>
					<th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Note:', 'woocommerce' ); ?></th>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></td>
				</tr>
				<?php
			}
			?>
		</table>
	</div>
</div>
<a id="km-cta" href="<?php echo $order->get_view_order_url(); ?>"><?php echo __( 'Voir ma commande', 'woocommmerce' ); ?></a>

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>
