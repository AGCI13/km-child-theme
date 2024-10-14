<?php

/**
 * Ajoute un champ personnalisé au profil utilisateur.
 *
 * @param WP_User $user L'utilisateur.
 *
 * @return void
 */
function km_add_custom_user_profile_fields( $user ) {
	?>
<table id="user-note-table" class="form-table">
    <tr>
        <th>
            <label for="user_note"><?php esc_html_e( 'Note client', 'kingmateriaux' ); ?></label>
        </th>
        <td>
            <textarea name="user_note" id="user_note" rows="5"
                cols="30"><?php echo esc_textarea( get_user_meta( $user->ID, 'user_note', true ) ); ?></textarea><br />
            <span
                class="description"><?php esc_html_e( 'Ajoutez une note pour cet utilisateur.', 'kingmateriaux' ); ?></span>
        </td>
    </tr>
    <tr>
        <th><label for="birthday"><?php esc_html_e( 'Date d\'anniversaire', 'kingmateriaux' ); ?></label></th>
        <td>
            <input type="date" name="birthday" id="birthday"
                value="<?php echo esc_attr( get_user_meta( $user->ID, 'birthday', true ) ); ?>" class="regular-text" />
        </td>
    </tr>
</table>
<?php
}
add_action( 'show_user_profile', 'km_add_custom_user_profile_fields' );
add_action( 'edit_user_profile', 'km_add_custom_user_profile_fields' );

/**
 * Enregistre les champs personnalisés du profil utilisateur.
 *
 * @param int $user_id L'ID de l'utilisateur.
 * @return bool
 */
function km_save_custom_user_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}
	if ( isset( $_POST['user_note'] ) ) {
		update_user_meta( $user_id, 'user_note', sanitize_textarea_field( $_POST['user_note'] ) );
	}
	if ( isset( $_POST['birthday'] ) ) {
		update_user_meta( $user_id, 'birthday', sanitize_text_field( $_POST['birthday'] ) );
	}
	return true;
}
add_action( 'personal_options_update', 'km_save_custom_user_profile_fields' );
add_action( 'edit_user_profile_update', 'km_save_custom_user_profile_fields' );