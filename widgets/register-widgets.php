<?php

/**
 * Register widgets
 *
 * @param \Elementor\Widgets_Manager $widgets_manager
 */
function km_register_elementor_widgets( $widgets_manager ) {
	// Récupère tous les fichiers PHP qui commencent par 'widget-' dans le répertoire courant.
	$widget_files = glob( __DIR__ . '/*-widget.php' );

	foreach ( $widget_files as $file ) {
		// Vérifie que le fichier commence bien par 'widget-' et a l'extension '.php'.
			require_once $file;

			// Construit le nom de la classe à partir du nom du fichier.
			$class_name = str_replace( ' ', '_', ucwords( str_replace( '-', ' ', basename( $file, '.php' ) ) ) );

			// Assurez-vous que la classe existe avant de l'enregistrer.
		if ( class_exists( $class_name ) ) {
			$widgets_manager->register( new $class_name() );
		}
	}
}
add_action( 'elementor/widgets/register', 'km_register_elementor_widgets' );

/**
 * Add categories to Elementor widgets
 *
 * @param \Elementor\Elements_Manager $elements_manager
 */
function km_add_elementor_widget_categories( $elements_manager ) {

	$elements_manager->add_category(
		'kingmateriaux',
		array(
			'title' => esc_html__( 'Kingmatériaux', 'textdomain' ),
			'icon'  => 'fa fa-plug',
		)
	);
}
add_action( 'elementor/elements/categories_registered', 'km_add_elementor_widget_categories' );
