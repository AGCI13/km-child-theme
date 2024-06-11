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

	wp_enqueue_style( 'km-common-style', $css_uri . 'common.min.css', array(), filemtime( $css_path . 'common.min.css' ), 'all' );

	wp_register_script( 'km-ajax-script', $js_uri . 'ajax.min.js', array(), filemtime( $js_path . 'ajax.min.js' ), true );
	wp_localize_script( 'km-ajax-script', 'km_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	wp_enqueue_script( 'km-ajax-script' );

	wp_enqueue_script(
		'km-front-scripts',
		$js_uri . 'front.min.js',
		array( 'jquery' ),
		filemtime( $js_path . 'front.min.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	wp_enqueue_style( 'km-postcode-form-style', $css_uri . 'postcode-form.min.css', array(), filemtime( $css_path . 'postcode-form.min.css' ), 'all' );
	wp_enqueue_script(
		'km-postcode-form-script',
		$js_uri . 'postcode-form.min.js',
		array(),
		filemtime( $js_path . 'postcode-form.min.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	if ( is_page( 'se-connecter' ) ) {
		wp_enqueue_script(
			'km-registration-script',
			$js_uri . 'registration.min.js',
			array( 'jquery' ),
			filemtime( $js_path . 'registration.min.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	wp_register_style( 'km-tonnage-calculator-style', $css_uri . 'tonnage-calculator.min.css', array(), filemtime( $css_path . 'tonnage-calculator.min.css' ), 'all' );
	wp_register_script(
		'km-tonnage-calculator-script',
		$js_uri . 'tonnage-calculator.min.js',
		array( 'jquery' ),
		filemtime( $js_path . 'tonnage-calculator.min.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	if ( is_product() ) {
		wp_enqueue_style( 'km-product-single-style', $css_uri . 'product-single.min.css', array(), filemtime( $css_path . 'product-single.min.css' ), 'all' );
		wp_enqueue_script( 'km-product-single-script', $js_uri . 'product-single.min.js', array(), filemtime( $js_path . 'product-single.min.js' ), true );

	}

	wp_register_script(
		'add-to-cart-confirmation',
		$js_uri . 'add-to-cart-confirmation.min.js',
		array(),
		filemtime( $js_path . 'add-to-cart-confirmation.min.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	if ( is_product_category() || is_search() ) {
		wp_register_style( 'km-product-filters-style', $css_uri . 'product-filters.min.css', array(), filemtime( $css_path . 'product-filters.min.css' ), 'all' );
		wp_register_script(
			'km-product-filters-script',
			$js_uri . 'product-filters.min.js',
			array(),
			filemtime( $js_path . 'product-filters.min.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_enqueue_style( 'km-archive-product-style', $css_uri . 'product-archive.min.css', array(), filemtime( $css_path . 'product-archive.min.css' ), 'all' );
		wp_enqueue_script(
			'km-archive-product-script',
			$js_uri . 'product-archive.min.js',
			array( 'jquery' ),
			filemtime( $js_path . 'product-archive.min.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	if ( is_cart() || is_checkout() ) {
		wp_enqueue_style( 'km-cart-style', $css_uri . 'cart.min.css', array(), filemtime( $css_path . 'cart.min.css' ), 'all' );
		wp_enqueue_script(
			'km-cart-script',
			$js_uri . 'cart.min.js',
			array(),
			filemtime( $js_path . 'cart.min.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_localize_script( 'km-cart-script', 'themeObject', array( 'themeUrl' => get_stylesheet_directory_uri() ) );
	}

	if ( is_checkout() || is_page( 49 ) ) {
		wp_enqueue_style( 'km-checkout-style', $css_uri . 'checkout.min.css', array(), filemtime( $css_path . 'checkout.min.css' ), 'all' );
	}

	if ( is_checkout() ) {
		wp_enqueue_script(
			'km-registration-script',
			$js_uri . 'registration.min.js',
			array( 'jquery' ),
			filemtime( $js_path . 'registration.min.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_enqueue_style( 'km-datetimepicker-style', $css_uri . 'datetimepicker.min.css', array(), filemtime( $css_path . 'datetimepicker.min.css' ), 'all' );
		wp_enqueue_script(
			'km-checkout-script',
			$js_uri . 'checkout.min.js',
			array( 'jquery' ),
			filemtime( $js_path . 'checkout.min.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'km_front_scripts_enqueue' );

/**
 * Enqueue admin scripts
 *
 * @return void
 */
function km_admin_scripts_enqueue( $hook ) {
	global $post;
	$stylesheet_uri  = get_stylesheet_directory_uri();
	$stylesheet_path = get_stylesheet_directory();
	$js_uri          = $stylesheet_uri . '/assets/js/';
	$css_uri         = $stylesheet_uri . '/assets/css/';
	$js_path         = $stylesheet_path . '/assets/js/';
	$css_path        = $stylesheet_path . '/assets/css/';

	wp_enqueue_style( 'km-admin-style', $css_uri . 'admin-style.min.css', array(), filemtime( $css_path . 'admin-style.min.css' ), 'all' );

	if ( 'woocommerce_page_wc-settings' === $hook ) {

		if ( isset( $_GET['section'] ) && 'drive' === $_GET['section'] ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'km-drive-calendar-script', $js_uri . 'drive-calendar.min.js', array( 'jquery' ), filemtime( $js_path . 'drive-calendar.min.js' ), false );
		}

		if ( isset( $_GET['zone_id'] ) && ! empty( $_GET['zone_id'] ) ) {
			wp_enqueue_script( 'km-shipping-zone-script', $js_uri . 'shipping-zone.min.js', array( 'jquery' ), filemtime( $js_path . 'shipping-zone.min.js' ), false );
		}
	}

	if ( 'post.php' == $hook || 'post-new.php' == $hook ) {
		if ( 'shop_order' === $post->post_type ) {
			wp_enqueue_script( 'km-orders-script', $js_uri . 'wc-orders.min.js', array(), filemtime( $js_path . 'wc-orders.min.js' ), true );
		}

		if ( 'product' === $post->post_type ) {
			wp_enqueue_script( 'wc-product-admin', $js_uri . 'wc-product-admin.min.js', array(), filemtime( $js_path . 'wc-product-admin.min.js' ), true );
		}
	}

	wp_register_script( 'google-shopping-script', $js_uri . 'google-shopping.min.js', array(), filemtime( $js_path . 'google-shopping.min.js' ), false );
	wp_register_script( 'km-ajax-script', $js_uri . 'ajax.min.js', array(), filemtime( $js_path . 'ajax.min.js' ), false );
	wp_localize_script( 'km-ajax-script', 'km_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	wp_enqueue_script( 'km-ajax-script' );

	wp_enqueue_script( 'km-transporter-script', $js_uri . 'shop-orders.min.js', array( 'jquery' ), filemtime( $js_path . 'shop-orders.min.js' ), true );

	$field_object = get_field_object( 'field_6536a052fb38f' );
	$transporters = $field_object['choices'] ?? array();

	wp_localize_script( 'km-transporter-script', 'transportersData', $transporters );
}
add_action( 'admin_enqueue_scripts', 'km_admin_scripts_enqueue' );
