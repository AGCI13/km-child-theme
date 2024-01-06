<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles dynamic pricing based on shipping zones and classes in WooCommerce.
 */

class KM_Order_Processing {

	/**
	 * The single instance of the class.
	 *
	 * @var KM_Order_Processing|null
	 */

	use SingletonTrait;

	/**
	 *  The shipping zone instance.
	 *
	 * @var KM_Shipping_zone|null
	 */
	public $km_shipping_zone;
	/**
	 *  The shipping zone instance.
	 *
	 * @var KM_Dynamic_Pricing|null
	 */
	public $km_dynamic_pricing;

	/**
	 * Constructor.
	 *
	 * The constructor is protected to prevent creating a new instance from outside
	 * and to prevent creating multiple instances through the `new` keyword.
	 */
	private function __construct() {
		$this->km_shipping_zone   = KM_Shipping_zone::get_instance();
		$this->km_dynamic_pricing = KM_Dynamic_Pricing::get_instance();
		$this->register();
	}

	private function register() {
		add_action( 'woocommerce_checkout_create_order', array( $this, 'add_custom_data_to_order' ), 10, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_custom_order_item_meta' ), 50, 3 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_drive_checkout_fields' ), 10, 2 );
	}

	public function add_custom_data_to_order( $order, $data ) {
		// Définir la valeur personnalisée.
		$ugs_product_shipping = 7640;

		// Ajouter la donnée personnalisée à la commande.
		$order->update_meta_data( '_ugs_product_shipping', $ugs_product_shipping );
	}

	/**
	 * Save drive checkout fields
	 *
	 * @param WC_Order $order
	 * @param array    $data
	 * @return void
	 */
	public function save_drive_checkout_fields( $order, $data ) {
		if ( isset( $_POST['drive_date'] ) ) {
			$order->update_meta_data( '_drive_date', sanitize_text_field( $_POST['drive_date'] ) );
		}

		if ( isset( $_POST['drive_time'] ) ) {
			$order->update_meta_data( '_drive_time', sanitize_text_field( $_POST['drive_time'] ) );
		}
	}


	/**
	 *  Add custom order meta
	 *
	 * @param int $order_id
	 * @return void
	 */
	public function add_custom_order_item_meta( $order_id, $posted_data, $order ) {
		if ( ! $order ) {
			return;
		}

		foreach ( $order->get_items() as $item_id => $item ) {

			$item_data = $item->get_data();

			$product_id = $item_data['variation_id'] ? $item_data['variation_id'] : $item_data['product_id'];
			$product    = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			// Get the product price excluding tax with the product id.
			$product_price_excl_tax = wc_get_price_excluding_tax( $product );

			$product_price_incl_tax = wc_get_price_including_tax( $product );

			$product_tax_price = $product_price_incl_tax - $product_price_excl_tax;

			// Check if the product is product_is_bulk_or_bigbag().
			$is_bulk_or_bigbag = $this->km_dynamic_pricing->product_is_bulk_or_bigbag( $product_id );

			// If product $is_bulk_or_bigbag add the ecotaxe price to the price.
			if ( $is_bulk_or_bigbag ) {
				$product_price_excl_tax += $this->km_dynamic_pricing->ecotaxe_rate;
				$product_tax_price      += $this->km_dynamic_pricing->ecotaxe_rate_incl_taxes - $this->km_dynamic_pricing->ecotaxe_rate;
			}

			/* C'est le prix d'une unité de produit comme il est dans le backoffice + écotaxe HT s'il y en a une, HORS 13, vérifier dans 13 et hors 13 */
			wc_update_order_item_meta( $item_id, '_actual_product_price_excl', $product_price_excl_tax );

			/* C'est la TVA d'une unité de produit + TVA écotaxe vérifier dans 13 et hors 13 */
			wc_update_order_item_meta( $item_id, '_actual_product_tax_price', $product_tax_price );

			// Check if the product name contains 'vrac' and update item meta.
			if ( stripos( $item_data['name'], 'vrac' ) !== false ) {
				wc_update_order_item_meta( $item_id, '_tonnes', $item_data['quantity'] );
			}

			if ( $this->km_shipping_zone->is_in_thirteen ) {

				static $first_item_processed = false;

				// Vérifier si c'est le premier article de la commande.
				if ( ! $first_item_processed ) {
					// Marquer que le premier article a été traité.
					$first_item_processed = true;

					// Récupérer les données des champs cachés et les nettoyer.
					$km_shipping_sku   = isset( $_POST['km_shipping_sku'] ) ? sanitize_text_field( $_POST['km_shipping_sku'] ) : '';
					$km_shipping_price = isset( $_POST['km_shipping_price'] ) ? (float) $_POST['km_shipping_price'] : '';
					$km_shipping_tax   = isset( $_POST['km_shipping_tax'] ) ? (float) $_POST['km_shipping_tax'] : '';

					// Ajouter les métadonnées à l'article de la commande
					if ( ! empty( $km_shipping_sku ) ) {
						wc_update_order_item_meta( $item_id, '_ugs_product_shipping', $km_shipping_sku );
					}
					if ( ! empty( $km_shipping_price ) ) {
						wc_update_order_item_meta( $item_id, '_shipping_price_product_excl', $km_shipping_price );
					}
					if ( ! empty( $km_shipping_tax ) ) {
						wc_update_order_item_meta( $item_id, '_tax_prices_on_product_shipping', $km_shipping_tax );
					}
				}
			} else {
				$shipping_product = $this->km_shipping_zone->get_related_shipping_product( $product );

				if ( $shipping_product ) {

					// Obtenir le prix TTC
					$shipping_price_incl_tax = wc_get_price_including_tax( $shipping_product );
					// Obtenir le prix HT
					$shipping_price_excl_tax = wc_get_price_excluding_tax( $shipping_product );
					// Calculer le montant de la taxe
					$shipping_tax_amount = $shipping_price_incl_tax - $shipping_price_excl_tax;

					wc_update_order_item_meta( $item_id, '_ugs_product_shipping', $shipping_product->get_sku() );

					/*
					TODO: ex. 134811 - Si le prix du produit est à zéro, mettre le prix du transport à zéro. */
					wc_update_order_item_meta( $item_id, '_shipping_price_product_excl', $shipping_price_excl_tax );

					/*
					TODO: Si le prix du produit est à zéro, mettre les taxes du transport à zéro */
					wc_update_order_item_meta( $item_id, '_tax_prices_on_product_shipping', $shipping_tax_amount );
				}
			}
		}

		update_post_meta( $order_id, '_cookie_cp', $this->km_shipping_zone->zip_code );
	}
}
