/* =============================================================================
 * contacts-panel.js — Panneau de gestion des contacts
 *
 * Dépendances globales attendues :
 *   - jQuery
 *   - TomSelect
 *   - Routing (FOSJsRoutingBundle)
 *   - $.toast (toast-notifications.js)
 *   - CONTACTS_PANEL_CONFIG (injecté par le template Twig)
 *
 * CONFIG attendue (window.CONTACTS_PANEL_CONFIG) :
 *   urlAddExisting : URL AJAX formulaire ajout contact existant
 *   urlAdd         : URL AJAX formulaire création nouveau contact
 *   entityId       : identifiant de l'entité parente
 *   routeDelete    : nom de la route Symfony pour la suppression
 *   elementLie     : type d'entité ('commission' ou 'groupement')
 * ============================================================================= */

$(document).ready(function() {
    // Réinitialiser la modale lors de sa fermeture
    $('#ajout-contact').on('hidden.bs.modal', function () {
        $(this).find('.modal-title').html('Ajouter un contact');
        $(this).find('.modal-body').html('');
    });

    $('#ajout-contact-existant-bouton').click(function () {
        $.ajax({
            url: CONTACTS_PANEL_CONFIG.urlAddExisting,
            success: function (result) {
                $('#ajout-contact-existant .modal-body').html(result);
                $('#ajout-contact-existant').modal('show');

                new TomSelect('#add_existing_contacts_contacts', {
                    valueField: 'id',
                    labelField: 'name',
                    searchField: 'name',
                    plugins: ['remove_button'],
                    maxOptions: 100,
                    shouldLoad: function(query) {
                        return query.length > 2;
                    },
                    load: function(query, callback) {
                        const url = Routing.generate('contact_autocomplete', {search: encodeURIComponent(query)});
                        fetch(url)
                            .then(response => response.json())
                            .then(json => {
                                callback(json.map(contact => {
                                    let nameParts = []

                                    if (contact.societe) {
                                        nameParts.push(contact.societe)
                                    }
                                    if (contact.nom) {
                                        nameParts.push(contact.nom)
                                    }
                                    if (contact.prenom) {
                                        nameParts.push(contact.prenom)
                                    }

                                    return {
                                        id: contact.id,
                                        name: nameParts.join(" ")
                                    }
                                }))
                            }).catch(() => {
                            callback()
                        })
                    },
                })
            }
        })
    })

    $('#ajout-contact-bouton').click(function () {
        $.ajax({
            url: CONTACTS_PANEL_CONFIG.urlAdd,
            success: function (result) {
                $('#ajout-contact .modal-body').html(result);
                $('#ajout-contact').modal('show');
            }
        })
    })

    const entityId = CONTACTS_PANEL_CONFIG.entityId
    let contactId

    $('.delete-contact-button').on('click', function () {
        contactId = $(this).attr('data-contact-id')

        $.ajax({
            type: "GET",
            url: Routing.generate(CONTACTS_PANEL_CONFIG.routeDelete, {id: entityId, contact: contactId}),
            success: function(html) {
                $('#modal-suppression-contact .modal-content').html(html);
                $('#modal-suppression-contact').modal('show');
            },
            error: function() {
                $.toast.error("Erreur lors du chargement du formulaire de suppression.");
            }
        });
    })

    $('.edit-contact-button').on('click', function () {
        contactId = $(this).attr('data-contact-id')

        $.ajax({
            url: Routing.generate('contact_edit', {id: contactId, elementLie: CONTACTS_PANEL_CONFIG.elementLie, elementId: entityId}),
            success: function (result) {
                const modalAjout = document.getElementById('ajout-contact')
                modalAjout.querySelector('#ajout-contact-label').innerHTML = 'Modifier un contact'

                modalAjout.querySelector('.modal-body').innerHTML = result

                $('#ajout-contact').modal('show')
            }
        })
    })
})
