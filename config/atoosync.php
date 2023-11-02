<?php

function atoosync_add_postcode($order_id)
{
    $order = wc_get_order($order_id);
    $shipping_postcode = $order->get_shipping_postcode();

    if (!empty($shipping_postcode)) {
        add_post_meta(1, '_cookie_cp', $shipping_postcode);
    }
}
add_action('woocommerce_new_order', 'atoosync_add_postcode', 1, 1);

/**
 * @param $item_id
 * @param WC_Order_Item $item
 * @param $order_id
 * @return void
 * @throws Exception
 */
function atoosync_add_order_item_meta($item_id, $item, $order_id)
{
    $order = wc_get_order($order_id);
    $product_id = $item->get_meta('_product_id');
    $product_term = get_the_terms($product_id, 'product_shipping_class');

    if (!$product_term) {
        return;
    }

    $product_ugc = get_ugc_product($product_term);

    if (empty($product_ugc)) {
        return;
    }
    $product_ugc = wc_get_product($product_ugc[0]->ID);
    $product = wc_get_product($product_id);

    wc_add_order_item_meta($item_id, '_ugc_product_shipping', $product_ugc->get_meta('_sku'));

    wc_add_order_item_meta($item_id, '_actual_product_price_excl', $product->get_price());
    wc_add_order_item_meta($item_id, '_actual_product_tax_price', ($product->get_price() * .2));

    //Premier order_item de la commande
    if ($order->get_items()[0]->get_id() == $item_id) {
        wc_add_order_item_meta($item_id, '_shipping_price_product_excl', $product_ugc->get_price());
        wc_add_order_item_meta($item_id, '_tax_prices_on_product_shipping', ($product_ugc->get_price() * .2));
    }
}
add_action('woocommerce_new_order_item', 'atoosync_add_order_item_meta', 10, 3);
