$(document).ready(function() {
    $('.delete-champ').on('click', function(e) {
        e.preventDefault()
        deleteChamp(this)
    })
})

function deleteChamp(element) {
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
            success: function() {
                if (nbOfRows === 1) {
                    parentTable.remove()
                } else {
                    parentDiv.remove()
                }

                $("#modal-delete-champ").modal('hide')

                $.toast.success('Le champ ' + nom + ' a bien été supprimé.')
            },
            error: function() {
                $.toast.error('Erreur lors de la suppression du champ ' + nom + '.')

                return false
            }
        })
    })
}