/**
 * Initialisation calendriers pour édition dossiers
 * Utilise les routes backend legacy (Zend)
 */
(function($) {
    'use strict';

    const CONFIG = {
        /**
         * Routes non migrées en Symfony et conservées en Zend pour compatibilité Calendrier des commissions
         * Seront à migrer lors de la migration du Calendrier des commissions
         */
        routes: {
            getEvents: Routing.generate('calendrier_des_commissions_recuperer_evenements'),
            getLinkedDates: Routing.generate('calendrier_des_commissions_recuperer_dates_liees'),
            moveEvent: Routing.generate('calendrier_des_commissions_deplacer_date'),
            resizeEvent: Routing.generate('calendrier_des_commissions_redimensionner_date'),
            selectEvent: Routing.generate('calendrier_des_commissions_creer_date')
        },
        selectors: {
            showButton: '.showCalendar',
            hideButton: '.hideCalendar',
        },
    };

    function initCalendar($container, options) {
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
            lazyFetching: false, // Permet de ne pas réafficher les événements en cache
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
                handleEventClick(event, options.dateField, options.calendarType, options.type);
            },
            eventResize: function(event) {
                handleEventResize(event, $container)
            },
            eventDrop: function(event) {
                handleEventDrop(event, $container)
            },
            select: function(start, end) {
                handleSelect(start, end, options.commissionNom, options.type)
            }
        });
    }

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
                const events = parseEvents(response.items || response);
                callback(events);
            },
            error: function(xhr, status, error) {
                console.error('Erreur chargement événements:', error);
                callback([]);
            }
        });
    }

    function handleEventClick(event, $dateField, calendarType, dossierType) {
        $dateField.val(formatDateISO(event.start, event.end));
        // @todo: ajouter les identifiants d'affectation à la date de commission/visite

        /**
         * @fixme: La condition && (dossierType === '2' || dossierType === '3') est en théorie inutile
         * car le champ dateVisite ne s'affiche que pour des dossiers de type 2 ou 3.
         * On pourrait donc normalement la supprimer pour alléger.
         */
        if (calendarType === 'visite' && (dossierType === '2' || dossierType === '3')) {
            $.ajax({
                type: "GET",
                url: CONFIG.routes.getLinkedDates,
                data: {
                    idDate: event.id,
                },
                success: function(jsonResult) {
                    //On récupere toutes les dates liées et on les affiche
                    if (jsonResult.length > 1) {
                        const alertDatesLieesDiv = document.querySelectorAll('.dates-liees__wrapper');
                        alertDatesLieesDiv.forEach(div => div.remove());

                        const alertDatesLieesTemplate = document.querySelector('#dates-liees__template');
                        const alertDatesLieesTemplateClone = document.importNode(alertDatesLieesTemplate.content, true);
                        alertDatesLieesTemplateClone.querySelector('#dates-liees__content').textContent = getDatesLiees(event, jsonResult)

                        $dateField.closest('li').append(alertDatesLieesTemplateClone);
                    }
                }
            });
        }

        hideCalendar(calendarType);

        return false;
    }

    /**
     * Retourne les dates liées d'une date de commission en convertissant le format ISO en format FR.
     * Ex: date de commission = 2026-01-07 et dates liées = 2026-01-08 et 2026-01-09 => "08/01/2026, 09/01/2026"
     */
    function getDatesLiees(event, parsedResult) {
        return parsedResult
            .map(pr => pr['DATE_COMMISSION'].split('-').reverse().join('/'))
            .join(', ')
    }

    function handleEventResize(event, $container) {
        // Heure de fin change uniquement sur la vue semaine et jour, pas sur mois
        const view = $container.fullCalendar('getView');
        const vueInfo = view.title.split(" ");

        if (vueInfo.length === 2) {
            // Permet d'empêcher la modification en mode mois (Ajouter message d'indication si besoin)
            $container.fullCalendar('refetchEvents');

            return false;
        }

        $.ajax({
            type: 'POST',
            url: CONFIG.routes.resizeEvent,
            data: {
                idComm: event.id,
                fin: event.end.toISOString()
            },
            success: function(response) {
                if (response && response.success) {
                    $.toast.success('L\'événement a bien été modifié');
                } else {
                    const errorMsg = (response && response.error) ? response.error : 'Erreur lors de la modification';
                    $.toast.error(errorMsg);
                    $container.fullCalendar('refetchEvents'); // Annuler le changement visuellement
                }
            },
            error: function(xhr, status, error) {
                $.toast.error('Erreur lors de la modification de l\'événement');
                $container.fullCalendar('refetchEvents'); // Annuler le changement visuellement
            }
        });
    }

    function handleEventDrop(event, $container) {
        const tabDateSelect = event.end.toString().split(" ");

        if (tabDateSelect[0] === "Sun" || tabDateSelect[0] === "Sat") {
            $container.fullCalendar('refetchEvents');

            return false;
        }

        $.ajax({
            type: 'POST',
            url: CONFIG.routes.moveEvent,
            data: {
                idComm: event.id,
                debut: event.start.toISOString(),
                fin: event.end.toISOString()
            },
            success: function(response) {
                if (response && response.success) {
                    $.toast.success('L\'événement a bien été déplacé');
                } else {
                    const errorMsg = (response && response.error) ? response.error : 'Erreur lors du déplacement';
                    $.toast.error(errorMsg);
                    $container.fullCalendar('refetchEvents'); // Annuler le changement visuellement
                }
            },
            error: function(xhr, status, error) {
                $.toast.error('Erreur lors du déplacement de l\'événement');
                $container.fullCalendar('refetchEvents'); // Annuler le changement visuellement
            }
        });
    }

    function handleSelect(start, end, commissionNom, dossierType) {
        $.ajax({
            url: CONFIG.routes.selectEvent,
            data: {
                libelle: commissionNom,
                typeEvenement: dossierType,
                start: start.toISOString(),
                end: end.toISOString(),
            },
            success: function(response) {
                $('#calendrier-des-commission__creation-date .modal-body').html(response)
                $('#calendrier-des-commission__creation-date').modal('show')

                $('#commission_dates_withPeriodicite').on('click', function () {
                    $('#commission_dates_dateFin').closest('.form-group').toggleClass('hidden')
                    $('#commission_dates_periodicite').closest('.form-group').toggleClass('hidden')
                })
            }
        })
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
     * Formate une date en ISO (yyyy-mm-dd)
     */
    function formatDateISO(start, end) {
        if (!start || !end) return '';

        const dateStart = new Date(start);
        const dateEnd = new Date(end);
        let date = dateEnd;

        if (dateEnd < dateStart) {
            date = dateStart;
        }

        const dd = ('0' + date.getUTCDate()).slice(-2);
        const mm = ('0' + (date.getUTCMonth() + 1)).slice(-2);
        const yyyy = date.getUTCFullYear();

        return yyyy + '-' + mm + '-' + dd;
    }

    /**
     * Retourne la couleur selon le type d'événement
     */
    function getEventColor(className) {
        const colors = {
            'display-1': '#3A87AD',  // Commission en salle
            'display-2': '#468847',  // Visite
            'display-3': '#F89406',   // Groupe de visite
        };

        return colors[className] || '#3A87AD';
    }

    /**
     * Affiche un calendrier
     */
    function showCalendar(type) {
        const containerId = (type === 'visite') ? 'calendarVisite' : 'calendar';
        const $container = $('#' + containerId);
        const $containerLi = $container.closest('li');

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
        const containerId = (type === 'visite') ? 'calendarVisite' : 'calendar';
        const $container = $('#' + containerId);
        const $containerLi = $container.closest('li');

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
            let dossierType = $('#dossier_type').val();
            const calendarType = $(this).data('calendar-type');

            // Règle legacy : si groupe de visite et calendrier commission, considérer comme en salle
            if (dossierType === '3' && calendarType === 'commission') {
                dossierType = 1
            }

            const commissionId = $('#dossier_commission').val();

            let nomEtab = "";
            let nomEtabSelector;
            if ($("#infosEtabs .etab").length > 0) {
                nomEtabSelector = "#infosEtabs .etab";
            } else if ($("#infosEtablissement .etab").length > 0) {
                nomEtabSelector = "#infosEtablissement .etab";
            }
            nomEtab = document.querySelector(nomEtabSelector).innerText.trim();
            nomEtab = nomEtab.replace('&nbsp;', ' ');
            nomEtab = nomEtab.replace("'", " ");

            const commissionNom = $('#dossier_nature option:selected').text().trim() + ' - ' + nomEtab;
            const $dateField = $(this).prev();

            if (!commissionId) {
                alert('Veuillez sélectionner une commission');
                return;
            }

            showCalendar(calendarType);

            const containerId = (calendarType === 'visite') ? 'calendarVisite' : 'calendar';
            const month = parseInt($('#month_' + calendarType.replace('visite', 'visite').replace('commission', 'comm')).val());
            const year = parseInt($('#year_' + calendarType.replace('visite', 'visite').replace('commission', 'comm')).val());

            initCalendar($('#' + containerId), {
                type: dossierType,
                calendarType: calendarType,
                commissionId: commissionId,
                month: month,
                year: year,
                dateField: $dateField,
                commissionNom: commissionNom
            });
        });

        // Bouton Cacher
        $(CONFIG.selectors.hideButton).on('click', function(e) {
            e.preventDefault();
            const type = $(this).data('calendar-type') ||
                ($(this).closest('li[id]').attr('id') === 'dateVisite' ? 'visite' : 'commission');
            hideCalendar(type);
        });
    });
})(jQuery);