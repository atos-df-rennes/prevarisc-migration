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
     * Synchronise le champ caché "opened" avec l'état réel du collapse Bootstrap
     * (panel-collapse), afin que l'état d'ouverture/repliement soit soumis avec le formulaire.
     *
     * Chaque panneau de rubrique porte l'attribut data-opened-checkbox="{rubriqueId}"
     * sur son champ opened (CheckboxType caché).
     */
    $(document).on('show.bs.collapse hide.bs.collapse', '.panel-formulaire-perso .panel-collapse', function(e) {
        const panel = $(this).closest('.panel-formulaire-perso');
        const rubriqueId = panel.attr('id') ? panel.attr('id').replace('afficher_rubrique-', '') : null;
        if (!rubriqueId) return;

        $('[data-opened-checkbox="' + rubriqueId + '"]').prop('checked', e.type === 'show');
    });

    /**
     * Réconciliation au submit : bootstrap-3.min.js est chargé dans base.html.twig, donc le
     * collapse est opérationnel avant que ce script n'enregistre le handler ci-dessus. Un clic
     * joué dans cette fenêtre bascule le panneau sans mettre à jour la checkbox cachée, et le
     * formulaire resoumet alors la valeur rendue par le serveur (le défaut administré), qui est
     * ensuite figée comme préférence utilisateur.
     *
     * On relit donc l'état réel juste avant l'envoi. aria-expanded est écrit synchroniquement par
     * Bootstrap dès le clic (contrairement à la classe "in", posée en fin de transition) et
     * indépendamment de ce script : un clic antérieur à son chargement est donc bien récupéré.
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