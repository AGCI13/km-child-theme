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
	 * The zip_code string.
	 *
	 * @var string|null
	 */
	public $zip_code = '';

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

	public $is_in_thirteen = false;

	/**
	 * Constructor.
	 *
	 * The constructor is protected to prevent creating a new instance from outside
	 * and to prevent creating multiple instances through the `new` keyword.
	 */
	private function __construct() {
		$this->get_zip_and_country_from_cookie();
		$this->shipping_zone_id   = $this->get_shipping_zone_id_from_cookie();
		$this->shipping_zone_name = $this->get_shipping_zone_name();

		$this->is_in_thirteen = $this->is_in_thirteen();

		$this->register();
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_postcode_submission_handler', array( $this, 'postcode_submission_handler' ) );
		add_action( 'wp_ajax_nopriv_postcode_submission_handler', array( $this, 'postcode_submission_handler' ) );
		add_action( 'wp_ajax_save_shipping_delays_handler', array( $this, 'save_shipping_delays_handler' ) );
		add_action( 'admin_footer', array( $this, 'add_custom_shipping_zone_fields' ) );
	}

	/**
	 * Checks if the current shipping zone is in the thirtheen.
	 *
	 * @return string
	 */
	public function get_zip_and_country_from_cookie() {
		if ( ! isset( $_COOKIE['zip_code'] ) || empty( $_COOKIE['zip_code'] ) ) {
			return false;
		}
		$zip_cookie = sanitize_text_field( wp_unslash( $_COOKIE['zip_code'] ) );

		$zip_cookie = explode( '-', $zip_cookie );

		if ( ! isset( $zip_cookie[0] ) || empty( $zip_cookie[0] ) || ! isset( $zip_cookie[1] ) || empty( $zip_cookie[1] ) ) {
			return false;
		}

		$this->zip_code     = $zip_cookie[0];
		$this->country_code = $zip_cookie[1];

		return true;
	}

	/**
	 * Retrieves the shipping class for a given product.
	 *
	 * @param int|WC_Product $product The product ID or product object.
	 * @return string|false The shipping class slug or false on failure.
	 */
	public function get_product_shipping_class( $product ) {
		// If an ID is passed, get the product object
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		// If the product doesn't exist, return false.
		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		// Get the shipping class ID.
		$shipping_class_id = $product->get_shipping_class_id();

		// If there is no shipping class ID, return false.
		if ( empty( $shipping_class_id ) ) {
			return false;
		}

		// Get the shipping class term.
		$shipping_class_term = get_term( $shipping_class_id, 'product_shipping_class' );

		// Return the shipping class slug or false if not found.
		return ( ! is_wp_error( $shipping_class_term ) && $shipping_class_term ) ? $shipping_class_term->slug : false;
	}

	/**
	 * Retrieves the shipping zone ID from the 'shipping_zone' cookie.
	 *
	 * @return int|null The shipping zone ID or null if the cookie is not set or the value is invalid.
	 */
	public function get_shipping_zone_id_from_cookie() {
		// Retrieve the 'shipping_zone' cookie value using the KM_Cookie_Handler.
		$zone_id = isset( $_COOKIE['shipping_zone'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['shipping_zone'] ) ) : null;

		// Validate the zone ID to ensure it's a positive integer.
		$zone_id = is_numeric( $zone_id ) ? (int) $zone_id : null;

		// Return the zone ID if it is a valid number, null otherwise.
		return ( $zone_id > 0 ) ? $zone_id : null;
	}

	/**
	 * Gets the shipping zone name using the ID from the 'shipping_zone' cookie.
	 *
	 * @return string|null The name of the shipping zone or null if the zone does not exist.
	 */
	public function get_shipping_zone_name() {

		// First, get the shipping zone ID.
		$shipping_zone_id = $this->shipping_zone_id ?: $this->get_shipping_zone_id_from_cookie();

		// If no valid zone ID is found, return null.
		if ( null === $shipping_zone_id ) {
			return null;
		}

		// Get the shipping zone object by ID
		$shipping_zone = new WC_Shipping_Zone( $shipping_zone_id );

		// Check if the shipping zone ID is valid by checking if it's greater than 0
		if ( 0 === $shipping_zone->get_id() ) {
			return null;
		}

		// Return the shipping zone name
		return $shipping_zone->get_zone_name();
	}

	/**
	 * Checks if the current shipping zone is in the thirtheen.
	 *
	 * @return bool True if the shipping zone is in the thirtheen, false otherwise.
	 */

	public function is_in_thirteen() {

		$shipping_zone_id = $this->get_shipping_zone_id_from_cookie();

		if ( in_array( $shipping_zone_id, $this->zones_in_thirteen ) ) {
			global $km_is_in_thirteen;
			$km_is_in_thirteen = true;
			return true;
		}

		return false;
	}

	/**
	 * Obtient le nom du produit de livraison associé.
	 *
	 * @param WC_Product $product Le produit.
	 */
	public function get_related_shipping_product( $product ) {

		if ( ! $product ) {
			return;
		}

		// Obtenir la classe de livraison du produit.
		$shipping_class_id = $product->get_shipping_class_id();

		if ( ! $shipping_class_id ) {
			return;
		}

		// Récupérer l'objet de la classe de livraison.
		$shipping_class_term = get_term( $shipping_class_id, 'product_shipping_class' );

		if ( ! $shipping_class_term || is_wp_error( $shipping_class_term ) ) {
			return;
		}

		// Récupérer le nom de la classe de livraison.
		$shipping_class_name = $shipping_class_term->name;

		// Vérifier si $shipping_class_name contient '²' avant de le remplacer.
		if ( strpos( $shipping_class_name, '²' ) !== false ) {
			$shipping_class_name = str_replace( '²', '2', $shipping_class_name );
		}

		$shipping_product_name = $this->shipping_zone_name . ' ' . $shipping_class_name;

		// Récupérer le produit de livraison associé.
		$args = array(
			'fields'         => 'ids', // Ce qu'on demande à recupérer.
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
	 * Ajax callback to get the shipping zone ID from a zip code.
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

		$form_data  = $this->validate_postcode_form_data( $_POST );
		$found_zone = $this->get_shipping_zone_id_from_postcode( $form_data['zip_code'] );
		if ( $found_zone ) {
			wp_send_json_success( $found_zone );
		} else {
			wp_send_json_error( array( 'message' => 'Une erreur est survenue.' ) );
		}
	}

	/**
	 * Validate the postcode form data.
	 *
	 * @param array $data The form data.
	 * @return array The validated data.
	 */
	public function validate_postcode_form_data() {

		$postcode = isset( $_POST['zip'] ) && ! empty( $_POST['zip'] ) ? wp_unslash( $_POST['zip'] ) : '';
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
		error_log( $zone_id );

		if ( ! $zone_id ) {
			wp_send_json_error( array( 'message' => __( 'Aucune zone de livraison trouvée. Si ce code postal est bien le votre, veuillez contacter le service client.', 'kingmateriaux' ) ) );
		}

		$user_id = is_user_logged_in();

		if ( $user_id ) {
			update_user_meta( $user_id, 'shipping_postcode', $postcode );
			update_user_meta( $user_id, 'shipping_country', $country );
		}

		WC()->customer->set_shipping_postcode( wc_clean( $postcode ) );
		WC()->customer->set_billing_postcode( wc_clean( $postcode ) );

		return array(
			'zip_code' => $postcode,
			'country'  => $country,
		);
	}

	/**
	 * Gets the shipping zone ID from a postcode.
	 *
	 * @param string $postcode The zip code.
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
					list($start_zip, $end_zip) = explode( '...', $location->code );
					if ( $postcode >= $start_zip && $postcode <= $end_zip ) {
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
	 * Add custom fields to shipping zones.
	 *
	 * @param WC_Shipping_Zone $zone The shipping zone object.
	 */
	public function add_custom_shipping_zone_fields( $zone ) {
		$screen = get_current_screen();

		if ( 'woocommerce_page_wc-settings' !== $screen->id || ! isset( $_GET['zone_id'] ) || empty( $_GET['zone_id'] ) ) {
			return;
		}

		$zone_id = $_GET['zone_id'];

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
}
