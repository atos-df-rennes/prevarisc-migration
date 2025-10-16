$(document).ready(function() {
    function hideShowListe(enfant) {
        const typeChampSelectSelector = enfant ? '#champ_enfant_type' : '#champ_type'
        const typeChampSelect = $(typeChampSelectSelector)

        const fieldsetSelector = enfant ? '#fieldset_valeurs_liste_enfant' : '#fieldset_valeurs_liste'
        const tableauSelector = enfant ? '#champ_enfant_tableau' : '#champ_tableau'

        typeChampSelect.on('change', function () {
            if (typeChampSelect.find(":selected").text() === 'Liste') {
                $(fieldsetSelector).show()
            } else {
                $(fieldsetSelector).hide()
            }

            if (!enfant) {
                if (typeChampSelect.find(":selected").text() === 'Parent') {
                    $(tableauSelector).closest('.form-group').show()
                } else {
                    $(tableauSelector).closest('.form-group').hide()
                }
            }
        })

        if (enfant) {
            $(tableauSelector).closest('.form-group').hide()
        }

        typeChampSelect.trigger('change')
    }

    hideShowListe(false)
    hideShowListe(true)
})