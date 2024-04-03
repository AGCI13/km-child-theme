<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles dynamic pricing based on shipping zones and classes in WooCommerce.
 */

class KM_Shipping_Zone {

	/**
	 * The single instance of the class.
	 *
	 * @var KM_Shipping_Zone|null
	 */

	use SingletonTrait;

	/**
	 * The shipping zone ID.
	 *
	 * @var int|null
	 */
	public $shipping_zone_id = null;

	/**
	 * The shipping zone name.
	 *
	 * @var string|null
	 */
	public $shipping_zone_name = '';

	/**
	 * The shipping_postcode string.
	 *
	 * @var string|null
	 */
	public $shipping_postcode = '';

	/**
	 * The country code string.
	 *
	 * @var string|null
	 */
	public $country_code = '';

	/**
	 * The shipping zone IDs in the thirtheen's departement.
	 *
	 * @var array
	 */
	public $zones_in_thirteen = array( 12, 13, 14, 15, 16, 17, 18 );

	/**
	 * The boolean to check if the current shipping zone is in the thirtheen.
	 *
	 * @var bool
	 */
	public $is_in_thirteen = false;

	/**
	 * Constructor.
	 *
	 * The constructor is protected to prevent creating a new instance from outside
	 * and to prevent creating multiple instances through the `new` keyword.
	 */
	private function __construct() {

		add_action( 'woocommerce_init', array( $this, 'init' ) );

		$this->register_front_hooks();
		$this->register_admin_hooks();
		$this->register_ajax_handlers();
	}

	public function init() {
		$this->shipping_zone_id = $this->get_shipping_zone_id();
		error_log( var_export( $this->shipping_zone_id, true ) );
		$this->shipping_postcode = $this->get_shipping_postcode_from_session();
		error_log( var_export( $this->shipping_postcode, true ) );
		$this->shipping_zone_name = $this->get_shipping_zone_name();
		$this->is_in_thirteen     = $this->is_zone_in_thirteen();
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register_front_hooks() {
		add_action( 'wp_footer', array( $this, 'modal_postcode_html' ) );
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'admin_footer', array( $this, 'add_custom_shipping_zone_fields' ) );
	}


	public function register_ajax_handlers() {
		add_action( 'wp_ajax_save_shipping_delays_handler', array( $this, 'save_shipping_delays_handler' ) );
		add_action( 'wp_ajax_store_in_wc_session', array( $this, 'store_in_wc_session_handler' ) );
		add_action( 'wp_ajax_nopriv_store_in_wc_session', array( $this, 'store_in_wc_session_handler' ) );
	}

	public function get_shipping_zone_id() {
		$shipping_zone_id = $this->maybe_get_zone_url_id();

		if ( $shipping_zone_id ) {
			WC()->session->set( 'shipping_zone', $shipping_zone_id );
		} elseif ( ! $this->shipping_zone_id ) {
			$shipping_zone_id = $this->get_shipping_zone_from_session();
		}

		return $shipping_zone_id;
	}

	private function maybe_get_zone_url_id() {
		if ( isset( $_GET['region_id'] ) && ! empty( $_GET['region_id'] ) ) {

			if ( is_numeric( $_GET['region_id'] ) && $_GET['region_id'] > 0 ) {
				return intval( $_GET['region_id'] );
			}

			return $this->get_zone_id_from_name( $_GET['region_id'] );
		}
		return null;
	}

	private function get_zone_id_from_name( $shipping_zone_name ) {
		$shipping_zones     = WC_Shipping_Zones::get_zones();
		$zone_id            = null;
		$shipping_zone_name = strtolower( str_replace( ' ', '', $shipping_zone_name ) );

		foreach ( $shipping_zones as $zone_data ) {
			$zone      = new WC_Shipping_Zone( $zone_data['id'] );
			$zone_name = strtolower( str_replace( ' ', '', $zone->get_zone_name() ) );

			if ( $zone_name === $shipping_zone_name ) {
				$zone_id = $zone_data['id'];
				break;
			}
		}
		return $zone_id;
	}

	/**
	 * Récupère le code postal et le pays à partir de la session WooCommerce.
	 *
	 * @return bool
	 */
	public function get_shipping_postcode_from_session() {
		if ( ! isset( WC()->session ) ) {
			return false;
		}
		$postcode = WC()->session->get( 'postcode' );
		if ( empty( $postcode ) ) {
			return false;
		}
		return $postcode;
	}
	/**
	 * Récupère le code postal et le pays à partir de la session WooCommerce.
	 *
	 * @return bool
	 */
	public function get_shipping_zone_from_session() {
		if ( ! isset( WC()->session ) ) {
			return false;
		}

		$shipping_zone = WC()->session->get( 'shipping_zone' );
		if ( empty( $shipping_zone ) ) {
			return false;
		}
		return $shipping_zone;
	}

	/**
	 * Checks if the current shipping zone is in the thirtheen.
	 *
	 * @param int $zone_id The zone ID.
	 *
	 * @return bool
	 */
	public function is_zone_in_thirteen( $zone_id = null ) {

		$zone_id = $zone_id ? $zone_id : $this->shipping_zone_id;

		if ( ! is_array( $this->zones_in_thirteen ) || empty( $this->zones_in_thirteen )
		|| ! is_numeric( $zone_id ) || $zone_id <= 0 ) {
			return false;
		}

		return in_array( $zone_id, $this->zones_in_thirteen, true );
	}

	/**
	 * Retrieves the shipping class for a given product.
	 *
	 * @param int|WC_Product $product The product ID or product object.
	 * @return string|false The shipping class slug or false on failure.
	 */
	public function get_product_shipping_class( $product ) {

		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		$shipping_class_id = $product->get_shipping_class_id();

		if ( empty( $shipping_class_id ) ) {
			return false;
		}

		$shipping_class_term = get_term( $shipping_class_id, 'product_shipping_class' );

		// Return the shipping class slug or false if not found.
		return ( ! is_wp_error( $shipping_class_term ) && $shipping_class_term ) ? $shipping_class_term->slug : false;
	}

	/**
	 * Gets the shipping zone name using the ID from the 'shipping_zone' cookie.
	 *
	 * @return string|null The name of the shipping zone or null if the zone does not exist.
	 */
	public function get_shipping_zone_name( $shipping_zone_id = null ) {

		if ( ! $shipping_zone_id ) {
			$shipping_zone_id = $this->shipping_zone_id;
		}

		if ( null === $shipping_zone_id ) {
			return null;
		}

		$shipping_zone = new WC_Shipping_Zone( $shipping_zone_id );

		if ( 0 === $shipping_zone->get_id() ) {
			return null;
		}
		return $shipping_zone->get_zone_name();
	}

	/**
	 * Obtient le nom du produit de livraison associé.
	 *
	 * @param WC_Product $product Le produit.
	 */
	public function get_related_shipping_product( $product, $zone_id = null ) {

		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( $product );
		}

		$shipping_class_id = $product->get_shipping_class_id();

		if ( ! $shipping_class_id ) {
			return;
		}

		$shipping_class_term = get_term( $shipping_class_id, 'product_shipping_class' );

		if ( ! $shipping_class_term || is_wp_error( $shipping_class_term ) ) {
			return;
		}

		if ( ! $zone_id ) {
			$zone_id = $this->get_shipping_zone_id();
		}

		$shipping_class_name = $shipping_class_term->name;

		if ( strpos( $shipping_class_name, '²' ) !== false ) {
			$shipping_class_name = str_replace( '²', '2', $shipping_class_name );
		}

		$shipping_zone_name    = $this->get_shipping_zone_name( $zone_id );
		$shipping_product_name = $shipping_zone_name . ' ' . $shipping_class_name;

		$args = array(
			'fields'         => 'ids',
			'post_type'      => 'product',
			'post_status'    => array( 'private' ),
			'posts_per_page' => 1,
			'title'          => $shipping_product_name,
			'exact'          => true,
		);

		$shipping_products_posts = get_posts( $args );

		if ( ! $shipping_products_posts ) {
			return;
		}

		$shipping_product = wc_get_product( $shipping_products_posts[0] );

		if ( ! $shipping_product ) {
			return;
		}

		return $shipping_product;
	}

	/**
	 * Ajax callback to get the shipping zone ID from a postcode code.
	 *
	 * @return void | json
	 */
	public function postcode_submission_handler() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		$nonce_value = isset( $_POST['nonce_postcode'] ) && ! empty( $_POST['nonce_postcode'] ) ? wp_unslash( $_POST['nonce_postcode'] ) : '';
		$nonce_value = sanitize_text_field( $nonce_value );

		if ( ! wp_verify_nonce( $nonce_value, 'postcode_submission_handler' ) ) {
			wp_send_json_error( array( 'message' => __( 'La vérification du nonce a échoué.' ) ) );
		}

		$zone_id = $this->validate_postcode_form_data( $_POST );

		if ( $zone_id ) {
			wp_send_json_success( $zone_id );
		} else {
			wp_send_json_error( array( 'message' => 'Une erreur est survenue.' ) );
		}
	}

	public function store_in_wc_session_handler() {
		check_ajax_referer( 'postcode_submission_handler', 'nonce_postcode' );

		if ( ! isset( $_POST['postcode'] ) || empty( $_POST['postcode'] ) ) {
			wp_send_json_error( array( 'message' => 'Données manquantes.' ) );
		}

		$postcode = sanitize_text_field( wp_unslash( $_POST['postcode'] ) );

		WC()->session->set( 'postcode', $postcode );
		WC()->session->set( 'shipping_zone', $this->get_shipping_zone_id_from_postcode( $postcode ) );

		setcookie( 'need_refresh', '1', time() + 60, '/' );

		wp_send_json_success( array( 'message' => 'Informations stockées avec succès.' ) );
	}

	/**
	 * Validate the postcode form data.
	 *
	 * @param array $data The form data.
	 * @return array The validated data.
	 */
	public function validate_postcode_form_data() {

		$postcode = isset( $_POST['postcode'] ) && ! empty( $_POST['postcode'] ) ? wp_unslash( $_POST['postcode'] ) : '';
		$postcode = sanitize_text_field( $postcode );

		if ( empty( $postcode ) ) {
			wp_send_json_error( array( 'message' => __( 'Le code postal est vide.', 'kingmateriaux' ) ) );
		}

		$country = isset( $_POST['country'] ) && ! empty( $_POST['country'] ) ? wp_unslash( $_POST['country'] ) : '';
		$country = sanitize_text_field( $country );

		if ( empty( $country ) ) {
			wp_send_json_error( array( 'message' => __( 'Le code pays est vide.', 'kingmateriaux' ) ) );
		}

		if ( ! in_array( $country, array( 'FR', 'BE' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Le code pays est invalide.', 'kingmateriaux' ) ) );
		}

		if ( $country === 'FR' && strlen( $postcode ) !== 5 ) {
			wp_send_json_error( array( 'message' => __( 'Le code postal FR doit contenir 5 chiffres.', 'kingmateriaux' ) ) );
		}

		if ( $country === 'BE' && strlen( $postcode ) !== 4 ) {
			wp_send_json_error( array( 'message' => __( 'Le code postal BE doit contenir 4 chiffres.', 'kingmateriaux' ) ) );
		}

		$zone_id = $this->get_shipping_zone_id_from_postcode( $postcode );

		if ( ! $zone_id ) {
			wp_send_json_error( array( 'message' => __( 'Aucune zone de livraison trouvée. Si ce code postal est bien le votre, veuillez contacter le service client.', 'kingmateriaux' ) ) );
		}

		return $zone_id;
	}

	/**
	 * Gets the shipping zone ID from a postcode.
	 *
	 * @param string $postcode The postcode code.
	 * @return int|null The shipping zone ID or null if no zone is found.
	 */
	public function get_shipping_zone_id_from_postcode( $postcode ) {
		$shipping_zones = WC_Shipping_Zones::get_zones();
		$found_zone     = null;

		foreach ( $shipping_zones as $zone_data ) {
			$zone           = new WC_Shipping_Zone( $zone_data['id'] );
			$zone_locations = $zone->get_zone_locations();

			foreach ( $zone_locations as $location ) {
				if ( strpos( $location->code, '...' ) !== false ) {
					list($start_postcode, $end_postcode) = explode( '...', $location->code );
					if ( $postcode >= $start_postcode && $postcode <= $end_postcode ) {
						$found_zone = $zone_data['id'];
						break 2; // Break out of both foreach loops.
					}
				} elseif ( $postcode === $location->code ) {
						$found_zone = $zone_data['id'];
						break 2;
				}
			}
		}

		return $found_zone;
	}

	/**
	 * Vérifie si le produit est achetable hors de la zone 13.
	 * Un produit est achetable hors de la zone 13 si il a une classe de livraison et que son prix est supérieur à 0€.
	 *
	 * @param WC_Product $product Le produit.
	 * @return bool Si le produit est achetable hors de la zone 13.
	 */
	public function is_product_shippable_out_13( $product, $zone_id = null ) {

		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product ) {
			return false;
		}

		return km_get_shipping_product_price( $product, $zone_id );
	}

	/**
	 * Add custom fields to shipping zones.
	 *
	 * @param WC_Shipping_Zone $zone The shipping zone object.
	 *
	 * @return void
	 */
	public function add_custom_shipping_zone_fields( $zone ) {
		$screen = get_current_screen();

		if ( 'woocommerce_page_wc-settings' !== $screen->id || ! isset( $_GET['zone_id'] ) || empty( $_GET['zone_id'] ) ) {
			return;
		}

		// Sanitize the zone_id.
		$zone_id = intval( $_GET['zone_id'] );

		// Récupèrer les paramètres si déjà enregistrés.
		$min_shipping_days_hs = get_option( 'min_shipping_days_hs_' . $zone_id );
		$max_shipping_days_hs = get_option( 'max_shipping_days_hs_' . $zone_id );
		$min_shipping_days_ls = get_option( 'min_shipping_days_ls_' . $zone_id );
		$max_shipping_days_ls = get_option( 'max_shipping_days_ls_' . $zone_id );

		// enqueue le script.
		wp_enqueue_script( 'km-shipping-zone-script' );

		// requiert le template.
		require_once get_stylesheet_directory() . '/templates/admin/shipping-zones-settings.php';
	}

	/**
	 * Ajax callback to save the shipping delays.
	 *
	 * @return void | json
	 */
	public function save_shipping_delays_handler() {
		if ( ! is_admin() || ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		$nonce_value = isset( $_POST['shipping_nonce'] ) && ! empty( $_POST['shipping_nonce'] ) ? wp_unslash( $_POST['shipping_nonce'] ) : '';
		$nonce_value = sanitize_text_field( $nonce_value );

		if ( ! wp_verify_nonce( $nonce_value, 'save_shipping_delays_handler' ) ) {
			wp_send_json_error( array( 'message' => __( 'La vérification du nonce a échoué.' ) ) );
		}

		$zone_id = isset( $_POST['zone_id'] ) ? intval( $_POST['zone_id'] ) : '';

		if ( isset( $_POST['min_shipping_days_hs'] ) ) {
			update_option( 'min_shipping_days_hs_' . $zone_id, wp_unslash( sanitize_text_field( $_POST['min_shipping_days_hs'] ) ) );
		}

		if ( isset( $_POST['max_shipping_days_hs'] ) ) {
			update_option( 'max_shipping_days_hs_' . $zone_id, wp_unslash( sanitize_text_field( $_POST['max_shipping_days_hs'] ) ) );
		}

		if ( isset( $_POST['min_shipping_days_ls'] ) ) {
			update_option( 'min_shipping_days_ls_' . $zone_id, wp_unslash( sanitize_text_field( $_POST['min_shipping_days_ls'] ) ) );
		}

		if ( isset( $_POST['max_shipping_days_ls'] ) ) {
			update_option( 'max_shipping_days_ls_' . $zone_id, wp_unslash( sanitize_text_field( $_POST['max_shipping_days_ls'] ) ) );
		}

		wp_send_json_success( array( 'message' => 'Délais de livraison sauvegardés' ) );
	}

	/**
	 * Display the postcode modal.
	 *
	 * @return void
	 */
	public function modal_postcode_html() {

		$shipping_zone_id = $this->shipping_zone_id ? $this->shipping_zone_id : WC()->session->get( 'shipping_zone' );

		$active = '';
		if ( ! $shipping_zone_id && ( is_home() || is_front_page() || is_product() || is_product_category() ) ) {
			$active = 'active';
		}

		// requiert le template.
		require_once get_stylesheet_directory() . '/templates/modals/postcode.php';
	}
}
