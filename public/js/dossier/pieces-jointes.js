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

    function initPopovers(context) {
        $(context || document).find('[data-toggle="popover"]').popover();
    }

    function showTable() {
        $('#tabPieceJointe').show();
        $('#pj-empty-msg').hide();
    }

    function insertRow(html) {
        const $row = $(html);
        $('#tabPieceJointe tbody').prepend($row);
        initPopovers($row[0]);
        showTable();
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

    initPopovers();

    // --- Modale d'ajout ---

    $('#btn-add-pj').on('click', function () {
        $('#modal-add-pj-body').html(
            '<p class="text-center"><span class="glyphicon glyphicon-refresh spinning" aria-hidden="true"></span> Chargement...</p>'
        );
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
                    $.toast.success('La pièce jointe a bien été ajoutée.');
                } else {
                    $.toast.error(result.message || "Erreur lors de l'ajout de la pièce jointe.");
                }
            },
            error: function () {
                $.toast.error("Erreur lors de l'envoi du fichier.");
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
            const errorMessage = (typeof message === 'object' && message.message)
                ? message.message
                : String(message);
            myDropzone.removeFile(file);
            if ($('#dropzone-pj .dz-preview').length === 0) {
                $('#dropzone-pj').removeClass('dz-started').hide();
            }
            $.toast.error(errorMessage);
        }
    });

    $('#btn-toggle-dropzone').on('click', function () {
        $('#dropzone-pj').toggle();
    });

    // --- Suppression AJAX ---

    $('#tabPieceJointe').on('click', '.btn-delete-pj', function () {
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
                    $.toast.success('La pièce jointe a été supprimée.');

                    if ($('#tabPieceJointe tbody tr').length === 0) {
                        $('#tabPieceJointe').hide();
                        $('#pj-empty-msg').show();
                    }
                } else {
                    $.toast.error(result.error || 'Erreur lors de la suppression.');
                }
            },
            error: function () {
                $.toast.error('Erreur lors de la suppression de la pièce jointe.');
            }
        });
    });

    // --- Retry export Platau ---

    $('#tabPieceJointe').on('click', '.btn-retry-platau', function () {
        const btn = $(this);
        const url = btn.data('url');
        const token = btn.data('token');
        const cell = btn.closest('td');
        const btnDelete = btn.closest('tr').find('.btn-delete-pj');

        btn.prop('disabled', true).html(
            '<span class="glyphicon glyphicon-refresh spinning" aria-hidden="true"></span> Renvoi...'
        );

        $.ajax({
            url: url,
            method: 'POST',
            data: { _token: token },
            success: function (result) {
                if (result.success) {
                    $.toast.success("La pièce jointe a été remise en file d'attente d'export vers Plat'AU.");
                    cell.html('<span class="label label-info">' + (result.statut || "En attente d'export") + '</span>');
                    btnDelete.remove();
                } else {
                    $.toast.error(result.error || "Erreur lors du renvoi vers Plat'AU.");
                    btn.prop('disabled', false).html('Renvoyer');
                }
            },
            error: function () {
                $.toast.error("Erreur lors du renvoi vers Plat'AU.");
                btn.prop('disabled', false).html('Renvoyer');
            }
        });
    });
});
