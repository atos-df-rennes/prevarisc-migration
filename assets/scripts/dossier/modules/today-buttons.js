/**
 * Module de gestion des boutons "Aujourd'hui"
 * Responsabilité : Gestion des boutons "Aujourd'hui" pour les champs date
 */

/**
 * Initialise le module Today Buttons
 */
export function initTodayButtonsModule() {
    initTodayButtons();
    
    // Réinitialiser les boutons quand de nouveaux champs sont affichés
    document.addEventListener('dossier:fieldsUpdated', function() {
        initTodayButtons();
    });
}

/**
 * Initialise les boutons "Aujourd'hui" pour les champs date
 */
function initTodayButtons() {
    const todayButtons = document.querySelectorAll('.btn-today');
    
    todayButtons.forEach(button => {
        // Éviter de réattacher plusieurs fois le même listener
        if (button.dataset.initialized === 'true') {
            return;
        }
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            if (targetId) {
                const dateInput = document.querySelector('#' + targetId);
                if (dateInput) {
                    // Format YYYY-MM-DD pour input type="date"
                    const today = new Date();
                    const yyyy = today.getFullYear();
                    const mm = String(today.getMonth() + 1).padStart(2, '0');
                    const dd = String(today.getDate()).padStart(2, '0');
                    dateInput.value = `${yyyy}-${mm}-${dd}`;
                }
            }
        });
        
        button.dataset.initialized = 'true';
    });
}
