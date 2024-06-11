<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handle user session and redirects based on login status and user role.
 */
function km_handle_user_redirects() {
	if ( wp_doing_ajax() || current_user_can( 'manage_options' ) ) {
		return;
	}

	$terms      = array();
	$term_array = get_the_terms( get_the_ID(), 'page-type' );

	if ( ! empty( $term_array ) && is_array( $term_array ) ) {
		foreach ( $term_array as $term ) {
			$terms[] = $term->slug;
		}
	}

	if ( ! is_user_logged_in() && ! ( in_array( 'public', $terms, true ) || in_array( 'auth', $terms, true ) ) ) {
		// Unlogged users can access public and auth pages
		wp_safe_redirect( home_url() );
		exit;
	} elseif ( is_user_logged_in() && in_array( 'auth', $terms, true ) ) {
		// Logged users can access public and private pages
		$permalink = get_permalink( 6 );
		wp_safe_redirect( $permalink );
		exit;
	}
}
add_action( 'template_redirect', 'km_handle_user_redirects' );
