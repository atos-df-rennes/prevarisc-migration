/**
 * Gestion des alertes email (BLOC 17 Part 3B)
 * 
 * Intercepte le clic sur les liens .alerte-link et affiche une modale Bootstrap 3
 * avec le formulaire d'envoi d'email chargé en AJAX.
 */

$(document).ready(function() {
    // Créer la structure de la modale Bootstrap 3 si elle n'existe pas
    if ($('#modalAlerte').length === 0) {
        $('body').append(
            '<div class="modal fade" id="modalAlerte" tabindex="-1" role="dialog" aria-labelledby="modalAlerteLabel">' +
            '  <div class="modal-dialog modal-lg" role="document">' +
            '    <div class="modal-content">' +
            '      <!-- Contenu chargé dynamiquement -->' +
            '    </div>' +
            '  </div>' +
            '</div>'
        );
    }

    // Intercepter les clics sur les liens d'alerte
    $(document).on('click', '.alerte-link', function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var typeChangement = $link.data('value'); // data-value="2" pour avis
        var etablissementId = $link.data('ets'); // data-ets="123"
        
        if (!etablissementId) {
            console.error('ID établissement manquant sur le lien alerte');
            return;
        }

        // Charger le formulaire via AJAX
        var url = '/symfony/alerte/formulaire?etablissement=' + etablissementId + '&type=' + typeChangement;
        
        var $modal = $('#modalAlerte');
        var $modalContent = $modal.find('.modal-content');
        
        // Afficher loader pendant chargement
        $modalContent.html(
            '<div class="modal-body text-center">' +
            '  <i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Chargement...' +
            '</div>'
        );
        
        // Afficher la modale
        $modal.modal('show');
        
        // Charger le contenu
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'html',
            success: function(html) {
                $modalContent.html(html);
            },
            error: function(xhr, status, error) {
                $modalContent.html(
                    '<div class="modal-header">' +
                    '  <button type="button" class="close" data-dismiss="modal">&times;</button>' +
                    '  <h4 class="modal-title">Erreur</h4>' +
                    '</div>' +
                    '<div class="modal-body">' +
                    '  <div class="alert alert-danger">Erreur lors du chargement du formulaire : ' + error + '</div>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                    '  <button type="button" class="btn btn-default" data-dismiss="modal">Fermer</button>' +
                    '</div>'
                );
            }
        });
    });

    // Nettoyer TinyMCE à la fermeture de la modale
    $('#modalAlerte').on('hidden.bs.modal', function() {
        if (tinymce.get('alerte-message')) {
            tinymce.get('alerte-message').remove();
        }
    });
});
