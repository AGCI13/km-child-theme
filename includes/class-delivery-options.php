<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles dynamic pricing based on shipping zones and classes in WooCommerce.
 */
class KM_Delivery_Options {

    use SingletonTrait;

    /**
     * The shipping zone instance.
     *
     * @var KM_Shipping_zone|null
     */
    private $km_shipping_zone;

    // Le constructeur est privé pour empêcher l'instanciation directe.
    private function __construct() {
        $this->km_shipping_zone = KM_Shipping_zone::get_instance();
        $this->register();
    }

    public function register() {
        if ( $this->km_shipping_zone->is_in_thirteen() ) {
            add_action( 'woocommerce_checkout_cart_item_quantity', array( $this, 'display_bulk_delivery_options' ), 10, 2 );
            add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_delivery_option_for_bulk_products' ), 10, 1 );
        }
    }

    public function display_bulk_delivery_options( $cart_item, $cart_item_key ) {

        $options_livraison = array(
            'option1'         => 'Option 1',
            'option1_express' => 'Option 1 EXPRESS',
            'option2'         => 'Option 2',
            'option2_express' => 'Option 2 EXPRESS',
        );
        // Récupérer l'option de livraison actuellement sélectionnée par l'utilisateur (s'il en a choisi une)
        $option_livraison_selectionnee = WC()->session->get( 'option_livraison' );

        ob_start(); // Démarre la mise en mémoire tampon

        // Assurez-vous que le chemin d'accès au fichier est correct et que le fichier existe.
        include get_stylesheet_directory() . '/templates/delivery-options.php';

        echo ob_get_clean();
    }

    public function add_delivery_option_for_bulk_products( $cart ) {
        if ( is_admin() && !defined( 'DOING_AJAX' ) ) {
            return;
        }

        // Parcourir les articles du panier
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            $product      = $cart_item['data'];
            $product_name = $product->get_name();

            // Vérifier si le nom du produit contient "VRAC"
            if ( strpos( $product_name, 'VRAC' ) !== false ) {
                $quantity = $cart_item['quantity'];
                // Ajouter une option de livraison basée sur la quantité
                $fee_name   = "Option de livraison pour $product_name";
                $fee_amount = $this->calculate_delivery_option_fee( $quantity ); // Définir une fonction pour calculer les frais
                $cart->add_fee( $fee_name, $fee_amount );
            }
        }
    }

    public function calculate_delivery_option_fee( $quantity ) {
        // Votre logique pour calculer les frais basés sur la quantité
        return 10.00; // Exemple de montant fixe
    }
}
