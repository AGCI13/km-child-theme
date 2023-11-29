<?php

/**
 * Register a custom post type called "product".
 *
 * @see get_post_type_labels() for label keys.
 */
function km_create_uses_taxonomy() {
	if ( taxonomy_exists( 'uses' ) ) {
		return;
	}

	$labels = array(
		'name'              => _x( 'Utilisations', 'taxonomy general name', 'kingmateriaux' ),
		'singular_name'     => _x( 'Utilisation', 'taxonomy singular name', 'kingmateriaux' ),
		'search_items'      => __( 'Rechercher des utilisations', 'kingmateriaux' ),
		'all_items'         => __( 'Toutes les utilisations', 'kingmateriaux' ),
		'parent_item'       => __( 'Utilisation parente', 'kingmateriaux' ),
		'parent_item_colon' => __( 'Utilisation parente:', 'kingmateriaux' ),
		'edit_item'         => __( 'Éditer l’utilisation', 'kingmateriaux' ),
		'update_item'       => __( 'Mettre à jour l’utilisation', 'kingmateriaux' ),
		'add_new_item'      => __( 'Ajouter une nouvelle utilisation', 'kingmateriaux' ),
		'new_item_name'     => __( 'Nom de la nouvelle utilisation', 'kingmateriaux' ),
		'menu_name'         => __( 'Utilisations', 'kingmateriaux' ),
	);

	$args = array(
		'hierarchical'      => false,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'utilisation' ),
	);

	register_taxonomy( 'uses', array( 'product' ), $args );
}
add_action( 'init', 'km_create_uses_taxonomy' );

/**
 * Add a form field in the new term page for the 'uses' taxonomy
 *
 * @param mixed $taxonomy
 *
 * @return void
 */
function km_add_taxonomy_image_field( $taxonomy ) {
	?>
	<div class="form-field term-group-wrap">
		<label for="taxonomy-image-id"><?php esc_html_e( 'Image', 'kingmateriaux' ); ?></label>
		<input type="hidden" id="taxonomy-image-id" name="image_id" value="">
		<div id="taxonomy-image-wrapper"></div>
		<p>
			<input type="button" class="button button-secondary km_upload_image_button" value="<?php esc_html_e( 'Ajouter une image', 'kingmateriaux' ); ?>" />
			<input type="button" class="button button-secondary km_remove_image_button" value="<?php esc_html_e( 'Retirer l\'image', 'kingmateriaux' ); ?>" />
		</p>
	</div>
	<?php
}
add_action( 'uses_add_form_fields', 'km_add_taxonomy_image_field', 10, 1 );

/**
 * Add a form field in the new category page
 *
 * @param mixed $term
 * @param mixed $taxonomy
 */
function km_edit_taxonomy_image_field( $term ) {
	?>
	<tr class="form-field term-group-wrap">
		<th scope="row">
		<label for="taxonomy-image-id"><?php esc_html_e( 'Image', 'kingmateriaux' ); ?></label>
		</th>
		<td>
		<?php $image_id = get_term_meta( $term->term_id, 'image_id', true ); ?>
		<input type="hidden" id="taxonomy-image-id" name="image_id" value="<?php echo esc_attr( $image_id ); ?>">
		<div id="taxonomy-image-wrapper">
			<?php if ( $image_id ) : ?>
				<?php echo wp_get_attachment_image( $image_id, 'thumbnail' ); ?>
			<?php endif; ?>
		</div>
		<p>
			<input type="button" class="button button-secondary km_upload_image_button" value="<?php esc_html_e( 'Ajouter/Modifier l\'image', 'kingmateriaux' ); ?>" />
			<input type="button" class="button button-secondary km_remove_image_button" value="<?php esc_html_e( 'Retirer l\'image', 'kingmateriaux' ); ?>" />
		</p>
		</td>
	</tr>
	<?php
}
add_action( 'uses_edit_form_fields', 'km_edit_taxonomy_image_field', 10, 2 );

/**
 * Save the form field
 *
 * @param mixed $term_id
 * @param mixed $tt_id
 */
function km_save_taxonomy_image( $term_id, $tt_id ) {
	if ( isset( $_POST['image_id'] ) && '' !== $_POST['image_id'] ) {
		update_term_meta( $term_id, 'image_id', absint( $_POST['image_id'] ) );
	} else {
		update_term_meta( $term_id, 'image_id', '' );
	}
}
add_action( 'edit_uses', 'km_save_taxonomy_image', 10, 2 );
add_action( 'created_uses', 'km_save_taxonomy_image', 10, 2 );

/**
 * Enqueue the media uploader script
 */
function km_load_media() {
	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'km_load_media' );


/**
 * Enqueue the color picker script
 */
function km_add_script() {
	?>
	<script>
		jQuery(document).ready(function($) {
			function km_media_upload(button_class) {
				var _custom_media = true,
				_orig_send_attachment = wp.media.editor.send.attachment;

				$('body').on('click', button_class, function(e) {
					var button_id = '#' + $(this).attr('id');
					var send_attachment_bkp = wp.media.editor.send.attachment;
					var button = $(button_id);
					_custom_media = true;
					wp.media.editor.send.attachment = function(props, attachment){
						if (_custom_media) {
							$('#taxonomy-image-id').val(attachment.id);
							$('#taxonomy-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
							$('#taxonomy-image-wrapper .custom_media_image').attr('src',attachment.url).css('display','block');
						} else {
							return _orig_send_attachment.apply(button_id, [props, attachment]);
						}
					}
					wp.media.editor.open(button);
					return false;
				});
			}
			km_media_upload('.km_upload_image_button');
			$('.km_remove_image_button').on('click', function(){
				$('#taxonomy-image-id').val('');
				$('#taxonomy-image-wrapper').html('');
			});
		});
	</script>
	<?php
}
add_action( 'admin_footer', 'km_add_script' );
