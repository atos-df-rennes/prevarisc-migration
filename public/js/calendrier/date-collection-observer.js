class DateCollectionObserver {
    constructor($collectionContainer, $planificationCheckbox) {
        this.$collection = $collectionContainer;
        this.$checkbox = $planificationCheckbox;
        this.$addButton = $collectionContainer.next().find('.add-collection-widget');

        this.init();
    }

    init() {
        // Configuration de l'observer
        const config = {
            childList: true,        // Observer les ajouts/suppressions d'enfants
            subtree: false,         // Pas besoin d'observer les sous-éléments
            attributes: false,      // On ne surveille pas les attributs
            characterData: false    // On ne surveille pas le texte
        };

        // Créer l'observer
        this.observer = new MutationObserver((mutations) => {
            this.handleCollectionChange(mutations);
        });

        // Démarrer l'observation
        this.observer.observe(this.$collection[0], config);

        // 🎯 État initial au chargement
        this.updateUI();

        // 🎯 Événement sur la checkbox (inverse)
        this.$checkbox.on('change', () => {
            this.handleCheckboxChange();
        });
    }

    handleCollectionChange(mutations) {
        // Filtrer uniquement les mutations qui ajoutent/suppriment des éléments
        const hasChanges = mutations.some(mutation =>
            mutation.type === 'childList' &&
            (mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0)
        );

        if (hasChanges) {
            this.updateUI();
        }
    }

    updateUI() {
        const dateCount = this.getDateCount();

        if (dateCount === 1) {
            // Une seule date : afficher la checkbox, masquer le bouton d'ajout
            this.showCheckbox();
        } else if (dateCount > 1) {
            // Plusieurs dates : masquer la checkbox et son contenu
            this.hideCheckbox();
            this.showAddButton();
        }
    }

    handleCheckboxChange() {
        if (this.$checkbox.is(':checked')) {
            this.hideAddButton();
        } else {
            const dateCount = this.getDateCount();
            if (dateCount === 1) {
                this.showAddButton();
            }
        }
    }

    getDateCount() {
        // Compter les éléments <li> ou .collection-item
        return this.$collection.children('li, .collection-item').length;
    }

    showCheckbox() {
        this.$checkbox.closest('.form-group, .row').show();
    }

    hideCheckbox() {
        this.$checkbox.closest('.form-group, .row').hide();
        // Décocher automatiquement si cachée
        this.$checkbox.prop('checked', false);
    }

    showAddButton() {
        this.$addButton.show();
    }

    hideAddButton() {
        this.$addButton.hide();
    }

    destroy() {
        // Nettoyer l'observer si besoin de détruire l'instance
        if (this.observer) {
            this.observer.disconnect();
        }
        this.$checkbox.off('change');
    }
}

// Export global
window.DateCollectionObserver = DateCollectionObserver;