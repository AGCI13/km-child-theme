<tr>
    <th><?php echo __( 'Sélectionner votre option de livraison', 'kingmateriaux' ); ?></th>
<td>
    <?php foreach ( $options_livraison as $option_value => $option_label ) : ?>
        <label class="option-livraison">
                <input type="radio" name="option_livraison" value="<?php esc_attr( $option_value ); ?>">
                <?php echo esc_html( $option_label ); ?>
            </label>
    <?php endforeach; ?>
</td>
</tr>
