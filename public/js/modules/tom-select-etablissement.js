/**
 * Initialise un TomSelect pour la recherche d'établissement via l'API.
 *
 * @param {string}   selectId   Sélecteur CSS de l'élément <select>
 * @param {string}   searchUrl  URL de l'API de recherche
 * @param {Function} onSelect   Callback appelé quand un item est sélectionné (reçoit la valeur)
 * @param {Function} onDeselect Callback appelé quand un item est désélectionné
 *
 * @returns {TomSelect}
 */
export function initTomSelectEtablissement(selectId, searchUrl, onSelect, onDeselect) {
    return new TomSelect(selectId, {
        valueField: 'id',
        labelField: 'libelle',
        searchField: 'libelle',
        shouldLoad: (query) => query.length > 1,
        load: (query, callback) => {
            fetch(searchUrl + '?q=' + encodeURIComponent(query))
                .then((r) => r.json())
                .then(callback)
                .catch(() => callback());
        },
        render: {
            // Fonctions classiques pour accéder à this.escape_html() de l'instance TomSelect
            option: function (item) {
                const label = item.genre
                    ? '[' + this.escape_html(item.genre) + '] ' + this.escape_html(item.libelle)
                    : this.escape_html(item.libelle);
                return '<div>' + label + '</div>';
            },
            item: function (item) {
                const label = item.genre
                    ? '[' + this.escape_html(item.genre) + '] ' + this.escape_html(item.libelle)
                    : this.escape_html(item.libelle);
                return '<div>' + label + '</div>';
            },
        },
        onItemAdd: onSelect,
        onItemRemove: onDeselect,
    });
}
