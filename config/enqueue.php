<?php

function km_front_scripts_enqueue() {

    $stylesheet_uri  = get_stylesheet_directory_uri();
    $stylesheet_path = get_stylesheet_directory();
    $js_uri          = $stylesheet_uri . '/assets/js/';
    $css_uri         = $stylesheet_uri . '/assets/css/';
    $js_path         = $stylesheet_path . '/assets/js/';
    $css_path        = $stylesheet_path . '/assets/css/';

    wp_enqueue_style( 'km-child-style', $stylesheet_uri . '/style.css', array(), filemtime( $stylesheet_path . '/style.css' ), 'all' );

    //Required before other scripts
    wp_register_script( 'km-ajax-script', $js_uri . 'ajax.js', array(), filemtime( $js_path . 'ajax.js' ), false );
    wp_localize_script( 'km-ajax-script', 'km_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    wp_enqueue_script( 'km-ajax-script' );

    wp_enqueue_script( 'km-front-scripts', $js_uri . 'front.js', 'jquery', filemtime( $js_path . 'front.js' ), false, array() );
    wp_register_script( 'km-footer-scripts', $js_uri . 'footer.js', 'jquery', filemtime( $js_path . 'footer.js' ), true, array() );

    wp_register_style( 'km-postcode-form-style', $css_uri . 'postcode-form.css', array(), filemtime( $css_path . 'postcode-form.css' ), 'all' );
    wp_enqueue_script( 'km-postcode-form-script', $js_uri . 'postcode-form.js', array(), filemtime( $js_path . 'postcode-form.js' ), false );

    wp_register_style( 'km-tonnage-calculator-style', $css_uri . 'tonnage-calculator.css', array(), filemtime( $css_path . 'tonnage-calculator.css' ), 'all' );
    wp_register_script( 'km-tonnage-calculator-script', $js_uri . 'tonnage-calculator.js', array( 'jquery' ), filemtime( $js_path . 'tonnage-calculator.js' ), false );

    if ( is_archive() ) {
        wp_enqueue_style( 'km-product-archive-style', $css_uri . 'product-archive.css', array(), filemtime( $css_path . 'product-archive.css' ), 'all' );
        wp_enqueue_script( 'km-product-archive-script', $js_uri . 'product-archive.js', array( 'jquery' ), filemtime( $js_path . 'product-archive.js' ), false );
    }

    if ( is_woocommerce() ) {
        wp_enqueue_script( 'km-add-to-cart-modal-script', $js_uri . 'add-to-cart-modal.js', array(), filemtime( $js_path . 'add-to-cart-modal.js' ), array( 'in_footer' => true ) );
        wp_enqueue_style( 'km-add-to-cart-modal-style', $css_uri . 'add-to-cart-modal.css', array(), filemtime( $css_path . 'add-to-cart-modal.css' ), 'all' );
    }

    if ( is_checkout() ) {
        wp_enqueue_script( 'km-checkout-script', $js_uri . 'checkout.js', array( 'jquery' ), filemtime( $js_path . 'checkout.js' ), false );
    }
}
add_action( 'wp_enqueue_scripts', 'km_front_scripts_enqueue' );

function km_admin_scripts_enqueue() {
    $admin_stylesheet_path = get_stylesheet_directory() . '/assets/css/';
    $admin_stylesheet_uri  = get_stylesheet_directory_uri() . '/assets/css/';

    if ( file_exists( $admin_stylesheet_path ) ) {
        wp_enqueue_style( 'km-admin-style', $admin_stylesheet_uri . 'admin-style.css', array(), filemtime( $admin_stylesheet_path . 'admin-style.css' ), 'all' );
    }
}
add_action( 'admin_enqueue_scripts', 'km_admin_scripts_enqueue' );

