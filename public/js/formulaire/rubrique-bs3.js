$(document).ready(function() {
    const typeChampSelect = $('#champ_type')

    typeChampSelect.on('change', function() {
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

    $('.delete-champ').on('click', function(e) {
        e.preventDefault()
        deleteChamp(this)
    })
})

function deleteChamp(element) {
    const idParent = element.getAttribute('data-id-parent')
    const nom = element.getAttribute('data-nom')
    const url = element.getAttribute('data-url')

    $("#name-champ").html(nom)
    $("#delete-champ").click(function() {
        const parentDiv = $(element).parent().parent().parent()
        const parentTable = $(element).closest('.saved-fields:not(.hidden)')
        const nbOfRows = parentTable.children('.panel').children('table').children('tbody').children('tr').length

        $.ajax({
            url: url,
            type: 'DELETE',
            data: idParent,
            success: function() {
                if (nbOfRows === 1) {
                    parentTable.remove()
                } else {
                    parentDiv.remove()
                }

                $("#modal-delete-champ").modal('hide')
            },
            error: function() {
                return false
            }
        })
    })
}