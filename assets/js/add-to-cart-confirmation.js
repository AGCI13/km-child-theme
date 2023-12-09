document.addEventListener('DOMContentLoaded', function () {
    const addToCartButton = document.querySelector('.single_add_to_cart_button');
    const modal = document.querySelector('#add-to-cart-confirmation-modal');

    addToCartButton.addEventListener('click', function (e) {

        if (e.target.classList.contains('disabled')) {
            return;
        }
        // Vérifie si le bouton a déjà été cliqué pour la confirmation
        if (!this.hasAttribute('data-confirmed')) {
            e.preventDefault();

            // Affiche la modale.
            if (modal) {
                showModal(modal, this);
            }
        } else {
            // Retire l'attribut pour réinitialiser le comportement du bouton
            this.removeAttribute('data-confirmed');
        }
    });

    const showModal = (modal, button) => {
        modal.classList.add('active');
        document.body.classList.add('modal-open');

        const cancelBtn = modal.querySelector('.btn-cancel');
        const confirmBtn = modal.querySelector('.btn-confirm');

        cancelBtn.addEventListener('click', function () {
            closeModal(modal);
        });

        confirmBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            handleLoading(e, true);

            action = this.getAttribute('data-action');
            
            if (!action) {
                return;
            }

            kmAjaxCall(action, { 'confirm': true }).then(response => {
                if (response.success) {
                    button.setAttribute('data-confirmed', 'true'); // Marquer le bouton comme confirmé
                    // Si le bouton est marqué comme confirmé, simuler un clic dessus
                    if (addToCartButton.hasAttribute('data-confirmed')) {
                        addToCartButton.click();
                    }
                }
                closeModal(modal);
            }).catch(error => {
                console.log(error);
            });
        });
    }

    function closeModal(modal) {
        modal.classList.remove('active');
        document.body.classList.remove('modal-open');
    }
});
