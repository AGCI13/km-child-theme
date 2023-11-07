<div class="header_postcode">
    <?php
    if ( $zip_code ) : ?>
        <p><?php echo __( 'Code postal', 'kingmateriaux' ); ?> : <span class="yellow" id="modal_pc_open_btn"><?php echo esc_html( $zip_code ); ?></span></p>
    <?php else : ?>
        <p><?php echo __( 'Pour voir nos tarifs', 'kingmateriaux' ); ?> : <span class="yellow" id="modal_pc_open_btn"><?php echo __( 'Rentrez votre code postal', 'kingmateriaux' ); ?></span></p>
    <?php endif; ?>
</div>

<div id="modal_pc_wrapper" class="<?php echo $zip_code && $shipping_zone_id ?: 'active'; ?>">
    <form id="form_postcode" method="POST">
    <div id="modal_postcode">
        <?php if ( $zip_code && $shipping_zone_id ) : ?>
        <span id="modal_pc_close_btn"><img src="<?php echo get_stylesheet_directory_uri() . '/assets/img/cross.svg'; ?>" alt="close modal"></span>
        <?php endif; ?>
        <p>
        <?php
            echo sprintf(
                esc_html__(
                    'Pour voir les tarifs de nos produits, renseignez votre %1$s code postal à 5 chiffres. %2$s',
                    'kingmateriaux'
                ),
                '<span class="yellow">',
                '</span>'
            );?>
        </p>
        <div>
            <label>
                <select id="country" name="country">
                <option value="FR"><?php echo __( 'France', 'kingmateriaux' ); ?></option>
                <option value="BE"><?php echo __( 'Belgique', 'kingmateriaux' ); ?></option>
                </select>
            </label>
            <label>
                <input id="zip_code" name="zip_code" type="text" maxlength="5" placeholder="Code postal" required>
                <span id="zip_code_label"></span>
            </label>
            <?php wp_nonce_field( 'get_shipping_zone_id_from_zip', 'nonce_header_postcode' ); ?>
            <input type="submit" id="submit_btn_modal_postcode" value="<?php echo __( 'Valider', 'kingmateriaux' ); ?>">
        </div>
    </div>
    </form>
</div>
