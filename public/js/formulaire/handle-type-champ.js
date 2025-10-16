$(document).ready(function() {
    const typeChampSelect = $('#champ_type')

    typeChampSelect.on('change', function () {
        if (typeChampSelect.find(":selected").text() === 'Liste') {
            $('#fieldset_valeurs_liste').show()
        } else {
            $('#fieldset_valeurs_liste').hide()
        }

        if (typeChampSelect.find(":selected").text() === 'Parent') {
            $('#champ_tableau').closest('.form-group').show()
        } else {
            $('#champ_tableau').closest('.form-group').hide()
        }
    })
    typeChampSelect.trigger('change')
})