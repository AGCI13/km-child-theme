<div class="header_cp" id="header_cp">
<?php

if (isset($_COOKIE['zip_code'])) {
    $zip_code = explode('-', $_COOKIE['zip_code'])[0];
?>
    <p>Votre code postal : <span class="yellow" id="cp_btn_modal"><?php echo $zip_code; ?></span></p>
<?php } else { ?>
    <p>Pour voir nos tarifs : <span class="yellow" id="cp_btn_modal">Rentrez votre code postal</span></p>
    <?php }?>

</div>


<div id="background_modal_cp">
    <form id="form_cp" method="post">
    <div id="modal_cp">
        <span class="close_btn_modal_cp"><img src="<?php echo get_stylesheet_directory_uri() . '/assets/img/cross.svg'; ?>" alt="close modal"></span>
        <p>Pour voir les tarifs de nos produits, renseignez votre <span class="yellow">code postal à 5 chiffres</span>.
        </p>
        <div>
            <label>
                <select id="country" name="country">
                    <option value="FR">France</option>
                    <option value="BE">Belgique</option>
                </select>
            </label>
            <label>
                <input id="zip_code" name="zip_code" type="text" maxlength="5" placeholder=" | Code postal" required>
                <span id="zip_code_label"></span>
            </label>
            <span data-ajaxurl="<?php echo admin_url( 'admin-ajax.php' ); ?>" id="submit_btn_modal_cp">Appliquer</span>
        </div>
    </div>
    </form>
</div>