<?php

function km_front_scripts_enqueue() {

    $stylesheet_dir = get_stylesheet_directory_uri();
    $js_dir         = $stylesheet_dir . '/assets/js/';
    $css_dir        = $stylesheet_dir . '/assets/css/';

    wp_enqueue_style( 'km-child-style', $stylesheet_dir . '/style.css', array(), '1.0', 'all' );

    wp_enqueue_script( 'km-front-scripts', $js_dir . 'front.js', 'jquery', '1.0', false, array() );
    wp_enqueue_script( 'km-footer-scripts', $js_dir . 'footer.js', 'jquery', '1.0', true, array() );
    wp_localize_script( 'km-footer', 'frontend_ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

    wp_register_style( 'km-header-postcode-style', $css_dir . 'header-cp.css', array(), '1.0', 'all' );
    wp_register_script( 'km-header-postcode-script', $js_dir . 'header-postcode.js', array( 'jquery' ), '1.0', false );
    wp_register_script( 'km-header-postcode-mobile-script', $js_dir . 'header-postcode-mobile.js', array( 'jquery' ), '1.0', false );

    wp_register_style( 'km-tonnage-calculator-style', $css_dir . 'tonnage-calculator.css', array(), '1.0', 'all' );
    wp_register_script( 'km-tonnage-calculator-script', $js_dir . 'tonnage-calculator.js', array( 'jquery' ), '1.0', false );

    if ( is_product_category() || is_archive() ) {
        wp_enqueue_style( 'km-product-archive-style', $css_dir . 'product-archive.css', array(), '1.0', 'all' );
        wp_enqueue_script( 'km-product-archive-script', $js_dir . 'product-archive.js', array( 'jquery' ), '1.0', false );
    }

    if ( is_checkout() ) {
        wp_enqueue_script( 'km-checkout-script', $js_dir . 'checkout.js', array( 'jquery' ), '1.0', false );
    }
}
add_action( 'wp_enqueue_scripts', 'km_front_scripts_enqueue' );

function km_admin_scripts_enqueue() {
    $admin_stylesheet_path = get_stylesheet_directory() . '/assets/css/admin-style.css';

    if ( file_exists( $admin_stylesheet_path ) ) {
        wp_enqueue_style( 'km-admin-style', $admin_stylesheet_path, array(), '1.0', 'all' );
    }
}
add_action( 'admin_enqueue_scripts', 'km_admin_scripts_enqueue' );


