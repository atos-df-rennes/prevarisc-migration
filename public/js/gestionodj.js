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

    // Génération de documents : appel des routes legacy en attendant la migration de cette fonctionnalité
    $("#createConvoc").click(function() {
        if ($("#external-events").children("div").children("div").length == 0) {
            $.ajax({
                url: "/calendrier-des-commissions/generationconvoc",
                data: "dateCommId=" + $("#dateCommId").val(),
                type: "POST",
                beforeSend: function() {
                    $("#infoGeneration").html("Création en cours, veuillez patienter   <img src='/images/load.gif' />");
                },
                success: function(affichageResultat) {
                    $("#infoGeneration").html(affichageResultat);
                },
                error: function() {
                    $("#infoGeneration").html("Une erreur s'est produite");
                    return false;
                }
            });
        } else {
            $("#infoGeneration").html("<span id='notif' class='ui-state-error' style='padding: 0.3em;'>Certains dossiers prévus n'ont pas été placés dans le calendrier</span><br/><br/><br/>");
        }
    });

    $("#createOdj").click(function() {
        if ($("#external-events").children("div").children("div").length == 0) {
            $.ajax({
                url: "/calendrier-des-commissions/generationodj",
                data: "dateCommId=" + $("#dateCommId").val(),
                type: "POST",
                beforeSend: function() {
                    $("#infoGeneration").html("Création en cours, veuillez patienter   <img src='/images/load.gif' />");
                },
                success: function(affichageResultat) {
                    $("#infoGeneration").html(affichageResultat);
                },
                error: function() {
                    $("#infoGeneration").html("Une erreur s'est produite");
                    return false;
                }
            });
        } else {
            $("#infoGeneration").html("<span id='notif' class='ui-state-error' style='padding: 0.3em;'>Certains dossiers prévus n'ont pas été placés dans le calendrier</span><br/><br/><br/>");
        }
    });

    $("#createPv").click(function() {
        if ($("#external-events").children("div").children("div").length == 0) {
            $.ajax({
                url: "/calendrier-des-commissions/generationpv",
                data: "dateCommId=" + $("#dateCommId").val(),
                type: "POST",
                beforeSend: function() {
                    $("#infoGeneration").html("Création en cours, veuillez patienter   <img src='/images/load.gif' />");
                },
                success: function(affichageResultat) {
                    $("#infoGeneration").html(affichageResultat);
                },
                error: function() {
                    $("#infoGeneration").html("Une erreur s'est produite");
                    return false;
                }
            });
        } else {
            $("#infoGeneration").html("<span id='notif' class='ui-state-error' style='padding: 0.3em;'>Certains dossiers prévus n'ont pas été placés dans le calendrier</span><br/><br/><br/>");
        }
    });

    $("#creationCompteRendu").click(function() {
        if ($("#external-events").children("div").children("div").length == 0) {
            $.ajax({
                url: "/calendrier-des-commissions/generationcompterendu",
                data: "dateCommId=" + $("#dateCommId").val(),
                type: "POST",
                beforeSend: function() {
                    $("#infoGeneration").html("Création en cours, veuillez patienter   <img src='/images/load.gif' />");
                },
                success: function(affichageResultat) {
                    $("#infoGeneration").html(affichageResultat);
                },
                error: function() {
                    $("#infoGeneration").html("Une erreur s'est produite");
                    return false;
                }
            });
        } else {
            $("#infoGeneration").html("<span id='notif' class='ui-state-error' style='padding: 0.3em;'>Certains dossiers prévus n'ont pas été placés dans le calendrier</span><br/><br/><br/>");
        }
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
