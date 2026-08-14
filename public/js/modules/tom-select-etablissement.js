/**
 * Module partagé d'autocomplétion d'établissements (TomSelect).
 *
 * Unique consommateur de la route API `api_etablissements_search`, utilisée par la
 * recherche de la barre de navigation, les liaisons de dossier et les établissements
 * père/enfants du formulaire établissement.
 *
 * Le rendu reproduit le legacy : « [genre] libellé (commune) ».
 */

/** Nombre minimum de caractères avant déclenchement (aligné sur le seuil serveur). */
export const MIN_QUERY_LENGTH = 3;

/** Délai anti-rebond entre deux appels API, en millisecondes. */
const LOAD_THROTTLE = 300;

/**
 * Formate le libellé affiché d'un résultat : « [genre] libellé (commune) ».
 * Utilisé côté client pour l'affichage dans les options TomSelect.
 *
 * @param {{libelle: string, genre: ?string, commune: ?string}} item
 * @param {Function} escape Fonction d'échappement fournie par TomSelect
 *
 * @returns {string}
 */
function formatEtablissementDisplay(item, escape) {
    let label = escape(item.libelle || '');

    if (item.genre) {
        label = '[' + escape(item.genre) + '] ' + label;
    }

    if (item.commune) {
        label += ' (' + escape(item.commune) + ')';
    }

    return label;
}

/**
 * Initialise un TomSelect de recherche d'établissement branché sur l'API commune.
 *
 * @param {string} selector Sélecteur CSS de l'élément support (`<select>` ou `<input>`)
 * @param {string} searchUrl URL de la route `api_etablissements_search`
 * @param {Object} [options] Options complémentaires
 * @param {Function} [options.onSelect] Appelé lors de la sélection d'un item (valeur, item)
 * @param {Function} [options.onDeselect] Appelé lors de la désélection d'un item
 * @param {Function} [options.onChange] Appelé à chaque changement de valeur (valeur, vide si effacé)
 * @param {Function} [options.onType] Appelé à chaque frappe (texte saisi)
 * @param {string} [options.placeholder] Texte indicatif du champ
 * @param {number} [options.maxItems] Nombre maximum d'items sélectionnables
 *
 * @returns {TomSelect}
 */
export function initTomSelectEtablissement(selector, searchUrl, options) {
    const settings = options || {};

    return new TomSelect(selector, {
        valueField: 'id',
        labelField: '__display__',
        searchField: ['libelle', 'numeroId', 'commune'],
        create: false,
        maxItems: undefined === settings.maxItems ? 1 : settings.maxItems,
        placeholder: settings.placeholder,
        loadThrottle: LOAD_THROTTLE,
        shouldLoad: (query) => query.trim().length >= MIN_QUERY_LENGTH,
        load: (query, callback) => {
            fetch(searchUrl + '?q=' + encodeURIComponent(query.trim()))
                .then((response) => (response.ok ? response.json() : []))
                .then((items) => {
                    // Transform API response to add display label, avoiding TomSelect render bug
                    return items.map((item) => ({
                        ...item,
                        __display__: formatEtablissementDisplay(item, (str) => str),
                    }));
                })
                .then(callback)
                .catch(() => callback());
        },
        onType: (str) => {
            if (settings.onType) {
                settings.onType(str);
            }
        },
        onItemAdd: function (value) {
            if (settings.onSelect) {
                settings.onSelect(value, this.options[value]);
            }
        },
        onItemRemove: (value) => {
            if (settings.onDeselect) {
                settings.onDeselect(value);
            }
        },
        onChange: (value) => {
            if (settings.onChange) {
                settings.onChange(value);
            }
        },
    });
}
