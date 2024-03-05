<?php
/**
 * Use this file for all your template filters and actions.
 * Requires PDF Invoices & Packing Slips for WooCommerce 1.4.13 or higher
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Add the eco-tax to the invoice
 *
 * @param $order
 * @param $ecotaxe_qty
 * @return array
 */
function km_get_invoice_subtotal_data( $order, $ecotaxe_qty ) {

	if ( ! $order instanceof WC_Order ) {
		$order = wc_get_order( $order );
	}

	$total_ecotax_ht = km_get_ecotaxe_rate() * $ecotaxe_qty;

	$order_data = $order->get_data();

	$subtotal_ht    = 0;
	$shipping_total = 0;

	$total_ecotax_ht  = km_get_ecotaxe_rate() * $ecotaxe_qty;
	$total_ecotax_ttc = km_get_ecotaxe_rate( true ) * $ecotaxe_qty;

	foreach ( $order_data['line_items'] as $item ) {
		$subtotal_ht += (float) $item['line_subtotal'];
	}

	foreach ( $order_data['shipping_lines'] as $shipping ) {
		$shipping_total += (float) $shipping['total'];
	}

	$discount_ht = isset( $order_data['discount_total'] ) ? (float) $order_data['discount_total'] : 0;

	$total_ht  = wc_price( $subtotal_ht + $shipping_total - $discount_ht );
	$total_ttc = wc_price( $order_data['total'] );

	if ( $total_ecotax_ht > 0 ) {
		$total_ht .= '<br>(dont ' . wc_price( $total_ecotax_ht ) . 'd\'éco-taxe)';
	}
	if ( $total_ecotax_ttc > 0 ) {
		$total_ttc .= '<br>(dont ' . wc_price( $total_ecotax_ttc ) . 'd\'éco-taxe)';
	}

	return array(
		'subtotal_ht'       => $subtotal_ht,
		'shipping_ht'       => $shipping_total,
		'total_ht'          => $total_ht,
		'total_ecotax_ht'   => $total_ecotax_ht,
		'total_taxes'       => $order_data['total_tax'],
		'discount_total_ht' => $discount_ht,
		'total_ttc'         => $total_ttc,
	);
}
