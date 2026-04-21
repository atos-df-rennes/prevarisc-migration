class DateCollectionObserver {
    constructor($collectionContainer, $planificationCheckbox) {
        this.$collection = $collectionContainer;
        this.$checkbox = $planificationCheckbox;
        this.$addButton = $collectionContainer.next().find('.add-collection-widget');

        this.init();
    }

    init() {
        const config = {
            childList: true,
            subtree: false,
            attributes: false,
            characterData: false
        };

        this.observer = new MutationObserver((mutations) => {
            this.handleCollectionChange(mutations);
        });

        this.observer.observe(this.$collection[0], config);

        this.updateUI();

        this.$checkbox.on('change', () => {
            this.handleCheckboxChange();
        });
    }

    handleCollectionChange(mutations) {
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
            // Une seule date : afficher #addReapeat (contient la checkbox)
            $('#addReapeat').show();
        } else if (dateCount > 1) {
            // Plusieurs dates : masquer #addReapeat et #planification
            $('#addReapeat').hide();
            $('#planification').hide();
            this.$checkbox.prop('checked', false);
            this.showAddButton();
        }
    }

    handleCheckboxChange() {
        if (this.$checkbox.is(':checked')) {
            $('#planification').show();
            this.hideAddButton();
        } else {
            $('#planification').hide();
            const dateCount = this.getDateCount();
            if (dateCount === 1) {
                this.showAddButton();
            }
        }
    }

    getDateCount() {
        return this.$collection.children('li, .collection-item').length;
    }

    showAddButton() {
        this.$addButton.show();
    }

    hideAddButton() {
        this.$addButton.hide();
    }

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
        this.$checkbox.off('change');
    }
}

// Export global
window.DateCollectionObserver = DateCollectionObserver;