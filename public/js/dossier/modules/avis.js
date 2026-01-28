/**
 * Module de gestion des avis
 * Responsabilité : Gestion affichage/masquage avis & dérogations
 */

/**
 * Configuration des champs par nature (référence depuis type-nature.js)
 */
let fieldsConfig = {};

/**
 * Initialise le module Avis
 * @param {Object} config - Configuration des champs par nature
 */
export function initAvisModule(config) {
    fieldsConfig = config || {};
    
    // Initialiser les labels d'avis au chargement
    initAvisLabelsOnLoad();
    
    // Appliquer la logique d'affichage des avis selon les checkboxes cochées (au chargement)
    applyHideAvisLogicOnLoad();
    
    // Afficher facteur/échéancier si avis commission = Défavorable (au chargement)
    applyFacteurEcheancierDisplayOnLoad();
    
    // Écouter les checkboxes qui masquent les avis (Bloc 6)
    initHideAvisCheckboxListeners();
    
    // Attacher listeners événements personnalisés
    attachEventListeners();
}

/**
 * Attache les event listeners pour les événements personnalisés
 */
function attachEventListeners() {
    // Écouter les événements personnalisés pour réappliquer la logique d'affichage des avis
    // Permet aux autres modules (gestion-incomplet.js) de déclencher la mise à jour
    document.addEventListener('dossier:updateAvisVisibility', function() {
        applyHideAvisLogicOnLoad();
    });
    
    // Réinitialiser labels avis si changement de nature
    document.addEventListener('dossier:natureChanged', function() {
        initAvisLabelsOnLoad();
        applyHideAvisLogicOnLoad();
        applyFacteurEcheancierDisplayOnLoad();
    });
    
    // Écouter les changements d'avis de commission pour afficher/masquer facteur et échéancier
    const avisCommissionSelect = document.querySelector('#dossier_avisCommission');
    if (avisCommissionSelect) {
        avisCommissionSelect.addEventListener('change', applyFacteurEcheancierDisplayOnLoad);
    }
}

/**
 * Initialise les event listeners pour les checkboxes masquant les avis
 * Checkboxes concernées : horsdelai, absquorum, npsp
 * Legacy: index.phtml ligne 359 $(".hideavis").click(function() { afficherCacherAvis(); });
 */
function initHideAvisCheckboxListeners() {
    const horsdelaiCheckbox = document.querySelector('.horsdelai');
    const absquorumCheckbox = document.querySelector('.absquorum');
    const npspCheckbox = document.querySelector('.npsp');

    // Ajouter event listener sur chaque checkbox
    [horsdelaiCheckbox, absquorumCheckbox, npspCheckbox].forEach(checkbox => {
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                // Réappliquer la logique complète d'affichage/masquage
                applyHideAvisLogicOnLoad();
                // Réappliquer aussi facteur/échéancier (car peut dépendre de l'avis affiché)
                applyFacteurEcheancierDisplayOnLoad();
            });
        }
    });
}

/**
 * Initialise les labels d'avis au chargement de la page (Phase 1 - statique)
 * Note: En Phase 2, cette fonction deviendra dynamique (réagira aux changements de type/nature)
 */
function initAvisLabelsOnLoad() {
    const typeSelect = document.querySelector('#dossier_type');
    const natureSelect = document.querySelector('#dossier_nature');
    
    if (!typeSelect || !natureSelect) {
        return;
    }

    const typeDossier = typeSelect.value;
    const natureDossier = natureSelect.value;
    let addText = '';

    if (typeDossier === '1') { // Étude
        if (['19', '7', '17', '16'].includes(natureDossier)) {
            addText = ' à l\'exploitation';
        } else {
            addText = ' à l\'étude';
        }
    } else if (typeDossier === '2' || typeDossier === '3') { // Visite ou Groupe de visites
        if (['20', '25'].includes(natureDossier)) {
            addText = ' à la réception des travaux';
        } else if (['21', '23', '24', '26', '28', '29'].includes(natureDossier)) {
            addText = ' à l\'exploitation';
        } else {
            addText = ' à l\'exploitation';
        }
    }

    // Mettre à jour les options des selects avis (une seule fois au chargement)
    const avisSelect = document.querySelector('#dossier_avis');
    const avisCommissionSelect = document.querySelector('#dossier_avisCommission');

    [avisSelect, avisCommissionSelect].forEach(select => {
        if (!select) return;
        
        Array.from(select.options).forEach(option => {
            if (option.value === '' || option.value === '0') return;
            
            // Extraire le texte de base (sans suffixe)
            let baseText = option.text.replace(/ à l'.*$/, '').replace(/ à la.*$/, '');
            
            if (option.value === '6') {
                // "Pas d'avis" - pas de suffixe
                option.text = baseText;
            } else {
                option.text = baseText + addText;
            }
        });
    });
}

/**
 * Applique la logique d'affichage/masquage des avis au chargement (Phase 1 - statique)
 * Reproduit la logique legacy de afficheChamps() lignes 980-997 et afficherCacherAvis()
 * 
 * Logique legacy (ordre de priorité) :
 * 1. Si "incomplet" coché → masquer AVIS, AVIS_COMMISSION, HORSDELAI
 * 2. Sinon si "horsdelai" coché → masquer AVIS, AVIS_COMMISSION, dateCommission
 * 3. Sinon si "absquorum" ou "npsp" coché → masquer AVIS_COMMISSION, dateCommission
 * 4. Sinon afficher selon type (standard)
 * 
 * @public Exposée via événement personnalisé pour appel externe
 */
function applyHideAvisLogicOnLoad() {
    const typeSelect = document.querySelector('#dossier_type');
    const natureSelect = document.querySelector('#dossier_nature');
    if (!typeSelect || !natureSelect) {
        return;
    }

    const typeDossier = typeSelect.value;
    const natureDossier = natureSelect.value;
    
    // Vérifier si les champs sont dans la configuration de la nature
    const natureFields = fieldsConfig[natureDossier] || [];
    const hasAvisInNature = natureFields.includes('avis');
    const hasAvisCommissionInNature = natureFields.includes('avisCommission');
    
    // Récupérer les checkboxes (legacy: lignes 980-984)
    const incompletCheckbox = document.querySelector('input[name="dossier[incomplet]"]:checked');
    const horsdelaiCheckbox = document.querySelector('.horsdelai:checked');
    const absquorumCheckbox = document.querySelector('.absquorum:checked');
    const npspCheckbox = document.querySelector('.npsp:checked');
    
    const avisRow = findFieldRow('avis');
    const avisCommissionRow = findFieldRow('avisCommission');
    const dateCommissionRow = findFieldRow('dateCommission');
    const horsDelaiRow = findFieldRow('horsDelai');

    // Logique par ordre de priorité (legacy: lignes 986-997)
    if (incompletCheckbox && incompletCheckbox.value === '1') {
        // Cas 1: Incomplet → masquer AVIS, AVIS_COMMISSION, HORSDELAI (ligne 987-989)
        if (avisRow) avisRow.style.display = 'none';
        if (avisCommissionRow) avisCommissionRow.style.display = 'none';
        if (horsDelaiRow) horsDelaiRow.style.display = 'none';
    } else if (horsdelaiCheckbox) {
        // Cas 2: Hors délai → masquer AVIS, AVIS_COMMISSION, dateCommission (ligne 990-993)
        if (avisRow) avisRow.style.display = 'none';
        if (avisCommissionRow) avisCommissionRow.style.display = 'none';
        if (dateCommissionRow) dateCommissionRow.style.display = 'none';
    } else if (absquorumCheckbox || npspCheckbox) {
        // Cas 3: Absence quorum ou NPSP → masquer AVIS_COMMISSION, dateCommission (ligne 994-996)
        if (avisCommissionRow) avisCommissionRow.style.display = 'none';
        if (dateCommissionRow) dateCommissionRow.style.display = 'none';
        // L'avis du rapporteur reste affiché si dans la nature
        if (avisRow && hasAvisInNature) avisRow.style.display = '';
    } else {
        // Cas 4: Aucune checkbox spéciale → afficher selon type ET nature (standard)
        if (typeDossier === '1') { // Étude
            if (avisRow && hasAvisInNature) avisRow.style.display = '';
            if (avisCommissionRow && hasAvisCommissionInNature) avisCommissionRow.style.display = '';
        } else if (typeDossier === '2') { // Visite
            if (avisRow && hasAvisInNature) avisRow.style.display = '';
            if (avisCommissionRow && hasAvisCommissionInNature) avisCommissionRow.style.display = '';
        } else if (typeDossier === '3') { // Groupe de visites
            if (avisRow && hasAvisInNature) avisRow.style.display = '';
            if (avisCommissionRow && hasAvisCommissionInNature) avisCommissionRow.style.display = '';
        }
    }
}

/**
 * Affiche facteur de criticité et échéancier de travaux au chargement si avis commission = Défavorable
 * 
 * Règles legacy (lignes 995-1011 et 1256-1274) :
 * - Type 2 ou 3 (Visites/Groupes) + Avis commission = 2 (Défavorable) → Afficher FACTDANGE + ECHEANCIERTRAV
 * - Type 1 (Étude) + Nature 19 (Levée de réserves) + Avis commission = 2 → Afficher FACTDANGE uniquement (pas ECHEANCIERTRAV)
 * 
 * @note La gestion dynamique (changement d'avis) est assurée par onAvisCommissionChange()
 */
function applyFacteurEcheancierDisplayOnLoad() {
    const avisCommissionSelect = document.querySelector('#dossier_avisCommission');
    if (!avisCommissionSelect) {
        return;
    }

    const typeSelect = document.querySelector('#dossier_type');
    const natureSelect = document.querySelector('#dossier_nature');
    
    if (!typeSelect || !natureSelect) {
        return;
    }

    const typeDossier = typeSelect.value;
    const natureDossier = natureSelect.value;
    const selectedAvisId = avisCommissionSelect.value;
    
    const facteurRow = findFieldRow('facteurDangerosite');
    const echeancierRow = findFieldRow('echeancierTravaux');
    
    // Réinitialiser l'affichage
    if (facteurRow) facteurRow.style.display = 'none';
    if (echeancierRow) echeancierRow.style.display = 'none';
    
    // Règle 1 : Type 2 ou 3 (Visites/Groupes) + Avis Défavorable
    if ((typeDossier === '2' || typeDossier === '3') && selectedAvisId === '2') {
        if (facteurRow) facteurRow.style.display = '';
        if (echeancierRow) echeancierRow.style.display = '';
    }
    // Règle 2 : Type 1 (Étude) + Nature 19 (Levée de réserves) + Avis Défavorable
    else if (typeDossier === '1' && natureDossier === '19' && selectedAvisId === '2') {
        if (facteurRow) facteurRow.style.display = '';
        // IMPORTANT : écheancier ne s'affiche PAS pour les études, même nature 19
    }
}

/**
 * Trouve la ligne (<li>) contenant un champ
 * @param {string} fieldName - Nom du champ
 * @returns {HTMLElement|null}
 */
function findFieldRow(fieldName) {
    const fieldId = 'dossier_' + fieldName;
    
    // Chercher par ID de ligne direct (format Symfony)
    var row = document.querySelector('#field-' + fieldName);
    if (row) {
        return row;
    }

    // Sinon chercher le champ et remonter au parent li
    var field = document.querySelector('#' + fieldId);
    if (!field) {
        return null;
    }

    // Remonter jusqu'à la <li> parente
    return field.closest('li.row');
}
