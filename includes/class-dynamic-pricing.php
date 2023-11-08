<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles dynamic pricing based on shipping zones and classes in WooCommerce.
 */

class KM_Dynamic_Pricing {

    /**
     * The single instance of the class.
     *
     * @var KM_Dynamic_Pricing|null
     */

    use SingletonTrait;

    /**
     *  The shipping zone instance.
     *
     *  @var KM_Shipping_zone|null
     */
    public $km_shipping_zone;

    /**
     * The message to display when the product is not available in the shipping zone.
     *
     * @var string
     */
    public $unavailable_message;

    /**
     * Constructor.
     *
     * The constructor is protected to prevent creating a new instance from outside
     * and to prevent creating multiple instances through the `new` keyword.
     */
    private function __construct() {
        $this->km_shipping_zone    = KM_Shipping_zone::get_instance();
        $this->unavailable_message = __( 'Ce produit n\'est pas disponible dans votre zone de livraison', 'kingmateriaux' );
        $this->register();
    }

    private function register() {

        add_action( 'wp', array( $this, 'set_prices_on_zip_or_zone_missing' ) );

        // Si la zone de livraison est la 13, on modifie les prix des produits
        if ( !$this->km_shipping_zone->is_in_thirteen() ) {
            // Hook pour le produit simple
            add_filter( 'woocommerce_product_get_price', array( $this, 'change_product_price_based_on_shipping_zone' ), 1, 2 );
            // Hook pour les variations de produit
            add_filter( 'woocommerce_product_variation_get_price', array( $this, 'change_product_price_based_on_shipping_zone' ), 1, 2 );
            // Hook pour les prices des variations de produit (notamment min et max)
            add_filter( 'woocommerce_variation_prices', array( $this, 'change_variation_prices_based_on_shipping_zone' ), 1, 3 );

            add_filter( 'woocommerce_get_price_html', array( $this, 'no_shipping_class_price_html' ), 99, 2 );

            add_filter( 'woocommerce_is_purchasable', array( $this, 'no_shipping_class_is_purchasable' ), 10, 2 );

            //Décommenter la ligne ci dessous afin de masquer les produits lorsqu'ils n'ont pas de classe de livraison
            // add_action( 'pre_get_posts', array( $this, 'hide_products_out_thirteen' ), 10, 1 );
        }
    }

    public function set_prices_on_zip_or_zone_missing() {
        if ( $this->km_shipping_zone->zip_code && $this->km_shipping_zone->shipping_zone_id ) {
            return;
        }
        add_filter( 'woocommerce_is_purchasable', '__return_false' );
        add_filter( 'woocommerce_get_price_html', array( $this, 'display_message_instead_on_price' ), 10, 2 );
    }

    /**
     * Obtient le prix d'un produit par son titre.
     *
     * @param string $product_title Le titre du produit.
     * @return float|null Le prix du produit ou null si le produit n'est pas trouvé.
     */
    public function get_price_by_product_title( $product_title ) {

        // Arguments pour la requête
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 1,
            'post_status'    => array( 'private' ),
            'name'           => sanitize_title( $product_title ),
        );

        // Effectuer la requête
        $products = get_posts( $args );

        // Si un produit est trouvé, retourner son prix
        if ( !empty( $products ) ) {
            $product = wc_get_product( $products[0]->ID );
            return $product->get_price();
        }

        return null;
    }

    public function change_product_price_based_on_shipping_zone( $price, $product ) {

        $shipping_product_title = $this->km_shipping_zone->get_related_shipping_product_title( $product );
        $shipping_product       = $this->km_shipping_zone->get_related_shipping_product_by_title( $shipping_product_title );

        $shipping_price = $shipping_product->get_price();

        if ( $shipping_price > 0 ) {
            $price += $shipping_price;
        }

        return $price;
    }


    /**
     * Change les prix des variations de produit en fonction de la zone de livraison.
     * 
     * @param array $prices Les prix des variations de produit.
     *  
     */
    public function change_variation_prices_based_on_shipping_zone( $prices, $variation, $product ) {
        foreach ( $prices as $price_type => $variation_prices ) {
            foreach ( $variation_prices as $variation_id => $price ) {
                $variation_prices[ $variation_id ] = $this->change_product_price_based_on_shipping_zone( $price, wc_get_product( $variation_id ) );
            }
            $prices[ $price_type ] = $variation_prices;
        }
        return $prices;
    }

    public function no_shipping_class_price_html( $price, $product ) {

        if ( $product->is_type( 'variable' ) ) {
            $variations = $product->get_available_variations();
            foreach ( $variations as $variation ) {
                $variation_obj = wc_get_product( $variation['variation_id'] );
                if ( $variation_obj->get_shipping_class_id() ) {
                    return $price; // Si au moins une variation a une classe de livraison, retourner le prix.
                }
            }
            // Si aucune variation n'a de classe de livraison, afficher le message.
            return $this->unavailable_message;
        } else {
            // Pour les produits non variables, continuez avec la logique existante.
            if ( !$product->get_shipping_class_id() ) {
                return $this->unavailable_message;
            }
            return $price;
        }
    }

    public function no_shipping_class_is_purchasable( $is_purchasable, $product ) {
        if ( !$this->has_shipping_class( $product ) ) {
            return false;
        }
        return $is_purchasable;
    }

    private function has_shipping_class( $product ) {
        $shipping_class = $product->get_shipping_class();
        return !empty( $shipping_class );
    }

    public function hide_products_out_thirteen( $query ) {

        // Ne pas modifier les requêtes dans l'administration ou qui ne sont pas la requête principale.
        if ( is_admin() || $query->get( 'post_type' ) !== 'product' ) {
            return;
        }

        // Obtenir tous les termes de la classe d'expédition.
        $shipping_class_ids = get_terms(
            array(
                'taxonomy'   => 'product_shipping_class',
                'fields'     => 'ids',
                'hide_empty' => false,
            )
        );

        // S'il n'y a pas de classes d'expédition, ne rien faire.
        if ( empty( $shipping_class_ids ) ) {
            return;
        }

        // Modifier la requête pour exclure les produits sans classe d'expédition.
        $tax_query = (array) $query->get( 'tax_query' );

        $tax_query[] = array(
            'taxonomy' => 'product_shipping_class',
            'field'    => 'term_id',
            'terms'    => $shipping_class_ids,
            'operator' => 'IN',
        );

        $query->set( 'tax_query', $tax_query );

    }

    /**
     * Affiche un message au lieu du prix du produit.
     *
     * @param string $price Le prix du produit.
     */
    public function display_message_instead_on_price( $price, $product ) {

        // Vous pouvez ajouter une condition pour vérifier si un code postal a été entré ou non.
        // Si aucun code postal n'est entré, affichez le message.
        if ( !isset( $_COOKIE['zip_code'] ) || empty( $_COOKIE['zip_code'] ) ) {
            return __( 'Entrer votre code postal', 'kingmateriaux' );
        }
        // Sinon, retournez le prix habituel.
        return $price;
    }
}
