/**
 * Initialisation calendriers pour édition dossiers
 * Utilise les routes backend legacy (Zend)
 */
(function($) {
    'use strict';

    // Configuration
    var CONFIG = {
        /**
         * Routes non migrées en Symfony et conservées en Zend pour compatibilité Calendrier des commissions
         * Seront à migrer lors de la migration du Calendrier des commissions
         */
        routes: {
            getEvents: '/calendrier-des-commissions/recupevenement',
            getLinkedDates: '/calendrier-des-commissions/recupdateliee',
            moveEvent: '/calendrier-des-commissions/deplacecommissiondate',
            resizeEvent: '/calendrier-des-commissions/resizecommissiondate'
        },
        selectors: {
            showButton: '.showCalendar',
            hideButton: '.hideCalendar',
        }
    };

    /**
     * Initialise un calendrier FullCalendar
     */
    function initCalendar($container, options) {
        var calendarId = $container.attr('id');

        $container.fullCalendar({
            buttonIcons:{
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
            month: options.month,
            year: options.year,
            slotMinutes: 5, //Permet d'afficher un interval de 10 minutes au lieu de 30 par défaut
            selectHelper: true,
            firstDay: 1,
            weekends: true,
            monthNames: ['Janvier', 'F\u00e9vrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Ao\u00fbt',
                'Septembre', 'Octobre', 'Novembre', 'D\u00e9cembre'
            ],
            monthAbbrevs: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aout', 'Sep', 'Oct', 'Nov',
                'Dec'],
            dayNames: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
            dayNamesShort: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            lazyFetching: false, //permet de ne pas réafficher les évenements en cache
            minTime: 8,
            maxTime: 19,
            editable: true,
            disableResizing: false,
            selectable: true,
            droppable: false,
            theme: true,
            aspectRatio: 2,
            events: function(start, end, callback) {
                fetchEvents(start, end, options.commissionId, options.type, callback);
            },
            eventClick: function(event) {
                handleEventClick(event, options.dateField, options.calendarType);
            }
        });
    }

    /**
     * Récupère les événements via AJAX
     */
    function fetchEvents(start, end, commissionId, type, callback) {
        $.ajax({
            url: CONFIG.routes.getEvents,
            data: {
                start: start.getTime(),
                end: end.getTime(),
                idComm: commissionId,
                type: type,
                format: 'json'
            },
            success: function(response) {
                var events = parseEvents(response.items || response);
                callback(events);
            },
            error: function(xhr, status, error) {
                console.error('Erreur chargement événements:', error);
                callback([]);
            }
        });
    }

    /**
     * Parse les événements (remplace eval())
     */
    function parseEvents(items) {
        if (typeof items === 'string') {
            try {
                items = JSON.parse(items);
            } catch (e) {
                console.error('Erreur parsing événements:', e);
                return [];
            }
        }

        return items.map(function(item) {
            return {
                id: item.id,
                title: item.title,
                start: item.start,
                end: item.end,
                allDay: item.allDay || false,
                color: getEventColor(item.className)
            };
        });
    }

    /**
     * Gère le clic sur un événement
     */
    function handleEventClick(event, $dateField, type) {
        $dateField.value = formatDateFr(event.end || event.start);

        // Stocker l'ID de l'affectation
        var affectationFieldId = (type === 'visite')
            ? 'id_affectation_visite'
            : 'id_affectation_commission';
        $('#' + affectationFieldId).val(event.id);

        // Fermer le calendrier
        hideCalendar(type);
    }

    /**
     * Formate une date en français (dd/mm/yyyy)
     */
    function formatDateFr(dateObj) {
        if (!dateObj) return '';
        var d = new Date(dateObj);
        var dd = ('0' + d.getDate()).slice(-2);
        var mm = ('0' + (d.getMonth() + 1)).slice(-2);
        var yyyy = d.getFullYear();
        return dd + '/' + mm + '/' + yyyy;
    }

    /**
     * Retourne la couleur selon le type d'événement
     */
    function getEventColor(className) {
        var colors = {
            'display-1': '#3A87AD',  // Commission en salle
            'display-2': '#468847',  // Visite
            'display-3': '#F89406'   // Groupe de visite
        };
        return colors[className] || '#3A87AD';
    }

    /**
     * Affiche un calendrier
     */
    function showCalendar(type) {
        var containerId = (type === 'visite') ? 'calendarVisite' : 'calendar';
        var $container = $('#' + containerId);
        var $containerLi = $container.closest('li');

        $container.show();
        $containerLi.show();

        // Boutons
        $('[data-calendar-type="' + type + '"].showCalendar').toggleClass('hidden');
        $('[data-calendar-type="' + type + '"].hideCalendar').toggleClass('hidden');
    }

    /**
     * Cache un calendrier
     */
    function hideCalendar(type) {
        var containerId = (type === 'visite') ? 'calendarVisite' : 'calendar';
        var $container = $('#' + containerId);
        var $containerLi = $container.closest('li');

        $container.html('');
        $container.hide();
        $containerLi.hide();

        // Boutons
        $('[data-calendar-type="' + type + '"].showCalendar').toggleClass('hidden');
        $('[data-calendar-type="' + type + '"].hideCalendar').toggleClass('hidden');
    }

    // Initialisation au chargement
    $(document).ready(function() {
        // Bouton Afficher
        $(CONFIG.selectors.showButton).on('click', function(e) {
            e.preventDefault();
            var dossierType = $('#dossier_type').val();
            var calendarType = $(this).data('calendar-type');

            // Règle legacy : si groupe de visite et calendrier commission, considérer comme en salle
            if (dossierType === '3' && calendarType === 'commission') {
                dossierType = 1
            }

            var commissionId = $('#dossier_commission').val();
            var $dateField = $(this)[0].previousElementSibling;

            if (!commissionId) {
                alert('Veuillez sélectionner une commission');
                return;
            }

            showCalendar(calendarType);

            var containerId = (calendarType === 'visite') ? 'calendarVisite' : 'calendar';
            var month = parseInt($('#month_' + calendarType.replace('visite', 'visite').replace('commission', 'comm')).val());
            var year = parseInt($('#year_' + calendarType.replace('visite', 'visite').replace('commission', 'comm')).val());

            initCalendar($('#' + containerId), {
                type: dossierType,
                calendarType: calendarType,
                commissionId: commissionId,
                month: month,
                year: year,
                dateField: $dateField
            });
        });

        // Bouton Cacher
        $(CONFIG.selectors.hideButton).on('click', function(e) {
            e.preventDefault();
            var type = $(this).data('calendar-type') ||
                ($(this).closest('li[id]').attr('id') === 'dateVisite' ? 'visite' : 'commission');
            hideCalendar(type);
        });
    });
})(jQuery);