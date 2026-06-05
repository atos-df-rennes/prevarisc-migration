(function($) {
    'use strict';

    const CONFIG = {
        modal: '#reponse-modal',
        modalContent: '.modal-content',
        sectionsContainer: '#sections-personnalisation',
        dashboardPanels: '.dashboard .panel:visible',
    };

    const SECTIONS = {
        dossiers: { title: 'Dossiers', icon: 'icon-folder-close' },
        etablissements: { title: 'Établissements', icon: 'icon-th' },
        autres: { title: 'Messages, Courriers & Ordre du jour', icon: '' },
    };

    const getSectionKey = type =>
        ['dossiers', 'etablissements'].includes(type) ? type : 'autres';

    const blocHTML = bloc => `
        <div class="bloc-sortable" data-bloc-id="${bloc.id}" data-type="${bloc.type}">
            ${bloc.icon ? `<i class="${bloc.icon}" style="margin-right:8px;"></i>` : ''}
            <span class="bloc-titre">${bloc.titre}</span>
            ${bloc.compteur ? `<span class="badge badge-info">${bloc.compteur}</span>` : ''}
        </div>
    `;

    const sectionHTML = (key, section) => `
        <div class="section-personnalisation" data-section="${key}">
            <h4>${section.icon ? `<i class="${section.icon}"></i>` : ''} ${section.title}</h4>
            <div class="blocs-sortable" data-type="${key}"></div>
        </div>
    `;

    function construireSections() {
        const $container = $(CONFIG.sectionsContainer).empty();
        const sections = { dossiers: [], etablissements: [], autres: [] };

        // Collecter les blocs existants
        $(CONFIG.dashboardPanels).each(function() {
            const $p = $(this);
            const bloc = {
                id: $p.attr('id'),
                type: $p.data('type'),
                titre: $p.find('.panel-heading').clone().find('.compteur').remove().end().text().trim(),
                compteur: $p.find('.compteur').text().trim(),
            };
            sections[getSectionKey(bloc.type)].push(bloc);
        });

        Object.entries(SECTIONS).forEach(([key, section]) => {
            if (sections[key].length === 0) return;
            $container.append(sectionHTML(key, section));
            const $zone = $container.find(`.blocs-sortable[data-type="${key}"]`);
            sections[key].forEach(bloc => $zone.append(blocHTML(bloc)));
        });

        // Activer le drag & drop
        $('.blocs-sortable').sortable({
            placeholder: 'bloc-placeholder',
            forcePlaceholderSize: true,
            containment: 'parent'
        }).on('sortupdate', enregistrerOrdre);

        // Rechargement du dashboard après fermeture
        $(CONFIG.modal).on('hidden.bs.modal', () => location.reload());
    }

    function enregistrerOrdre() {
        const ordre = [];
        $('.blocs-sortable').each(function() {
            $(this).children().each(function() {
                ordre.push($(this).data('bloc-id'));
            });
        });

        $.ajax({
            url: '/index/save-bloc-order',
            type: 'POST',
            dataType: 'json',
            data: { ordre },
            success: res => console.log('Ordre sauvegardé', res),
            error: xhr => console.error('Erreur AJAX', xhr.responseText)
        });
    }

    const ModalPersonnalisation = {
        ouvrir() {
            $(`${CONFIG.modal} ${CONFIG.modalContent}`).html(`
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">&times;</button>
                    <h3 class="modal-title">Personnaliser l'organisation des cartes</h3>
                </div>
                <div class="modal-body">
                    <div id="sections-personnalisation"></div>
                </div>
            `);
            construireSections();
            $(CONFIG.modal).modal('show');
        }
    };

    $(document).on('click', '.filtre-cartes-personnaliser-btn', e => {
        e.preventDefault();
        ModalPersonnalisation.ouvrir();
    });

})(jQuery);