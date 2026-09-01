$(document).ready(function() {
    const selectType = $('select[name="TYPE"]')

    selectType.on('change', function() {
        const label = selectType.val() === 'Avis' ? "Ajouter l'avis" : "Ajouter la dérogation"
        $('button.js-submit-label').text(label)
    })

    // Gestion du bouton de désélection
    const deselectButton = document.getElementById('deselect')

    if (deselectButton) {
        deselectButton.addEventListener('click', function() {
            $('.avis-derogation__leve input[type="radio"]:checked').prop('checked', false)
            $('#deselect').addClass('hide')
        })
    }

    $('.avis-derogation__leve input[type="radio"]').on('change', function() {
        $('#deselect').removeClass('hide')
    })
})