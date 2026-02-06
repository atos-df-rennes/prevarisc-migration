/**
 * Gestion des alertes email (BLOC 17 Part 3B)
 * 
 * Intercepte le clic sur les liens .alerte-link et charge le contenu
 * du formulaire dans la modale Bootstrap 3 déjà présente dans le DOM.
 */

$(document).ready(function() {
    // Intercepter les clics sur les liens d'alerte
    $(document).on('click', '.alerte-link', function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var typeChangement = $link.data('value');
        var etablissementId = $link.data('ets');
        
        if (!etablissementId) {
            console.error('ID établissement manquant sur le lien alerte');
            return;
        }

        var url = '/symfony/alerte/formulaire?etablissement=' + etablissementId + '&type=' + typeChangement;
        var $modal = $('#modalAlerte');
        var $modalContent = $modal.find('.modal-content');
        
        // Afficher loader
        $modalContent.html(
            '<div class="modal-body text-center">' +
            '<span class="glyphicon glyphicon-refresh glyphicon-spin" aria-hidden="true"></span> Chargement...' +
            '</div>'
        );
        
        $modal.modal('show');
        
        // Charger le contenu
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'html',
            success: function(html) {
                $modalContent.html(html);
            },
            error: function() {
                $modalContent.html(
                    '<div class="modal-header">' +
                    '<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>' +
                    '<h4 class="modal-title">Erreur</h4>' +
                    '</div>' +
                    '<div class="modal-body">' +
                    '<div class="alert alert-danger">Erreur lors du chargement du formulaire.</div>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-default" data-dismiss="modal">Fermer</button>' +
                    '</div>'
                );
            }
        });
    });

    // Nettoyer TinyMCE à la fermeture
    $('#modalAlerte').on('hidden.bs.modal', function() {
        if (tinymce.get('alerte-message')) {
            tinymce.get('alerte-message').remove();
        }
    });
});
