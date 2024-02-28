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
	public function km_display_shipping_delays() {

		if ( 'cart' === $this->context ) {
			$longuest_delays = $this->calculate_longest_delays_for_cart();
		} elseif ( 'product' === $this->context ) {
			$longuest_delays = $this->calculate_longest_delays_for_product( get_the_ID() );
		} else {
			return;
		}

		return $this->generate_delay_html( $longuest_delays );
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
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$delays                = $this->get_shipping_delays( $cart_item['product_id'] );
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

		$html = '';

		if ( 0 === $delays['min'] && 0 === $delays['max'] ) {
			return '';
		}

		// Calculer les dates de livraison estimées.
		$current_date           = current_time( 'timestamp' );
		$min_delivery_timestamp = strtotime( '+' . $delays['min'] . ' days', $current_date );
		$max_delivery_timestamp = strtotime( '+' . $delays['max'] . ' days', $current_date );

		// Formater les dates selon la locale.
		$formatted_min_date = date_i18n( 'j F Y', $min_delivery_timestamp );
		$formatted_max_date = date_i18n( 'j F Y', $max_delivery_timestamp );

		// Construire le HTML en fonction du contexte.
		if ( 'cart' === $this->context ) {
			if ( $delays['min'] === $delays['max'] ) {
				$html .= esc_html__( 'Livraison estimée le ', 'kingmateriaux' ) . $formatted_min_date;
			} else {
				$html .= esc_html__( 'Livraison estimée entre le ', 'kingmateriaux' ) . date_i18n( 'j', $min_delivery_timestamp ) . ' et le ' . $formatted_max_date;
			}
		} elseif ( 'product' === $this->context ) {
			if ( $delays['min'] === $delays['max'] ) {
				$html .= esc_html__( 'Livraison estimée le ', 'kingmateriaux' ) . $formatted_min_date;
			} else {
				$html .= esc_html__( 'Livraison estimée entre le ', 'kingmateriaux' ) . date_i18n( 'j', $min_delivery_timestamp ) . ' et le ' . $formatted_max_date;
			}
		}

		return $html;
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
		$season                = $this->get_current_season();
		$shipping_zone_delays  = $this->get_zone_delays( $season );
		$custom_product_delays = $this->get_product_delays( $product_id, $season );

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
	 * Récupère les délais de livraison personnalisés pour un produit donné.
	 *
	 * @param int    $product_id L'ID du produit.
	 * @param string $season     La saison.
	 *
	 * @return array
	 */
	private function get_product_delays( $product_id, $season ) {
		$custom_delays = get_field( "product_shipping_delays_product_shipping_delays_{$season}", $product_id );
		$min_delay     = $custom_delays[ 'min_shipping_days_' . $season ] ?? 0;
		$max_delay     = $custom_delays[ 'max_shipping_days_' . $season ] ?? 0;
		return array(
			'min' => (int) $min_delay,
			'max' => (int) $max_delay,
		);
	}
}
