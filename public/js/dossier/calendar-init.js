/**
 * Initialisation calendriers commission/visite pour édition dossiers
 * Adapté de prevarisc/application/views/scripts/dossier/index.phtml (lignes 194-238)
 */

$(document).ready(function() {
    'use strict';
    
    /**
     * Gestion bouton "Afficher le calendrier"
     */
    $('.showCalendar').on('click', function(e) {
        e.preventDefault();
        
        var btnId = $(this).attr('id');
        var typeCalendar = $(this).closest('li').attr('id');
        var divName;
        var mois;
        var annee;
        
        // Déterminer quel calendrier afficher
        if (typeCalendar == 'field-dateVisite') {
            divName = 'calendarVisite';
            mois = parseInt($('#month_visite').val());
            annee = parseInt($('#year_visite').val());
        } else if (typeCalendar == 'field-dateCommission') {
            divName = 'calendar';
            mois = parseInt($('#month_comm').val());
            annee = parseInt($('#year_comm').val());
        }
        
        // Récupérer paramètres
        var idCommission = $('#dossier_commission').val();
        var typeDossier = $('#dossier_type').val();
        
        if (!idCommission) {
            alert('Veuillez sélectionner une commission');
            return false;
        }
        
        // Afficher conteneur calendrier (les selects sont déjà dedans)
        $('#' + divName).parent().show();
        
        // Initialiser FullCalendar v1
        $('#' + divName).afficheCalendar(
            idCommission,
            'dossier',
            typeDossier,
            divName,
            btnId,
            mois,
            annee
        );
        
        // Toggle boutons
        $(this).hide();
        $(this).siblings('.hideCalendar').show();
        
        return false;
    });
    
    /**
     * Gestion bouton "Cacher le calendrier"
     */
    $('.hideCalendar').on('click', function(e) {
        e.preventDefault();
        
        var typeCalendar = $(this).closest('li').attr('id');
        var divName;
        
        if (typeCalendar == 'field-dateVisite') {
            divName = 'calendarVisite';
        } else if (typeCalendar == 'field-dateCommission') {
            divName = 'calendar';
        }
        
        // Masquer conteneur calendrier complet
        $('#' + divName).parent().hide();
        $('#' + divName).html('');
        
        // Toggle boutons
        $(this).hide();
        $(this).siblings('.showCalendar').show();
        
        return false;
    });
    
    /**
     * Changement select mois/année calendrier commission
     */
    $('#month_comm, #year_comm').on('change', function() {
        var idCommission = $('#dossier_commission').val();
        var typeDossier = $('#dossier_type').val();
        var mois = parseInt($('#month_comm').val());
        var annee = parseInt($('#year_comm').val());
        
        // Recharger calendrier avec nouvelle date
        $('#calendar').html('');
        $('#calendar').afficheCalendar(
            idCommission,
            'dossier',
            typeDossier,
            'calendar',
            'showCommission',
            mois,
            annee
        );
    });
    
    /**
     * Changement select mois/année calendrier visite
     */
    $('#month_visite, #year_visite').on('change', function() {
        var idCommission = $('#dossier_commission').val();
        var typeDossier = $('#dossier_type').val();
        var mois = parseInt($('#month_visite').val());
        var annee = parseInt($('#year_visite').val());
        
        // Recharger calendrier avec nouvelle date
        $('#calendarVisite').html('');
        $('#calendarVisite').afficheCalendar(
            idCommission,
            'dossier',
            typeDossier,
            'calendarVisite',
            'showVisite',
            mois,
            annee
        );
    });
    
    /**
     * Changement commission : reset calendriers + afficher/masquer boutons
     */
    $('#dossier_commission').on('change', function() {
        var commissionId = $(this).val();
        
        // Reset calendriers et masquer conteneurs
        $('#calendar, #calendarVisite').html('').parent().hide();
        $('.hideCalendar').hide();
        
        if (commissionId && commissionId != '0') {
            // Commission sélectionnée → afficher boutons
            $('.showCalendar').show();
        } else {
            // Aucune commission → masquer boutons
            $('.showCalendar').hide();
        }
    });
    
    /**
     * Initialisation au chargement : afficher/masquer boutons selon commission
     */
    var initialCommission = $('#dossier_commission').val();
    if (initialCommission && initialCommission != '0') {
        $('.showCalendar').show();
    } else {
        $('.showCalendar').hide();
    }
});
