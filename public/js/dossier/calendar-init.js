/**
 * Initialisation calendriers commission/visite pour édition dossiers
 * Adapté de prevarisc/application/views/scripts/dossier/index.phtml (lignes 194-238)
 * 
 * Structure HTML attendue (legacy) :
 * - #DATEVISITE ou #DATECOMM : <li> contenant input + boutons + selects
 * - Boutons .showCalendar et .hideCalendar dans cette <li>
 * - Selects #month_visite/#year_visite ou #month_comm/#year_comm
 * - Conteneur calendrier dans une <li> suivante (cachée par défaut)
 */

$(document).ready(function() {
    'use strict';
    
    /**
     * Gestion bouton "Afficher le calendrier"
     * Legacy: lignes 194-218
     */
    $('.showCalendar').on('click', function(e) {
        e.preventDefault();
        
        // Legacy: $(this).parent().parent().attr('id') → remonte au <li> parent
        var $parentLi = $(this).closest('li[id]');
        var typeCalendar = $parentLi.attr('id');
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
        } else {
            console.warn('Type calendrier non reconnu:', typeCalendar);
            return false;
        }
        
        // Récupérer paramètres (IDs Symfony ou legacy)
        var idCommission = $('#dossier_commission').val() || $('#COMMISSION_DOSSIER').val();
        var typeDossier = $('#dossier_type').val() || $('#TYPE_DOSSIER').val();
        
        if (!idCommission) {
            alert('Veuillez sélectionner une commission');
            return false;
        }
        
        // Legacy: $("#" + divName).show(); $("#" + divName).parent().show();
        // Afficher conteneur calendrier (le div) et son parent (la <li>)
        $('#' + divName).show();
        $('#' + divName).parent().show();
        
        // Initialiser FullCalendar v1
        $('#' + divName).afficheCalendar(
            idCommission,
            'dossier',
            typeDossier,
            divName,
            $(this).attr('id'),
            mois,
            annee
        );
        
        // Legacy: $(this).hide(); $("#" + typeCalendar).find(".hideCalendar").show();
        $(this).hide();
        $('#' + typeCalendar).find('.hideCalendar').show();
        
        return false;
    });
    
    /**
     * Gestion bouton "Cacher le calendrier"
     * Legacy: lignes 221-238
     */
    $('.hideCalendar').on('click', function(e) {
        e.preventDefault();
        
        // Legacy: $(this).parent().parent().attr('id')
        var $parentLi = $(this).closest('li[id]');
        var typeCalendar = $parentLi.attr('id');
        var divName;
        
        if (typeCalendar == 'DATEVISITE') {
            divName = 'calendarVisite';
        } else if (typeCalendar == 'DATECOMM') {
            divName = 'calendar';
        } else {
            console.warn('Type calendrier non reconnu:', typeCalendar);
            return false;
        }
        
        // Legacy: $("#" + divName).hide(); $("#" + divName).parent().hide(); $("#" + divName).html('');
        $('#' + divName).hide();
        $('#' + divName).parent().hide();
        $('#' + divName).html('');
        
        // Legacy: $(this).hide(); $("#" + typeCalendar).find(".showCalendar").show();
        $(this).hide();
        $('#' + typeCalendar).find('.showCalendar').show();
        
        return false;
    });
    
    /**
     * Changement de mois/année dans les selects → rafraîchir le calendrier
     * Legacy: comportement implicite via réinitialisation calendrier
     */
    $('#month_visite, #year_visite').on('change', function() {
        var $calendarDiv = $('#calendarVisite');
        // Si le calendrier est visible, le rafraîchir
        if ($calendarDiv.is(':visible')) {
            var mois = parseInt($('#month_visite').val());
            var annee = parseInt($('#year_visite').val());
            $calendarDiv.fullCalendar('gotoDate', annee, mois);
        }
    });
    
    $('#month_comm, #year_comm').on('change', function() {
        var $calendarDiv = $('#calendar');
        // Si le calendrier est visible, le rafraîchir
        if ($calendarDiv.is(':visible')) {
            var mois = parseInt($('#month_comm').val());
            var annee = parseInt($('#year_comm').val());
            $calendarDiv.fullCalendar('gotoDate', annee, mois);
        }
    });
    
    /**
     * Changement commission : reset calendriers + afficher/masquer boutons
     * Support des IDs Symfony et legacy
     * Legacy: showCalendar() fonction (lignes 367-371)
     */
    $('#dossier_commission, #COMMISSION_DOSSIER').on('change', function() {
        var commissionId = $(this).val();
        
        // Reset calendriers et masquer conteneurs
        $('#calendar').hide().parent().hide();
        $('#calendar').html('');
        $('#calendarVisite').hide().parent().hide();
        $('#calendarVisite').html('');
        
        // Reset boutons à état initial
        $('.hideCalendar').hide();
        
        if (commissionId && commissionId != '' && commissionId != '0') {
            // Commission sélectionnée → afficher boutons "Afficher"
            $('.showCalendar').show();
        } else {
            // Aucune commission → masquer boutons
            $('.showCalendar').hide();
        }
    });
    
    /**
     * Initialisation au chargement : afficher/masquer boutons selon commission
     * Legacy: showCalendar() (lignes 367-371)
     */
    function showCalendar() {
        var initialCommission = $('#dossier_commission').val() || $('#COMMISSION_DOSSIER').val();
        if (initialCommission && initialCommission != '' && initialCommission != '0') {
            $('.showCalendar').show();
        } else {
            $('.showCalendar').hide();
        }
    }
    
    showCalendar();
});
