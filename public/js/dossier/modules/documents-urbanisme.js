/**
 * Module de gestion des documents d'urbanisme
 * Responsabilité : Gestion documents urbanisme avec préfixes automatiques
 */

/**
 * Initialise le module Documents Urbanisme
 */
export function initDocumentsUrbanismeModule() {
    initDocumentsUrbanismeHandlers();
}

/**
 * Gestion spécifique des documents d'urbanisme
 * Préfixe automatiquement les nouveaux documents selon la nature du dossier
 */
function initDocumentsUrbanismeHandlers() {
    // Écouter l'événement personnalisé dispatché par add-collection-widget.js
    document.addEventListener('collection:item-added', function(e) {
        const { list, element } = e.detail;
        
        // Vérifier si c'est la collection des documents d'urbanisme
        if (list.id !== 'dossier_documentsUrbanisme') {
            return;
        }
        
        // Récupérer la nature sélectionnée
        const natureSelect = document.querySelector('#dossier_nature');
        if (!natureSelect) {
            return;
        }
        
        const natureId = natureSelect.value;
        const prefix = getDocumentUrbanismePrefix(natureId);
        
        if (prefix) {
            const input = element.querySelector('input[type="text"]');
            if (input && !input.value) {
                input.value = prefix + ' ';
                // Positionner le curseur après le préfixe
                input.focus();
                input.setSelectionRange(prefix.length + 1, prefix.length + 1);
            }
        }
    });
}

/**
 * Retourne le préfixe à appliquer selon la nature du dossier
 * @param {string} natureId - ID de la nature
 * @returns {string|null} - Préfixe ou null si aucun
 */
function getDocumentUrbanismePrefix(natureId) {
    // Mapping des natures vers leurs préfixes
    const prefixMapping = {
        '1': 'PC',
        '2': 'AT',
        '14': 'PA',
        '30': 'DP',
        '62': 'CU'
    };
    
    return prefixMapping[natureId] || null;
}
