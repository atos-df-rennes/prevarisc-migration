/* =============================================================================
 * gestionodj.js — Gestion de l'ordre du jour (FullCalendar + drag & drop)
 *
 * Dépendances globales attendues :
 *   - jQuery, jQuery UI (draggable, sortable)
 *   - FullCalendar v1
 *   - Routing (FOSJsRoutingBundle)
 *   - $.toast (toast-notifications.js)
 *   - GESTIONODJ_CONFIG (injecté par le template Twig)
 *
 * CONFIG attendue (window.GESTIONODJ_CONFIG) :
 *   urlRecupEvenement   : route calendrier_des_commissions_recup_evenement_odj
 *   urlDropOdj          : route calendrier_des_commissions_drop_odj
 *   urlAffecteDoss      : route calendrier_des_commissions_affecte_doss_odj
 *   urlResizeOdj        : route calendrier_des_commissions_resize_odj
 *   urlGestionHeures    : route calendrier_des_commissions_gestion_heures
 *   urlChangementOrdre  : route calendrier_des_commissions_changement_ordre
 * ============================================================================= */

/* ── Helper timezone local ────────────────────────────────────────────────── */
/* toISOString() produit une heure UTC (ex: 9h Paris → 8hZ en hiver).        */
/* Ce helper envoie l'heure locale sans décalage pour que PHP crée une       */
/* \DateTime dans le fuseau par défaut du serveur (Europe/Paris).            */
function toLocalISOString(date) {
    if (!date) { return ''; }
    var pad = function(n) { return String(n).padStart(2, '0'); };
    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate())
        + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds());
}

$(document).ready(function() {
    $('#external-events div.external-event').each(function() {
        // Création d'un objet Event (FullCalendar v1)
        var eventObject = {
            title: $.trim($(this).text())
        };
        $(this).data('eventObject', eventObject);

        // Rendu draggable (jQuery UI)
        $(this).draggable({
            zIndex: 999,
            revert: 'invalid',   // revenir seulement si drop invalide (hors calendrier)
            revertDuration: 0
        });
    });

    ( function($) {
        $.fn.afficheCalendar = function(jour, mois, annee, hDeb, hFin) {
            var calendar = $('#calendar').fullCalendar( {
                date: jour,
                month: mois,
                year: annee,
                header: {
                    left: '',
                    center: 'title',
                    right: ''
                },
                columnFormat: {
                    month: 'dddd',
                    week: 'dddd dd',
                    day: 'dddd dd MMMM yyyy'
                },
                defaultView: 'agendaDay',
                timeFormat: 'H:mm{ - H:mm}',
                allDaySlot: false,
                axisFormat: 'H(:mm)',
                slotMinutes: 5,
                selectHelper: true,
                weekends: false,
                monthNames: ['Janvier','F\u00e9vrier','Mars','Avril','Mai','Juin','Juillet','Ao\u00fbt','Septembre','Octobre','Novembre','D\u00e9cembre'],
                monthAbbrevs: ['Jan','Fev','Mar','Avr','Mai','Juin','Juil','Aout','Sep','Oct','Nov','Dec'],
                dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
                dayNamesShort: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam','Dim'],
                lazyFetching: false,
                minTime: hDeb,
                maxTime: hFin,
                editable: true,
                disableResizing: false,
                selectable: true,
                droppable: true,
                theme: true,
                aspectRatio: 0,
                defaultEventMinutes: 5,
                events: function(start, end, callback) {
                    mois++;
                    var myDate = new Date(annee + "/" + mois + "/" + jour);

                    $.ajax({
                        url: GESTIONODJ_CONFIG.urlRecupEvenement + '?start=' + myDate.getTime() + "&end=" + myDate.getTime(),
                        data: {
                            dateCommId: $("#dateCommId").val()
                        },
                        success: function( result ) {
                            var events = [];
                            for( var x in result["items"] ) {
                                events.push({
                                    id: result["items"][x]["id"],
                                    title: result["items"][x]["title"],
                                    start: result["items"][x]["start"],
                                    end: result["items"][x]["end"],
                                    allDay: result["items"][x]["allDay"],
                                    url: result["items"][x]["url"],
                                    backgroundColor: result["items"][x]["backgroundColor"],
                                    textColor: "black"
                                });
                            }
                            callback(events);
                        }
                    });
                },
                select: function(start, end, allDay) {
                    $(this).fullCalendar('refetchEvents');
                },
                eventDrop: function( event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view ) {
                    var heureDeb = event.start;
                    var heureFin = event.end;

                    var hrefTab = $(this).attr('href').split("/");
                    var tailleTab = hrefTab.length;
                    var idDossier = hrefTab[tailleTab - 1];

                    $.ajax({
                        url: GESTIONODJ_CONFIG.urlDropOdj,
                        type: 'POST',
                        data: {
                            heureDebut: toLocalISOString(heureDeb),
                            heureFin: toLocalISOString(heureFin),
                            dateCommId: $("#dateCommId").val(),
                            idDossier: idDossier
                        },
                        success: function(result) {
                            $.toast.fromResponse(result);
                        },
                        error: function() {
                            $.toast.error('Erreur lors du déplacement de l\'événement.');
                        }
                    });
                },
                drop: function(date, allDay) {
                    var idDossier = $(this).attr('id');
                    /* Capturer $(this) et les données avant l'appel AJAX           */
                    /* (le contexte 'this' change dans les callbacks asynchrones).  */
                    var $droppedEl = $(this);
                    var originalEventObject = $droppedEl.data('eventObject');

                    $.ajax({
                        url: GESTIONODJ_CONFIG.urlAffecteDoss,
                        type: 'POST',
                        data: {
                            datadebut: toLocalISOString(date),
                            dateCommId: $("#dateCommId").val(),
                            idDossier: idDossier
                        },
                        success: function(result) {
                            $("#nbDossiersAffect").text(result.countAffect);
                            $.toast.success('Dossier affecté à l\'ordre du jour.');

                            var copiedEventObject = $.extend({}, originalEventObject || { title: $.trim($droppedEl.text()) });
                            copiedEventObject.start = date;
                            copiedEventObject.end = new Date(date.getTime() + 5 * 60000);
                            copiedEventObject.url = Routing.generate('dossier_index', { id: idDossier });
                            copiedEventObject.allDay = allDay;
                            copiedEventObject.backgroundColor = result.verrou == 1 ? '#83bff6' : '#E2A420';
                            copiedEventObject.textColor = 'black';

                            $('#calendar').fullCalendar('renderEvent', copiedEventObject, true);
                            $droppedEl.remove();
                        },
                        error: function() {
                            $.toast.error('Erreur lors de l\'affectation du dossier.');
                        }
                    });
                },
                eventResize: function( event, dayDelta, minuteDelta, revertFunc ) {
                    var dateFin = event.end;

                    var hrefTab = $(this).attr('href').split("/");
                    var tailleTab = hrefTab.length;
                    var idDossier = hrefTab[tailleTab - 1];

                    $.ajax({
                        url: GESTIONODJ_CONFIG.urlResizeOdj,
                        type: 'POST',
                        data: {
                            dateFin: toLocalISOString(dateFin),
                            dateCommId: $("#dateCommId").val(),
                            idDossier: idDossier
                        },
                        success: function(result) {
                            $.toast.fromResponse(result);
                        },
                        error: function() {
                            $.toast.error('Erreur lors du redimensionnement de l\'événement.');
                        }
                    });
                }
            });
        }
    })(jQuery);

    var date;
    var myDate = new Date($("#dateOdj").val());

    $("#calendar").afficheCalendar(myDate.getUTCDate(), myDate.getUTCMonth(), myDate.getUTCFullYear(), $("#heureDeb").val(), $("#heureFin").val());

    $(".fc-agenda-allday").hide();
    $(".fc-header").hide();

    /*
     * INITIALISATION ET GESTION DES BOUTONS PJ, ODJ et CONVOC
     */

    var dialogDateCommission = $('#modal-pieces-jointes');

    $("#commissionPj").click(function() {
        $.ajax({
            type: "GET",
            url: Routing.generate('calendrier_des_commissions_pieces_jointes', { id: $("#dateCommId").val() }),
            success: function(msg) {
                dialogDateCommission.find('.modal-body').html(msg);
                dialogDateCommission.modal('show');
            }
        });
    });

    // Génération de documents de commission (Symfony)
    function generateDocument(url, csrfToken, documentLabel) {
        if ($("#external-events").children("div").children("div").length !== 0) {
            $.toast.warning("Certains dossiers prévus n'ont pas été placés dans le calendrier.");
            return;
        }

        var enCoursToastId = $.toast.info('Création ' + documentLabel + ', veuillez patienter…', { autoClose: false });

        $.ajax({
            url: url,
            data: { _token: csrfToken },
            type: "POST",
            dataType: "json",
            success: function(response) {
                $.toast.close(enCoursToastId);
                if (response.success) {
                    $.toast.success(response.summary, { autoClose: false });
                } else {
                    var details = [];
                    if (response.results) {
                        for (var i = 0; i < response.results.length; i++) {
                            var r = response.results[i];
                            if (r.status === 'error') {
                                details.push(r.memberLabel + ' : ' + r.message);
                            }
                        }
                    }
                    var message = response.summary;
                    if (details.length > 0) {
                        message += '\n' + details.join('\n');
                    }
                    $.toast.warning(message, { autoClose: false });
                }
            },
            error: function(xhr) {
                $.toast.close(enCoursToastId);
                var msg = "Une erreur s'est produite lors de la génération.";
                if (xhr.responseJSON && xhr.responseJSON.summary) {
                    msg = xhr.responseJSON.summary;
                }
                $.toast.error(msg, { autoClose: false });
            }
        });
    }

    $("#createConvoc").click(function() {
        generateDocument(
            GESTIONODJ_CONFIG.urlGenerationConvoc,
            GESTIONODJ_CONFIG.csrfTokenConvoc,
            'des convocations'
        );
    });

    $("#createOdj").click(function() {
        generateDocument(
            GESTIONODJ_CONFIG.urlGenerationOdj,
            GESTIONODJ_CONFIG.csrfTokenOdj,
            "de l'ordre du jour"
        );
    });

    $("#createPv").click(function() {
        generateDocument(
            GESTIONODJ_CONFIG.urlGenerationPv,
            GESTIONODJ_CONFIG.csrfTokenPv,
            'du procès-verbal'
        );
    });

    $("#creationCompteRendu").click(function() {
        generateDocument(
            GESTIONODJ_CONFIG.urlGenerationCompteRendu,
            GESTIONODJ_CONFIG.csrfTokenCompteRendu,
            'du compte-rendu'
        );
    });

    $(".heures").click(function() {
        var gestionHeures = $(this).attr('id');
        $.ajax({
            url: GESTIONODJ_CONFIG.urlGestionHeures,
            data: "dateCommId=" + $("#dateCommId").val() + "&gestionHeure=" + gestionHeures,
            type: "POST",
            beforeSend: function() {},
            success: function(affichageResultat) {
                window.location.reload();
            },
            error: function() {
                return false;
            }
        });
    });

    $(".sortable").sortable({
        update: function(event, ui) {
            var listeDossierUpdate = new Array();
            var num = 0;
            $(".sortable > li").each(function() {
                $(this).attr('num', num);
                listeDossierUpdate.push($(this).children("div").attr('id'));
                num = num + 1;
            });
            $.ajax({
                url: GESTIONODJ_CONFIG.urlChangementOrdre,
                data: "dateCommId=" + $("#dateCommId").val() + "&ordreDossier=" + listeDossierUpdate,
                type: "POST",
                beforeSend: function() {},
                success: function(result) {
                    $.toast.fromResponse(result);
                },
                error: function() {
                    $.toast.error('Erreur lors du réordonnancement.');
                }
            });
        }
    });
});
