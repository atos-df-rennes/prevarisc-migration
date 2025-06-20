function convertDateAngToFr(dateAng) {
    var tabDate = dateAng.split("-");
    return tabDate[2] + "/" + tabDate[1] + "/" + tabDate[0];
}
 
function my_dialog(url, data, callback) { 
    // Récupération du contenu de la boite de dialogue
        $.ajax({
            data: data,
            url: url,
            type: "POST",
            success: function(result) {
                $("#dialogComm").html(result);
                // On créé la boite de dialogue
                $("#dialogComm").dialog({
                    resizable: false,
                    height: 500,
                    width: 700,
                    draggable: false,
                    modal: true,
                    buttons: {
                        'Fermer la fenêtre d\'édition': function() {
                            $("#dialogComm").dialog('close').html('');
                        }
                    }
                });

                if (callback) {
                    callback();
                }
            }
        });
    }

$.fn.afficheCalendar = function(idComm, emplacement, type, divInfo, infoCalendar, mois, annee) {

    var calendar = $("#" + divInfo).fullCalendar({
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
        month: mois,
        year: annee,
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
            var type = $("#TYPE_DOSSIER").val();

            if (type == 3) {
                //cas type de dossier groupe de visite suivant le champ infoCalendar on choisi ce que l'on affiche
                if (infoCalendar == 'showCommission') {
                    type = 1;
                }
            }

            $.ajax({
                url: '/evenements',
                type: 'GET',
                data: {
                    start: start.toISOString(),
                    end: end.toISOString(),
                    type: type,
                    idComm: idComm,
                    format: 'json',
                },
                success: function(result) {
                    var events = [];
                    for (var x in result) {
                        var colorCal;
                        switch (result[x]["className"]) {
                            case "display-1":
                                colorCal = "#3A87AD";
                                break;
                            case "display-2":
                                colorCal = "#468847";
                                break;
                            case "display-3":
                                colorCal = "#F89406";
                                break;
                            default:
                                break;
                        }
                        events.push({
                            id: result[x]["id"],
                            title: result[x]["title"],
                            start: result[x]["start"],
                            end: result[x]["end"],
                            allDay: result[x]["allDay"],
                            color: colorCal
                        });
                    }
                    callback(events);
                    return false;
                }
            });
        },
        // Lorsque l'on selectionne une ou plusieurs dates on ouvre la boite de dialogue correspondante
        select: function(start, end, allDay) {
            var dateSelected = new Date(start);
            // On empêche la selection d'un samedi ou d'un dimanche
            // Boite de dialogue en ajoutant en callback le traitement du formulaire
            var nomEtab = "";
            if ($("#infosEtabs > .etab").length > 0) {
                nomEtab = document.querySelector('#infosEtabs > .etab').innerText.trim();
            } else if ($("#infosEtablissement > .etab").length > 0) {
                nomEtab = document.querySelector('#infosEtablissement > .etab').innerText.trim();
            }
            nomEtab = nomEtab.replace('&nbsp;', ' ');
            nomEtab = nomEtab.replace("'", " ");

            var nomNature = $("#selectNature option:selected").text().trim();
            var nomCom = nomNature + " - " + nomEtab;
            var type;
            if ($("#TYPE_DOSSIER").val() == 1) {
                //cas type dossier étude. On affiche alors que les commissions de type En Salle
                type = 1;
            } else if ($("#TYPE_DOSSIER").val() == 2) {
                //cas type de dossier visite ou groupe de visite suivant le champ infoCalendar on choisi ce que l'on affiche
                if (infoCalendar == 'showVisite') {
                    type = $("#TYPE_DOSSIER").val();
                } else if (infoCalendar == 'showCommission') {
                    type = 1;
                }
            } else if ($("#TYPE_DOSSIER").val() == 3) {
                if (infoCalendar == 'showVisite') {
                    type = $("#TYPE_DOSSIER").val();
                } else if (infoCalendar == 'showCommission') {
                    type = 1;
                }
            }
            my_dialog( 
                '/dialogcomm',  // c’est maintenant l’URL Symfony
                'do=newComm&idComm=' + idComm +
                '&dateD=' + start.toISOString().split('T')[0] +
                '&dateF=' + end.toISOString().split('T')[0]+
                '&libelleCom=' + nomCom +
                '&type=' + type,
                function () {

                    $("#dialogComm").dialog("option", "buttons", [
                        {
                            text: 'Enregistrer dans le calendrier',
                            class: 'btn btn-success',
                            click: function() {
                                // Lors de la validation de la boite de dialogue
                                $.ajax({
                                    data: $("#formDateComm").serialize() +
                                        "&idComm=" + idComm+
                                        "&repeat=" + ($("#repeat").is(":checked") ? "on" : "")+
                                        "&periodicite=" + $("#periodicite").val() +
                                        "&dateFin=" + $("#dateFin").val().replace(/\//g, "."),
                                    url: "/adddates",
                                    dataType: 'json',
                                    type: "POST",
                                    success: function(affichageResultat) {
                                        //En cas de succes il faut inserer tous les évenements dans le calendrier et la BD
                                        //Récupération du Json et affichage de ce
                                        // lui-ci
                                       
                                        var rez =  affichageResultat 
                                        for (var i in rez) {
                                            calendar.fullCalendar(
                                            'renderEvent', {
                                                id: rez[i]['id'],
                                                title: rez[i]['title'],
                                                className: rez[i][
                                                    'className'
                                                ],
                                                start: rez[i]['start'],
                                                end: rez[i]['end'],
                                                allDay: false,
                                                url: "/commission/id/" +
                                                    rez[i]['id']
                                            });
                                        }
                                        $("#" + divInfo).fullCalendar(
                                            'refetchEvents');
                                        $("#dialogComm").dialog('close').html(
                                            '');

                                        // Permet d'affecter la date de commission au champ du dossier en un seul clic
                                        var eventId = rez[0]['id'];

                                        // Calcul de la date
                                        var dateEnd = new Date(end);
                                        var dateStart = new Date(start);
                                        var date = dateEnd;

                                        if( date < dateStart){
                                            date = dateStart;
                                        }

                                        dd = date.getDate();
                                        mm = date.getUTCMonth() + 1;
                                        aa = date.getUTCFullYear();

                                        if (dd < 10) {
                                            dd = '0' + dd;
                                        }
                                        if (mm < 10) {
                                            mm = '0' + mm;
                                        }

                                        /*
                                        Remplissage du champ concerné
                                        Pour groupe de visite :
                                            - type = 1 pour le champ "Date de commission en salle"
                                            - type = 3 pour le champ "Date de visite"
                                        */

                                        if (type == 1) {
                                            $("#DATECOMM_INPUT").val(dd + "/" + mm + "/" + aa);
                                            $("#ID_AFFECTATION_DOSSIER_COMMISSION").val(eventId);
                                            $("#hideCommission").click();
                                        } else if (type == 2 || type == 3 ) {
                                            $("#DATEVISITE_INPUT").val(dd + "/" + mm + "/" + aa);
                                            $("#ID_AFFECTATION_DOSSIER_VISITE").val(eventId);
                                            $("#hideVisite").click();
                                        }
                                    }
                                });
                            }
                        },
                                        
                        {
                            text: "Fermer la fenêtre d'édition",
                            class: 'btn',
                            click: function() {
                                $("#dialogComm").dialog('close').html('');
                            }
                        }
                    ]);
                });
        },
        eventClick: function(event, jsEvent, view) {
            // Boite de dialogue
            if (emplacement == 'calendrierComm') {
                my_dialog("/dialogcomm", "idComm=" + idComm +
                    "&idDateComm=" + event.id + "&do=edit");
            } else if (emplacement == 'dossier') {
                //Lorsque l'on clique sur une commission la date s'ajoute automatiquement dans l'input en dessous
                //Faire vérification du nombre de date liée
                if ($("#TYPE_DOSSIER").val() == 1) {
                    //Si une seule date on la renseinge aussi dans l'input date de passage en commission
                    var dateSelect = $(this).parent().parent().parent().parent().parent().prev().attr(
                        'id');
                    var dateEnd = new Date(event.end);
                    var dateStart = new Date(event.start);
                    var date = dateEnd;

                    if (date < dateStart) {
                        date = dateStart;
                    }

                    dd = date.getUTCDate();
                    mm = date.getUTCMonth() + 1;
                    aa = date.getUTCFullYear();

                    if (dd < 10) {
                        dd = '0' + dd;
                    }
                    if (mm < 10) {
                        mm = '0' + mm;
                    }

                    if (dateSelect == "DATEVISITE") {
                        $("#DATEVISITE_INPUT").val(dd + "/" + mm + "/" + aa);
                        $("#ID_AFFECTATION_DOSSIER_VISITE").val(event.id);
                    } else if (dateSelect == "DATECOMM") {
                        $("#DATECOMM_INPUT").val(dd + "/" + mm + "/" + aa);
                        $("#ID_AFFECTATION_DOSSIER_COMMISSION").val(event.id);
                    }
                    $("#" + dateSelect).find(".hideCalendar").click();
                } else if ($("#TYPE_DOSSIER").val() == 3 || $("#TYPE_DOSSIER").val() == 2) {
                    var dateSelect = $(this).parent().parent().parent().parent().parent().prev().attr(
                        'id');
                    if (dateSelect == "DATEVISITE") {
                        $.ajax({
                            data: "idDate=" + event.id,
                            url: "/recupdateliee",
                            type: "GET",
                            success: function(affichageResultat) {
                                //On récupere toutes les dates liées et on les affiche
                                var rez = eval("(" + affichageResultat + ")");
                                if (rez.length == 1) {
                                    //Si une seule date on la renseinge aussi dans l'input date de passage en commission
                                    var dateEnd = new Date(event.end);
                                    var dateStart = new Date(event.start);
                                    var date = dateEnd;

                                    if (date < dateStart) {
                                        date = dateStart;
                                    }

                                    dd = date.getUTCDate();
                                    mm = date.getUTCMonth() + 1;
                                    aa = date.getUTCFullYear();

                                    if (dd < 10) {
                                        dd = '0' + dd;
                                    }
                                    if (mm < 10) {
                                        mm = '0' + mm;
                                    }

                                    $("#DATEVISITE_INPUT").val(dd + "/" + mm + "/" + aa);
                                    $("#ID_AFFECTATION_DOSSIER_VISITE").val(event.id);
                                    $("#hideVisite").click();
                                } else if (rez.length > 1) {
                                    var nbResultats = rez.length;
                                    var nbResultatsTotal = nbResultats;

                                    var listeDates = "";
                                    for (var i in rez) {
                                        if (nbResultats == nbResultatsTotal) {
                                            //On prend l'id de la premiere date pour la lier au dossier
                                            $("#ID_AFFECTATION_DOSSIER_VISITE").val(event
                                                .id);
                                        }
                                        var dateFr = convertDateAngToFr(rez[i][
                                            'DATE_COMMISSION'
                                        ]);
                                        if (nbResultats > 1) {
                                            listeDates += dateFr + ", ";
                                        } else if (nbResultats == 1) {
                                            listeDates += dateFr;
                                        }
                                        nbResultats--;
                                    }
                                    $("#DATEVISITE_INPUT").val(listeDates);

                                    $("#hideVisite").click();
                                }
                            }
                        });
                    } else if (dateSelect == "DATECOMM") {
                        var dateEnd = new Date(event.end);
                        var dateStart = new Date(event.start);
                        var date = dateEnd;

                        if (date < dateStart) {
                            date = dateStart;
                        }

                        dd = date.getUTCDate();
                        mm = date.getUTCMonth() + 1;
                        aa = date.getUTCFullYear();

                        if (dd < 10) {
                            dd = '0' + dd;
                        }
                        if (mm < 10) {
                            mm = '0' + mm;
                        }

                        $("#DATECOMM_INPUT").val(dd + "/" + mm + "/" + aa);
                        $("#ID_AFFECTATION_DOSSIER_COMMISSION").val(event.id);
                        $("#hideCommission").click();
                    }
                }
            }
            return false;
        },
        eventDrop: function(event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view) {
            //Lorsque l'on déplace un élément
            var tabDateSelect = event.end;
            tabDateSelect = tabDateSelect.toString();
            tabDateSplited = tabDateSelect.split(" ");

            if (tabDateSplited[0] == "Sun" || tabDateSplited[0] == "Sat") {
                $("#" + divInfo).fullCalendar('refetchEvents');
            } else {
                $.ajax({
                    data: "idComm=" + event.id + "&debut=" + event.start.toISOString() + "&fin=" + event.end.toISOString(),
                    url: "/deplaceCommissionDate",
                    type: "POST",
                    success: function(affichageResultat) {},
                });
            }
        },
        eventResize: function(event, jsEvent, ui, view) {
            //heure de fin change uniquement fonctionne uniquement sur la vue semaine et jour. Pas sur mois
            var view = $("#" + divInfo).fullCalendar('getView');
            var vueInfo = view.title;
            vueInfo = vueInfo.split(" ");
            if (vueInfo.length == 2) {
                //permet d'empecher la modification en mode mois (Ajouter message d'indication si besoin)
                $("#" + divInfo).fullCalendar('refetchEvents');
                return false;
            }
            var finLocal = event.end.getFullYear() + '-' +
                String(event.end.getMonth() + 1).padStart(2, '0') + '-' +
                String(event.end.getDate()).padStart(2, '0') + 'T' +
                String(event.end.getHours()).padStart(2, '0') + ':' +
                String(event.end.getMinutes()).padStart(2, '0') + ':00';
            $.ajax({
            data: {
                    idComm: event.id,
                    fin: finLocal,
                },                
            url: "/resizeCommissionDate",
            type: "POST",
            });
        }
    });
};