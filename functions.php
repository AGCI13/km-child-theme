<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Affiche un message d'erreur admin si une dépendance est manquante et ne charge pas les fichiers du thème.
 *
 * @param string $dependency Le nom de la dépendance requise.
 */
function display_admin_dependency_error( $dependency ) {
	$message = sprintf(
		'<div class="error"><p><strong>%s</strong>: %s</p></div>',
		esc_html__( 'Attention', 'kingmateriaux' ),
		esc_html__( sprintf( '%s est requis pour que ce thème personnalisé fonctionne.', $dependency ), 'kingmateriaux' )
	);

	echo wp_kses(
		$message,
		array(
			'div'    => array( 'class' => array() ),
			'p'      => array(),
			'strong' => array(),
		)
	);
}

if ( ! class_exists( 'WooCommerce' ) ) {
	add_action(
		'admin_notices',
		function () {
			display_admin_dependency_error( 'WooCommerce' );
		}
	);
	return;
}

if ( ! class_exists( 'ACF' ) ) {
	add_action(
		'admin_notices',
		function () {
			display_admin_dependency_error( 'Advanced Custom Field PRO (ACF)' );
		}
	);
	return;
}

// Importation des fichiers.
require_once 'enqueue.php';

require_once 'widgets/register-widgets.php';

require_once 'includes/marketing-ops-functions.php';

require_once 'includes/taxonomies/product-taxo-colors.php';
require_once 'includes/taxonomies/product-taxo-uses.php';

add_action(
	'woocommerce_init',
	function () {
		require_once 'includes/wc-common-functions.php';
		require_once 'includes/wc-cart-functions.php';
		require_once 'includes/wc-order-functions.php';
		require_once 'includes/wc-product-functions.php';
		require_once 'includes/wc-product-archive-functions.php';
		require_once 'includes/wc-checkout-functions.php';
		require_once 'includes/wc-my-account-functions.php';
		require_once 'includes/wp-users-functions.php';

		require_once 'includes/class-singleton-trait.php';
		require_once 'includes/class-dynamic-pricing.php';
		require_once 'includes/class-shipping-zone.php';
		require_once 'includes/class-order-processing.php';
		require_once 'includes/class-palletization-manager.php';

		require_once 'includes/class-shipping-methods.php';
		require_once 'includes/shipping-methods/class-shipping-method-1.php';
		require_once 'includes/shipping-methods/class-shipping-method-1-express.php';
		require_once 'includes/shipping-methods/class-shipping-method-2.php';
		require_once 'includes/shipping-methods/class-shipping-method-2-express.php';
		require_once 'includes/shipping-methods/class-shipping-method-drive.php';
		require_once 'includes/shipping-methods/class-shipping-method-out-13.php';
		require_once 'includes/shipping-methods/class-shipping-method-dumpster.php';
		require_once 'includes/shipping-methods/class-shipping-method-included.php';

		// Initialisation des classes.
		KM_Shipping_Zone::get_instance();
		KM_Dynamic_Pricing::get_instance();
		KM_Shipping_Methods::get_instance();
		KM_Order_Processing::get_instance();
		KM_Palletization_Manager::get_instance();
	}
);
