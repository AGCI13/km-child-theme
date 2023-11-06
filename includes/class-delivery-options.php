<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles dynamic pricing based on shipping zones and classes in WooCommerce.
 */

class KM_Delivery_Options {

    use SingletonTrait;

    // Le constructeur est privé pour empêcher l'instanciation directe.
    private function __construct() {
        $this->register();
    }

    public function register() {
        add_action( 'woocommerce_review_order_before_order_total', array( $this, 'display_delivery_options_on_checkout' ) );
    }

    public function display_delivery_options_on_checkout() {
        $options_livraison = array(
            'option1'         => 'Option 1',
            'option1_express' => 'Option 1 EXPRESS',
            'option2'         => 'Option 2',
            'option2_express' => 'Option 2 EXPRESS',
        );

        // Récupérer l'option de livraison actuellement sélectionnée par l'utilisateur (s'il en a choisi une)
        $option_livraison_selectionnee = WC()->session->get( 'option_livraison' );

        $html = '<div class="options-livraison">';

        foreach ( $options_livraison as $option_value => $option_label ) {
            $is_selected = ( $option_value === $option_livraison_selectionnee ) ? 'selected' : '';
            $html       .= '<div class="option-livraison ' . $is_selected . '">';
            $html       .= '<input type="radio" name="option_livraison" value="' . esc_attr( $option_value ) . '" ' . checked( $option_value, $option_livraison_selectionnee, false ) . '>';
            $html       .= '<label>' . esc_html( $option_label ) . '</label>';
            $html       .= '</div>';
        }

        $html .='</div>';
        echo $html;
    }
}
