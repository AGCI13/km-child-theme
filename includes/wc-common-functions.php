<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function km_wc_customer_session_enabler() {
	if ( ! is_admin() && isset( WC()->session ) && ! WC()->session->has_session() ) {
		WC()->session->set_customer_session_cookie( true );
	}
}
add_action( 'woocommerce_init', 'km_wc_customer_session_enabler' );

// function search_filter( $query ) {
// if ( ! is_admin() && $query->is_main_query() ) {
// if ( $query->is_search ) {
// $query->set( 'post_type', 'product' ); // Limiter la recherche aux produits
// $query->set( 'posts_per_page', 10 ); // Nombre de produits à afficher
// }
// }
// return $query;
// }
// add_action( 'pre_get_posts', 'search_filter' );

// function search_by_title_only( $search, $wp_query ) {
// global $wpdb;
// if ( ! empty( $search ) && ! is_admin() && $query->is_search && $query->is_main_query() ) {
// $q      = $wp_query->query_vars;
// $n      = ! empty( $q['exact'] ) ? '' : '%';
// $search = $searchand = '';
// foreach ( (array) $q['search_terms'] as $term ) {
// $term      = esc_sql( $wpdb->esc_like( $term ) );
// $search   .= "{$searchand}($wpdb->posts.post_title LIKE '{$n}{$term}{$n}')";
// $searchand = ' AND ';
// }
// if ( ! empty( $search ) ) {
// $search = " AND ({$search}) ";
// if ( ! is_user_logged_in() ) {
// $search .= " AND ($wpdb->posts.post_password = '') ";
// }
// }
// }
// return $search;
// }
// add_filter( 'posts_search', 'search_by_title_only', 500, 2 );
