<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'config/enqueue.php';

require_once 'widgets/register-widgets.php';

require_once 'includes/taxonomies/product-taxo-colors.php';
require_once 'includes/taxonomies/product-taxo-uses.php';

require_once 'includes/wc-cart-functions.php';
require_once 'includes/wc-order-functions.php';
require_once 'includes/wc-product-functions.php';
require_once 'includes/wc-product-archive-functions.php';
require_once 'includes/wc-checkout-functions.php';
require_once 'includes/wc-my-account-functions.php';

require_once 'includes/class-singleton-trait.php';
require_once 'includes/class-dynamic-pricing.php';
require_once 'includes/class-shipping-zone.php';
require_once 'includes/class-order-processing.php';
require_once 'includes/class-palletization-manager.php';
require_once 'includes/class-big-bag-manager.php';

KM_Shipping_Zone::get_instance();
KM_Dynamic_Pricing::get_instance();
KM_Order_Processing::get_instance();
KM_Palletization_Manager::get_instance();
KM_Big_Bag_Manager::get_instance();

/**
 * Include your shipping file.
 */
function km_include_shipping_methods() {
	require_once 'includes/class-shipping-methods.php';
	require_once 'includes/shipping-methods/class-shipping-method-1.php';
	require_once 'includes/shipping-methods/class-shipping-method-1-express.php';
	require_once 'includes/shipping-methods/class-shipping-method-2.php';
	require_once 'includes/shipping-methods/class-shipping-method-2-express.php';
	require_once 'includes/shipping-methods/class-shipping-method-drive.php';
	KM_Shipping_Methods::get_instance();
}
add_action( 'woocommerce_shipping_init', 'km_include_shipping_methods' );
