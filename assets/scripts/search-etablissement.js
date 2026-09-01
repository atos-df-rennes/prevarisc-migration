/* =============================================================================
 * search-etablissement.js — Recherche des établissements
 *
 * Dépendances globales attendues :
 *   - jQuery
 *   - TomSelect
 *   - jQuery Autocomplete (plugin legacy)
 *   - SEARCH_ETABLISSEMENT_CONFIG (injecté par le template Twig)
 *
 * CONFIG attendue (window.SEARCH_ETABLISSEMENT_CONFIG) :
 *   urlAjaxEtablissement : route search_ajax_etablissement
 *   urlGetCommunes       : route api_adresse_get_communes
 *   urlGetVoies          : route api_adresse_get_voies
 * ============================================================================= */

$(document).ready(function() {

    $("input[name='label']").focus();
    $('input[type="submit"]').attr('disabled', false);

    // Gestion des multiselects → TomSelect
    const tsMultiConfig = { plugins: ['remove_button', 'checkbox_options'], maxOptions: null };
    new TomSelect("select[name='statuts[]']", Object.assign({}, tsMultiConfig, { placeholder: 'Tous les statuts' }));
    const tsGenres = new TomSelect("select[name='genres[]']", Object.assign({}, tsMultiConfig, { placeholder: 'Tous les genres' }));
    new TomSelect("select[name='categories[]']", Object.assign({}, tsMultiConfig, { placeholder: 'Toutes les catégories' }));
    new TomSelect("select[name='classes[]']", Object.assign({}, tsMultiConfig, { placeholder: 'Toutes les classes' }));
    new TomSelect("select[name='familles[]']", Object.assign({}, tsMultiConfig, { placeholder: 'Toutes les familles' }));
    new TomSelect("select[name='types_activites[]']", Object.assign({}, tsMultiConfig, { placeholder: "Tous les types d'activités" }));
    new TomSelect("select[name='avis[]']", Object.assign({}, tsMultiConfig, { placeholder: 'Tous les avis' }));
    new TomSelect("select[name='presences_local_sommeil[]']", Object.assign({}, tsMultiConfig, { placeholder: "Qu'importe la présence de local à sommeil" }));
    new TomSelect("select[name='commissions[]']", Object.assign({}, tsMultiConfig, { placeholder: 'Toutes les commissions' }));
    new TomSelect("select[name='groupements_territoriaux[]']", Object.assign({}, tsMultiConfig, { placeholder: 'Tous les groupements territoriaux' }));
    new TomSelect("select[name='preventionniste']", { allowEmptyOption: true });

    // Affichage des critères en fonction du genre
    function display_criteres_by_genres() {
        $("#categorie_span, #classe_span, #famille_span, #type_span").hide();
        tsGenres.items.forEach(function(value) {
            switch(value) {
                case '2' : $("#type_span, #categorie_span").show(); break;
                case '3' : $("#type_span, #categorie_span").show(); break;
                case '4' : $("#famille_span").show(); break;
                case '5' : $("#classe_span").show(); break;
            }
        });
    }

    $("select[name='genres[]']").on('change', function() { display_criteres_by_genres(); });
    display_criteres_by_genres();

    // Slide des établissements enfants
    $("ul.recherche_liste li.slide").live("click", function() {
        if($(this).is('a')) {
            return;
        }
        var container = this;
        if( $(this).hasClass("active") ) {
            $(this).next().slideUp(400, function() {$(container).next().remove()});
            $(this).toggleClass("active");
        }
        else {
            $(container).find(".load").show();
            $.post(SEARCH_ETABLISSEMENT_CONFIG.urlAjaxEtablissement, {parent: $(this).attr("id")}, function(html) {
                $(container).toggleClass("active").find(".load").hide();
                $(container).after("<li class='ui-helper-hidden' style='overflow:auto; height:auto;'>" + html + "</li>").next().slideDown();
            });
        }
    });

    $('#zone_de_recherche_edit').click(function(e){
        e.preventDefault();
        $('#zone_de_recherche_modal').modal();
    });

    $('#zone_de_recherche_reset').click(function(e){
        e.preventDefault();
        $("input[name='zone_de_recherche_text']").val("Département");
        $(".zone_de_recherche_text").text("Département");

        $("input[name='city']").val("");
        $("input[name='street']").val("");
        $("input[name='number']").val("");
    });

    // Auto complétion de la ville
    $("#zone_de_recherche_modal input[name='commune_ac']").autocomplete(SEARCH_ETABLISSEMENT_CONFIG.urlGetCommunes, {
        minChars: 2,
        cacheLength: 0,
        parse: function(data) {
            return $.map(data["response"], function(row) {return {data: row, value: row.LIBELLE_COMMUNE, result: row.LIBELLE_COMMUNE}});
        },
        formatItem: function(item) {
            return item.LIBELLE_COMMUNE + " (" + item.NUMINSEE_COMMUNE + ")";
        }
    }).result(function(e, item) {
        $("input[name='code_insee']").val( item.NUMINSEE_COMMUNE );
        $("input[name='voie_ac']").removeAttr('disabled');
        $("input[name='numero_ac']").removeAttr('disabled');
    }).click(function() {
        $(this).val("");
        $("input[name='voie_ac']").val("").attr("disabled", true).blur();
        $("input[name='voie']").val("").blur();
        $("input[name='numero_ac']").val("").attr("disabled", true).blur();
        $("input[name='numero_voie']").val("").blur();
    });

    // Auto complétion de la rue
    $("#zone_de_recherche_modal input[name='voie_ac']").autocomplete(SEARCH_ETABLISSEMENT_CONFIG.urlGetVoies, {
        minChars: 2,
        cacheLength: 0,
        extraParams: { code_insee: function(){ return $("input[name='code_insee']").val() } },
        parse: function(data) {
            return $.map(data["response"], function(row) { return {data: row, value: row.LIBELLE_RUE, result: row.LIBELLE_RUE} });
        },
        formatItem: function(item) {
            return item.LIBELLE_RUE;
        }
    }).result(function(e, item) {
        $("input[name='voie']").val( item.ID_RUE );
        $("input[name='numero_ac']").removeAttr('disabled');
    }).click(function() {
        $(this).val("");
        $("input[name='numero_ac']").val("").attr("disabled", true).blur();
        $("input[name='numero_voie']").val("").blur();
    });

    // Validation de la zone de recherche
    $('#zone_de_recherche_modal a.submit').click(function() {
        var text = 'Département';

        if($("input[name='code_insee']").val() != '') {
            var text = $("input[name='commune_ac']").val();
            if($("input[name='voie_ac']").val() != '') {
                var text = $("input[name='commune_ac']").val() + ', ' + $("input[name='voie_ac']").val();
                if($("input[name='numero_ac']").val() != '') {
                    var text = $("input[name='commune_ac']").val() + ', ' + $("input[name='voie_ac']").val() + ', ' + $("input[name='numero_ac']").val();
                }
            }
        }

        $("input[name='zone_de_recherche_text']").val(text);
        $(".zone_de_recherche_text").text(text);

        $("input[name='city']").val($("input[name='code_insee']").val());
        $("input[name='street']").val($("input[name='voie']").val());
        $("input[name='number']").val($("input[name='numero_ac']").val());
        $('#zone_de_recherche_modal').modal('hide');
    });
});

function masquerBoutonExportCalc ()
{
    $('#exporter').css('visibility', 'hidden');
    $('#exporter').parent().css('visibility', 'hidden');
}
function afficherBoutonExportCalc ()
{
    $('#exporter').css('visibility', 'visible');
    $('#exporter').parent().css('visibility', 'visible');
}
