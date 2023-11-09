<div id="confirmationModal" class="km-modal" tabindex="-1" role="dialog">
  <div class="km-modal-dialog" role="document">
    <img class="km-modal-close" src="<?php echo get_stylesheet_directory_uri() . '/assets/img/cross.svg'; ?>" alt="close modal"></span>
      <div class="km-modal-header">
        <h5 class="km-modal-title"><?php echo __( 'Confirmation', 'kingmateriaux' ); ?></h5>
      </div>
      <div class="km-modal-body">
        <p><?php echo __( 'Veuillez confirmer les conditions suivantes avant de continuer :', 'kingmateriaux' ); ?></p>
        <form id="confirmationForm">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="access-condition" required>
            <label class="form-check-label" for="access-condition">
            <?php echo __( 'Conditions d\'accès au chantier', 'kingmateriaux' ); ?>
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input"type="checkbox" value="" id="unload-condition" required>
            <label class="form-check-label" for="unload-condition">
              <?php echo __( 'Conditions de déchargement', 'kingmateriaux' ); ?>
            </label>
          </div>
          <?php wp_nonce_field( 'add_to_cart_validation', 'nonce_cart_validation' ); ?>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="cancelBtn"><?php echo __( 'Annuler', 'kingmateriaux' ); ?></button>
        <button type="button" class="btn btn-primary" id="confirmBtn"><?php echo __( 'Confirmer', 'kingmateriaux' ); ?></button>
      </div>
  </div>
</div>
