<div class="tonnage_calculator">
    <div class="form_tonnage_calculator">
        <h3><?php echo __( 'Le calculateur', 'kingmateriaux' ); ?></h3>
        <form id="tonnage_calculator" method="post">
            <label>
            <?php echo __( 'Longueur', 'kingmateriaux' ); ?>
                <input type="number" name="lon" min="0" placeholder="<?php echo __( 'Longueur de votre dalle en cm', 'kingmateriaux' ); ?>" required>
            </label>
            <label>
            <?php echo __( 'Largeur', 'kingmateriaux' ); ?>
                <input type="number" name="lar" min="0" placeholder="<?php echo __( 'Largeur de votre dalle en cm', 'kingmateriaux' ); ?>" required>
            </label>
            <label>
            <?php echo __( 'Épaisseur', 'kingmateriaux' ); ?>
                <input type="number" name="epa" min="0" placeholder="<?php echo __( 'Épaisseur de votre dalle en cm', 'kingmateriaux' ); ?>" required>
            </label>
            <div class="recommandation"><?php echo __( 'Nous recommandons une épaisseur minimale de 5 cm', 'kingmateriaux' ); ?></div>

            <label class="densite">
                <span><?php echo __( 'Densité', 'kingmateriaux' ); ?></span>
                <img id="img_info_bull_density" src="<?php echo get_stylesheet_directory_uri() . '/assets/img/icone-information.svg'; ?>" alt="icon information">
                <div id="info_bull_density">
                    <div id="close_info_bull_density">
                        <img id="img_close_info_bull_density" src="<?php echo get_stylesheet_directory_uri() . '/assets/img/close-info-tooltip.svg'; ?>" alt="close info bull densite">
                    </div>
                    <?php echo __( 'Définition, Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris, cursus morbi ac auctor.', 'kingmateriaux' ); ?>
                </div>
                <select name="den" required>
                    <option value="" disabled selected><?php echo __( 'Choisissez une option', 'kingmateriaux' ); ?></option>
                    <option value="1"><?php echo __( 'Galets - 1', 'kingmateriaux' ); ?></option>
                    <option value="1.8"><?php echo __( 'Sables - 1,8', 'kingmateriaux' ); ?></option>
                    <option value="1.6"><?php echo __( 'Mélanges - 1,6', 'kingmateriaux' ); ?></option>
                    <option value="1.2"><?php echo __( 'Terre - 1,2', 'kingmateriaux' ); ?></option>
                    <option value="0.8"><?php echo __( 'Pouzzolane - 0,8', 'kingmateriaux' ); ?></option>
                </select>
            </label>

            <span class="btn" id="submit_tonnage_calculator"><?php echo __( 'Calculer', 'kingmateriaux' ); ?></span>
        </form>
    </div>
    <div class="result_tonnage_calculator">
        <h3><?php echo __( 'TOTAL - Tonnage', 'kingmateriaux' ); ?> </h3>
        <div class="result_weight"><b><?php echo __( 'Poids :', 'kingmateriaux' ); ?> </b><span id="poids"></span></div>
        <div class="result_bag"><b>
        <?php
        echo __( 'Sac correspondant :', 'kingmateriaux' ); ?></b><span id="bag"></span></div>
        <h3 class="false_h3"><?php echo __( 'Mesures indiquées', 'kingmateriaux' ); ?><img
                    src="<?php echo get_stylesheet_directory_uri() . ' /assets /img /regle . svg'; ?>" alt="icone de mesure">
        </h3>
        <div class="result_longueur"><b><?php echo __( 'Longueur :', 'kingmateriaux' ); ?> </b><span id="longueur_cm"></span> <?php echo __( 'cm', 'kingmateriaux' ); ?> <img
                    src="<?php echo get_stylesheet_directory_uri() . ' /assets /img /Arrow . svg'; ?>"
                    alt="fleche vers la droite"><span id="longueur_m"></span> <?php echo __( 'm', 'kingmateriaux' ); ?>
        </div>
        <div class="result_largeur"><b><?php echo __( 'Largeur :', 'kingmateriaux' ); ?> </b><span id="largeur_cm"></span> 
        <?php
        echo __(
            'cm',
            'kingmateriaux'
        ); ?> <img
                    src="<?php echo get_stylesheet_directory_uri() . '/assets/img/Arrow.svg'; ?>"
                    alt="fleche vers la droite"><span id="largeur_m"></span> <?php echo __( 'm', 'kingmateriaux' ); ?>
        </div>
        <div class="result_epaiseur"><b>
        <?php echo __( 'Épaisseur :', 'kingmateriaux' ); ?> </b><span id="epaiseur_cm"></span> <?php echo __( 'cm', 'kingmateriaux' ); ?></div>
        <div class="recommandation"><?php echo __( 'Nous recommandons une épaisseur minimale de 5 cm', 'kingmateriaux' ); ?></div>
        <div class="density_body_result"><b><?php echo __( 'Densité :', 'kingmateriaux' ); ?> </b><span id ='densite_value'> </span > </div >
        <span id ='reset_tonnage_calculator'> <?php echo __( 'Calculer un nouveau tonnage', 'kingmateriaux' ); ?></span>
    </div>
    <div class="img_tonnage_calculator_form">
        <img src="<?php echo get_stylesheet_directory_uri() . '/assets/img/tonnage_calculator_right.png'; ?>"
             alt="image de calcul de tonnage">
    </div>
    <div class="img_tonnage_calculator_result">
        <img src="<?php echo get_the_post_thumbnail_url( get_the_ID(), 'full' ); ?>"
             alt="image de calcul de tonnage">
    </div>
</div>
<div class="densities">
    <?php
    echo __(
        'La densité des galets est de 1 - La densité du sable est de 1,8 - La densité du mélange est de 1,6 - La densité de
    la terre est de 1,2 - la densité de la pouzzolane est de 0,8',
        'kingmateriaux'
    ); ?>
</div>
