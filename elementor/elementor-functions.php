<?php
/**
 * Fonctions du thème.
 *
 * @package kingmateriaux
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_filter( 'uael_registration_form_fields', 'add_tva_field' );
function add_tva_field( $fields ) {
	$fields['tva'] = __( 'TVA', 'uael' );
	return $fields;
}

add_action( 'elementor_pro/forms/render_item', 'render_tva_field', 10, 3 );

function render_tva_field( $item, $item_index, $form ) {
	if ( 'tva' === $item['field_type'] ) {
		$form->add_render_attribute(
			'input' . $item_index,
			array(
				'type'        => 'text',
				'name'        => $item['field_type'],
				'id'          => 'form-field-' . $item['field_type'],
				'class'       => 'elementor-field elementor-size-' . $form->get_settings( 'input_size' ),
				'placeholder' => $item['placeholder'],
			)
		);

		?>
		<div class="elementor-field-group elementor-column elementor-field-type-<?php echo esc_attr( $item['field_type'] ); ?>">
			<label for="form-field-<?php echo esc_attr( $item['field_type'] ); ?>" class="elementor-field-label"><?php echo esc_html( $item['field_label'] ); ?></label>
			<input <?php echo $form->get_render_attribute_string( 'input' . $item_index ); ?>>
		</div>
		<?php
	}
}

// Valider le champ TVA
add_action( 'elementor_pro/forms/validation', 'validate_tva_field', 10, 2 );

function validate_tva_field( $record, $handler ) {
	$form_name = $record->get_form_settings( 'form_name' );

	// Vérifiez le nom du formulaire pour appliquer la validation uniquement au formulaire d'inscription
	if ( 'User Registration Form' === $form_name ) {
		$raw_fields = $record->get( 'fields' );

		foreach ( $raw_fields as $field ) {
			if ( 'tva' === $field['id'] && empty( $field['value'] ) ) {
				$handler->add_error( $field['id'], __( 'TVA number is required.', 'uael' ) );
			}
		}
	}
}

// Sauvegarder le champ TVA
add_action( 'elementor_pro/forms/new_record', 'save_tva_field', 10, 2 );

function save_tva_field( $record, $handler ) {
	$form_name = $record->get_form_settings( 'form_name' );

	// Vérifiez le nom du formulaire pour appliquer la sauvegarde uniquement au formulaire d'inscription
	if ( 'User Registration Form' === $form_name ) {
		$raw_fields = $record->get( 'fields' );

		foreach ( $raw_fields as $field ) {
			if ( 'tva' === $field['id'] ) {
				$tva_value = sanitize_text_field( $field['value'] );
				$user_id   = $handler->get_user_id();

				// Enregistrer le numéro de TVA dans la métadonnée utilisateur
				update_user_meta( $user_id, 'tva_number', $tva_value );
			}
		}
	}
}
