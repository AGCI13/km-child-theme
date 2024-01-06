<?php

function km_add_custom_user_profile_fields( $user ) {
	?>
	<table id="user-note-table" class="form-table">
		<tr>
			<th>
				<label for="user_note"><?php _e( 'Note client', 'kingmateriaux' ); ?></label>
			</th>
			<td>
				<textarea name="user_note" id="user_note" rows="5" cols="30"><?php echo esc_attr( get_the_author_meta( 'user_note', $user->ID ) ); ?></textarea><br />
				<span class="description"><?php _e( 'Ajoutez une note pour cet utilisateur.', 'kingmateriaux' ); ?></span>
			</td>
		</tr>
	</table>
	<?php
}

add_action( 'show_user_profile', 'km_add_custom_user_profile_fields' );
add_action( 'edit_user_profile', 'km_add_custom_user_profile_fields' );


function km_save_custom_user_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	update_user_meta( $user_id, 'user_note', $_POST['user_note'] );
}

add_action( 'personal_options_update', 'km_save_custom_user_profile_fields' );
add_action( 'edit_user_profile_update', 'km_save_custom_user_profile_fields' );
