<?php 

function km_register_account_widget( $widgets_manager ) {
    require_once __DIR__ . '/widget-account.php';

    $widgets_manager->register( new \Account_Widget() );
}
add_action( 'elementor/widgets/register', 'km_register_account_widget' );