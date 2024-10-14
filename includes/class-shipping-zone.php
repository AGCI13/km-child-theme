<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles dynamic pricing based on shipping zones and classes in WooCommerce.
 */
class KM_Shipping_Zone {
	use SingletonTrait;

	public $shipping_zone_id   = null;
	public $shipping_zone_name = '';
	public $shipping_postcode  = '';
	public $country_code       = '';
	public $zones_in_thirteen  = array( 12, 13, 14, 15, 16, 17, 18 );
	public $is_in_thirteen     = false;

	private $shipping_zones_cache           = array();
	private $product_shipping_class_cache   = array();
	private $shipping_zone_name_cache       = array();
	private $related_shipping_product_cache = array();
	private $zone_id_from_postcode_cache    = array();

	private function __construct() {
		$this->init();
		$this->register();
	}

	private function init() {
		$this->shipping_zone_id = $this->get_shipping_zone_id();
		$this->get_zip_and_country_from_cookie();
		$this->shipping_zone_name = $this->get_shipping_zone_name();
		$this->is_in_thirteen     = $this->is_zone_in_thirteen();
	}

	public function register() {
		add_action( 'wp_ajax_postcode_submission_handler', array( $this, 'postcode_submission_handler' ) );
		add_action( 'wp_ajax_nopriv_postcode_submission_handler', array( $this, 'postcode_submission_handler' ) );
		add_action( 'wp_ajax_save_shipping_delays_handler', array( $this, 'save_shipping_delays_handler' ) );
		add_action( 'admin_footer', array( $this, 'add_custom_shipping_zone_fields' ) );
		add_action( 'wp_footer', array( $this, 'modal_postcode_html' ) );
	}

	private function get_shipping_zone_id() {
		$shipping_zone_id = $this->maybe_get_zone_url_id();

		if ( $shipping_zone_id ) {
			setcookie( 'shipping_zone', $shipping_zone_id, time() + ( 86400 * 30 ), '/' );
			setcookie( 'zip_code', '', time() + ( 86400 * 30 ), '/' );
			return $shipping_zone_id;
		}

		return $this->get_shipping_zone_id_from_cookie();
	}

	private function maybe_get_zone_url_id() {
		if ( ! isset( $_GET['region_id'] ) || empty( $_GET['region_id'] ) ) {
			return null;
		}

		$region_id = sanitize_text_field( $_GET['region_id'] );
		return is_numeric( $region_id ) && $region_id > 0 ? intval( $region_id ) : $this->get_zone_id_from_name( $region_id );
	}

	private function get_zone_id_from_name( $shipping_zone_name ) {
		$shipping_zone_name = strtolower( str_replace( ' ', '', $shipping_zone_name ) );

		foreach ( $this->get_shipping_zones() as $zone_data ) {
			$zone_name = strtolower( str_replace( ' ', '', $zone_data['zone_name'] ) );
			if ( $zone_name === $shipping_zone_name ) {
				return $zone_data['id'];
			}
		}

		return null;
	}

	public function get_zip_and_country_from_cookie() {
		if ( ! isset( $_COOKIE['zip_code'] ) || empty( $_COOKIE['zip_code'] ) ) {
			return false;
		}

		$postcode = explode( '-', sanitize_text_field( wp_unslash( $_COOKIE['zip_code'] ) ) );

		if ( count( $postcode ) !== 2 ) {
			return false;
		}

		$this->shipping_postcode = $postcode[0];
		$this->country_code      = $postcode[1];

		return true;
	}

	public function is_zone_in_thirteen( $zone_id = null ) {
		$zone_id = $zone_id ?: $this->shipping_zone_id;
		return in_array( $zone_id, $this->zones_in_thirteen, true );
	}

	public function get_product_shipping_class( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		$product_id = $product->get_id();

		if ( ! isset( $this->product_shipping_class_cache[ $product_id ] ) ) {
			$shipping_class_id = $product->get_shipping_class_id();

			if ( empty( $shipping_class_id ) ) {
				$this->product_shipping_class_cache[ $product_id ] = false;
			} else {
				$shipping_class_term                               = get_term( $shipping_class_id, 'product_shipping_class' );
				$this->product_shipping_class_cache[ $product_id ] = ( ! is_wp_error( $shipping_class_term ) && $shipping_class_term ) ? $shipping_class_term->slug : false;
			}
		}

		return $this->product_shipping_class_cache[ $product_id ];
	}

	public function get_shipping_zone_id_from_cookie() {
		$shipping_zone_id = isset( $_COOKIE['shipping_zone'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['shipping_zone'] ) ) : null;
		return ( is_numeric( $shipping_zone_id ) && $shipping_zone_id > 0 ) ? (int) $shipping_zone_id : null;
	}

	public function get_shipping_zone_name( $shipping_zone_id = null ) {
		$shipping_zone_id = $shipping_zone_id ?: $this->shipping_zone_id;

		if ( null === $shipping_zone_id ) {
			return null;
		}

		if ( ! isset( $this->shipping_zone_name_cache[ $shipping_zone_id ] ) ) {
			$shipping_zone                                       = new WC_Shipping_Zone( $shipping_zone_id );
			$this->shipping_zone_name_cache[ $shipping_zone_id ] = $shipping_zone->get_id() !== 0 ? $shipping_zone->get_zone_name() : null;
		}

		return $this->shipping_zone_name_cache[ $shipping_zone_id ];
	}

	public function get_related_shipping_product( $product, $zone_id = null ) {
		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( $product );
		}

		$product_id = $product->get_id();
		$zone_id    = $zone_id ?: $this->get_shipping_zone_id();
		$cache_key  = $product_id . '_' . $zone_id;

		if ( isset( $this->related_shipping_product_cache[ $cache_key ] ) ) {
			return $this->related_shipping_product_cache[ $cache_key ];
		}

		$shipping_class_id = $product->get_shipping_class_id();

		if ( ! $shipping_class_id ) {
			$this->related_shipping_product_cache[ $cache_key ] = null;
			return null;
		}

		$shipping_class_term = get_term( $shipping_class_id, 'product_shipping_class' );

		if ( ! $shipping_class_term || is_wp_error( $shipping_class_term ) ) {
			$this->related_shipping_product_cache[ $cache_key ] = null;
			return null;
		}

		$shipping_class_name   = str_replace( '²', '2', $shipping_class_term->name );
		$shipping_zone_name    = $this->get_shipping_zone_name( $zone_id );
		$shipping_product_name = $shipping_zone_name . ' ' . $shipping_class_name;

		$shipping_product_id = get_posts(
			array(
				'fields'         => 'ids',
				'post_type'      => 'product',
				'post_status'    => array( 'private' ),
				'posts_per_page' => 1,
				'title'          => $shipping_product_name,
				'exact'          => true,
			)
		);

		$this->related_shipping_product_cache[ $cache_key ] = ! empty( $shipping_product_id ) ? wc_get_product( $shipping_product_id[0] ) : null;
		return $this->related_shipping_product_cache[ $cache_key ];
	}

	public function postcode_submission_handler() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		$this->verify_nonce( 'nonce_postcode', 'postcode_submission_handler' );

		$zone_id = $this->validate_postcode_form_data();

		if ( $zone_id ) {
			wp_send_json_success( $zone_id );
		} else {
			wp_send_json_error( array( 'message' => 'Une erreur est survenue.' ) );
		}
	}

	private function verify_nonce( $nonce_field, $nonce_action ) {
		$nonce_value = isset( $_POST[ $nonce_field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce_value, $nonce_action ) ) {
			wp_send_json_error( array( 'message' => __( 'La vérification du nonce a échoué.' ) ) );
		}
	}

	public function validate_postcode_form_data() {
		$postcode = isset( $_POST['zip'] ) ? sanitize_text_field( wp_unslash( $_POST['zip'] ) ) : '';
		$country  = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';

		if ( empty( $postcode ) ) {
			wp_send_json_error( array( 'message' => __( 'Le code postal est vide.', 'kingmateriaux' ) ) );
		}

		if ( empty( $country ) || ! in_array( $country, array( 'FR', 'BE' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Le code pays est invalide.', 'kingmateriaux' ) ) );
		}

		if ( ( $country === 'FR' && strlen( $postcode ) !== 5 ) || ( $country === 'BE' && strlen( $postcode ) !== 4 ) ) {
			wp_send_json_error( array( 'message' => __( 'Le code postal est invalide.', 'kingmateriaux' ) ) );
		}

		$zone_id = $this->get_shipping_zone_id_from_postcode( $postcode );

		if ( ! $zone_id ) {
			wp_send_json_error( array( 'message' => __( 'Aucune zone de livraison trouvée. Si ce code postal est bien le votre, veuillez contacter le service client.', 'kingmateriaux' ) ) );
		}

		return $zone_id;
	}

	public function get_shipping_zone_id_from_postcode( $postcode ) {
		if ( isset( $this->zone_id_from_postcode_cache[ $postcode ] ) ) {
			return $this->zone_id_from_postcode_cache[ $postcode ];
		}

		foreach ( $this->get_shipping_zones() as $zone_data ) {
			foreach ( $zone_data['zone_locations'] as $location ) {
				if ( strpos( $location->code, '...' ) !== false ) {
					list($start_zip, $end_zip) = explode( '...', $location->code );
					if ( $postcode >= $start_zip && $postcode <= $end_zip ) {
						$this->zone_id_from_postcode_cache[ $postcode ] = $zone_data['id'];
						return $zone_data['id'];
					}
				} elseif ( $postcode === $location->code ) {
					$this->zone_id_from_postcode_cache[ $postcode ] = $zone_data['id'];
					return $zone_data['id'];
				}
			}
		}

		$this->zone_id_from_postcode_cache[ $postcode ] = null;
		return null;
	}

	private function get_shipping_zones() {
		if ( empty( $this->shipping_zones_cache ) ) {
			$this->shipping_zones_cache = WC_Shipping_Zones::get_zones();
		}
		return $this->shipping_zones_cache;
	}

	public function is_product_shippable_out_13( $product, $zone_id = null ) {
		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( $product );
		}

		return $product && km_get_shipping_product_price( $product, $zone_id );
	}

	public function add_custom_shipping_zone_fields() {
		$screen = get_current_screen();
		if ( 'woocommerce_page_wc-settings' !== $screen->id || ! isset( $_GET['zone_id'] ) ) {
			return;
		}

		$zone_id       = intval( $_GET['zone_id'] );
		$shipping_days = array(
			'min_shipping_days_hs' => get_option( "min_shipping_days_hs_$zone_id" ),
			'max_shipping_days_hs' => get_option( "max_shipping_days_hs_$zone_id" ),
			'min_shipping_days_ls' => get_option( "min_shipping_days_ls_$zone_id" ),
			'max_shipping_days_ls' => get_option( "max_shipping_days_ls_$zone_id" ),
		);

		wp_enqueue_script( 'km-shipping-zone-script' );
		require_once get_stylesheet_directory() . '/templates/admin/shipping-zones-settings.php';
	}

	public function save_shipping_delays_handler() {
		if ( ! is_admin() || ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		$this->verify_nonce( 'shipping_nonce', 'save_shipping_delays_handler' );

		$zone_id = isset( $_POST['zone_id'] ) ? intval( $_POST['zone_id'] ) : '';

		$shipping_days_options = array(
			'min_shipping_days_hs',
			'max_shipping_days_hs',
			'min_shipping_days_ls',
			'max_shipping_days_ls',
		);

		foreach ( $shipping_days_options as $option ) {
			if ( isset( $_POST[ $option ] ) ) {
				update_option( "{$option}_{$zone_id}", sanitize_text_field( wp_unslash( $_POST[ $option ] ) ) );
			}
		}

		wp_send_json_success( array( 'message' => 'Délais de livraison sauvegardés' ) );
	}

	public function modal_postcode_html() {
		$active           = ( ! $this->shipping_zone_id && ( is_home() || is_front_page() || is_product() || is_product_category() ) ) ? 'active' : '';
		$shipping_zone_id = $this->shipping_zone_id ?: $this->get_shipping_zone_id_from_cookie();

		require_once get_stylesheet_directory() . '/templates/modals/postcode.php';
	}
}
