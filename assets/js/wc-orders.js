document.addEventListener('DOMContentLoaded', function() {
    const noteLinks = document.querySelectorAll('.km-note-preview');

    function closeAllModals() {
        // Trouver et fermer toutes les modales ouvertes
        const openModals = document.querySelectorAll('.km-user-note-modal');
        openModals.forEach(function(modal) {
            modal.remove();
        });
    }
    
    noteLinks.forEach(function(link) {
        link.addEventListener('click', function(event) {
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
