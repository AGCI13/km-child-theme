document.addEventListener('DOMContentLoaded', function () {
    console.log('test');
    // Attacher l'événement 'submit' aux formulaires de variations de produits et au formulaire de panier
    document.querySelectorAll('.single_add_to_cart_button').forEach(function (button) {
        console.log(button);
        button.addEventListener('click', function (e) {
            // Empêcher la soumission standard du formulaire
            e.preventDefault();

            // Afficher la modale
            const modal = document.getElementById('confirmationModal');
            const confirmBtn = document.getElementById('confirmBtn');
            const cancelBtn = document.getElementById('cancelBtn');

            // Ajouter la classe 'active' à la modale pour la faire apparaître
            modal.classList.add('active');
            // Ajouter la classe 'modal-active' au body pour empêcher le scroll
            document.body.classList.add('modal-active');

            // Récupérer les données du formulaire si nécessaire
            cancelBtn.onclick = function () {
                modal.classList.remove('active');
                document.body.classList.remove('modal-active');
            }

            // Trouver le bouton de confirmation et attacher un événement click
            confirmBtn.onclick = function () {

                const form = document.getElementById('confirmationForm');
                const data = {
                    condition_access: document.getElementById('access-condition').checked,
                    condition_unload: document.getElementById('unload-condition').checked,
                    nonce_cart_validation: document.getElementById('nonce_cart_validation').value,
                };

                document.body.classList.add('modal-active');
                // Vérifier que les conditions sont acceptées
                if (form.checkValidity()) {
                    // Simuler la soumission du formulaire avec AJAX
                    ajaxCall('add_to_cart_validation', data).then(function (response) {
                        // Traiter la réponse
                        modal.classList.remove('active');
                        document.body.classList.remove('modal-active');
                        // Si vous devez soumettre le formulaire original après confirmation
                        // form.submit();
                    }).catch(function (error) {
                        console.error('Error:', error);
                    });
                } else {
                    // Afficher un message d'erreur
                    alert('Veuillez cocher toutes les conditions avant de continuer.');
                }
            };
        });
    });
});
