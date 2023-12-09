<?php
/**
 * View Order
 *
 * Shows the details of a particular order on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/view-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

$notes             = $order->get_customer_order_notes();
$shipping_method   = strtolower( $order->get_shipping_method() );
$drive_method_data = get_option( 'woocommerce_drive_settings' );
$drive_date        = get_post_meta( $order->get_id(), '_drive_date', true );
$drive_time        = get_post_meta( $order->get_id(), '_drive_time', true );
$order = wc_get_order( $order_id );
$payment_method = $order->get_payment_method_title();
$saved_card = get_post_meta( $order->get_id(), '_payment_method_card', true );
$item_count = $order->get_item_count() - $order->get_item_count_refunded();
?>


<?php /*
	<p>
	<?php
	printf(
		/* translators: 1: order number 2: order date 3: order status */

/*

		esc_html__( 'Order #%1$s was placed on %2$s and is currently %3$s.', 'woocommerce' ),
		'<mark class="order-number">' . $order->get_order_number() . '</mark>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'<mark class="order-date">' . wc_format_datetime( $order->get_date_created() ) . '</mark>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'<mark class="order-status">' . wc_get_order_status_name( $order->get_status() ) . '</mark>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	?>
	</p>
*/ ?>

<?php if ( $notes ) : ?>
	<h2><?php esc_html_e( 'Order updates', 'woocommerce' ); ?></h2>
	<ol class="woocommerce-OrderUpdates commentlist notes">
		<?php foreach ( $notes as $note ) : ?>
		<li class="woocommerce-OrderUpdate comment note">
			<div class="woocommerce-OrderUpdate-inner comment_container">
				<div class="woocommerce-OrderUpdate-text comment-text">
					<p class="woocommerce-OrderUpdate-meta meta"><?php echo date_i18n( esc_html__( 'l jS \o\f F Y, h:ia', 'woocommerce' ), strtotime( $note->comment_date ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<div class="woocommerce-OrderUpdate-description description">
						<?php echo wpautop( wptexturize( $note->comment_content ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="clear"></div>
				</div>
				<div class="clear"></div>
			</div>
		</li>
		<?php endforeach; ?>
	</ol>
<?php endif; ?>

<?php //do_action( 'woocommerce_view_order', $order_id ); ?>


<a href="<?php echo esc_url( wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="return-button"><?php esc_html_e( 'Retour', 'woocommerce' ); ?></a>

<div class="order-details-header">
    <div class="order-details-row">
        <span class="order-details-title">n°<?php echo $order->get_order_number(); ?></span>
        <span class="order-details-price"><?php echo wc_price($order->get_total()); ?></span>
        <span class="order-details-date"><?php echo date('d/m/Y', strtotime($order->get_date_created())); ?></span>
        <span class="order-details-status <?php echo esc_attr($order->get_status()); ?>">Statut : <span class="status-<?php echo esc_attr($order->get_status()); ?>"><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></span></span>
        <span class="order-details-items"><?php echo sprintf(_n('%s item', '%s items', $item_count, 'woocommerce'), $item_count); ?></span>
    </div>
</div>

<div class="order-actions">
	<?php
	$actions = wc_get_account_orders_actions($order);
	if (!empty($actions) && isset($actions['invoice'])) {
		echo '<a href="' . esc_url($actions['invoice']['url']) . '" class="download-invoice">Télécharger la facture</a>';
	}
	?>
</div>

<div class="order-status-tracker">
    <?php
    $steps = [
        'completed' => 'Commande validée',
        'processing' => 'En cours de préparation',
        'shipped' => 'Expédiée',
        'delivered' => 'Livrée',
    ];

    foreach ($steps as $status => $label) {
        $date = $order->get_date_completed() && $status == 'completed' ? date_i18n('d/m/Y', strtotime($order->get_date_completed())) : '';
        $date = $order->get_date_paid() && $status == 'processing' ? date_i18n('d/m/Y', strtotime($order->get_date_paid())) : $date;

        $is_completed = $order->has_status($status) || $order->has_status('completed');
        
        $step_class = $is_completed ? 'completed' : '';
        $icon_class = $date ? 'check' : '';
		?>
		<div class="tracker-step <?php echo $step_class; ?>">
			<div class="tracker-text"><?php echo $label; ?><br>
				<?php $status_display = $date ? $date : '<span class="waiting">' . esc_html__('en attente', 'woocommerce') . '</span>'; ?>
				<span class="date"><?php echo $status_display; ?></span>
			</div>
			<div class="tracker-icon <?php echo $icon_class; ?>"></div>
		</div>
		<div class="tracker-line"></div>
    <?php } ?>
</div>

<div class="h2_grey_back"><h2>Produits</h2></div>

<div class="order-products">
    <?php 
    $subtotal = 0;
    foreach( $order->get_items() as $item_id => $item ) :
        $product = $item->get_product();
        $subtotal += $item->get_total();
    ?>
    <div class="order-product">
        <div class="order-product-image">
            <?php echo $product->get_image(); ?>
        </div>
        <div class="order-product-details">
            <span class="order-product-name"><?php echo $item->get_name(); ?></span>
            <span class="order-product-price"><?php echo wc_price($item->get_total()); ?></span>
            <span class="order-product-quantity"><?php echo $item->get_quantity(); ?> T</span>
        </div>
    </div>
    <?php endforeach; ?>

	<div class="order-totals">
		<div class="order-total-row">
			<span class="subtotal-label">Sous-total</span>
			<span class="subtotal-amount"><?php echo wc_price($subtotal); ?></span>
		</div>
		<div class="order-total-row">
			<span class="shipping-label">Frais de livraison</span>
			<span class="shipping-amount"><?php echo $order->get_shipping_total() > 0 ? wc_price($order->get_shipping_total()) : esc_html__('Inclus', 'woocommerce'); ?></span>
		</div>
		<div class="order-total-row">
			<span class="total-label">Total de la commande</span>
			<span class="total-amount"><?php echo wc_price($order->get_total()); ?></span>
		</div>
	</div>
</div>

<div class="h2_grey_back"><h2>Informations de paiement</h2></div>

<?php
echo '<p class="methode-paiement">Paiement sécurisée par ' . esc_html( $payment_method ) . '</p>';
if ( $saved_card ) {
    echo '<p class="moyen-paiement">Numéro de carte : ' . esc_html( $saved_card ) . '</p>';
}
?>
<div class="rassurance-paiement"></div>



<?php
if ( $order ) :	?>

	<?php if ( strpos( $shipping_method, 'drive' ) !== false ) : ?>

		<div class="h2_grey_back">
			<h2><?php esc_html_e( 'Retrait marchandise', 'kingmateriaux' ); ?></h2>
		</div>

		<div class="adresse-livraison">
			<p>Date: <?php esc_html_e( $drive_date ); ?></p>
			<p>Heure:<?php esc_html_e( $drive_time ); ?></p>
			<p><strong>Adresse du Drive :</strong><?php esc_html_e( $drive_method_data['location'] ); ?></p>
			</div>
		<?php else : ?>
		
			<div class="h2_grey_back"><h2><?php esc_html_e( 'Retrait marchandise', 'kingmateriaux' ); ?></h2></div>

		<div class="adresse-livraison">
		<p> <?php echo $order->get_shipping_address_1(); ?></p>
			<?php if ( ! empty( $order->get_shipping_address_2() ) ) : ?>
				<p><?php echo $order->get_shipping_address_2(); ?> </p>
			<?php endif; ?>
		<p><?php echo $order->get_shipping_city() . ', ' . $order->get_shipping_state() . ' ' . $order->get_shipping_postcode(); ?> </p>
		<p><?php echo $order->get_shipping_country(); ?></p>
		</div>';
	<?php endif; ?>
<?php endif; ?>

<div class="h2_grey_back"><h2>Facturation</h2></div>
<?php
if ( $order ) : 
    $billing_address = $order->get_billing_address_1();
    $billing_address_2 = $order->get_billing_address_2();
    $billing_city = $order->get_billing_city();
    $billing_state = $order->get_billing_state();
    $billing_postcode = $order->get_billing_postcode();
    $billing_country = $order->get_billing_country();

    echo "<div class='adresse-facturation'>".$billing_address . "<br>";
    echo $billing_address_2 . "<br>";
    echo $billing_city . ", " . $billing_state . " " . $billing_postcode . "<br>";
    echo $billing_country;
endif; ?>