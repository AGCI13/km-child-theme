<?php

// Create a class to handle your sessions.
class WC_Sessions_Manager {

	public function __construct() {
		add_action( 'woocommerce_init', array( $this, 'initiate_wc_sessions' ) );
		add_action( 'raja_aman_remove_session', array( $this, 'delete_wc_sessions' ), 10, 1 );
	}

	public function initiate_wc_sessions() {

		if ( is_user_logged_in() || is_admin() ) {
			return;
		}

		if ( isset( WC()->session ) ) {
			if ( ! WC()->session->has_session() ) {
				WC()->session->set_customer_session_cookie( true );
			}
		}
	}

	public function delete_wc_sessions( $session_key = '' ) {

		if ( ! empty( $session_key ) ) {
			WC()->session->set( $session_key, null );
		}
	}

	public function store_wc_sessions( $values = array( 1, 2, 3, 4, 5 ) ) {

		if ( empty( WC()->session->get( 'my-session' ) ) ) {
			WC()->session->set( 'my-session', $values );

		} else {

			// Remains the previously stored values and insert new one

			$values = WC()->session->get( 'my-session' );

			$new_values = array( 10, 11, 12, 13, 14 );

			array_push( $values, $new_values );

			WC()->session->set( 'my-session', $values );
		}

		// Use action hook like this to empty the session

		do_action( 'raja_aman_remove_session', 'my-session' );

		// Or simply do it.

		WC()->session->set( 'my-session', null );
	}
}
