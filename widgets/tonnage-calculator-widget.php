<?php
class Tonnage_Calculator_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'km_tonnage_calculator';
	}

	public function get_title() {
		return esc_html__( 'KM Calculateur de tonnage', 'elementor-addon' );
	}

	public function get_icon() {
		return 'eicon-layout-settings';
	}

	public function get_categories() {
		return array( 'kingmateriaux' );
	}

	protected function render() {

		// Si c'est un produit et qu'il n'y a pas l'option "Afficher le calculateur de tonnage" de coché, on ne l'affiche pas.
		if ( is_product() ) {
			global $product;

			// Obtenir les catégories de produits.
			$categories      = wp_get_post_terms( $product->get_id(), 'product_cat' );
			$parent_category = null;
			$child_category  = null;

			foreach ( $categories as $category ) {
				if ( $category->parent == 0 ) {
					// C'est une catégorie parente.
					$parent_category = $category;
				} else {
					// C'est une catégorie enfant.
					$child_category = $category;
				}
			}

			// Vous pouvez décider d'utiliser la catégorie parente ou enfant ici.
			$target_category = $child_category ? $child_category : $parent_category;

			if ( $target_category ) {
				// Récupérer la valeur du champ ACF pour cette catégorie
				$acf_value = get_field( 'show_tonnage_calculator', 'product_cat_' . $target_category->term_id );

				if ( ! $acf_value ) {
					return;
				}
			}
		}

		// if product first parent categroy is agrega
		wp_enqueue_style( 'km-tonnage-calculator-style' );
		wp_enqueue_script( 'km-tonnage-calculator-script' );

		?>		
	<div class="tonnage-calculator-wrapper">
		<div class="tonnage_calculator">
			<div class="form_tonnage_calculator h2_grey_back">
				<h2 class="elementor-heading-title"><?php esc_html_e( 'Calcul de tonnage', 'kingmateriaux' ); ?></h2>
				<form id="tonnage_calculator" method="post">
					<div class="input-group">
						<label>
						<?php esc_html_e( 'Longueur*', 'kingmateriaux' ); ?>
							<input type="number" name="lon" min="0" placeholder="<?php esc_html_e( 'Longueur en cm', 'kingmateriaux' ); ?>" required>
						</label>
						<label>
						<?php esc_html_e( 'Largeur*', 'kingmateriaux' ); ?>
							<input type="number" name="lar" min="0" placeholder="<?php esc_html_e( 'Largeur en cm', 'kingmateriaux' ); ?>" required>
						</label>
					</div>
					<label>
					<?php esc_html_e( 'Épaisseur*', 'kingmateriaux' ); ?>
						<input type="number" name="epa" min="0" placeholder="<?php esc_html_e( 'Épaisseur en cm', 'kingmateriaux' ); ?>" required>
					</label>
					<span class="recommandation"><?php esc_html_e( '5cm. Épaisseur minimale recommandé.', 'kingmateriaux' ); ?></span>

					<label class="densite">
						<span><?php esc_html_e( 'Densité', 'kingmateriaux' ); ?></span>
						<img id="img_info_bull_density" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/icone-information.svg' ); ?>" alt="icon information">
						<div id="info_bull_density">
							<div id="close_info_bull_density">
								<img id="img_close_info_bull_density" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/close-info-tooltip.svg' ); ?>" alt="close info bull densite">
							</div>
							<?php esc_html_e( 'Définition, Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris, cursus morbi ac auctor.', 'kingmateriaux' ); ?>
						</div>
						<select name="den" required>
							<option value="" disabled selected><?php esc_html_e( 'Choisissez une option', 'kingmateriaux' ); ?></option>
							<option value="1"><?php esc_html_e( 'Galets - 1', 'kingmateriaux' ); ?></option>
							<option value="1.8"><?php esc_html_e( 'Sables - 1,8', 'kingmateriaux' ); ?></option>
							<option value="1.6"><?php esc_html_e( 'Mélanges - 1,6', 'kingmateriaux' ); ?></option>
							<option value="1.2"><?php esc_html_e( 'Terre - 1,2', 'kingmateriaux' ); ?></option>
							<option value="0.8"><?php esc_html_e( 'Pouzzolane - 0,8', 'kingmateriaux' ); ?></option>
						</select>
					</label>
					<?php wp_nonce_field( 'tonnage_calculation', 'nonce_tonnage_calculator' ); ?>
					<span class="btn btn-primary" id="submit_tonnage_calculator"><?php esc_html_e( 'Calculer', 'kingmateriaux' ); ?></span>
				</form>
				<div class="densities">
					<?php
					esc_html_e(
						'La densité des galets est de 1 - La densité du sable est de 1,8 - La densité du mélange est de 1,6 - La densité de
						la terre est de 1,2 - la densité de la pouzzolane est de 0,8',
						'kingmateriaux'
					);
					?>
				</div>
			</div>
			<div class="result_tonnage_calculator">
				<h3><?php esc_html_e( 'TOTAL - Tonnage', 'kingmateriaux' ); ?> </h3>
				<div class="result_weight"><b><?php esc_html_e( 'Poids :', 'kingmateriaux' ); ?> </b><span id="poids"></span></div>
				<div class="result_bag"><b>
				<?php esc_html_e( 'Sac correspondant :', 'kingmateriaux' ); ?>
				</b><span id="bag"></span></div>
				<h3 class="false_h3"><?php esc_html_e( 'Mesures indiquées', 'kingmateriaux' ); ?><img
							src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/regle.svg' ); ?>" alt="icone de mesure" />
				</h3>
				<div class="result_longueur"><b><?php esc_html_e( 'Longueur :', 'kingmateriaux' ); ?> </b><span id="longueur_cm"></span> <?php esc_html_e( 'cm', 'kingmateriaux' ); ?> <img
							src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/Arrow.svg' ); ?>"
							alt="fleche vers la droite"><span id="longueur_m"></span> <?php esc_html_e( 'm', 'kingmateriaux' ); ?>
				</div>
				<div class="result_largeur"><b><?php esc_html_e( 'Largeur :', 'kingmateriaux' ); ?> </b><span id="largeur_cm"></span> 
				<?php esc_html_e( 'cm', 'kingmateriaux' ); ?>
				<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/Arrow.svg' ); ?>"
				alt="fleche vers la droite"><span id="largeur_m"></span> <?php esc_html_e( 'm', 'kingmateriaux' ); ?>
				</div>
				<div class="result_epaiseur"><b>
				<?php esc_html_e( 'Épaisseur :', 'kingmateriaux' ); ?> </b><span id="epaiseur_cm"></span> <?php esc_html_e( 'cm', 'kingmateriaux' ); ?></div>
				<div class="recommandation"><?php esc_html_e( 'Nous recommandons une épaisseur minimale de 5 cm', 'kingmateriaux' ); ?></div>
				<div class="density_body_result"><b><?php esc_html_e( 'Densité :', 'kingmateriaux' ); ?> </b><span id ='densite_value'> </span > </div >
				<span id ='reset_tonnage_calculator'> <?php esc_html_e( 'Calculer un nouveau tonnage', 'kingmateriaux' ); ?></span>
			</div>
			<?php
			/*
			<div class="img_tonnage_calculator_form">
				<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/femme-calcul-tonnage.png' ); ?>"
					alt="image de calcul de tonnage" />
			</div>
			*/
			?>
			<div class="img_tonnage_calculator_result">
				<img src="<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'full' ) ); ?>"
					alt="image de calcul de tonnage"/>
			</div>
		</div>
	</div>
	<?php
	}
}
