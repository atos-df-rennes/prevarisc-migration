/* global Routing */

/**
 * Port direct de index.phtml (lignes 77-684) + dialogcomm.phtml (lignes 1-146)
 * Adaptation : .live() → $(document).on(), URLs → Routing.generate()
 */

// ─── PLUGINS jQuery (hors document.ready) ────────────────────────────────────

(function ($) {
    // Permet de vérifier le nombre de dates sélectionnées
    $.fn.countLigne = function (action, type) {
        if (action === 'add') {
            $('#addReapeat').hide();
            $('#planification').hide();
        } else if (action === 'supp') {
            if (type === 'edit') {
                if ($('#listeDates ul').length === 3) {
                    $('#addReapeat').show();
                }
            } else if (type === 'new') {
                if ($('#listeDates ul').length === 2) {
                    $('#addReapeat').show();
                }
            }
        }
    };

    $.fn.buttonHide = function () {
        $('.edition').hide();
        $('.suppression').hide();
        $('.dateDefaut').hide();
        $('.addDate').hide();
        $('.add-collection-widget').hide();
    };

    $.fn.buttonShow = function () {
        $('.edition').show();
        $('.suppression').show();
        $('.dateDefaut').show();
        $('.addDate').show();
        $('.add-collection-widget').show();
    };

    $.fn.reapeatHide = function () {
        $('#addReapeat').hide();
        $('#planification').hide();
    };

    $.fn.reapeatShow = function () {
        $('#addReapeat').show();
        $('#planification').show();
        $('#listeDates').countLigne('new');
    };
})(jQuery);

// Plugin calendrier
(function ($) {
    $.fn.afficheCalendar = function (idComm, emplacement) {
        var datas;
        if (idComm === null || typeof idComm === 'undefined') {
            datas = { format: 'json' };
        } else {
            datas = { format: 'json', idComm: idComm };
        }

        var calendar = $('#calendar').fullCalendar({
            buttonIcons: {
                prevYear: 'seek-prev',
                nextYear: 'seek-next'
            },
            buttonText: {
                month: 'Mois',
                day: 'Jour',
                week: 'Semaine',
                today: "Aujourd'hui"
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
            titleFormat: {
                month: 'MMMM yyyy',
                week: "dd[ MMMM][ yyyy]{ '&#8212;' dd MMMM yyyy}",
                day: 'dddd dd MMMM yyyy'
            },
            defaultView: 'month',
            timeFormat: 'H:mm{ - H:mm}',
            axisFormat: 'H(:mm)',
            slotMinutes: 5,
            selectHelper: true,
            firstDay: 1,
            weekends: true,
            monthNames: ['Janvier', 'F\u00e9vrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Ao\u00fbt', 'Septembre', 'Octobre', 'Novembre', 'D\u00e9cembre'],
            monthAbbrevs: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aout', 'Sep', 'Oct', 'Nov', 'Dec'],
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
            events: function (start, end, callback) {
                $.ajax({
                    url: Routing.generate('calendrier_des_commissions_recuperer_evenements') + '?start=' + start.getTime() + '&end=' + end.getTime(),
                    data: datas,
                    success: function (result) {
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
            // Lorsque l'on sélectionne une ou plusieurs dates, on ouvre la boite de dialogue
            select: function (start, end, allDay) {
                if (idComm === null || typeof idComm === 'undefined') {
                    return false;
                }
                my_dialog(
                    Routing.generate('calendrier_des_commissions_dialog_edit'),
                    'do=newComm&idComm=' + idComm + '&dateD=' + encodeURIComponent(start.toISOString()) + '&dateF=' + encodeURIComponent(end.toISOString()),
                    function () {
                        $('#dialogComm').dialog('option', 'buttons', [
                            {
                                text: 'Enregistrer dans le calendrier',
                                'class': 'btn btn-success',
                                click: function () {
                                    $.ajax({
                                        data: $('#formDateComm').serialize(),
                                        url: $('#formDateComm').attr('action') || Routing.generate('calendrier_des_commissions_creer_date'),
                                        type: 'POST',
                                        success: function (result) {
                                            if (result && result.success) {
                                                $.toast.fromResponse(result);
                                                $('#calendar').fullCalendar('refetchEvents');
                                                $('#dialogComm').dialog('close').html('');
                                            } else if (result && (result.error || result.success === false)) {
                                                $.toast.fromResponse(result);
                                            } else if (typeof result === 'string') {
                                                // Formulaire avec erreurs de validation
                                                $('#dialogComm').html(result);
                                            }
                                        }
                                    });
                                }
                            },
                            {
                                text: "Fermer la fenêtre d'édition",
                                'class': 'btn btn-default',
                                click: function () {
                                    $('#dialogComm').dialog('close').html('');
                                }
                            }
                        ]);
                    }
                );
            },
            eventClick: function (event, jsEvent, view) {
                my_dialog(
                    Routing.generate('calendrier_des_commissions_dialog_edit'),
                    'idComm=' + idComm + '&idDateComm=' + event.id + '&do=edit'
                );
                return false;
            },
            eventDrop: function (event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view) {
                $.ajax({
                    data: 'idComm=' + event.id + '&debut=' + event.start.toISOString() + '&fin=' + (event.end ? event.end.toISOString() : ''),
                    url: Routing.generate('calendrier_des_commissions_deplacer_date'),
                    type: 'POST',
                    success: function (result) {
                        $.toast.fromResponse(result);
                        if (!result || !result.success) {
                            revertFunc();
                        }
                    },
                    error: function () {
                        $.toast.error('Erreur lors du déplacement de la date');
                        revertFunc();
                    }
                });
            },
            eventResize: function (event, jsEvent, ui, view) {
                var currentView = $('#calendar').fullCalendar('getView');
                var vueInfo = currentView.title.split(' ');
                if (vueInfo.length === 2) {
                    $('#calendar').fullCalendar('refetchEvents');
                    return false;
                }
                $.ajax({
                    data: 'idComm=' + event.id + '&fin=' + (event.end ? event.end.toISOString() : ''),
                    url: Routing.generate('calendrier_des_commissions_redimensionner_date'),
                    type: 'POST',
                    success: function (result) {
                        $.toast.fromResponse(result);
                        if (!result || !result.success) {
                            $('#calendar').fullCalendar('refetchEvents');
                        }
                    },
                    error: function () {
                        $.toast.error('Erreur lors de la modification de la durée');
                        $('#calendar').fullCalendar('refetchEvents');
                    }
                });
            },
            viewRender: function (view, element) {
                var date = $('#calendar').fullCalendar('getDate');
                var month_int = date.getMonth() + 1;
                var year_int = date.getFullYear();
                // @todo: Route exportoutlookmois non migrée (Phase D) — lien désactivé
                $('#exportCom').attr('href', '#');
                $('#syncCal').hide();
                $('#syncCom').show();
            }
        });
    };
})(jQuery);

// ─── HANDLERS (dans document.ready) ──────────────────────────────────────────

$(document).ready(function () {

    // Initialisation si une commission est déjà sélectionnée (retour de page)
    if ($('#idCommission').val() !== '') {
        $('.commissionLink[data-value="' + $('#idCommission').val() + '"]').click();
        $('#calendar').html('').afficheCalendar(null, 'calendrierComm');
        $('#syncCal').show();
        $('#syncCom').hide();
    }

    // Clic sur "Voir tous les événements"
    $('.menu_dashboard').on('click', '#viewAllLink', function () {
        $('#calendar').html('').afficheCalendar(null, 'calendrierComm');
        $('.commissionLink').parent().removeClass('active');
        $('#syncCal').show();
        $('#syncCom').hide();
        $('#exportCom').hide();
        $(this).parent().addClass('active');
    });

    // Sélection d'une commission dans le menu latéral
    $(document).on('click', '.commissionLink', function (e) {
        e.preventDefault();
        var $self = $(this);
        var idCommission = $self.attr('data-value');
        $('#viewAllLink').parent().removeClass('active');
        $('.commissionLink').parent().removeClass('active');
        $self.parent().addClass('active');
        $('#infosCom').html("<input type='hidden' name='commissionId' id='commissionId' value='" + idCommission + "' />");
        var syncComHref = $('#syncCal').attr('href') + '&commission=' + idCommission;
        $('#syncCom').attr('href', syncComHref);
        $('h2.page-header').html('Calendrier de la ' + $self.attr('data-type') + ' de "' + $self.html().trim() + '"');
        $('#calendar').html('').afficheCalendar(idCommission, 'calendrierComm');
        $('#exportCom').show();
    });

    // ─── Handlers issus de dialogcomm.phtml ───────────────────────────────

    // Applique les plugins jQuery sur les inputs date et heure
    $(document).on('focus', '.time', function () {
        $(this).timeEntry($.timeEntry.regional['fr']);
    });

    $(document).on('click', '.date', function () {
        $(this).datepicker({ showOn: 'focus', dateFormat: 'dd/mm/yy', firstDay: 1 }).focus();
    });

    // Affiche la div d'ajout de date
    $(document).on('click', '.addDate', function () {
        $('#divAddDate').show();
        $('#dialogComm').buttonHide();
        return false;
    });

    // Validation de l'ajout de date
    $(document).on('click', '.validAddDate', function () {
        var dateSelect = $('#addDate').val();
        $('#addDate').attr('value', '');
        if (dateSelect !== '') {
            if ($('#dateCommission').val()) {
                // Édition : insertion en base puis rafraîchissement calendrier
                $.ajax({
                    data: 'do=addDateS&date=' + dateSelect + '&idDateCommLiee=' + $('#dateCommission').val() + '&_csrf_dialog=' + $('#csrf_token_dialog').val(),
                    type: 'POST',
                    url: Routing.generate('calendrier_des_commissions_dialog_edit'),
                    beforeSend: function () {
                        $('#divAddDate').hide();
                    },
                    success: function (affichageResultat) {
                        $('#listeDates').last('ul').append(affichageResultat);
                        $('#calendar').fullCalendar('refetchEvents');
                    },
                    error: function () {
                        return false;
                    }
                });
            } else {
                // Création : ajout simple de la ligne
                $.ajax({
                    data: 'do=addDateN&date=' + dateSelect,
                    type: 'POST',
                    url: Routing.generate('calendrier_des_commissions_dialog_edit'),
                    beforeSend: function () {
                        $('#divAddDate').hide();
                    },
                    success: function (affichageResultat) {
                        $('#listeDates').last('ul').append(affichageResultat);
                        $('.suppression').last().button({
                            icons: { primary: 'ui-icon-trash' },
                            text: false
                        });
                    },
                    error: function () {
                        return false;
                    }
                });
            }
        }
        $('#dialogComm').buttonShow();
        $('#listeDates').countLigne('add', 'edit');
        return false;
    });

    // Annulation de l'ajout de date
    $(document).on('click', '.cancelAddDate', function () {
        $('#addDate').attr('value', '');
        $('#divAddDate').hide();
        $('#dialogComm').buttonShow();
        return false;
    });

    // Checkbox periodicité
    $(document).on('click', '#repeat', function () {
        if ($(this).prop('checked')) {
            $('#planification').fadeIn();
            $('#dialogComm').buttonHide();
        } else {
            $('#planification').fadeOut();
            $('#commission_dates_dateFin').val('');
            $('#commission_dates_periodicite').val('1');
            $('#dialogComm').buttonShow();
        }
    });

    $(document).on('click', '#valid_periodicite', function () {
        $('#dialogComm').buttonShow();
        $('#dialogComm').reapeatHide();
    });

    $(document).on('click', '#annule_periodicite', function () {
        $('#dialogComm').buttonShow();
        $('#dialogComm').reapeatHide();
    });

    // ─── Bouton édition ────────────────────────────────────────────────────

    $(document).on('click', '.edition', function () {
        $('#dialogComm').buttonHide();
        var tabDo = $(this).attr('id').split('_');
        var data;
        switch (tabDo[1]) {
            case 'libelleCom':
            case 'typeCom':
                data = 'idDateComm=' + $('#dateCommission').val() + '&do=' + tabDo[1];
                $.ajax({
                    data: data,
                    url: Routing.generate('calendrier_des_commissions_dialog_edit'),
                    type: 'POST',
                    beforeSend: function () {
                        $('#div_' + tabDo[1]).html("<img src='/images/load.gif' />");
                    },
                    success: function (affichageResultat) {
                        $('#div_' + tabDo[1]).html(affichageResultat);
                        return false;
                    },
                    error: function () {
                        return false;
                    }
                });
                break;
            case 'dateComm':
                data = 'idDateComm=' + tabDo[2] + '&do=' + tabDo[1];
                if (tabDo.length === 4 && tabDo[3] === 'First') {
                    data += '&first=1';
                }
                $.ajax({
                    data: data,
                    url: Routing.generate('calendrier_des_commissions_dialog_edit'),
                    type: 'POST',
                    beforeSend: function () {
                        $('#ligne_' + tabDo[2]).html("<img src='/images/load.gif' />");
                    },
                    success: function (affichageResultat) {
                        $('#ligne_' + tabDo[2]).html(affichageResultat);
                        return false;
                    },
                    error: function () {
                        return false;
                    }
                });
                break;
            default:
                break;
        }
        return false;
    });

    // ─── Bouton validation ─────────────────────────────────────────────────

    $(document).on('click', '.validation', function () {
        var tabDo = $(this).attr('id').split('_');
        var data;
        switch (tabDo[1]) {
            case 'libelleCom':
                data = $('#value_' + tabDo[1]).val();
                $.ajax({
                    data: 'idDateComm=' + $('#dateCommission').val() + '&do=valid_' + tabDo[1] + '&data=' + data + '&_csrf_dialog=' + $('#csrf_token_dialog').val(),
                    url: Routing.generate('calendrier_des_commissions_dialog_edit'),
                    type: 'POST',
                    beforeSend: function () {
                        $('#div_' + tabDo[1]).html("<img src='/images/load.gif' />");
                    },
                    success: function (affichageResultat) {
                        $('#div_' + tabDo[1]).html(affichageResultat);
                        $('#calendar').fullCalendar('refetchEvents');
                        return false;
                    },
                    error: function () {
                        return false;
                    }
                });
                break;
            case 'dateCom':
                data = '&date=' + $('#date_' + tabDo[2]).val() + '&hd=' + $('#D_' + tabDo[2]).val() + '&hf=' + $('#F_' + tabDo[2]).val();
                if ($('#first').val() == 1) {
                    data += '&first=1';
                }
                $.ajax({
                    data: 'idDateComm=' + tabDo[2] + '&do=valid_' + tabDo[1] + data + '&_csrf_dialog=' + $('#csrf_token_dialog').val(),
                    url: Routing.generate('calendrier_des_commissions_dialog_edit'),
                    type: 'POST',
                    beforeSend: function () {
                        $('#ligne_' + tabDo[2]).html("<img src='/images/load.gif' />");
                    },
                    success: function (affichageResultat) {
                        $('#ligne_' + tabDo[2]).html(affichageResultat);
                        $('#calendar').fullCalendar('refetchEvents');
                        return false;
                    },
                    error: function () {
                        return false;
                    }
                });
                break;
            case 'typeCom':
                $.ajax({
                    data: 'typeSelect=' + $('#typeCom').val() + '&do=valid_' + tabDo[1] + '&idDateComm=' + $('#dateCommission').val() + '&_csrf_dialog=' + $('#csrf_token_dialog').val(),
                    url: Routing.generate('calendrier_des_commissions_dialog_edit'),
                    type: 'POST',
                    beforeSend: function () {
                        $('#div_' + tabDo[1]).html("<img src='/images/load.gif' />");
                    },
                    success: function (affichageResultat) {
                        $('#div_' + tabDo[1]).html(affichageResultat);
                        $('#calendar').fullCalendar('refetchEvents');
                        return false;
                    },
                    error: function () {
                        return false;
                    }
                });
                break;
            default:
                break;
        }
        $('#dialogComm').buttonShow();
        return false;
    });

    // ─── Bouton annulation ─────────────────────────────────────────────────

    $(document).on('click', '.annulation', function () {
        var tabDo = $(this).attr('id').split('_');
        var data;
        switch (tabDo[1]) {
            case 'libelleCom':
            case 'typeCom':
                data = 'do=annule_' + tabDo[1] + '&idDateComm=' + $('#dateCommission').val();
                $.ajax({
                    data: data,
                    url: Routing.generate('calendrier_des_commissions_dialog_edit'),
                    type: 'POST',
                    beforeSend: function () {
                        $('#div_' + tabDo[1]).html("<img src='/images/load.gif' />");
                    },
                    success: function (affichageResultat) {
                        $('#div_' + tabDo[1]).html(affichageResultat);
                    },
                    error: function () {
                        return false;
                    }
                });
                break;
            case 'dateCom':
                data = 'do=annule_' + tabDo[1] + '&idDateComm=' + tabDo[2];
                if ($('#first').val() == 1) {
                    data += '&first=1';
                }
                $.ajax({
                    data: data,
                    url: Routing.generate('calendrier_des_commissions_dialog_edit'),
                    type: 'POST',
                    beforeSend: function () {
                        $('#ligne_' + tabDo[2]).html("<img src='/images/load.gif' />");
                    },
                    success: function (affichageResultat) {
                        $('#ligne_' + tabDo[2]).html(affichageResultat);
                    },
                    error: function () {
                        return false;
                    }
                });
                break;
            default:
                break;
        }
        $('#dialogComm').buttonShow();
        return false;
    });

    // ─── Bouton dateDefaut ─────────────────────────────────────────────────

    $(document).on('click', '.dateDefaut', function () {
        var tabId = $(this).attr('id').split('_');
        var id = tabId[2];
        var data = 'do=makeDefaut&idDateComm=' + id + '&_csrf_dialog=' + $('#csrf_token_dialog').val();
        $.ajax({
            data: data,
            url: Routing.generate('calendrier_des_commissions_dialog_edit'),
            type: 'POST',
            success: function (affichageResultat) {
                $.ajax({
                    data: 'idComm=' + $('#commissionId').val() + '&idDateComm=' + id + '&do=edit',
                    url: Routing.generate('calendrier_des_commissions_dialog_edit'),
                    type: 'POST',
                    beforeSend: function () {
                        $('#dialogComm').html("<img src='/images/load.gif' />");
                    },
                    success: function (affichageResultat) {
                        $('#dialogComm').html(affichageResultat);
                        $('body').css('overflow', 'hidden');
                        $('#dialogComm').dialog({
                            resizable: false,
                            height: 800,
                            width: 1200,
                            buttons: {
                                "Fermer la fenêtre d'édition": function () {
                                    $(this).dialog('close');
                                    $('#dialogComm').html('');
                                }
                            },
                            close: function (event, ui) {
                                $('body').css('overflow', 'auto');
                            }
                        });
                    },
                    error: function () {
                        return false;
                    }
                });
            },
            error: function () {
                return false;
            }
        });
        return false;
    });

    // ─── Bouton suppression ────────────────────────────────────────────────

    $(document).on('click', '.suppression', function () {
        if ($('#dateCommission').val()) {
            // Édition : supprime en base et rafraîchit le calendrier
            var tabDo = $(this).attr('id').split('_');
            var data = 'do=supp_dateCom&idDateComm=' + tabDo[2] + '&_csrf_dialog=' + $('#csrf_token_dialog').val();
            $.ajax({
                data: data,
                url: Routing.generate('calendrier_des_commissions_dialog_edit'),
                type: 'POST',
                success: function (affichageResultat) {
                    if (affichageResultat === '') {
                        alert("Il est impossible de supprimer cette date. D'autres dates sont liées à celle-ci.\nUtilisez le bouton prévu à cet effet afin d'affecter une autre date principale à la commission");
                    } else {
                        $('#listeDates').countLigne('supp', 'edit');
                        $('#ligne_' + tabDo[2]).fadeOut().remove();
                        $('#calendar').fullCalendar('refetchEvents');
                    }
                },
                error: function () {
                    return false;
                }
            });
        } else {
            // Création : retire simplement la ligne
            $(this).parent().parent().fadeOut().remove();
            $('#listeDates').countLigne('supp', 'new');
        }
        return false;
    });

    // ─── Dialogue standard ─────────────────────────────────────────────────

    // Validation de suppression depuis la boite de confirmation
    $(document).on('click', '#validSupp', function () {
        $.ajax({
            url: Routing.generate('calendrier_des_commissions_validsuppression'),
            data: $('#infosSuppDate').serialize(),
            type: 'POST',
            success: function (affichageResultat) {
                $('#calendar').fullCalendar('refetchEvents');
                $('#dialogComm').dialog('close').html('');
                return false;
            },
            error: function () {
                return false;
            }
        });
    });

    $(document).on('click', '#cancelSupp', function () {
        $('#dialogComm').dialog('close').html('');
    });

}); // fin document.ready

// ─── Fonction dialogue standard (globale) ────────────────────────────────────

function my_dialog(url, data, callback) {
    $.ajax({
        data: data,
        url: url,
        type: 'POST',
        success: function (result) {
            $('#dialogComm').html(result);

            $('#dialogComm').dialog({
                resizable: false,
                height: '500',
                width: '800',
                modal: true,
                buttons: [
                    {
                        text: 'Supprimer la date',
                        'class': 'btn btn-warning',
                        click: function () {
                            $.ajax({
                                url: Routing.generate('calendrier_des_commissions_alertsuppression'),
                                data: '&dateCommission=' + $('#dateCommission').val() + '&commissionId=' + $('#commissionId').val(),
                                type: 'POST',
                                success: function (affichageResultat) {
                                    $('#dialogComm').html(affichageResultat);
                                    $('.ui-dialog-buttonset').children('button').remove();
                                    return false;
                                },
                                error: function () {
                                    return false;
                                }
                            });
                        }
                    },
                    {
                        text: "Afficher l'ordre du jour",
                        'class': 'btn btn-primary',
                        click: function () {
                            window.location.replace('/calendrier-des-commissions/gestionodj/dateCommId/' + $('#dateClick').val());
                        }
                    },
                    {
                        text: "Fermer la fenêtre d'édition",
                        'class': 'btn btn-default',
                        click: function () {
                            $('#dialogComm').dialog('close').html('');
                        }
                    }
                ]
            });

            // Initialise les boutons de suppression sur les dates pré-remplies (sélection de plage).
            // DOMContentLoaded s'étant déjà déclenché, add-collection-widget.js n'a pas pu les initialiser.
            // On saute le premier élément (date maître) comme le fait calendar-init.js côté dossier.
            var collWidget = document.querySelector('#dialogComm .remove-collection-widget');
            if (collWidget && typeof addTagFormDeleteLink === 'function') {
                var items = collWidget.children;
                for (var i = 1; i < items.length; i++) {
                    addTagFormDeleteLink(items[i]);
                }
            }

            if (callback) {
                callback();
            }
        }
    });
}

// Lorsqu'une date est ajoutée via add-collection-widget (formulaire newComm),
// masquer la section de planification périodique (incompatible avec multi-dates).
document.addEventListener('collection:item-added', function () {
    var listeItems = document.querySelectorAll('#liste_dates > li');
    if (listeItems.length >= 2) {
        $('#addReapeat').hide();
        $('#planification').hide();
        var repeatCheckbox = document.getElementById('repeat');
        if (repeatCheckbox) {
            repeatCheckbox.checked = false;
        }
    }
});

// Lorsqu'une date est supprimée via add-collection-widget (formulaire newComm),
// ré-afficher #addReapeat si une seule date reste.
document.addEventListener('collection:item-removed', function (e) {
    var list = e.detail && e.detail.list;
    if (list && list.children.length <= 1) {
        $('#addReapeat').show();
    }
});
