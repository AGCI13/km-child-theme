<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

add_action( 'wp_footer', 'km_load_add_to_cart_modal_template' );
function km_load_add_to_cart_modal_template() {
    if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
        ob_start(); // Démarre la mise en mémoire tampon
        include get_stylesheet_directory() . '/templates/add-to-cart-modal.php';
        echo ob_get_clean();
    }
}

add_action( 'wp_ajax_add_to_cart_validation', 'km_add_to_cart_validation' );
add_action( 'wp_ajax_nopriv_add_to_cart_validation', 'km_add_to_cart_validation' );
function km_add_to_cart_validation() {
    $nonce_value = isset( $_POST['nonce_cart_validation'] ) && !empty( $_POST['nonce_cart_validation'] ) ? wp_unslash( $_POST['nonce_cart_validation'] ) : '';
    $nonce_value = sanitize_text_field( $nonce_value );

    if ( !wp_verify_nonce( $nonce_value, 'add_to_cart_validation' ) ) {
        wp_send_json_error( array( 'message' => 'La vérification du nonce a échoué.' ) );
    }

    if ( !isset( $_POST['condition_access'] ) || !isset( $_POST['condition_unload'] ) ) {
        wp_send_json_error( __( 'Veuillez confirmer les conditions d\'accès au chantier et de déchargement.', 'kingmateriaux' ) );
    }

    wp_send_json_success( 'ok' );

    //TODO: stocker les conditions dans la session
    // try {
    //     // update_session_data( 'access_condition', $access_condition );
    //     // update_session_data( 'unload_condition', $access_condition );
    //     //transient ?

    // } catch ( Exception $e ) {
    //     wp_send_json_error( array( 'message' => $e->getMessage() ) );
    // }
}
