/**
 * Gestion des tableaux dynamiques pour les formulaires personnalisés Symfony.
 *
 * Utilise le mécanisme officiel Symfony CollectionType (data-prototype, data-index).
 *
 * Structure attendue dans le template :
 *   <tbody data-prototype="..." data-index="N">
 *     <tr>...</tr>  // lignes existantes
 *   </tbody>
 *   <button class="addRow">Ajouter une ligne</button>
 */
$(document).ready(function () {

    // Ajout d'une ligne via le prototype Symfony CollectionType
    $(document).on('click', '.addRow', function () {
        const $tbody = $(this).closest('.table-tableau').find('tbody');
        const prototype = $tbody.data('prototype');
        const index = parseInt($tbody.data('index'), 10) || 0;

        const newRow = prototype.replace(/__name__/g, index);
        $tbody.append(newRow);
        $tbody.data('index', index + 1);
    });

    // Suppression d'une ligne
    $(document).on('click', '.deleteRow', function () {
        $(this).closest('tr').remove();
    });

    /**
     * Renomme les indices des champs d'une collection après un drag & drop.
     * À appeler après chaque déplacement de ligne.
     *
     * Exemple : si une ligne avec index 2 est déplacée en position 0,
     * cette fonction rénumérote tous les champs pour que l'ordre de soumission
     * reflète l'ordre affiché.
     *
     * @param {jQuery} $tbody - Le tbody contenant les lignes du tableau
     */
    window.reindexTableauRows = function ($tbody) {
        $tbody.find('tr').each(function (newIndex) {
            $(this).find('input, select, textarea').each(function () {
                const name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/\[lignes\]\[\d+\]/, '[lignes][' + newIndex + ']'));
                }
                const id = $(this).attr('id');
                if (id) {
                    $(this).attr('id', id.replace(/_lignes_\d+_/, '_lignes_' + newIndex + '_'));
                }
            });
        });
        // Mise à jour data-index : prochain index = nombre de lignes actuelles
        $tbody.data('index', $tbody.find('tr').length);
    };

});
