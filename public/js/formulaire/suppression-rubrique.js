$(document).ready(function() {
    $('.delete-rubrique').on('click', function(e) {
        e.preventDefault()
        deleteRubrique(this)
    })
})

function deleteRubrique(element) {
    const nom = element.getAttribute('data-nom')
    const url = element.getAttribute('data-url')
    
    $("#name-rubrique").html(nom)
    $("#delete-rubrique").click(function() {
        const parentDiv = $(element).parent().parent().parent()
        const parentTable = $(element).closest('.saved-rubriques:not(.hidden)')
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

                $("#modal-delete-rubrique").modal('hide')
            },
            error: function() {
                return false
            }
        })
    })
}