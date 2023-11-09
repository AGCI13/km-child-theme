<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 *  --------------- START ECO-TAX ----------------------
 *
 * Ajoute l'éco-taxe aux produits en vrac et aux bigbags
 */
add_action( 'woocommerce_cart_calculate_fees', 'add_ecotaxe_as_fee' );
function add_ecotaxe_as_fee( $cart ) {
    if ( is_admin() && !defined( 'DOING_AJAX' ) ) {
        return;
    }

    $ecotaxe_total        = 0;
    $km_dynamique_pricing = KM_Dynamic_Pricing::get_instance();

    // Calculer le total de l'éco-taxe pour tous les produits éligibles dans le panier
    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
        if ( $km_dynamique_pricing->product_is_bulk_or_bigbag( $cart_item['data'] ) ) {
            $ecotaxe_total += $km_dynamique_pricing->ecotaxe_rate * $cart_item['quantity'];
        }
    }

    // Si l'éco-taxe totale est supérieure à zéro, l'ajouter en tant que frais
    if ( $ecotaxe_total > 0 ) {
        $cart->add_fee( __( 'Écotaxe', 'kingmateriaux' ), $ecotaxe_total, true );
    }
}

// Hook pour afficher la mention de l'éco-taxe sous le prix unitaire
add_filter( 'woocommerce_cart_item_price', 'display_ecotaxe_with_unit_price', 10, 3 );
function display_ecotaxe_with_unit_price( $price_html, $cart_item, $cart_item_key ) {
    $km_dynamique_pricing = KM_Dynamic_Pricing::get_instance();

    if ( $km_dynamique_pricing->product_is_bulk_or_bigbag( $cart_item['data'] ) ) {
        $price_html .= '<br><small class="ecotaxe-amount">' . sprintf( __( 'Dont %s d\'Ecotaxe', 'kingmateriaux' ), wc_price( $km_dynamique_pricing->ecotaxe_rate ) ) . '</small>';
    }
    return $price_html;
}

// // Hook pour afficher la mention de l'éco-taxe sous le sous-total
add_filter( 'woocommerce_cart_item_subtotal', 'display_ecotaxe_with_subtotal', 10, 3 );
function display_ecotaxe_with_subtotal( $subtotal_html, $cart_item, $cart_item_key ) {
    $km_dynamique_pricing = KM_Dynamic_Pricing::get_instance();

    if ( $km_dynamique_pricing->product_is_bulk_or_bigbag( $cart_item['data'] ) ) {
        $ecotaxe_total  = $km_dynamique_pricing->ecotaxe_rate * $cart_item['quantity'];
        $subtotal_html .= '<br><small class="ecotaxe-amount">' . sprintf( __( 'Dont %s d\'Ecotaxe', 'kingmateriaux' ), wc_price( $ecotaxe_total ) ) . '</small>';
    }
    return $subtotal_html;
}

// Ajoute une action pour ajuster les prix des produits dans le panier
add_action( 'woocommerce_before_calculate_totals', 'add_ecotaxe_to_cart_item_prices' );
function add_ecotaxe_to_cart_item_prices( $cart ) {
    if ( is_admin() && !defined( 'DOING_AJAX' ) ) {
        return;
    }

    // S'assurer que cette action n'est exécutée qu'une seule fois
    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
        return;
    }

    $km_dynamique_pricing = KM_Dynamic_Pricing::get_instance();

    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
        if ( $km_dynamique_pricing->product_is_bulk_or_bigbag( $cart_item['data'] ) ) {
            // Ajouter l'éco-taxe au prix de l'article dans le panier
            $original_price = $cart_item['data']->get_price();
            $new_price      = $original_price + $km_dynamique_pricing->ecotaxe_rate;
            $cart_item['data']->set_price( $new_price );
        }
    }
}

/**
 *  --------------- END ECO-TAX ----------------------
 */

