/**
 * Gestion des alertes email (BLOC 17 Part 3B)
 * 
 * Intercepte le clic sur les liens .alerte-link et charge le contenu
 * du formulaire dans la modale. Gestion des états via show/hide.
 */

$(document).ready(function() {
    $(document).on('click', '.alerte-link', function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var typeChangement = $link.data('value');
        var etablissementId = $link.data('ets');
        
        if (!etablissementId) {
            console.error('ID établissement manquant');
            return;
        }

        var url = '/symfony/alerte/formulaire?etablissement=' + etablissementId + '&type=' + typeChangement;
        var $modal = $('#modalAlerte');
        
        // Afficher état loading
        $('#alerte-loading').show();
        $('#alerte-error').hide();
        $('#alerte-content').hide();
        
        $modal.modal('show');
        
        // Charger le contenu
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'html',
            success: function(html) {
                $('#alerte-loading').hide();
                $('#alerte-content').html(html).show();
            },
            error: function() {
                $('#alerte-loading').hide();
                $('#alerte-error').show();
            }
        });
    });

    // Nettoyer à la fermeture
    $('#modalAlerte').on('hidden.bs.modal', function() {
        if (tinymce.get('alerte-message')) {
            tinymce.get('alerte-message').remove();
        }
        $('#alerte-content').empty();
    });
});
