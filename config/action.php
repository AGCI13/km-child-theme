<?php

// Register new status
function register_sav_order_status()
{
    register_post_status('wc-sav', array(
        'label'                     => 'En cours de SAV',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('En cours de SAV (%s)', 'En cours de SAV (%s)')
    ));
}
add_action('init', 'register_sav_order_status');


add_filter('woocommerce_persistent_cart_enabled', 'disable_persistent_cart_for_logged_in_users');
function disable_persistent_cart_for_logged_in_users($enabled)
{
    return false;
}


function custom_elementor_archive_posts_query($query) {
    if ( is_admin() || !$query->is_main_query() ) {
        return;
    }

    if ( is_search() ) {
        $query->set( 'post_type', 'product' );
    }
}
add_action( 'pre_get_posts', 'custom_elementor_archive_posts_query' );