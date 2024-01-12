document.addEventListener('DOMContentLoaded', function () {
    console.log('ready!');
    const noteLinks = document.querySelectorAll('.km-note-preview');

    function closeAllModals() {
        // Trouver et fermer toutes les modales ouvertes
        const openModals = document.querySelectorAll('.km-user-note-modal');
        openModals.forEach(function (modal) {
            modal.remove();
        });
    }

    noteLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();

            closeAllModals();

            const userNote = link.getAttribute('data-user-note');
            const userId = link.getAttribute('data-user-id');

            // Créez et affichez la modale avec la note de l'utilisateur
            const modal = document.createElement('div');
            modal.classList.add('km-user-note-modal');
            modal.style.position = 'fixed';
            modal.style.left = '50%';
            modal.style.top = '50%';
            modal.style.transform = 'translate(-50%, -50%)';
            modal.style.backgroundColor = '#fff';
            modal.style.border = '1px solid #f4f4f4';
            modal.style.padding = '20px';
            modal.style.zIndex = '1000';
            modal.innerHTML = `<a href="/wp-admin/user-edit.php?user_id=${userId}#user-note-table">Éditer</a><button style="float: right;" onclick="this.parentElement.style.display='none'">Fermer</button><p>${userNote}</p>`;

            document.body.appendChild(modal);
        });
    });
});

// Change transporteur with ajax
jQuery(document).ready(function ($) {
    const transporterField = document.querySelector('[data-key="field_6536a052fb38f"]');
    const selectField = transporterField.querySelector('.acf-input select');

    if (selectField) {
        $(selectField).on('select2:select', function (e) {
            const urlParams = new URLSearchParams(window.location.search);
            const post_id = urlParams.get('post');
            const transporteur = this.value;

            removeMessages();

            transporterField.insertAdjacentHTML('beforeend', '<p class="km-loading-message">Chargement...</p>');

            kmAjaxCall('save_transporteur', { transporteur: transporteur, post_id: post_id })
                .then(response => {
                    removeMessages();
                    transporterField.insertAdjacentHTML('beforeend', '<p class="km-success-message">Transporteur mis à jour !</p>');
                    removeMessagesAfterDelay();
                })
                .catch(error => {
                    removeMessages();
                    transporterField.insertAdjacentHTML('beforeend', '<p class="km-error-message">Une erreur est survenue: ' + error + '</p>');
                    removeMessagesAfterDelay();
                });
        });
    }

    function removeMessages() {
        const messages = document.querySelectorAll('.km-success-message, .km-error-message');
        const loadingMessages = document.querySelectorAll('.km-loading-message');
      
        messages.forEach(function (message) {
           $(message).fadeOut();
        });

        loadingMessages.forEach(function (loadingMessage) {
            loadingMessage.remove();
         });
    }

    function removeMessagesAfterDelay() {
        setTimeout(removeMessages, 2500); // 3000 millisecondes, soit 3 secondes
    }
});
