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

    /**
     * Synchronise les champs cachés "opened" (data-opened-checkbox="{rubriqueId}") avec l'état
     * réel des collapses Bootstrap, afin que l'ouverture/repliement soit soumis avec le formulaire.
     *
     * La lecture se fait au submit plutôt qu'au fil des événements show/hide.bs.collapse :
     * bootstrap-3.min.js est chargé par base.html.twig avant ce script, donc un collapse basculé
     * dans cet intervalle échapperait à tout handler enregistré ici. Le formulaire resoumettrait
     * alors la valeur rendue par le serveur (le défaut administré), ensuite figée comme préférence
     * utilisateur. Relire l'état au moment de l'envoi supprime cette course.
     *
     * aria-expanded est la source de vérité : Bootstrap l'écrit sur le déclencheur de façon
     * synchrone dès le clic, contrairement à la classe "in" posée en fin de transition.
     */
    $(document).on('submit', 'form', function() {
        $(this).find('.panel-formulaire-perso').each(function() {
            const rubriqueId = this.id ? this.id.replace('afficher_rubrique-', '') : null;
            if (!rubriqueId) return;

            const estOuverte = $(this).find('.panel-title__link').attr('aria-expanded') === 'true';
            $('[data-opened-checkbox="' + rubriqueId + '"]').prop('checked', estOuverte);
        });
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