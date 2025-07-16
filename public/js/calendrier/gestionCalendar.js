function gestionCalendrier(idShowBtn, idHideBtn, idCalendarDiv, idMonth, idYear) {
    $("#" + idShowBtn).click(function () {
        const $calendar = $("#" + idCalendarDiv);
        $calendar.empty().show();
        if(idShowBtn == 'showVisite'){
            $("#calendarRowVisite").show();
        }
        else{
            $("#calendarRow").show();
        }
        $(this).hide();
        $("#" + idHideBtn).show();

        $calendar.afficheCalendar($("#COMMISSION_DOSSIER").val(), 'dossier', $('#TYPE_DOSSIER').val(), idCalendarDiv, idShowBtn, $("#" + idMonth).val(), $("#" + idYear).val());
    });

    // Bouton "Cacher"
    $("#" + idHideBtn).click(function () {
        const $calendar = $("#" + idCalendarDiv);
       
        if(idShowBtn == 'showVisite'){
            $("#calendarRowVisite").hide();
        }
        else{
            $("#calendarRow").hide(); 
        }
        $calendar.hide();
        $(this).hide();
        $("#" + idShowBtn).show();
    });
}