<?php


function modifier_prix_par_code_postal( $price, $product ) {
    if ( isset( $_COOKIE['zip_code'] ) && isset( $_COOKIE['shipping_zone'] ) ) {
        $code_postal   = substr( $_COOKIE['zip_code'], 0, -3 );
        $shipping_zone = new WC_Shipping_Zone( $_COOKIE['shipping_zone'] );

        if ( !str_starts_with( $code_postal, '13' ) ) {
            $quantity     = max( 1, WC()->cart->get_cart_item_quantities()[ $product->get_id() ] ?? 1 );
            $nouveau_prix = floatval( $price ) + 5 * $quantity;
            return $nouveau_prix;
        }
    }
    return $price;
}
//add_filter('woocommerce_product_get_price', 'modifier_prix_par_code_postal', 10, 2);

function ajouter_frais_livraison_panier(): void {
    if ( isset( $_COOKIE['zip_code'] ) && isset( $_COOKIE['shipping_zone'] ) ) {
        $code_postal   = substr( $_COOKIE['zip_code'], 0, -3 );
        $shipping_zone = new WC_Shipping_Zone( $_COOKIE['shipping_zone'] );

        if ( str_starts_with( $code_postal, '13' ) ) {
            $frais_livraison = 10; // Montant des frais de livraison à ajouter
            WC()->cart->add_fee( 'Frais de livraison', $frais_livraison );
        }
    }
}
//add_action('woocommerce_cart_calculate_fees', 'ajouter_frais_livraison_panier');


function afficher_options_livraison_div_cart_totals(): void {
    $options_livraison = array(
        'option1'         => 'Option 1',
        'option1_express' => 'Option 1 EXPRESS',
        'option2'         => 'Option 2',
        'option2_express' => 'Option 2 EXPRESS',
    );

    // Récupérer l'option de livraison actuellement sélectionnée par l'utilisateur (s'il en a choisi une)
    $option_livraison_selectionnee = WC()->session->get( 'option_livraison' );

    echo '<div class="options-livraison">';

    foreach ( $options_livraison as $option_value => $option_label ) {
        $is_selected = ( $option_value === $option_livraison_selectionnee ) ? 'selected' : '';
        echo '<div class="option-livraison ' . $is_selected . '">';
        echo '<input type="radio" name="option_livraison" value="' . esc_attr( $option_value ) . '" ' . checked( $option_value, $option_livraison_selectionnee, false ) . '>';
        echo '<label>' . esc_html( $option_label ) . '</label>';
        echo '</div>';
    }

    echo '</div>';
    die();
}
//add_action('woocommerce_review_order_before_order_total', 'afficher_options_livraison_div_cart_totals');


function modifier_frais_expedition_selon_produits( $rates, $package ) {

    if ( isset( $_COOKIE['zip_code'] ) && isset( $_COOKIE['shipping_zone'] ) ) {
        $code_postal   = substr( $_COOKIE['zip_code'], 0, -3 );
        $shipping_zone = new WC_Shipping_Zone( $_COOKIE['shipping_zone'] );

        // Parcourir les éléments du panier pour vérifier quels produits sont présents
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = $cart_item['product_id'];
            echo '<pre>';
            var_dump( array_values( $rates )[0] );
            echo '</pre>';
            var_dump( 'flat_rate:' . $shipping_zone->get_id() );
            // Appliquer les modifications de frais d'expédition en fonction du produit et de l'option sélectionnée
            if ( $product_id === 3458 ) {
                // Modifier le coût d'expédition pour le produit avec l'ID VOTRE_ID_DE_PRODUIT et l'option 1
                $rates[ 'flat_rate:' . $shipping_zone ]->cost = 18.00; // Remplacez 10.00 par le coût souhaité
            } elseif ( $product_id === 3459 ) {
                // Modifier le coût d'expédition pour le produit avec l'ID AUTRE_ID_DE_PRODUIT et l'option 2
                $rates['flat_rate']->cost = 15.00; // Remplacez 15.00 par le coût souhaité
            }
            // Ajoutez autant de conditions que nécessaire pour chaque produit et option de livraison
        }
    }
    return $rates;
}
//add_filter('woocommerce_package_rates', 'modifier_frais_expedition_selon_produits', 10, 2);
