/* global $, Dropzone */

$(document).ready(function () {
    const config = document.getElementById('pj-config');
    if (!config) {
        return;
    }

    const urlForm = config.dataset.urlForm;
    const urlAdd = config.dataset.urlAdd;
    const urlCheckBase = config.dataset.urlCheckBase;
    const csrfAdd = config.dataset.csrfAdd;

    // --- Helpers ---

    function buildCheckUrl(idpj) {
        return urlCheckBase.replace('__IDPJ__', idpj);
    }

    function showTable() {
        $('#pj-table').show();
        $('#pj-empty-msg').hide();
    }

    function insertRow(html) {
        $('#pj-table tbody').prepend(html);
        showTable();
        // Réinitialiser FancyBox sur les nouvelles images
        initFancybox();
    }

    function afterUpload(id) {
        $.ajax({
            url: buildCheckUrl(id),
            method: 'POST',
            data: { _token: csrfAdd },
            success: function (result) {
                if (result.exists) {
                    insertRow(result.html);
                }
            }
        });
    }

    // --- Modale d'ajout ---

    $('#btn-add-pj').on('click', function () {
        $('#modal-add-pj-body').html('<p class="text-center"><span class="glyphicon glyphicon-refresh"></span> Chargement...</p>');
        $('#modal-add-pj').modal('show');

        $.get(urlForm, function (html) {
            $('#modal-add-pj-body').html(html);
        });
    });

    $('#btn-submit-pj').on('click', function () {
        const form = document.getElementById('pj-add-form');
        if (!form) {
            return;
        }

        const formData = new FormData(form);

        $.ajax({
            url: urlAdd,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (result) {
                if (result.success) {
                    $('#modal-add-pj').modal('hide');
                    afterUpload(result.id);
                } else {
                    $('#modal-add-pj-body').prepend(
                        '<div class="alert alert-danger">' + (result.message || 'Erreur lors de l\'ajout.') + '</div>'
                    );
                }
            },
            error: function () {
                $('#modal-add-pj-body').prepend(
                    '<div class="alert alert-danger">Erreur lors de l\'envoi du fichier.</div>'
                );
            }
        });
    });

    // --- Dropzone (ajout rapide) ---

    Dropzone.autoDiscover = false;

    const myDropzone = new Dropzone('#dropzone-pj', {
        url: urlAdd,
        paramName: 'fichier',
        autoProcessQueue: true,
        sending: function (file, xhr, formData) {
            formData.append('_token', csrfAdd);
        },
        success: function (file, response) {
            if (response && response.success) {
                file.previewElement.remove();
                afterUpload(response.id);
            }

            if ($('#dropzone-pj .dz-preview').length === 0) {
                $('#dropzone-pj').removeClass('dz-started').hide();
            }
        },
        error: function (file, message) {
            file.previewElement.querySelector('.dz-error-message span').textContent =
                (typeof message === 'object' && message.message) ? message.message : String(message);
        }
    });

    $('#btn-toggle-dropzone').on('click', function () {
        $('#dropzone-pj').toggle();
    });

    // --- Suppression AJAX ---

    $('#pj-table').on('click', '.btn-delete-pj', function () {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette pièce jointe ?')) {
            return;
        }

        const btn = $(this);
        const url = btn.data('url');
        const token = btn.data('token');
        const row = btn.closest('tr');

        $.ajax({
            url: url,
            method: 'POST',
            data: { _token: token },
            success: function (result) {
                if (result.success) {
                    row.remove();

                    if ($('#pj-table tbody tr').length === 0) {
                        $('#pj-table').hide();
                        $('#pj-empty-msg').show();
                    }
                }
            }
        });
    });

    // --- Retry export Platau ---

    $('#pj-table').on('click', '.btn-retry-platau', function () {
        const btn = $(this);
        const url = btn.data('url');
        const token = btn.data('token');

        $.ajax({
            url: url,
            method: 'POST',
            data: { _token: token },
            success: function () {
                // Recharger la page pour afficher le nouveau statut
                window.location.reload();
            }
        });
    });

    // --- FancyBox ---

    function initFancybox() {
        $('[data-fancybox="pj-gallery"]').fancybox({
            transitionIn: 'elastic',
            transitionOut: 'elastic',
            speedIn: 600,
            speedOut: 200
        });
    }

    initFancybox();
});
