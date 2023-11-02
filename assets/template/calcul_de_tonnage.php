<div class="calcul_de_tonnage">
    <div class="form_calcul_de_tonnage">
        <h3>Le calculateur</h3>
        <form id="calcul_de_tonnage" method="post">
            <label>
                Longueur
                <input type="number" name="lon" min="0" placeholder="Longueur de votre dalle en cm" required>
            </label>
            <label>
                Largeur
                <input type="number" name="lar" min="0" placeholder="Largeur de votre dalle en cm" required>
            </label>
            <label>
                Épaisseur
                <input type="number" name="epa" min="0" placeholder="Épaisseur de votre dalle en cm" required>
            </label>
            <div class="recommandation">Nous recommandons une épaisseur minimale de 5 cm</div>

            <label class="densite">
                <span>Densité</span>
                <img id="img_info_bull_density" src="<?php echo get_stylesheet_directory_uri() . '/assets/img/icone-information.svg'; ?>" alt="icon information">
                <div id="info_bull_density">
                    <div id="close_info_bull_density">
                        <img id="img_close_info_bull_density" src="<?php echo get_stylesheet_directory_uri() . '/assets/img/close_info_bull.svg'; ?>" alt="close info bull densite">
                    </div>
                    Définition, Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris, cursus morbi ac auctor.
                </div>
                <select name="den" required>
                    <option value="" disabled selected>Choisissez une option</option>
                    <option value="1">Galets - 1</option>
                    <option value="1.8">Sables - 1,8</option>
                    <option value="1.6">Mélanges - 1,6</option>
                    <option value="1.2">Terre - 1,2</option>
                    <option value="0.8">Pouzzolane - 0,8</option>
                </select>
            </label>

            <span class="btn" id="submit_calcul_de_tonnage">Calculer</span>
        </form>
    </div>
    <div class="result_calcul_de_tonnage">
        <h3>TOTAL - Tonnage </h3>
        <div class="result_weight"><b>Poids : </b><span id="poids"></span></div>
        <div class="result_bag"><b>Sac correspondant : </b><span id="bag"></span></div>
        <h3 class="false_h3">Mesures indiquées<img
                    src="<?php echo get_stylesheet_directory_uri() . '/assets/img/regle.svg'; ?>" alt="icone de mesure">
        </h3>
        <div class="result_longueur"><b>Longueur : </b><span id="longueur_cm"></span> cm <img
                    src="<?php echo get_stylesheet_directory_uri() . '/assets/img/Arrow.svg'; ?>"
                    alt="fleche vers la droite"><span id="longueur_m"></span> m
        </div>
        <div class="result_largeur"><b>Largeur : </b><span id="largeur_cm"></span> cm <img
                    src="<?php echo get_stylesheet_directory_uri() . '/assets/img/Arrow.svg'; ?>"
                    alt="fleche vers la droite"><span id="largeur_m"></span> m
        </div>
        <div class="result_epaiseur"><b>Épaisseur : </b><span id="epaiseur_cm"></span> cm</div>
        <div class="recommandation">Nous recommandons une épaisseur minimale de 5 cm</div>
        <div class="density_body_result"><b>Densité : </b><span id="densite_value"></span></div>
        <span id="reset_calcul_de_tonnage">Calculer un nouveau tonnage</span>
    </div>
    <div class="img_calcul_de_tonnage_form">
        <img src="<?php echo get_stylesheet_directory_uri() . '/assets/img/calcul_de_tonnage_right.png'; ?>"
             alt="image de calcul de tonnage">
    </div>
    <div class="img_calcul_de_tonnage_result">
        <img src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'full'); ?>"
             alt="image de calcul de tonnage">
    </div>
</div>
<div class="densities">
    La densité des galets est de 1 - La densité du sable est de 1,8 - La densité du mélange est de 1,6 - La densité de
    la terre est de 1,2 - la densité de la pouzzolane est de 0,8
</div>