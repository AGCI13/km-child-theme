<?php

/**
 * Customer invoice email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-invoice.php.
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

if (!defined('ABSPATH')) {
	exit;
}

$invoice = wcpdf_get_document('invoice', $order);
if ($invoice && $invoice->exists()) {
	$invoice_number = 'N°' . $invoice->get_number(); // this retrieves the number object with all the data related to the number
}

/**
 * Executes the e-mail header.
 *
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email); ?>

<?php if ($order->needs_payment()) { ?>
	<p>
		<?php
		printf(
			wp_kses(
				/* translators: %1$s Site title, %2$s Order pay link */
				__('An order has been created for you on %1$s. Your invoice is below, with a link to make payment when you’re ready: %2$s', 'woocommerce'),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			),
			esc_html(get_bloginfo('name', 'display')),
			'<a href="' . esc_url($order->get_checkout_payment_url()) . '">' . esc_html__('Pay for this order', 'woocommerce') . '</a>'
		);
		?>
	</p>

<?php } else { ?>
	<h1 class="email-heading"><?php printf(__('Votre facture %s', 'woocommerce'), esc_html($invoice_number)); ?></h1>

	<p><?php printf(__('Bonjour <span class="highlighted"><strong>%s</strong></span>,', 'woocommerce'), esc_html($order->get_billing_first_name())); ?></p>

	<p><?php echo __('Veuillez trouver ci-joint la facture de votre commande.', 'woocommerce'); ?></p>

	<p style="margin-bottom:30px;"><?php echo __('<b>À savoir :</b> en matière de vente à distance vous avez le droit à un délai légal de rétractation de 14 jours sans avoir à justifier de motifs, ni à payer de pénalités, à l’exception des frais de retour. Pour savoir comment exercer votre droit de rétractation, consultez les <a href="https://kingmateriaux.com/conditions-generales-de-vente">conditions générales de vente - Article 11</a>', 'woocommerce'); ?></p>

	<?php if ($order && $order->get_billing_address_1() && $order->get_shipping_method() && $order->get_date_created()) : ?>
		<div class="box">
			<table>
				<tr>
					<td>
						<strong><?php echo __('Livraison :', 'woocommerce'); ?></strong> à partir du <?php echo wc_format_datetime($order->get_date_created(), 'd F'); ?>
					<td>
				</tr>
				<tr>
					<td>
						<strong><?php echo __('Mode de livraison :', 'woocommerce'); ?></strong> <?php echo $order->get_shipping_method(); ?>
					<td>
				</tr>
				<tr>
					<td>
						<strong><?php echo __('Adr. de livraison :', 'woocommerce'); ?></strong> <?php echo $order->get_billing_address_1(); ?>, <?php echo $order->get_billing_city(); ?> <?php echo $order->get_billing_postcode(); ?>
					<td>
				</tr>
			</table>
		</div>
	<?php endif; ?>

<?php
}

/**
 * Hook for the woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

/**
 * Hook for the woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

/**
 * Hook for woocommerce_email_customer_details.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ($additional_content) {
	echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

/**
 * Executes the email footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
