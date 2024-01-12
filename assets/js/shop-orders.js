jQuery(document).ready(function ($) {
    var timeoutId;

    $('.transporter_column mark').mouseenter(function () {
        clearTimeout(timeoutId); // Annuler le délai précédent
        $('.transporter-list').remove(); // Supprimer les listes existantes

        var $list = $('<ul class="transporter-list"></ul>');

        $.each(transportersData, function (key, value) {
            $list.append('<li class="transporter-item" data-value="' + key + '">' + value + '</li>');
        });

        $(this).after($list);
    }).mouseleave(function () {
        // Définir un délai pour supprimer la liste
        timeoutId = setTimeout(function () {
            $('.transporter-list').remove();
        }, 250); // Délai avant de supprimer la liste
    });

    $(document).on('mouseenter', '.transporter-list', function () {
        clearTimeout(timeoutId); // Annuler le délai lors de l'entrée dans la liste
    }).on('mouseleave', '.transporter-list', function () {
        // Définir un délai pour supprimer la liste
        timeoutId = setTimeout(function () {
            $('.transporter-list').remove();
        }, 250);
    });


    // Gestion du clic sur un transporteur
    $(document).on('click', '.transporter-item', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var transporterId = $(this).data('value');
        var transporterName = $(this).text(); // Nom du transporteur sélectionné
        var postId = $(this).closest('tr').attr('id');

        if (!postId) {
            return;
        }

        postId = postId.replace('post-', '');
        $('.transporter-list').remove(); // Fermer la liste
        $('#post-' + postId).find('.transporter_column mark').after('<span class="loader">Chargement...</span>'); // Afficher un loader

        // Remplacer les caractères accentués et slugifier
        var transporterSlug = slugifyTransporterName(transporterName);

        // Appel AJAX pour mettre à jour le transporteur
        kmAjaxCall('save_transporteur', { transporteur: transporterId, post_id: postId })
            .then(response => {
                var markElement = $('#post-' + postId).find('.transporter_column mark');
                updateMarkElement(markElement, transporterSlug, transporterName);
                $('#post-' + postId).find('.loader').remove(); // Retirer le loader
            })
            .catch(error => {
                // Gérer l'erreur
                $('#post-' + postId).find('.loader').remove(); // Retirer le loader en cas d'erreur
            });
    });

    function slugifyTransporterName(name) {
        var slug = name.toLowerCase();
        slug = slug.replace(/[éèê]/g, 'e'); // Remplacer les caractères accentués par 'e'
        slug = slug.replace(/[\s\W-]+/g, '-'); // Remplacer les espaces et les caractères non alphanumériques
        return slug;
    }

    function updateMarkElement(markElement, transporterSlug, transporterName) {
        markElement.removeClass(); // Retirer toutes les classes actuelles
        markElement.addClass('transp-label ' + transporterSlug); // Ajouter les nouvelles classes
        markElement.text(transporterName); // Mettre à jour le texte
    }
});
