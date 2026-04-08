$(document).ready(function() {
    /**
     * TOC Modal : synchronise les checkboxes miroirs (data-controls-rubrique)
     * avec la visibilité des panneaux de rubrique et le champ display réel.
     *
     * Les checkboxes dans le TOC modal ont l'attribut data-controls-rubrique="{rubriqueId}".
     * Chaque panneau de rubrique a l'id "afficher_rubrique-{rubriqueId}".
     * Le champ display réel a l'attribut data-display-checkbox="{rubriqueId}".
     */
    $('[data-controls-rubrique]').on('change', function() {
        const rubriqueId = $(this).data('controls-rubrique');
        const isChecked = this.checked;

        // Affiche/masque le panneau de rubrique
        $('#afficher_rubrique-' + rubriqueId).toggleClass('hide', !isChecked);

        // Met à jour le champ display réel du formulaire Symfony
        $('[data-display-checkbox="' + rubriqueId + '"]').prop('checked', isChecked);
    });

    $('.table-responsive').each(function() {
        if ($(this)[0].scrollWidth > $(this)[0].clientWidth) {
            $(this).find('.colonne-edition').each(function() {
                $(this).addClass('with-shadow')
            })
        } else {
            $(this).find('.table')[0].style.marginBottom = 0
        }
    })

    $('.table-responsive').on('scroll', function() {
        if ($(this)[0].scrollLeft < $(this)[0].scrollLeftMax) {
            $(this).find('.colonne-edition').each(function() {
                $(this).addClass('with-shadow')
            })
        } else {
            $(this).find('.colonne-edition').each(function() {
                $(this).removeClass('with-shadow')
            })
        }
    })

    let beforeUnloadEnabled = true

    document.addEventListener('click', function(event) {
        if (event.target.hasAttribute('data-do-not-unload')) {
            beforeUnloadEnabled = false
        }
    })

    window.addEventListener('beforeunload', function(event) {
        if (beforeUnloadEnabled) {
            event.preventDefault()
        }
    })
})

    $('.table-responsive').each(function() {
        if ($(this)[0].scrollWidth > $(this)[0].clientWidth) {
            $(this).find('.colonne-edition').each(function() {
                $(this).addClass('with-shadow')
            })
        } else {
            $(this).find('.table')[0].style.marginBottom = 0
        }
    })

    $('.table-responsive').on('scroll', function() {
        if ($(this)[0].scrollLeft < $(this)[0].scrollLeftMax) {
            $(this).find('.colonne-edition').each(function() {
                $(this).addClass('with-shadow')
            })
        } else {
            $(this).find('.colonne-edition').each(function() {
                $(this).removeClass('with-shadow')
            })
        }
    })

    let beforeUnloadEnabled = true

    document.addEventListener('click', function(event) {
        if (event.target.hasAttribute('data-do-not-unload')) {
            beforeUnloadEnabled = false
        }
    })

    window.addEventListener('beforeunload', function(event) {
        if (beforeUnloadEnabled) {
            event.preventDefault()
        }
    })
})