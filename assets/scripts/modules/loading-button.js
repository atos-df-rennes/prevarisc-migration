/**
 * Module de gestion de l'état de chargement des boutons de soumission.
 * Responsabilité : Désactiver un bouton et afficher un spinner pendant le traitement.
 */

const SPINNER_HTML = '<span class="glyphicon glyphicon-refresh spinning" aria-hidden="true"></span> ';

/**
 * Active l'état de chargement sur un bouton lors de la soumission du formulaire parent.
 *
 * @param {string} selector - Sélecteur CSS du bouton (ex: '#mon-btn')
 * @param {string} loadingText - Texte affiché pendant le chargement (ex: 'Enregistrement...')
 */
export function initLoadingButton(selector, loadingText) {
    const button = document.querySelector(selector);
    if (!button) {
        return;
    }

    const form = button.closest('form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', function () {
        button.disabled = true;
        button.innerHTML = SPINNER_HTML + loadingText;
    });
}

/**
 * Active l'état de chargement sur un bouton lors d'un clic (pour les boutons hors formulaire
 * ou avec soumission programmatique).
 *
 * @param {string} selector - Sélecteur CSS du bouton
 * @param {string} loadingText - Texte affiché pendant le chargement
 */
export function initLoadingButtonOnClick(selector, loadingText) {
    const button = document.querySelector(selector);
    if (!button) {
        return;
    }

    button.addEventListener('click', function () {
        button.disabled = true;
        button.innerHTML = SPINNER_HTML + loadingText;
    });
}
