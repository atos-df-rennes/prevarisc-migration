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
            mois = $('#month_visite').val();
            annee = $('#year_visite').val();
        } else if (typeCalendar == 'field-dateCommission') {
            divName = 'calendar';
            mois = $('#month_comm').val();
            annee = $('#year_comm').val();
        }
        
        // Récupérer paramètres
        var idCommission = $('#dossier_commission').val();
        var typeDossier = $('#dossier_type').val();
        
        if (!idCommission) {
            alert('Veuillez sélectionner une commission');
            return false;
        }
        
        // Afficher conteneur calendrier + selects
        $('#' + divName).show();
        $('#' + divName).parent().show();
        if (divName == 'calendar') {
            $('#month_comm, #year_comm').show();
        } else {
            $('#month_visite, #year_visite').show();
        }
        
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
            $('#month_visite, #year_visite').hide();
        } else if (typeCalendar == 'field-dateCommission') {
            divName = 'calendar';
            $('#month_comm, #year_comm').hide();
        }
        
        // Masquer calendrier
        $('#' + divName).hide();
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
        var mois = $('#month_comm').val();
        var annee = $('#year_comm').val();
        
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
        var mois = $('#month_visite').val();
        var annee = $('#year_visite').val();
        
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
        
        // Reset calendriers
        $('#calendar, #calendarVisite').hide().parent().hide().html('');
        $('.hideCalendar').hide();
        $('#month_comm, #year_comm, #month_visite, #year_visite').hide();
        
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
