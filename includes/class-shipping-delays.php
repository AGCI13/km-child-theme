<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles dynamic pricing based on shipping zones and classes in WooCommerce.
 */
class KM_Shipping_Delays {

	/**
	 * Le contexte dans lequel les délais de livraison sont affichés.
	 *
	 * @var string
	 */
	private $context;

	/**
	 * L'ID de la zone de livraison.
	 *
	 * @var int
	 */
	private $shipping_zone_id;

	/**
	 * Constructeur.
	 *
	 * @param int    $shipping_zone_id L'ID de la zone de livraison.
	 * @param string $context          Le contexte dans lequel les délais de livraison sont affichés.
	 */
	public function __construct( $shipping_zone_id, $context ) {
		$this->shipping_zone_id = $shipping_zone_id;
		$this->context          = $context;
	}

	/**
	 * Affiche les délais de livraison en fonction du contexte.
	 *
	 * @return string
	 */
	public function display_shipping_dates() {

		if ( 'cart' === $this->context ) {
			$longest_delays = $this->calculate_longest_delays_for_cart();
		} elseif ( 'product' === $this->context ) {
			$longest_delays = $this->calculate_longest_delays_for_product( get_the_ID() );
		} else {
			return;
		}

		return $this->generate_delay_html( $longest_delays );
	}

	/**
	 * Calcule les délais de livraison les plus longs pour le panier.
	 *
	 * @return array
	 */
	private function calculate_longest_delays_for_cart() {
		$longest_delays = array(
			'min' => 0,
			'max' => 0,
		);

		if ( ! WC()->cart || ! WC()->cart->get_cart() ) {
			return $longest_delays;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_id = $cart_item['data']->get_id();
			$delays     = $this->get_shipping_delays( $product_id );

			$longest_delays['min'] = max( $longest_delays['min'], $delays['min'] );
			$longest_delays['max'] = max( $longest_delays['max'], $delays['max'] );
		}

		return $longest_delays;
	}

	/**
	 * Calcule les délais de livraison les plus longs pour un produit donné.
	 *
	 * @param int $product_id L'ID du produit.
	 *
	 * @return array
	 */
	private function calculate_longest_delays_for_product( $product_id ) {
		return $this->get_shipping_delays( $product_id );
	}

	/**
	 * Génère le HTML des délais de livraison.
	 * Si les délais de livraison sont de 0 à 0, le HTML est vide.
	 * Si les délais de livraison sont les mêmes, le HTML est "Livraison estimée le {date}".
	 *
	 * @param array $delays Les délais de livraison.
	 *
	 * @return string
	 */
	private function generate_delay_html( $delays ) {
		if ( 0 === $delays['min'] && 0 === $delays['max'] ) {
			return '';
		}

		$current_date      = time();
		$min_delivery_date = wp_date( 'j F Y', strtotime( '+' . $delays['min'] . ' days', $current_date ) );
		$max_delivery_date = wp_date( 'j F Y', strtotime( '+' . $delays['max'] . ' days', $current_date ) );

		if ( $delays['min'] === $delays['max'] ) {
			$delivery_estimate = esc_html__( 'Livraison estimée le ', 'kingmateriaux' ) . $min_delivery_date;
		} else {
			// Vérifie si le mois et l'année sont identiques pour les deux dates.
			$min_month_year = wp_date( 'F Y', strtotime( '+' . $delays['min'] . ' days', $current_date ) );
			$max_month_year = wp_date( 'F Y', strtotime( '+' . $delays['max'] . ' days', $current_date ) );

			if ( $min_month_year === $max_month_year ) {
				// Si le mois et l'année sont identiques, affiche seulement le jour pour la date minimale.
				$min_day           = wp_date( 'j', strtotime( '+' . $delays['min'] . ' days', $current_date ) );
				$delivery_estimate = esc_html__( 'Livraison estimée entre le ', 'kingmateriaux' ) . $min_day . ' et le ' . $max_delivery_date;
			} else {
				$delivery_estimate = esc_html__( 'Livraison estimée entre le ', 'kingmateriaux' ) . $min_delivery_date . ' et le ' . $max_delivery_date;
			}
		}

		return $delivery_estimate;
	}

	/**
	 * Récupère les délais de livraison pour un produit donné.
	 * Les délais de livraison sont déterminés par la saison et les délais de livraison personnalisés du produit.
	 * Les délais de livraison personnalisés du produit ont la priorité sur les délais de livraison de la zone.
	 * Les délais de livraison de la zone sont déterminés par la saison.
	 * Les délais de livraison de la zone ont la priorité sur les délais de livraison par défaut.
	 * Les délais de livraison par défaut sont de 0 à 0.
	 *
	 * @param int $product_id L'ID du produit.
	 *
	 * @return array
	 */
	private function get_shipping_delays( $product_id ) {
		$season               = $this->get_current_season();
		$shipping_zone_delays = $this->get_zone_delays( $season );

		// Vérifier si le produit est une variation et récupérer les délais de la variation si disponibles
		$product = wc_get_product( $product_id );
		if ( $product->is_type( 'variation' ) ) {
			$variation_id            = $product->get_id();
			$custom_variation_delays = $this->get_variation_delays( $variation_id, $season );

			// Si les délais de la variation sont définis, les utiliser
			if ( $custom_variation_delays['min'] !== 0 || $custom_variation_delays['max'] !== 0 ) {
				return $custom_variation_delays;
			}

			// Sinon, utiliser les délais du produit parent
			$parent_id             = $product->get_parent_id();
			$custom_product_delays = $this->get_product_delays( $parent_id, $season );
		} else {
			$custom_product_delays = $this->get_product_delays( $product_id, $season );
		}

		$min_delay = max( $shipping_zone_delays['min'], $custom_product_delays['min'] );
		$max_delay = max( $shipping_zone_delays['max'], $custom_product_delays['max'] );

		return array(
			'min' => $min_delay,
			'max' => $max_delay,
		);
	}

	/**
	 * Récupère la saison actuelle.
	 *
	 * @return string
	 */
	private function get_current_season() {
		$current_month = gmdate( 'n' );
		return ( $current_month >= 3 && $current_month <= 8 ) ? 'hs' : 'ls';
	}

	/**
	 * Récupère les délais de livraison pour une zone donnée.
	 *
	 * @param string $season La saison.
	 *
	 * @return array
	 */
	private function get_zone_delays( $season ) {
		$min_delay = get_option( "min_shipping_days_{$season}_" . $this->shipping_zone_id, 0 );
		$max_delay = get_option( "max_shipping_days_{$season}_" . $this->shipping_zone_id, 0 );

		return array(
			'min' => (int) $min_delay,
			'max' => (int) $max_delay,
		);
	}

	/**
	 * Récupère les délais de livraison personnalisés pour une variation donnée.
	 *
	 * @param int    $variation_id L'ID de la variation.
	 * @param string $season       La saison.
	 *
	 * @return array
	 */
	private function get_variation_delays( $variation_id, $season ) {
		$min_delay = get_post_meta( $variation_id, "min_shipping_days_{$season}", true );
		$max_delay = get_post_meta( $variation_id, "max_shipping_days_{$season}", true );

		return array(
			'min' => (int) ( $min_delay ? $min_delay : 0 ),
			'max' => (int) ( $max_delay ? $max_delay : 0 ),
		);
	}

	/**
	 * Récupère les délais de livraison personnalisés pour un produit donné.
	 *
	 * @param int    $product_id L'ID du produit.
	 * @param string $season     La saison.
	 *
	 * @return array
	 */
	private function get_product_delays( $product_id, $season ) {
		$custom_delays = get_field( 'product_shipping_delays', $product_id );

		$min_delay = 0;
		$max_delay = 0;

		if ( $custom_delays ) {
			if ( $season === 'hs' ) {
				$min_delay = isset( $custom_delays['product_shipping_delays_hs']['min_shipping_days_hs'] ) ? $custom_delays['product_shipping_delays_hs']['min_shipping_days_hs'] : 0;
				$max_delay = isset( $custom_delays['product_shipping_delays_hs']['max_shipping_days_hs'] ) ? $custom_delays['product_shipping_delays_hs']['max_shipping_days_hs'] : 0;
			} else {
				$min_delay = isset( $custom_delays['product_shipping_delays_ls']['min_shipping_days_ls'] ) ? $custom_delays['product_shipping_delays_ls']['min_shipping_days_ls'] : 0;
				$max_delay = isset( $custom_delays['product_shipping_delays_ls']['max_shipping_days_ls'] ) ? $custom_delays['product_shipping_delays_ls']['max_shipping_days_ls'] : 0;
			}
		}

		return array(
			'min' => (int) $min_delay,
			'max' => (int) $max_delay,
		);
	}
}
