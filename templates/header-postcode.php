<div class="header_cp" id="header_cp">
<?php

if ( isset( $_COOKIE['zip_code'] ) ) :
    !$zip_code = explode( '-', $_COOKIE['zip_code'] )[0];
    ?>
    <p><?php echo __( 'Code postal', 'kingmateriaux' ); ?> : <span class="yellow" id="cp_btn_modal"><?php echo esc_html( $zip_code ); ?></span></p>
<?php else : ?>
    <p><?php echo __( 'Pour voir nos tarifs', 'kingmateriaux' ); ?> : <span class="yellow" id="cp_btn_modal"><?php echo __( 'Rentrez votre code postal', 'kingmateriaux' ); ?></span></p>
<?php endif; ?>

</div>

<div id="background_modal_cp">
    <form id="form_cp" method="post">
    <div id="modal_cp">
        <span class="close_btn_modal_cp"><img src="<?php echo get_stylesheet_directory_uri() . '/assets/img/cross.svg'; ?>" alt="close modal"></span>
        <p>
        <?php
            echo sprintf(
                esc_html__(
                    'Pour voir les tarifs de nos produits, renseignez votre %1$s code postal à 5 chiffres %2$s',
                    'kingmateriaux'
                ),
                '<span class="yellow">',
                '</span>'
            );?>.
        </p>
        <div>
            <label>
                <select id="country" name="country">
                <option value="FR"><?php echo __( 'France', 'kingmateriaux' ); ?></option>
                <option value="BE"><?php echo __( 'Belgique', 'kingmateriaux' ); ?></option>
                </select>
            </label>
            <label>
                <input id="zip_code" name="zip_code" type="text" maxlength="5" placeholder=" | Code postal" required>
                <span id="zip_code_label"></span>
            </label>
            <?php wp_nonce_field( 'get_shipping_zone_id_from_zip', 'nonce_header_postcode' ); ?>
            <input type="submit" id="submit_btn_modal_cp" value="<?php echo __( 'Valider', 'kingmateriaux' ); ?>">        </div>
    </div>
    </form>
</div>
