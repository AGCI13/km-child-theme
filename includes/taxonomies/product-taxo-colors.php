<?php

/**
 * Register a custom post type called "product".
 *
 * @see get_post_type_labels() for label keys.
 */
function km_create_colors_taxonomy() {
	if ( taxonomy_exists( 'colors' ) ) {
		return;
	}

	$labels = array(
		'name'              => _x( 'Couleurs', 'taxonomy general name', 'kingmateriaux' ),
		'singular_name'     => _x( 'Couleur', 'taxonomy singular name', 'kingmateriaux' ),
		'search_items'      => __( 'Rechercher des couleurs', 'kingmateriaux' ),
		'all_items'         => __( 'Toutes les couleurs', 'kingmateriaux' ),
		'parent_item'       => __( 'Couleur parente', 'kingmateriaux' ),
		'parent_item_colon' => __( 'Couleur parente:', 'kingmateriaux' ),
		'edit_item'         => __( 'Éditer la couleur', 'kingmateriaux' ),
		'update_item'       => __( 'Mettre à jouor la couleur', 'kingmateriaux' ),
		'add_new_item'      => __( 'Ajouter une nouvelle couleur', 'kingmateriaux' ),
		'new_item_name'     => __( 'Nom de la nouvelle couleur', 'kingmateriaux' ),
		'menu_name'         => __( 'Couleurs', 'kingmateriaux' ),
	);

	$args = array(
		'hierarchical'      => false,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'couleur' ),
	);

	register_taxonomy( 'colors', array( 'product' ), $args );
}

add_action( 'init', 'km_create_colors_taxonomy' );


/**
 * Add a form field in the new category page
 *
 * @param mixed $term
 * @param mixed $taxonomy
 * @return void
 */
function km_add_colors_color_field() {
	?>
	<div class="form-field term-colorpicker-wrap">
		<label for="term-colorpicker"><?php esc_html_e( 'Couleur', 'kingmateriaux' ); ?></label>
		<input type="text" name="color" id="term-colorpicker" class="colorpicker" data-default-color="#ffffff" />
	</div>
	<?php
}
add_action( 'colors_add_form_fields', 'km_add_colors_color_field', 10, 1 );

/**
 * Add a form field in the new category page
 *
 * @param mixed $term
 * @param mixed $taxonomy
 */
function km_edit_colors_color_field( $term ) {
	// Récupérer la valeur actuelle du code couleur
	$color_value = get_term_meta( $term->term_id, 'color', true );
	?>
	<tr class="form-field term-colorpicker-wrap">
		<th scope="row"><label for="term-colorpicker"><?php esc_html_e( 'Couleur', 'kingmateriaux' ); ?></label></th>
		<td>
			<input type="text" name="color" id="term-colorpicker" value="<?php echo esc_attr( $color_value ); ?>" class="colorpicker" data-default-color="#ffffff" />
		</td>
	</tr>
	<?php
}
add_action( 'colors_edit_form_fields', 'km_edit_colors_color_field', 10, 2 );

/**
 * Save the form field
 *
 * @param mixed $term_id
 * @param mixed $tt_id
 */
function km_save_colors_color( $term_id, $tt_id ) {

	if ( isset( $_POST['color'] ) && ! empty( $_POST['color'] ) ) {
		update_term_meta( $term_id, 'color', sanitize_hex_color( $_POST['color'] ) );
	}
	else{
		update_term_meta( $term_id, 'color', '' );
	}
}
add_action( 'edit_colors', 'km_save_colors_color', 10, 2 );
add_action( 'created_colors', 'km_save_colors_color', 10, 2 );

/**
 * Enqueue the color picker script
 */
function km_load_color_picker( $hook_suffix ) {
	if ( 'colors' !== get_current_screen()->taxonomy ) {
		return;
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	add_action( 'admin_footer', 'km_color_picker_script' );
}
add_action( 'admin_enqueue_scripts', 'km_load_color_picker' );

/**
 * Enqueue the color picker script
 */
function km_color_picker_script() {
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.colorpicker').wpColorPicker();
		});
	</script>
	<?php
}

