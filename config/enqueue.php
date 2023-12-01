<?php

/**
 * Enqueue scripts and styles
 *
 * @return void
 */
function km_front_scripts_enqueue() {

	$stylesheet_uri  = get_stylesheet_directory_uri();
	$stylesheet_path = get_stylesheet_directory();
	$js_uri          = $stylesheet_uri . '/assets/js/';
	$css_uri         = $stylesheet_uri . '/assets/css/';
	$js_path         = $stylesheet_path . '/assets/js/';
	$css_path        = $stylesheet_path . '/assets/css/';

	wp_enqueue_style( 'km-child-style', $stylesheet_uri . '/style.css', array(), filemtime( $stylesheet_path . '/style.css' ), 'all' );

	wp_register_script( 'km-ajax-script', $js_uri . 'ajax.js', array(), filemtime( $js_path . 'ajax.js' ), false );
	wp_localize_script( 'km-ajax-script', 'km_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	wp_enqueue_script( 'km-ajax-script' );

	wp_enqueue_script( 'km-front-scripts', $js_uri . 'front.js', 'jquery', filemtime( $js_path . 'front.js' ), false, array() );

	wp_register_style( 'km-postcode-form-style', $css_uri . 'postcode-form.css', array(), filemtime( $css_path . 'postcode-form.css' ), 'all' );
	wp_register_script( 'km-postcode-form-script', $js_uri . 'postcode-form.js', array(), filemtime( $js_path . 'postcode-form.js' ), array( 'in_footer' => true ) );

	wp_register_style( 'km-tonnage-calculator-style', $css_uri . 'tonnage-calculator.css', array(), filemtime( $css_path . 'tonnage-calculator.css' ), 'all' );
	wp_register_script( 'km-tonnage-calculator-script', $js_uri . 'tonnage-calculator.js', array( 'jquery' ), filemtime( $js_path . 'tonnage-calculator.js' ), false );

	wp_register_style( 'km-product-filters-style', $css_uri . 'product-filters.css', array(), filemtime( $css_path . 'product-filters.css' ), 'all' );
	wp_register_script( 'km-product-filters-script', $js_uri . 'product-filters.js', array(), filemtime( $js_path . 'product-filters.js' ), false );

	wp_enqueue_style( 'km-cart-style', $css_uri . 'cart.css', array(), filemtime( $css_path . 'cart.css' ), 'all' );
	wp_enqueue_script( 'km-cart-script', $js_uri . 'cart.js', array(), filemtime( $js_path . 'cart.js' ), array( 'in_footer' => true ) );
	wp_localize_script( 'km-cart-script', 'themeObject', array( 'themeUrl' => get_stylesheet_directory_uri() ) );

	wp_register_style( 'km-archive-product-style', $css_uri . 'product-archive.css', array(), filemtime( $css_path . 'product-archive.css' ), 'all' );
	wp_enqueue_script( 'km-archive-product-script', $js_uri . '/product-archive.js', array( 'jquery' ), filemtime( $js_path . 'product-archive.js' ), false );

	wp_register_script( 'add-to-cart-confirmation', $js_uri . 'add-to-cart-confirmation.js', array(), filemtime( $js_path . 'add-to-cart-confirmation.js' ), false );

	if ( is_checkout() ) {
		wp_enqueue_style( 'km-checkout-style', $css_uri . 'checkout.css', array(), filemtime( $css_path . 'checkout.css' ), 'all' );
		wp_enqueue_script( 'km-checkout-script', $js_uri . 'checkout.js', array( 'jquery' ), filemtime( $js_path . 'checkout.js' ), false );
		//  wp_enqueue_script( 'km-checkout-jquery-script', 'https://code.jquery.com/jquery-3.7.1.min.js' );
		// wp_enqueue_script( 'km-checkout-jquery-ui-script', 'https://code.jquery.com/ui/1.13.1/jquery-ui.min.js' );
	}
}
add_action( 'wp_enqueue_scripts', 'km_front_scripts_enqueue' );

/**
 * Enqueue admin scripts
 *
 * @return void
 */
function km_admin_scripts_enqueue() {
	$admin_stylesheet_path = get_stylesheet_directory() . '/assets/css/';
	$admin_stylesheet_uri  = get_stylesheet_directory_uri() . '/assets/css/';

	if ( file_exists( $admin_stylesheet_path ) ) {
		wp_enqueue_style( 'km-admin-style', $admin_stylesheet_uri . 'admin-style.css', array(), filemtime( $admin_stylesheet_path . 'admin-style.css' ), 'all' );
	}
}
add_action( 'admin_enqueue_scripts', 'km_admin_scripts_enqueue' );
