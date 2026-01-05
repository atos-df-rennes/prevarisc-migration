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
        if (typeCalendar == 'DATEVISITE') {
            divName = 'calendarVisite';
            mois = parseInt($('#month_visite').val());
            annee = parseInt($('#year_visite').val());
        } else if (typeCalendar == 'DATECOMM') {
            divName = 'calendar';
            mois = parseInt($('#month_comm').val());
            annee = parseInt($('#year_comm').val());
        }
        
        // Récupérer paramètres (IDs Symfony ou legacy)
        var idCommission = $('#dossier_commission').val() || $('#COMMISSION_DOSSIER').val();
        var typeDossier = $('#dossier_type').val() || $('#TYPE_DOSSIER').val();
        
        if (!idCommission) {
            alert('Veuillez sélectionner une commission');
            return false;
        }
        
        // Afficher conteneur calendrier (la row <li> qui contient le calendrier)
        $('#' + divName).closest('li').show();
        $('#' + divName).show();
        
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
        
        if (typeCalendar == 'DATEVISITE') {
            divName = 'calendarVisite';
        } else if (typeCalendar == 'DATECOMM') {
            divName = 'calendar';
        }
        
        // Masquer conteneur calendrier complet (la row <li>)
        $('#' + divName).closest('li').hide();
        $('#' + divName).hide().html('');
        
        // Toggle boutons
        $(this).hide();
        $(this).siblings('.showCalendar').show();
        
        return false;
    });
    
    /**
     * Changement commission : reset calendriers + afficher/masquer boutons
     * Support des IDs Symfony et legacy
     */
    $('#dossier_commission, #COMMISSION_DOSSIER').on('change', function() {
        var commissionId = $(this).val();
        
        // Reset calendriers et masquer conteneurs
        $('#calendar, #calendarVisite').each(function() {
            $(this).html('').hide().closest('li').hide();
        });
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
    var initialCommission = $('#dossier_commission').val() || $('#COMMISSION_DOSSIER').val();
    if (initialCommission && initialCommission != '0') {
        $('.showCalendar').show();
    } else {
        $('.showCalendar').hide();
    }
});
