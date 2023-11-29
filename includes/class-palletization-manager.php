<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles palletization management based on product added in WooCommerce cart.
 */

class KM_Palletization_Manager {

	use SingletonTrait;

	/**
	 * The pallet product ID.
	 *
	 * @var int
	 */
	private $pallet_product_id = 96426;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_product_before_adding_to_cart' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'disable_quantity_input_for_pallet' ), 10, 3 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'prevent_direct_pallet_addition' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'disable_remove_button_for_pallet' ), 10, 2 );

		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'adjust_pallet_quantity_on_cart_update' ), 10, 4 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'adjust_pallet_quantity_after_cart_item_removal' ), 10, 2 );
		add_action( 'woocommerce_before_cart_item_quantity_zero', array( $this, 'adjust_pallet_quantity_after_cart_item_removal' ), 10, 2 );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'move_pallet_to_end_of_cart' ) );
		add_action( 'template_redirect', array( $this, 'redirect_from_pallet_product_page' ) );
		add_action( 'woocommerce_after_single_product', array( $this, 'render_pallet_confirmation_popup' ) );
		add_action( 'wp_ajax_pallet_user_accept', array( $this, 'pallet_user_accept' ) );
		add_action( 'wp_ajax_nopriv_pallet_user_accept', array( $this, 'pallet_user_accept' ) );

		add_filter( 'woocommerce_cart_item_name', array( $this, 'display_custom_meta_data_in_cart' ), 10, 3 );
	}

	/**
	 * Vérifie si le produit a les métadonnées de palette.
	 *
	 * @param int $product_id L'ID du produit.
	 * @return bool Vrai si le produit a les métadonnées de palette, faux sinon.
	 */
	public function validate_product_before_adding_to_cart( $valid, $product_id, $quantity ) {
		$quantity_per_pallet  = get_post_meta( $product_id, '_quantite_par_palette', true );
		$pallet_from_quantity = get_post_meta( $product_id, '_palette_a_partir_de', true );

		if ( empty( $quantity_per_pallet ) || empty( $pallet_from_quantity ) ) {
			return $valid;
		}

		$this->add_pallet_to_cart_if_needed( $product_id, $quantity, $quantity_per_pallet, $pallet_from_quantity );

		return $valid;
	}

	/**
	 * Ajoute une palette au panier si nécessaire.
	 *
	 * @param int $product_id L'ID du produit.
	 * @param int $quantity La quantité du produit.
	 *
	 * @return void
	 */
	public function add_pallet_to_cart_if_needed( $product_id, $quantity, $quantity_per_pallet, $pallet_from_quantity ) {
		static $adding_pallet = false;

		if ( $adding_pallet ) {
			return;
		}

		$required_pallets = $this->calculate_required_pallets( $quantity, $quantity_per_pallet, $pallet_from_quantity );

		if ( $required_pallets > 0 ) {
			$adding_pallet = true;

			WC()->cart->add_to_cart( $this->pallet_product_id, $required_pallets );

			$adding_pallet = false;
		}
	}

	/**
	 * Calcule le nombre de palettes nécessaires pour une quantité donnée.
	 *
	 * @param int $quantity La quantité.
	 * @param int $quantity_per_pallet La quantité par palette.
	 * @param int $pallet_from_quantity La quantité à partir de laquelle une palette est nécessaire.
	 * @return int Le nombre de palettes nécessaires.
	 */
	public function calculate_required_pallets( $quantity, $quantity_per_pallet, $pallet_from_quantity ) {
		// Si la quantité est inférieure à $pallet_from_quantity, aucune palette n'est nécessaire.
		if ( $quantity < $pallet_from_quantity ) {
			return 0;
		}

		// Calculer le nombre de palettes nécessaires en fonction de la quantité
		$additional_pallets = ceil( ( $quantity - $pallet_from_quantity + 1 ) / $quantity_per_pallet );

		return $additional_pallets;
	}

	/**
	 * Ajuste la quantité de palettes dans le panier lorsque la quantité d'un article est mise à jour.
	 *
	 * @param string  $cart_item_key La clé de l'article dans le panier.
	 * @param int     $quantity La nouvelle quantité de l'article.
	 * @param int     $old_quantity L'ancienne quantité de l'article.
	 * @param WC_Cart $cart Le panier.
	 */
	public function adjust_pallet_quantity_on_cart_update( $cart_item_key, $quantity, $old_quantity, $cart ) {
		static $adjusting = false;

		if ( $adjusting ) {
			return;
		}

		$adjusting = true;

		// Recalcule la quantité totale pour les produits nécessitant des palettes.
		$total_pallets_required = $this->calculate_pallet_quantity( $cart );

		// Si le produit mis à jour est le produit "Palette" lui-même, arrêtez ici.
		$product = $cart->get_cart()[ $cart_item_key ]['data'];
		if ( $product->get_id() == $this->pallet_product_id ) {
			$adjusting = false;
			return;
		}

		// Ajuster la quantité de palettes dans le panier.
		$this->update_pallet_in_cart( $total_pallets_required, $cart );

		$adjusting = false;
	}

		/**
		 * Ajuste la quantité de palettes dans le panier lorsque la quantité d'un article est supprimée.
		 *
		 * @param string  $removed_cart_item_key La clé de l'article dans le panier.
		 * @param WC_Cart $cart Le panier.
		 */
	public function adjust_pallet_quantity_after_cart_item_removal( $removed_cart_item_key, $cart ) {
		// Recalcul de la quantité de palettes nécessaire.
		$total_pallets_required = $this->calculate_pallet_quantity( $cart );
		$this->update_pallet_in_cart( $total_pallets_required, $cart );
	}

	/**
	 * Ajoute ou met à jour le produit "Palette" dans le panier.
	 *
	 * @param int     $new_pallet_quantity La nouvelle quantité de palettes.
	 * @param WC_Cart $cart Le panier.
	 */
	public function update_pallet_in_cart( $new_pallet_quantity, $cart ) {
		$pallet_in_cart = false;
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( $cart_item['product_id'] == $this->pallet_product_id ) {
				$pallet_in_cart = true;
				if ( $new_pallet_quantity <= 0 ) {
					$cart->remove_cart_item( $cart_item_key );
				} else {
					$cart->set_quantity( $cart_item_key, $new_pallet_quantity, false );
				}
				break;
			}
		}

		// Si aucune palette n'est dans le panier et qu'une nouvelle est requise, ajoute-la.
		if ( ! $pallet_in_cart && $new_pallet_quantity > 0 ) {
			$cart->add_to_cart( $this->pallet_product_id, $new_pallet_quantity );
		}
	}


	/**
	 * Supprime le produit "Palette" du panier.
	 *
	 * @param WC_Cart $cart Le panier.
	 */
	private function remove_pallet_from_cart( $cart ) {
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( $cart_item['product_id'] == $this->pallet_product_id ) {
				$cart->remove_cart_item( $cart_item_key );
				return;
			}
		}
	}

	/**
	 * Vérifie si le produit a les métadonnées de palette.
	 *
	 * @param int $product_id L'ID du produit.
	 * @return bool Vrai si le produit a les métadonnées de palette, faux sinon.
	 */
	public function has_pallet_meta( $product_id ) {
		$quantity_per_pallet  = get_post_meta( $product_id, '_quantite_par_palette', true );
		$pallet_from_quantity = get_post_meta( $product_id, '_palette_a_partir_de', true );

		return ! empty( $quantity_per_pallet ) && ! empty( $pallet_from_quantity );
	}

	/**
	 * Calcule la quantité de palettes nécessaires pour le panier.
	 *
	 * @param WC_Cart $cart Le panier.
	 * @return int La quantité de palettes nécessaires.
	 */
	public function calculate_pallet_quantity( $cart ) {
		$total_pallets_required = 0;

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$product_id           = $cart_item['product_id'];
			$quantity             = $cart_item['quantity'];
			$quantity_per_pallet  = get_post_meta( $product_id, '_quantite_par_palette', true );
			$pallet_from_quantity = get_post_meta( $product_id, '_palette_a_partir_de', true );

			// Si le produit nécessite des palettes.
			if ( ! empty( $quantity_per_pallet ) ) {
				$pallet_from_quantity         = $pallet_from_quantity ?: 1;
				$required_pallets_for_product = $this->calculate_required_pallets( $quantity, $quantity_per_pallet, $pallet_from_quantity );
				$total_pallets_required      += $required_pallets_for_product;
			}
		}

		// Supprime le produit "Palette" si aucun autre produit ne nécessite des palettes.
		if ( $total_pallets_required == 0 ) {
			$this->remove_pallet_from_cart( $cart );
		}

		return $total_pallets_required;
	}

	/**
	 * Désactive la saisie de la quantité pour le produit "Palette".
	 *
	 * @param string $product_quantity La quantité du produit.
	 * @param string $cart_item_key La clé de l'article dans le panier.
	 *
	 * @return string La quantité du produit.
	 */
	public function disable_quantity_input_for_pallet( $product_quantity, $cart_item_key, $cart_item ) {
		if ( $cart_item['product_id'] == $this->pallet_product_id ) {
			return $cart_item['quantity'];
		}
		return $product_quantity;
	}

	/**
	 * Désactive le bouton de suppression pour le produit "Palette".
	 *
	 * @param string $remove_link Le lien de suppression.
	 * @param string $cart_item_key La clé de l'article dans le panier.
	 * @return string Le lien de suppression.
	 */
	public function disable_remove_button_for_pallet( $remove_link, $cart_item_key ) {
		$cart_item = WC()->cart->get_cart()[ $cart_item_key ];
		if ( $cart_item['product_id'] == $this->pallet_product_id ) {
			return '';
		}
		return $remove_link;
	}

	/**
	 * Déplace le produit "Palette" à la fin du panier.
	 *
	 * @param WC_Cart $cart Le panier.
	 */
	public function move_pallet_to_end_of_cart( $cart ) {
		$pallet_cart_item_key = null;
		$pallet_cart_item     = null;

		// Cherche le produit "Palette" dans le panier.
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( $cart_item['product_id'] == $this->pallet_product_id ) {
				$pallet_cart_item_key = $cart_item_key;
				$pallet_cart_item     = $cart_item;
				break;
			}
		}

		// Si le produit "Palette" est trouvé, le retirer puis le réajouter pour qu'il soit à la fin.
		if ( $pallet_cart_item_key ) {
			$cart->cart_contents                          = array_diff_key( $cart->cart_contents, array( $pallet_cart_item_key => '' ) );
			$cart->cart_contents[ $pallet_cart_item_key ] = $pallet_cart_item;
		}
	}

	/**
	 * Empêche l'ajout direct du produit "Palette" au panier.
	 *
	 * @param bool $valid Vrai si le produit peut être ajouté au panier, faux sinon.
	 * @param int  $product_id L'ID du produit.
	 * @param int  $quantity La quantité du produit.
	 * @return bool Vrai si le produit peut être ajouté au panier, faux sinon.
	 */
	public function prevent_direct_pallet_addition( $valid, $product_id, $quantity ) {
		if ( $product_id == $this->pallet_product_id ) {
			wc_add_notice( __( 'Ce produit ne peut pas être ajouté directement au panier.', 'woocommerce' ), 'error' );
			return false;
		}

		return $valid;
	}

	/**
	 *  Redirige la page produit de la palette vers la page d'accueil
	 */
	public function redirect_from_pallet_product_page() {
		if ( ! is_admin() && is_product() && get_the_ID() == $this->pallet_product_id ) {
			wp_redirect( home_url() ); // Redirige vers la page d'accueil, modifie au besoin.
			exit;
		}
	}

	/**
	 * Affiche la popup de confirmation d'ajout de palette.
	 */
	public function render_pallet_confirmation_popup() {

		if ( ! $this->has_pallet_meta( get_the_ID() ) ) {
			return;
		}

		if ( isset( $_COOKIE['palett_user_accept'] ) || WC()->session->get( 'palett_user_accept' ) ) {
			return;
		}

		wp_enqueue_script( 'add-to-cart-confirmation' );
		?>
			<div id="add-to-cart-confirmation-modal" class="km-modal">
				<div class="km-modal-dialog" role="document">
					<h3><?php esc_html_e( 'Ce produit est palétisé' ); ?></h3>
					<p><?php esc_html_e( 'En plus de votre produit, une ou plusieurs palettes consignée(s) seront ajoutées automatiquement à votre panier.' ); ?> 
					</p>
					<p><?php esc_html_e( '(28,80 € TTC la palette, remboursable à hauteur de 20,40 € TTC par palette).' ); ?>
					</p>
					<div class="km-form-fields">
						<div class="modal-actions inline">
							<button class="btn-cancel btn btn-secondary"><?php esc_html_e( 'Annuler', 'kingmateriaux' ); ?></button>
							<button class="btn-confirm btn btn-primary" data-action="pallet_user_accept">
								<span class="btn-confirm-label"><?php esc_html_e( 'Confirmer', 'kingmateriaux' ); ?></span>
								<span class="btn-confirm-loader"></span>
							</button>
						</div>
					</div>
				</div>
			</div>
		<?php
	}

	/**
	 * Gère la confirmation d'ajout de palette.
	 */
	public function pallet_user_accept() {

		if ( is_user_logged_in() ) {
			WC()->cart->set_session( 'palett_user_accept', true );
		}

		if ( ! isset( $_COOKIE['palett_user_accept'] ) ) {
			setcookie( 'palett_user_accept', true, time() + 3600 * 30, '/' );
		}

		wp_send_json_success();
	}

	/**
	 * Affiche les métadonnées de palette dans le panier.
	 *
	 * @param string $item_name Le nom de l'article.
	 * @param array  $cart_item Les données de l'article.
	 * @param string $cart_item_key La clé de l'article dans le panier.
	 * @return string Le nom de l'article avec les métadonnées de palette.
	 */

	public function display_custom_meta_data_in_cart( $item_name, $cart_item, $cart_item_key ) {
		// Récupération des métadonnées du produit.
		$product_id           = $cart_item['product_id'];
		$quantity_per_pallet  = get_post_meta( $product_id, '_quantite_par_palette', true );
		$pallet_from_quantity = get_post_meta( $product_id, '_palette_a_partir_de', true );

		// Construction du texte à afficher.
		$additional_text = '';
		if ( ! empty( $quantity_per_pallet ) ) {
			$additional_text .= 'Quantité par palette: ' . esc_html( $quantity_per_pallet ) . '<br>';
		}
		if ( ! empty( $pallet_from_quantity ) ) {
			$additional_text .= 'Palette à partir de: ' . esc_html( $pallet_from_quantity );
		}

		// Concaténation du texte supplémentaire au nom du produit.
		return $item_name . '<br><small class="cart-item-meta">' . $additional_text . '</small>';
	}
}
