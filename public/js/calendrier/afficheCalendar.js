function convertDateAngToFr(dateAng) {
    var tabDate = dateAng.split("-");
    return tabDate[2] + "/" + tabDate[1] + "/" + tabDate[0];
}

function openModalComm(url, data = {}) {
    const infoCalendar = data.infoCalendar || '';
    $.ajax({
        url: url,
        type: "POST",
        data: data,
        success: function (response) {
            $('#modalComm .modal-content').html(response);
            $('#modalComm').modal('show');
            initModalCommButtons(infoCalendar); 
        },
        error: function (xhr, status, error) {
            console.error("Erreur :", xhr);
            alert("Erreur lors du chargement de la modale : " + (xhr.responseText || error));
        }

    });
}

function initModalCommButtons(infoCalendar) {
    $('#btnSaveComm').off('click').on('click', function () {
        const formArray = $("#formDateComm").serializeArray();
        const formData = {};

        formArray.forEach(field => {
            formData[field.name] = field.value;
        });

        formData.idComm = $("#idComm").val();
        formData.repeat = $("#repeat").is(":checked") ? "on" : "";
        formData.periodicite = $("#periodicite").val() || '';
        formData.dateFin = ($("#dateFin").val() || '').replace(/\//g, '.');

        if (formData.repeat === "on" && !formData.dateFin) {
            alert("Veuillez renseigner une date de fin.");
            return;
        }

            $.ajax({
                url: "/adddates",
                type: "POST",
                data: formData,
                dataType: "json",
                success: function (response) {
                    if (typeof response === "string") {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            console.error("Erreur JSON parse:", e);
                            alert("Erreur serveur : réponse non valide.");
                            return;
                        }
                    }

                if (!response || !Array.isArray(response.dates) || response.dates.length === 0) {
                    alert("Aucune date reçue du serveur.");
                    return;
                }
                
                // Ajout des événements dans le calendrier
                response.dates.forEach(date => {
                    if(infoCalendar == 'showCommission'){
                        $('#calendarCommission').fullCalendar('renderEvent', {
                            id: date.id,
                            title: date.title,
                            className: date.className,
                            start: date.start,
                            end: date.end,
                            allDay: false,
                            url: "/commission/id/" + date.id
                        }, true)
                    }else{
                        $('#calendarVisiteAffichage').fullCalendar('renderEvent', {
                            id: date.id,
                            title: date.title,
                            className: date.className,
                            start: date.start,
                            end: date.end,
                            allDay: false,
                            url: "/commission/id/" + date.id
                        }, true)   
                    }   
                });

                // Rafraîchir les événements
                $('#calendarCommission').fullCalendar('refetchEvents');
                $('#calendarVisiteAffichage').fullCalendar('refetchEvents');

                // Fermer et vider la modale
                $('#modalComm').modal('hide');
                $('#modalComm .modal-content').html('');

                // Mise à jour des champs de formulaire
                const firstEvent = response.dates[0];
                const startDate = new Date(firstEvent.start);
            
                const dd = String(startDate.getDate()).padStart(2, '0');
                const mm = String(startDate.getMonth() + 1).padStart(2, '0');
                const yyyy = startDate.getFullYear();
                const formattedDate = `${dd}/${mm}/${yyyy}`;

                const eventId = firstEvent.id;
                const typeDossier = parseInt($("#TYPE_DOSSIER").val());

                if (typeDossier === 1 || infoCalendar === 'showCommission') {
                    $("#DATECOMM_INPUT").val(formattedDate);
                    $("#ID_AFFECTATION_DOSSIER_COMMISSION").val(eventId);
                    $("#hideCommission").click();
                } else if (typeDossier === 2 || typeDossier === 3 || infoCalendar === 'showVisite') {
                    $("#DATEVISITE_INPUT").val(formattedDate);
                    $("#ID_AFFECTATION_DOSSIER_VISITE").val(eventId);
                    $("#hideVisite").click();
                }

                // Masquage du calendrier après insertion
                if(infoCalendar === 'showCommission'){
                    $("#calendarCommission").hide();
                    $("#calendarRow").hide();
                    $("#showCommission").show();
                    $("#hideCommission").hide();
                }else{
                    $("#calendarVisiteAffichage").hide();
                    $("#calendarRowVisite").hide();
                    $("#showVisite").show();
                    $("#hideVisite").hide();
                }
                
            },
            error: function (xhr, status, error) {
                console.error("Erreur AJAX :", xhr);
                alert("Erreur lors de l'enregistrement : " + (xhr.responseText || error));
            }
        });
    });
}
function my_dialog(url, data, callback) { 
    // Récupération du contenu de la boite de dialogue
        $.ajax({
            data: data,
            url: url,
            type: "POST",
            success(result) {
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
        select: function(start, end) {
            var nomEtab = '';
            if ($("#infosEtabs > .etab").length > 0) {
                nomEtab = $("#infosEtabs > .etab").text().trim();
            } else if ($("#infosEtablissement > .etab").length > 0) {
                nomEtab = $("#infosEtablissement > .etab").text().trim();
            }
            nomEtab = nomEtab.replace('&nbsp;', ' ').replace("'", " ");

            var nomNature = $("#selectNature option:selected").text().trim();
            var nomCom = nomNature + " - " + nomEtab;

            var type;
            if ($("#TYPE_DOSSIER").val() == 1) {
                type = 1;
            } else if ($("#TYPE_DOSSIER").val() == 2 || $("#TYPE_DOSSIER").val() == 3) {
                if (infoCalendar == 'showVisite') {
                    type = $("#TYPE_DOSSIER").val();
                } else if (infoCalendar == 'showCommission') {
                    type = 1;
                }
            }

            openModalComm('/dialogcomm', {
                do: 'newComm',
                idComm: "",
                dateD: start.toISOString().split('T')[0],
                dateF: end.toISOString().split('T')[0],
                libelleCom: nomCom,
                type: type,
                infoCalendar: infoCalendar
            });
        },

        eventClick: function(event, jsEvent, view) {
            // Boite de dialogue
            if (emplacement == 'calendrierComm') {
                my_dialog("/dialogcomm", "idComm=" + idComm +"&idDateComm=" + event.id + "&do=edit");
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
                                var rez =  affichageResultat ;
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