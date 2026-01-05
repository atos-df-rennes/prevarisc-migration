/**
 * Calendrier FullCalendar v1 (legacy) pour édition dossiers
 * Adapté de prevarisc/application/views/scripts/dossier/index.phtml (lignes 194-843)
 * 
 * Utilise jQuery FullCalendar v1 plugin (jquery.fullcalendar.js)
 * Routes backend: /calendrier-des-commissions/* (legacy Zend)
 */

(function($) {
    'use strict';

    /**
     * Plugin jQuery pour afficher calendrier FullCalendar v1
     * 
     * Usage: $('#calendar').afficheCalendar(idComm, emplacement, typeDossier, divName, btnShowId, mois, annee);
     */
    $.fn.afficheCalendar = function(idComm, emplacement, typeDossier, divInfo, infoCalendar, mois, annee) {
        
        var calendar = $(this).fullCalendar({
            buttonIcons: {
                prevYear: 'seek-prev',
                nextYear: 'seek-next'
            },
            buttonText: {
                month: 'Mois',
                day: 'Jour',
                week: 'Semaine',
                today: 'Aujourd\'hui'
            },
            header: {
                left: 'prevYear prev,next nextYear today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            columnFormat: {
                month: 'dddd',
                week: 'dddd dd',
                day: 'dddd dd MMMM yyyy'
            },
            defaultView: 'month',
            timeFormat: 'H:mm{ - H:mm}',
            axisFormat: 'H(:mm)',
            month: mois,
            year: annee,
            slotMinutes: 5,
            selectHelper: true,
            firstDay: 1,
            weekends: true,
            monthNames: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
            monthNamesShort: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aout', 'Sep', 'Oct', 'Nov', 'Dec'],
            dayNames: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
            dayNamesShort: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            lazyFetching: false,
            minTime: 8,
            maxTime: 19,
            editable: true,
            disableResizing: false,
            selectable: true,
            droppable: false,
            theme: true,
            aspectRatio: 2,
            
            // Récupération événements AJAX (route legacy Zend)
            events: function(start, end, callback) {
                var type = typeDossier;
                
                if (type == 3) {
                    if (infoCalendar == 'showCommission') {
                        type = 1;
                    }
                }
                
                $.ajax({
                    url: '/calendrier-des-commissions/recupevenement?start=' + start.getTime() + 
                         '&end=' + end.getTime() + '&type=' + type,
                    data: {
                        format: 'json',
                        idComm: idComm
                    },
                    success: function(result) {
                        var events = [];
                        for (var x in result['items']) {
                            var colorCal;
                            switch (result['items'][x]['className']) {
                                case 'display-1':
                                    colorCal = '#3A87AD';
                                    break;
                                case 'display-2':
                                    colorCal = '#468847';
                                    break;
                                case 'display-3':
                                    colorCal = '#F89406';
                                    break;
                                default:
                                    break;
                            }
                            events.push({
                                id: result['items'][x]['id'],
                                title: result['items'][x]['title'],
                                start: result['items'][x]['start'],
                                end: result['items'][x]['end'],
                                allDay: result['items'][x]['allDay'],
                                color: colorCal
                            });
                        }
                        callback(events);
                        return false;
                    }
                });
            },
            
            // Sélection d'une plage de dates (création nouvel événement)
            // Note: Pour l'instant désactivé - nécessite création modale Bootstrap 3 + formulaire Symfony
            // TODO: Implémenter modale création date commission/visite
            select: function(start, end, allDay) {
                // Désactivé temporairement
                console.warn('Création de dates depuis le calendrier non implémentée (nécessite modale Bootstrap 3)');
                calendar.fullCalendar('unselect');
                return false;
            },
            
            // Clic sur événement existant
            eventClick: function(event, jsEvent, view) {
                if (emplacement == 'calendrierComm') {
                    // Menu Calendrier → édition
                    my_dialog('/calendrier-des-commissions/dialogcomm', 
                             'idComm=' + idComm + '&idDateComm=' + event.id + '&do=edit');
                } else if (emplacement == 'dossier') {
                    // Édition dossier → remplir champ date
                    
                    if (typeDossier == 1) {
                        // Type étude : sélection simple
                        var dateSelect = $(this).parent().parent().parent().parent().parent().prev().attr('id');
                        var dateEnd = new Date(event.end);
                        var dateStart = new Date(event.start);
                        var date = dateEnd;
                        
                        if (date < dateStart) {
                            date = dateStart;
                        }
                        
                        var dd = date.getUTCDate();
                        var mm = date.getUTCMonth() + 1;
                        var aa = date.getUTCFullYear();
                        
                        if (dd < 10) { dd = '0' + dd; }
                        if (mm < 10) { mm = '0' + mm; }
                        
                        if (dateSelect == 'DATEVISITE' || dateSelect == 'field-dateVisite') {
                            $('#DATEVISITE_INPUT').val(dd + '/' + mm + '/' + aa);
                            $('#ID_AFFECTATION_DOSSIER_VISITE').val(event.id);
                        } else if (dateSelect == 'DATECOMM' || dateSelect == 'field-dateCommission') {
                            $('#DATECOMM_INPUT').val(dd + '/' + mm + '/' + aa);
                            $('#ID_AFFECTATION_DOSSIER_COMMISSION').val(event.id);
                        }
                        $('#' + dateSelect).find('.hideCalendar').click();
                        
                    } else if (typeDossier == 3 || typeDossier == 2) {
                        // Type visite/groupe visite : dates liées possibles
                        var dateSelect = $(this).parent().parent().parent().parent().parent().prev().attr('id');
                        
                        if (dateSelect == 'DATEVISITE' || dateSelect == 'field-dateVisite') {
                            // Récupérer dates liées
                            $.ajax({
                                data: 'idDate=' + event.id,
                                url: '/calendrier-des-commissions/recupdateliee',
                                type: 'GET',
                                success: function(affichageResultat) {
                                    var rez = eval('(' + affichageResultat + ')');
                                    
                                    if (rez.length == 1) {
                                        // Une seule date
                                        var dateEnd = new Date(event.end);
                                        var dateStart = new Date(event.start);
                                        var date = dateEnd;
                                        
                                        if (date < dateStart) {
                                            date = dateStart;
                                        }
                                        
                                        var dd = date.getUTCDate();
                                        var mm = date.getUTCMonth() + 1;
                                        var aa = date.getUTCFullYear();
                                        
                                        if (dd < 10) { dd = '0' + dd; }
                                        if (mm < 10) { mm = '0' + mm; }
                                        
                                        $('#DATEVISITE_INPUT').val(dd + '/' + mm + '/' + aa);
                                        $('#ID_AFFECTATION_DOSSIER_VISITE').val(event.id);
                                        $('#hideVisite').click();
                                        
                                    } else if (rez.length > 1) {
                                        // Plusieurs dates
                                        var nbResultats = rez.length;
                                        var nbResultatsTotal = nbResultats;
                                        var listeDates = '';
                                        
                                        for (var i in rez) {
                                            if (nbResultats == nbResultatsTotal) {
                                                $('#ID_AFFECTATION_DOSSIER_VISITE').val(event.id);
                                            }
                                            var dateFr = convertDateAngToFr(rez[i]['DATE_COMMISSION']);
                                            if (nbResultats > 1) {
                                                listeDates += dateFr + ', ';
                                            } else if (nbResultats == 1) {
                                                listeDates += dateFr;
                                            }
                                            nbResultats--;
                                        }
                                        $('#DATEVISITE_INPUT').val(listeDates);
                                        $('#hideVisite').click();
                                    }
                                }
                            });
                        } else if (dateSelect == 'DATECOMM' || dateSelect == 'field-dateCommission') {
                            var dateEnd = new Date(event.end);
                            var dateStart = new Date(event.start);
                            var date = dateEnd;
                            
                            if (date < dateStart) {
                                date = dateStart;
                            }
                            
                            var dd = date.getUTCDate();
                            var mm = date.getUTCMonth() + 1;
                            var aa = date.getUTCFullYear();
                            
                            if (dd < 10) { dd = '0' + dd; }
                            if (mm < 10) { mm = '0' + mm; }
                            
                            $('#DATECOMM_INPUT').val(dd + '/' + mm + '/' + aa);
                            $('#ID_AFFECTATION_DOSSIER_COMMISSION').val(event.id);
                            $('#hideCommission').click();
                        }
                    }
                }
                return false;
            },
            
            // Drag & drop événement (déplacement)
            eventDrop: function(event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view) {
                var tabDateSelect = event.end;
                tabDateSelect = tabDateSelect.toString();
                tabDateSplited = tabDateSelect.split(' ');
                
                if (tabDateSplited[0] == 'Sun' || tabDateSplited[0] == 'Sat') {
                    $('#' + divInfo).fullCalendar('refetchEvents');
                } else {
                    $.ajax({
                        data: 'idComm=' + event.id + '&debut=' + event.start + '&fin=' + event.end,
                        url: '/calendrier-des-commissions/deplacecommissiondate',
                        type: 'POST',
                        success: function(affichageResultat) {}
                    });
                }
            },
            
            // Redimensionnement événement (changement heure fin)
            eventResize: function(event, jsEvent, ui, view) {
                var view = $('#' + divInfo).fullCalendar('getView');
                var vueInfo = view.title;
                vueInfo = vueInfo.split(' ');
                
                if (vueInfo.length == 2) {
                    // Mode mois : interdire modification
                    $('#' + divInfo).fullCalendar('refetchEvents');
                    return false;
                }
                
                $.ajax({
                    data: 'idComm=' + event.id + '&fin=' + event.end,
                    url: '/calendrier-des-commissions/resizecommissiondate',
                    type: 'POST'
                });
            }
        });
    };
    
    /**
     * Fonction utilitaire : convertir date anglaise en française
     */
    window.convertDateAngToFr = function(dateAng) {
        if (!dateAng || dateAng == '0000-00-00') {
            return '';
        }
        var parts = dateAng.split('-');
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    };

})(jQuery);
