<?php

/**
 * Customer processing order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-processing-order.php.
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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$customer_first_name = $order->get_billing_first_name();

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<h1 class="email-heading"><?php printf( esc_html( $email_heading ) . ' <span class="highlighted">%s</span>', esc_html( $customer_first_name ) ); ?></h1>

<img id="km-order-steps" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/wc-order-steps-confirmed.jpg' ); ?>" alt="order step" />

<p><?php printf( __( 'Bonjour <span class="highlighted"><strong>%s</strong></span>,', 'woocommerce' ), esc_html( $customer_first_name ) ); ?></p>

<p><?php printf( __( 'Merci d’avoir passé commande sur %s et de nous faire confiance pour l’aménagement de votre extérieur/décoration !', 'woocommerce' ), '<a href="' . get_home_url() . '">kingmateriaux.com</a>' ); ?></p>

<h2><?php esc_html_e( 'Vos informations de livraison', 'woocommerce' ); ?></h2>

<?php if ( $order && $order->get_billing_address_1() && $order->get_shipping_method() && $order->get_date_created() ) : ?>
	<div class="box">
		<table>
			<?php if ( strpos( strtolower( $order->get_shipping_method() ), 'drive' ) === false ) : ?>
				<tr>
					<td>
						<strong><?php esc_html_e( 'Mode de livraison :', 'woocommerce' ); ?></strong> <?php echo $order->get_shipping_method(); ?>
					</td>
				</tr>
			<?php endif; ?>
			<tr>
				<td>
					<?php if ( strpos( strtolower( $order->get_shipping_method() ), 'drive' ) !== false ) : ?>
						<strong><?php esc_html_e( 'Récupération de la commande au Drive le', 'woocommerce' ); ?></strong> <?php echo get_post_meta( $order->get_id(), '_drive_date', true ); ?> à <?php echo get_post_meta( $order->get_id(), '_drive_time', true ); ?>
					<?php else : ?>
						<strong><?php esc_html_e( 'Livraison :', 'woocommerce' ); ?></strong> à partir du <?php echo wc_format_datetime( $order->get_date_created(), 'd F' ); ?>
					<?php endif; ?>
				</td>
			</tr>
			<?php if ( strpos( strtolower( $order->get_shipping_method() ), 'drive' ) === false ) : ?>
				<tr>
					<td>
						<strong><?php esc_html_e( 'Adr. de livraison :', 'woocommerce' ); ?></strong> <?php echo $order->get_billing_address_1(); ?>, <?php echo $order->get_billing_city(); ?> <?php echo $order->get_billing_postcode(); ?>
					</td>
				</tr>
			<?php endif; ?>
		</table>
	</div>
	<?php
endif;

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
