<?php

/**
 * Register widgets
 *
 * @param \Elementor\Widgets_Manager $widgets_manager The widgets manager.
 *
 * @return void
 */
function km_register_elementor_widgets( $widgets_manager ) {

	$widget_files = glob( __DIR__ . '/*-widget.php' );

	foreach ( $widget_files as $file ) {

			require_once $file;
			$class_name = str_replace( ' ', '_', ucwords( str_replace( '-', ' ', basename( $file, '.php' ) ) ) );

		if ( class_exists( $class_name ) ) {
			$widgets_manager->register( new $class_name() );
		}
	}
}
add_action( 'elementor/widgets/register', 'km_register_elementor_widgets' );

/**
 * Add categories to Elementor widgets
 *
 * @param \Elementor\Elements_Manager $elements_manager The elements manager.
 *
 * @return void
 */
function km_add_elementor_widget_categories( $elements_manager ) {

	$elements_manager->add_category(
		'kingmateriaux',
		array(
			'title' => esc_html__( 'Kingmatériaux', 'kingmateriaux' ),
			'icon'  => 'fa fa-plug',
		)
	);
}
add_action( 'elementor/elements/categories_registered', 'km_add_elementor_widget_categories' );
