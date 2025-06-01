function gestionCalendrier(idShowBtn, idHideBtn, idCalendarDiv, idMonth, idYear) {
    $("#" + idShowBtn).click(function () {
        const $calendar = $("#" + idCalendarDiv);
        $calendar.empty().show().closest('div.row').show();

        $(this).hide();
        $("#" + idHideBtn).show();

        $calendar.afficheCalendar(
            $("#COMMISSION_DOSSIER").val(),
            'dossier',
            $('#TYPE_DOSSIER').val(),
            idCalendarDiv,
            idShowBtn,
            $("#" + idMonth).val(),
            $("#" + idYear).val()
        );
    });

    // Bouton "Cacher"
    $("#" + idHideBtn).click(function () {
        const $calendar = $("#" + idCalendarDiv);
        $calendar.hide().closest('div.row').hide();

        $(this).hide();
        $("#" + idShowBtn).show();
    });
}