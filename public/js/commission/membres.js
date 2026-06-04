$(document).ready(function () {
    var $collection = $('#membres-accordion');

    // L'index est porté par data-index pour éviter toute dépendance Twig dans ce fichier
    var index = parseInt($collection.data('index'), 10) || 0;

    /**
     * Initialise TomSelect sur les champs select enrichis (groupement, contact) d'un container.
     * Ignore les champs déjà initialisés pour permettre l'appel multiple sans effet de bord.
     *
     * @param {jQuery} $container - Container dans lequel chercher les éléments .tom-select.
     */
    function initTomSelect($container) {
        $container.find('.tom-select').each(function (_i, el) {
            if (!el.tomselect) {
                new TomSelect(el, { plugins: ['remove_button'], maxOptions: null });
            }
        });
    }

    /**
     * Affiche/masque le widget conditionnel (groupement ou contact) selon la valeur
     * sélectionnée dans le select "type de membre".
     *   - valeur 1 → groupement
     *   - valeur 2 → contact utilisateur
     *   - autre    → aucun widget affiché
     *
     * @param {jQuery} $panel - Le panel membre concerné.
     */
    function initTypeMembre($panel) {
        var $typeSelect = $panel.find('.type-membre-select');

        function updateTypeMembre() {
            var val = parseInt($typeSelect.val(), 10);
            $panel.find('.type-membre-widget').hide();
            if (val === 1) {
                $panel.find('.type-membre-groupement').show();
            } else if (val === 2) {
                $panel.find('.type-membre-contact').show();
            }
        }

        $typeSelect.on('change', updateTypeMembre);
        updateTypeMembre();
    }

    /**
     * Synchronise en temps réel le libellé affiché dans l'en-tête du panel
     * avec la valeur saisie dans le champ texte libellé.
     * Affiche "Nouveau membre" si le champ est vide.
     *
     * @param {jQuery} $panel - Le panel membre concerné.
     */
    function initLibelleSync($panel) {
        $panel.find('.membre-libelle-input').on('input', function () {
            var val = $(this).val().trim() || 'Nouveau membre';
            $panel.find('.membre-libelle-display').text(val);
        });
    }

    /**
     * Met à jour l'icône du bouton "Tout" (catégories ERP, classes IGH).
     * Affiche glyphicon-unchecked si toutes les cases sont cochées, glyphicon-check sinon.
     *
     * @param {jQuery} $btn - Le bouton "Tout" à mettre à jour.
     */
    function updateSelectAllBtn($btn) {
        var targetId = $btn.data('target');
        var $checkboxes = $('#' + targetId + ' input[type="checkbox"]');
        var allChecked = $checkboxes.length > 0 && $checkboxes.filter(':checked').length === $checkboxes.length;
        if (allChecked) {
            $btn.html('<span class="glyphicon glyphicon-unchecked" aria-hidden="true"></span> Tout');
        } else {
            $btn.html('<span class="glyphicon glyphicon-check" aria-hidden="true"></span> Tout');
        }
    }

    /**
     * Met à jour l'icône du bouton "Tout" pour les types d'activité ERP.
     * Affiche glyphicon-unchecked si toutes les activités sont cochées, glyphicon-check sinon.
     *
     * @param {jQuery} $panel - Le panel membre concerné.
     */
    function updateSelectAllActivitesBtn($panel) {
        var $btn = $panel.find('.select-all-activites-btn');
        if ($btn.length === 0) { return; }
        var $checkboxes = $panel.find('.activite-item input[type="checkbox"]');
        var allChecked = $checkboxes.length > 0 && $checkboxes.filter(':checked').length === $checkboxes.length;
        if (allChecked) {
            $btn.html('<span class="glyphicon glyphicon-unchecked" aria-hidden="true"></span> Tout');
        } else {
            $btn.html('<span class="glyphicon glyphicon-check" aria-hidden="true"></span> Tout');
        }
    }

    /**
     * Met à jour l'icône du bouton "Tout" pour les types et natures de dossier.
     * Affiche glyphicon-unchecked si tous les types sont cochés (sans indéterminé), glyphicon-check sinon.
     *
     * @param {jQuery} $panel - Le panel membre concerné.
     */
    function updateSelectAllDossiersBtn($panel) {
        var $btn = $panel.find('.select-all-dossiers-btn');
        if ($btn.length === 0) { return; }
        var $typeCheckboxes = $panel.find('.dossier-type-checkbox');
        var allChecked = $typeCheckboxes.length > 0 && $typeCheckboxes.filter(':checked').length === $typeCheckboxes.length;
        if (allChecked) {
            $btn.html('<span class="glyphicon glyphicon-unchecked" aria-hidden="true"></span> Tout');
        } else {
            $btn.html('<span class="glyphicon glyphicon-check" aria-hidden="true"></span> Tout');
        }
    }

    /**
     * Met à jour l'état (coché / indéterminé) de la checkbox maître d'un groupe
     * d'activités ERP en fonction de l'état des checkboxes individuelles du groupe.
     *
     * @param {jQuery} $panel   - Le panel membre concerné.
     * @param {string} groupKey - Clé unique du groupe (attribut data-group).
     */
    function updateActiviteGroupToggle($panel, groupKey) {
        var $toggle = $panel.find('.activite-group-toggle[data-group="' + groupKey + '"]');
        var $checkboxes = $panel.find('#activite-group-' + groupKey + ' input[type="checkbox"]');
        if ($checkboxes.length === 0) { return; }
        var checkedCount = $checkboxes.filter(':checked').length;
        $toggle.prop('checked', checkedCount === $checkboxes.length);
        $toggle.prop('indeterminate', checkedCount > 0 && checkedCount < $checkboxes.length);
    }

    /**
     * Met à jour l'état (coché / indéterminé) de la checkbox d'un type de dossier
     * en fonction des natures qui lui sont rattachées.
     * Sans natures associées, la checkbox reste libre (gérée manuellement par l'utilisateur).
     *
     * @param {jQuery} $panel  - Le panel membre concerné.
     * @param {string} typeId  - Identifiant du type de dossier (attribut data-type-id).
     */
    function updateDossierTypeCheckbox($panel, typeId) {
        var $typeCheckbox = $panel.find('.dossier-type-checkbox[data-type-id="' + typeId + '"]');
        var $natureItems = $panel.find('.dossier-nature-item[data-type-id="' + typeId + '"] input[type="checkbox"]');
        if ($natureItems.length === 0) {
            return;
        }
        var checkedCount = $natureItems.filter(':checked').length;
        $typeCheckbox.prop('checked', checkedCount === $natureItems.length);
        $typeCheckbox.prop('indeterminate', checkedCount > 0 && checkedCount < $natureItems.length);
    }

    /**
     * Initialise l'ensemble des comportements interactifs d'un panel membre :
     * TomSelect, affichage conditionnel du type de membre, synchronisation du libellé,
     * et état initial de toutes les checkboxes (catégories, activités ERP, types/natures dossier).
     *
     * @param {jQuery} $panel - Le panel membre à initialiser.
     */
    function initPanel($panel) {
        initTomSelect($panel);
        initTypeMembre($panel);
        initLibelleSync($panel);

        $panel.find('.select-all-checkbox-btn').each(function () { updateSelectAllBtn($(this)); });

        $panel.find('.activite-group-toggle').each(function () {
            updateActiviteGroupToggle($panel, $(this).data('group'));
        });
        updateSelectAllActivitesBtn($panel);

        $panel.find('.dossier-type-checkbox').each(function () {
            updateDossierTypeCheckbox($panel, $(this).data('type-id'));
        });
        updateSelectAllDossiersBtn($panel);
    }

    // Initialisation des panels existants au chargement de la page
    $collection.find('.membre-rule').each(function () { initPanel($(this)); });

    // Replie tous les panels existants sauf le premier
    $collection.find('.membre-rule:not(:first-child) .panel-collapse').removeClass('in');
    $collection.find('.membre-rule:not(:first-child) .panel-title__link')
        .attr('aria-expanded', 'false')
        .addClass('collapsed');

    // --- Boutons "Tout" — catégories ERP et classes IGH ---

    $(document).on('click', '.select-all-checkbox-btn', function () {
        var $btn = $(this);
        var targetId = $btn.data('target');
        var $checkboxes = $('#' + targetId + ' input[type="checkbox"]');
        var allChecked = $checkboxes.filter(':checked').length === $checkboxes.length;
        $checkboxes.prop('checked', !allChecked);
        updateSelectAllBtn($btn);
    });

    $(document).on('change', '.checkbox-scrollable-area input[type="checkbox"]', function () {
        var $area = $(this).closest('.checkbox-scrollable-area');
        var $btn = $('[data-target="' + $area.attr('id') + '"]');
        if ($btn.length) { updateSelectAllBtn($btn); }
    });

    // --- Boutons "Tout" — types d'activité ERP ---

    $(document).on('click', '.select-all-activites-btn', function () {
        var $panel = $(this).closest('.membre-rule');
        var $checkboxes = $panel.find('.activite-item input[type="checkbox"]');
        var allChecked = $checkboxes.length > 0 && $checkboxes.filter(':checked').length === $checkboxes.length;
        $panel.find('.activite-group-toggle').prop('checked', !allChecked).prop('indeterminate', false).trigger('change');
        updateSelectAllActivitesBtn($panel);
    });

    // --- Boutons "Tout" — types et natures de dossier ---

    $(document).on('click', '.select-all-dossiers-btn', function () {
        var $panel = $(this).closest('.membre-rule');
        var $typeCheckboxes = $panel.find('.dossier-type-checkbox');
        var allChecked = $typeCheckboxes.length > 0 && $typeCheckboxes.filter(':checked').length === $typeCheckboxes.length;
        $typeCheckboxes.prop('checked', !allChecked).prop('indeterminate', false).trigger('change');
        updateSelectAllDossiersBtn($panel);
    });

    // --- Groupes d'activités ERP ---

    /**
     * Coche ou décoche toutes les activités d'un groupe lorsque la checkbox maître change.
     */
    $(document).on('change', '.activite-group-toggle', function () {
        var $toggle = $(this);
        var groupKey = $toggle.data('group');
        var $panel = $toggle.closest('.membre-rule');
        $panel.find('#activite-group-' + groupKey + ' input[type="checkbox"]').prop('checked', $toggle.prop('checked'));
        $toggle.prop('indeterminate', false);
        updateSelectAllActivitesBtn($panel);
    });

    $(document).on('change', '.activite-item input[type="checkbox"]', function () {
        var $panel = $(this).closest('.membre-rule');
        updateActiviteGroupToggle($panel, $(this).closest('.activite-item').data('group'));
        updateSelectAllActivitesBtn($panel);
    });

    // --- Types et natures de dossier ---

    /**
     * Coche ou décoche toutes les natures d'un type lorsque la checkbox du type change.
     */
    $(document).on('change', '.dossier-type-checkbox', function () {
        var $cb = $(this);
        var typeId = $cb.data('type-id');
        var $panel = $cb.closest('.membre-rule');
        $cb.prop('indeterminate', false);
        $panel.find('.dossier-nature-item[data-type-id="' + typeId + '"] input[type="checkbox"]').prop('checked', $cb.prop('checked'));
        updateSelectAllDossiersBtn($panel);
    });

    $(document).on('change', '.dossier-nature-item input[type="checkbox"]', function () {
        var $panel = $(this).closest('.membre-rule');
        updateDossierTypeCheckbox($panel, $(this).closest('.dossier-nature-item').data('type-id'));
        updateSelectAllDossiersBtn($panel);
    });

    // --- Ajout d'un membre ---

    /**
     * Ajoute un nouveau panel membre via le prototype Symfony forms stocké en data-prototype.
     * Replie tous les panels ouverts avant l'insertion, puis initialise le nouveau panel.
     */
    $(document).on('click', '.add-membre-btn', function () {
        var prototype = $collection.data('prototype');
        if (!prototype) { return; }

        var newForm = prototype.replace(/__name__/g, index);
        $collection.find('.no-membres-message').remove();
        $collection.find('.panel-collapse.in').removeClass('in');
        $collection.append(newForm);

        initPanel($collection.find('.membre-rule:last'));
        index++;
    });

    // --- Suppression d'un membre ---

    /**
     * Supprime un panel membre du DOM.
     * Détruit les instances TomSelect avant suppression pour éviter les fuites mémoire.
     * Affiche un message vide si la collection ne contient plus aucun membre.
     */
    $(document).on('click', '.remove-membre-btn', function () {
        var $panel = $(this).closest('.membre-rule');
        $panel.find('.tom-select').each(function (_i, el) {
            if (el.tomselect) { el.tomselect.destroy(); }
        });
        $panel.remove();

        if ($collection.find('.membre-rule').length === 0) {
            $collection.html('<div class="no-membres-message"><p class="text-muted"><em>Aucun membre défini pour cette commission.</em></p></div>');
        }
    });

    // --- Indicateur de chargement à la soumission ---

    $('#membres-form').on('submit', function () {
        var $btn = $(this).find('.save-membres-btn');
        $btn.prop('disabled', true).html(
            '<span class="glyphicon glyphicon-refresh spinning" aria-hidden="true"></span> Sauvegarde en cours...'
        );
    });
});
